<?php

/**
 * Cereus License — Cryptographic helpers.
 *
 * @author  Thomas Urban / Urban-Software.de
 * @license GPL-2.0-or-later
 */

/**
 * Get the embedded RSA public key for license verification.
 *
 * @return string PEM-encoded public key
 */
function cereus_license_get_public_key(): string {
	return <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4Vyp7X2d67ZmfKlrkpOn
9gg7NlPmkP16nFmM7I53UJp74S1Ckz5c67hNVgfErHKDQF1gZcIa7ppOw4REtv5Q
Z+XYzOqaYFFwUMYweuf3Fpx0LSJR9HRXugipyjODU9g4UAIXnQSlTzvB72cuUvtE
eLZ/j2DYzFNbnPGeC5fp97jEAlOD7KCbUtl9AqeoO6aumhSORjwxVRQsdffVfjJ2
8Y2CfPQEI6DhI5ezkUgLzVnYoXuiApNwGr/040yLuY9Tu0KIQ/9socu0dGkoNmD9
Id5kqBVxeJuEJddxvM5k4tGFR/I3SexyRuc4jwdS1tMjshnGChDTm7MWbl5l13oC
pwIDAQAB
-----END PUBLIC KEY-----
PEM;
}

/**
 * URL-safe Base64 encode (no +, /, or = characters).
 *
 * @param  string $data Raw bytes
 * @return string Encoded string
 */
function cereus_license_b64url_encode(string $data): string {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * URL-safe Base64 decode.
 *
 * @param  string $data Encoded string
 * @return string Raw bytes
 */
function cereus_license_b64url_decode(string $data): string {
	$padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT);

	$decoded = base64_decode($padded, true);

	return ($decoded !== false) ? $decoded : '';
}

/**
 * Verify an RSA-SHA256 signature against the embedded public key.
 *
 * @param  string $payload   The signed data
 * @param  string $signature Raw signature bytes
 * @return bool   True if signature is valid
 */
function cereus_license_verify_signature(string $payload, string $signature): bool {
	$public_key = openssl_pkey_get_public(cereus_license_get_public_key());

	if ($public_key === false) {
		return false;
	}

	$result = openssl_verify($payload, $signature, $public_key, OPENSSL_ALGO_SHA256);

	return ($result === 1);
}
