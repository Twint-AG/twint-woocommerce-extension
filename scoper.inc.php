<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However, beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
// $excludedFiles = array_map(
//     static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
//     iterator_to_array(
//         Finder::create()->files()->in(__DIR__),
//         false,
//     ),
// );
$excludedFiles = [];

function getWpExcludedSymbols(string $fileName): array
{
    $filePath = __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $fileName;

    return json_decode(
        file_get_contents($filePath),
        true,
    );
}

$wpClasses = getWpExcludedSymbols('exclude-wordpress-classes.json');
$wpFunctions = getWpExcludedSymbols('exclude-wordpress-functions.json');
$wpConstants = getWpExcludedSymbols('exclude-wordpress-constants.json');

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => 'TwintWoo',

    // 'php-version' => '8.1',

    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => null,

    // By default, when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        Finder::create()->files()->in('src'),
        Finder::create()->files()->in('vendor'),
        Finder::create()->files()->in('dist'),
        Finder::create()->files()->in('assets'),
        Finder::create()->files()->in('languages'),
        Finder::create()->append([
            'bin/console',
            'twint-woocommerce-extension.php',
            'composer.json',
            'readme.txt'
        ])
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => array_merge($excludedFiles, [
        'src/View/Admin/settings.php',
        'src/View/Admin/diagnostics.php',
        'src/View/Frontend/paid.php',
        'src/View/Frontend/unpaid.php'
    ]),

    // PHP version (e.g. `'7.2'`) in which the PHP parser and printer will be configured into. This will affect what
    // level of code it will understand and how the code will be printed.
    // If none (or `null`) is configured, then the host version will be used.
    //    'php-version' => '8.1',

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if (str_ends_with($filePath, 'bin/console')) {
                $replace = "
                    if (PHP_VERSION_ID >= 80400) {
                        require __DIR__ . '/../vendor84/autoload.php';
                    } else {
                        require __DIR__.'/../vendor/autoload.php';
                    }
                ";
                return str_replace("require __DIR__ . '/../vendor/autoload.php';", $replace, $contents);
            }

            if (str_ends_with($filePath, 'twint-woocommerce-extension.php')) {
                $replace = "
                    if (PHP_VERSION_ID >= 80400) {
                        require __DIR__ . '/vendor84/autoload.php';
                    } else {
                        require __DIR__.'/vendor/autoload.php';
                    }
                ";
                return str_replace("require __DIR__ . '/vendor/autoload.php';", $replace, $contents);
            }

            if (str_contains($filePath, '/psl/')) {
                $contents = str_replace('use Psl;', 'use TwintWoo\\Psl;', $contents);
            }

            if (str_contains($filePath, '/phpseclib/') && str_ends_with($filePath, '.php')) {
                $contents = str_replace("'phpseclib3", "'TwintWoo\\phpseclib3", $contents);
                $contents = str_replace("'\\phpseclib3\\", "'TwintWoo\\phpseclib3\\", $contents);

                $contents = str_replace("extension_loaded('bcmath')", 'true', $contents);       
            }

            if (str_ends_with($filePath, 'Normalizer.php')) {
                $contents = str_replace('namespace {', 'namespace TwintWoo {', $contents);
            }

            if (str_ends_with($filePath, 'twint-ag/sdk/src/polyfill.php')) {
                $contents = preg_replace('/\\\class_alias\(\'TwintWoo.*?\);$/m', '', $contents);
            }

            if(str_ends_with($filePath, 'plugin-update-checker/Puc/v5p5/PucFactory.php')){
                $contents = str_replace(
                    '$checkerClass = $type . \'\\UpdateChecker\';',
                    '$checkerClass = "TwintWoo\\\" . $type . "\UpdateChecker";',
                    $contents
                );
            }

            $contents = str_replace('\\false,', 'false,', $contents);

            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        'Automattic\WooCommerce',         // WooCommerce namespace
        '~^$~',                           // Root namespace
        'Twint\Woo',                      // TWINT WooCommerce extension namespace
    ],
    'exclude-classes' => array_merge($wpClasses, [
        'ComposerAutoloaderInit*',
        'Deprecated',
        'Override',
        'Stringable',
    ]),
    'exclude-functions' => [
        ...$wpFunctions,
    ],
    'exclude-constants' => $wpConstants,

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
