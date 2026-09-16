<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use Twint\Woo\Plugin;

/**
 * Normalises a legacy-encoded PKCS#12 certificate (RC2-40 / 3DES, the classic
 * .p12 export default) into a modern one (AES-256 / PBES2).
 *
 * Why this exists: OpenSSL 3.x moved the legacy algorithms into a "legacy"
 * provider that is not loaded by default, so `openssl_pkcs12_read()` fails for
 * such files with "digital envelope routines::unsupported". The OpenSSL config
 * cannot be changed for the running web process (providers are loaded at library
 * init, before plugin code runs), so instead the conversion is performed in a
 * short-lived PHP subprocess whose OPENSSL_CONF enables the legacy provider. The
 * re-exported certificate uses modern algorithms and is then readable by the web
 * process and the TWINT SDK on any OpenSSL version.
 *
 * The subprocess is launched with `php -r` and a runtime-generated OpenSSL config
 * file, so this class is fully self-contained: nothing extra has to be shipped or
 * whitelisted in the release build. If the host cannot spawn a subprocess
 * (proc_open disabled), conversion is skipped and callers fall back to their
 * normal error handling.
 */
class LegacyPkcs12Converter
{
    /**
     * Minimal OpenSSL config that activates both the default and the legacy
     * provider. Used only by the conversion subprocess.
     */
    private const OPENSSL_LEGACY_CONF = <<<'CNF'
        openssl_conf = openssl_init

        [openssl_init]
        providers = provider_sect

        [provider_sect]
        default = default_sect
        legacy = legacy_sect

        [default_sect]
        activate = 1

        [legacy_sect]
        activate = 1
        CNF;

    /**
     * Worker executed via `php -r` inside the subprocess. Reads the .p12 at
     * $argv[1], re-exports it (modern default algorithm) to $argv[2]. The
     * passphrase is read from STDIN so it never appears in the process list.
     */
    private const WORKER = <<<'PHP'
        $in = $argv[1] ?? '';
        $out = $argv[2] ?? '';
        if ($in === '' || $out === '') { fwrite(STDERR, 'usage'); exit(64); }
        $pass = stream_get_contents(STDIN);
        if ($pass === false) { $pass = ''; }
        $pass = preg_replace('/\r?\n$/', '', $pass);
        $data = @file_get_contents($in);
        if ($data === false || $data === '') { fwrite(STDERR, 'no input'); exit(66); }
        while (openssl_error_string() !== false) {}
        $certs = null;
        if (!openssl_pkcs12_read($data, $certs, $pass)) { fwrite(STDERR, 'read failed'); exit(3); }
        $args = [];
        if (!empty($certs['extracerts'])) { $args['extracerts'] = $certs['extracerts']; }
        $modern = null;
        if (!openssl_pkcs12_export($certs['cert'], $modern, $certs['pkey'], $pass, $args)) { fwrite(STDERR, 'export failed'); exit(4); }
        if (@file_put_contents($out, $modern) === false) { fwrite(STDERR, 'write failed'); exit(65); }
        @chmod($out, 0600);
        echo 'ok';
        PHP;

    /**
     * Whether this environment can run the conversion subprocess at all.
     */
    public function canRun(): bool
    {
        return function_exists('proc_open');
    }

    /**
     * Convert a (possibly legacy) PKCS#12 blob to a modern-encoded one.
     *
     * @param string $pkcs12 Raw .p12 file contents.
     * @param string $passphrase Certificate passphrase.
     * @return string|null Modern .p12 contents, or null if conversion was
     *                     not possible (wrong passphrase, subprocess
     *                     unavailable, or not a valid PKCS#12 file).
     */
    public function toModern(string $pkcs12, string $passphrase): ?string
    {
        if ($pkcs12 === '' || !$this->canRun()) {
            return null;
        }

        $input = $this->tempFile();
        $output = $this->tempFile();
        $config = $this->tempFile();
        if ($input === null || $output === null || $config === null) {
            $this->cleanup([$input, $output, $config]);
            return null;
        }

        $written = @file_put_contents($input, $pkcs12) !== false
            && @file_put_contents($config, self::OPENSSL_LEGACY_CONF) !== false;
        if (!$written) {
            $this->cleanup([$input, $output, $config]);
            return null;
        }
        @chmod($input, 0600);

        $php = apply_filters('twint_poll_php_executable', Plugin::php());
        $command = [(string) $php, '-r', self::WORKER, '--', $input, $output];

        $success = $this->run($command, $passphrase, $config);

        $modern = null;
        if ($success && is_readable($output)) {
            $content = @file_get_contents($output);
            if (is_string($content) && $content !== '') {
                $modern = $content;
            }
        }

        $this->cleanup([$input, $output, $config]);

        return $modern;
    }

    /**
     * @param array<int, string> $command
     */
    private function run(array $command, string $passphrase, string $configPath): bool
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, null, $this->environment($configPath));
        if (!is_resource($process)) {
            return false;
        }

        // Feed the passphrase via stdin so it never appears in the process list.
        fwrite($pipes[0], $passphrase);
        fclose($pipes[0]);

        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($process) === 0;
    }

    /**
     * Build a minimal environment for the child, adding OPENSSL_CONF so the
     * legacy provider is loaded. proc_open replaces the whole environment, so a
     * few essentials are carried over explicitly.
     *
     * @return array<string, string>
     */
    private function environment(string $configPath): array
    {
        $env = [];
        foreach (['PATH', 'HOME', 'TMPDIR', 'TEMP', 'TMP', 'SystemRoot', 'LD_LIBRARY_PATH'] as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $env[$key] = $value;
            }
        }

        $env['OPENSSL_CONF'] = $configPath;

        return $env;
    }

    private function tempFile(): ?string
    {
        $dir = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $file = @tempnam($dir, 'twint_p12_');

        return $file === false ? null : $file;
    }

    /**
     * @param array<int, string|null> $files
     */
    private function cleanup(array $files): void
    {
        foreach ($files as $file) {
            if (is_string($file) && $file !== '' && file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
