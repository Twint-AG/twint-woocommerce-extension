<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use InvalidArgumentException;
use RuntimeException;

class CryptoHandler
{
    public const CIPHERING = 'AES-128-CBC';
    private string $key = 'twint';

    /**
     * Encrypts the given data using OpenSSL.
     *
     * @param string $data The data to be encrypted.
     * @return string The encrypted data, base64 encoded.
     * @throws InvalidArgumentException If the encryption fails.
     */
    public function encrypt(string $data): string
    {
        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        if ($ivLen === false) {
            throw new InvalidArgumentException('Invalid cipher algorithm.');
        }

        $iv = openssl_random_pseudo_bytes($ivLen);
        if ($iv === '' || $iv === '0') {
            throw new InvalidArgumentException('Failed to generate initialization vector.');
        }

        $ciphertextRaw = openssl_encrypt($data, self::CIPHERING, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertextRaw === false) {
            throw new InvalidArgumentException('Encryption failed.');
        }

        $hmac = hash_hmac('sha256', $ciphertextRaw, $this->key, true);
        if ($hmac === '0') {
            throw new InvalidArgumentException('Failed to compute HMAC.');
        }

        return base64_encode($iv . $hmac . $ciphertextRaw);
    }

    /**
     * Decrypts the given encoded data.
     *
     * @param string $encodedData The encoded data to be decrypted.
     * @return string The decrypted data.
     * @throws InvalidArgumentException If the input data is invalid or cannot be decrypted.
     */
    public function decrypt(string $encodedData): string
    {
        $c = base64_decode($encodedData, true);
        if ($c === false) {
            throw new InvalidArgumentException('Invalid base64 encoded data.');
        }

        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        if ($ivLen === false) {
            throw new InvalidArgumentException('Invalid cipher algorithm.');
        }

        $iv = substr($c, 0, $ivLen);

        // Assuming $sha2len is a constant, replace it with the actual value
        $hmacLen = 32; // Replace 32 with the actual length of the HMAC
        $ciphertextOffset = $ivLen + $hmacLen;

        $ciphertextRaw = substr($c, $ciphertextOffset);
        if ($ciphertextRaw === '' || $ciphertextRaw === '0') {
            throw new InvalidArgumentException('Invalid ciphertext data.');
        }

        $decryptedData = openssl_decrypt($ciphertextRaw, self::CIPHERING, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($decryptedData === false) {
            throw new InvalidArgumentException('Failed to decrypt the data.');
        }

        $expectedHmac = hash_hmac('sha256', $ciphertextRaw, $this->key, true);
        if ($expectedHmac === '0') {
            throw new InvalidArgumentException('Failed to compute HMAC for verification.');
        }

        $hmac = substr($c, $ivLen, $hmacLen);
        if (!hash_equals($hmac, $expectedHmac)) {
            throw new InvalidArgumentException('HMAC verification failed. The data may have been tampered with.');
        }

        return $decryptedData;
    }

    /**
     * Converts a string to a hexadecimal representation and encodes it as a base64 string.
     *
     * @param string $data The input string to be hashed.
     * @return string The base64-encoded hexadecimal representation of the input string.
     */
    public function hash(string $data): string
    {
        $hexString = unpack('H*', $data);
        if ($hexString === false) {
            throw new InvalidArgumentException('Failed to convert input data to hexadecimal.');
        }

        $hex = array_shift($hexString);
        if ($hex === null) {
            throw new RuntimeException('Unexpected error: Failed to extract hexadecimal string.');
        }

        return base64_encode($hex);
    }

    /**
     * Decodes a base64-encoded hexadecimal string into its original string representation.
     *
     * @param string $encodedData The base64-encoded hexadecimal string to be decoded.
     * @return string The original string representation of the encoded data.
     * @throws InvalidArgumentException If the input data is not a valid base64-encoded hexadecimal string.
     */
    public function unHash(string $encodedData): string
    {
        $hex = base64_decode($encodedData, true);
        if ($hex === false) {
            throw new InvalidArgumentException('Invalid base64-encoded hexadecimal string.');
        }

        $decodedData = hex2bin($hex);
        if ($decodedData === false) {
            throw new InvalidArgumentException('Failed to decode the hexadecimal string.');
        }

        return $decodedData;
    }
}
