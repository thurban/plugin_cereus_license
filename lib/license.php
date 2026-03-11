<?php

/**
 * Cereus License — Core validation and public API.
 *
 * Premium plugins include this file and call:
 *   cereus_license_is_licensed('product_id')
 *   cereus_license_check_feature('product_id', 'feature_name')
 *   cereus_license_get_info('product_id')
 *   cereus_license_get_tier('product_id')
 *
 * @author  Thomas Urban / Urban-Software.de
 * @license GPL-2.0-or-later
 */

include_once(__DIR__ . '/crypto.php');
include_once(__DIR__ . '/../includes/constants.php');

/**
 * Validate the license key.
 *
 * Parses, verifies signature, checks expiry. Results are cached per-request
 * and across requests via the Cacti settings table.
 *
 * @param  string|null $raw_key License key string (null = read from settings)
 * @return array {valid: bool, data: ?array, error: string, grace: bool, days_remaining: int}
 */
function cereus_license_validate(?string $raw_key = null): array {
	/* Per-request static cache */
	static $cache = null;
	static $cache_key_hash = null;

	if ($raw_key === null) {
		$raw_key = read_config_option('cereus_license_key');
	}

	$raw_key = trim((string) $raw_key);

	$current_hash = hash('sha256', $raw_key);

	/* Return cached result if same key */
	if ($cache !== null && $cache_key_hash === $current_hash) {
		return $cache;
	}

	/* Check cross-request cache */
	$stored_cache = read_config_option('cereus_license_cache');
	if (!empty($stored_cache)) {
		$cached = @unserialize($stored_cache);
		if (is_array($cached)
			&& isset($cached['key_hash'], $cached['timestamp'], $cached['result'])
			&& $cached['key_hash'] === $current_hash
			&& (time() - $cached['timestamp']) < CEREUS_LICENSE_CACHE_TTL) {

			$cache = $cached['result'];
			$cache_key_hash = $current_hash;

			/* Refresh days_remaining (it changes daily) */
			if (!empty($cache['data']['expires'])) {
				$cache['days_remaining'] = cereus_license_days_remaining($cache['data']['expires']);
				$cache['grace'] = ($cache['days_remaining'] < 0 && $cache['days_remaining'] >= -CEREUS_LICENSE_GRACE_DAYS);
				$cache['valid'] = ($cache['days_remaining'] >= -CEREUS_LICENSE_GRACE_DAYS);
			}

			return $cache;
		}
	}

	/* Default result */
	$result = array(
		'valid'          => false,
		'data'           => null,
		'error'          => '',
		'grace'          => false,
		'days_remaining' => 0,
	);

	if (empty($raw_key)) {
		$result['error'] = 'No license key configured';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	/* Split into payload.signature */
	$parts = explode('.', $raw_key);
	if (count($parts) !== 2) {
		$result['error'] = 'Invalid license key format';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	$payload_b64   = $parts[0];
	$signature_b64 = $parts[1];

	/* Decode */
	$payload_json  = cereus_license_b64url_decode($payload_b64);
	$signature_raw = cereus_license_b64url_decode($signature_b64);

	if (empty($payload_json) || empty($signature_raw)) {
		$result['error'] = 'Invalid license key encoding';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	/* Verify RSA signature */
	if (!cereus_license_verify_signature($payload_b64, $signature_raw)) {
		$result['error'] = 'License key signature verification failed';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	/* Decode JSON payload */
	$data = json_decode($payload_json, true);
	if (!is_array($data)) {
		$result['error'] = 'Invalid license key payload';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	/* Validate required fields */
	$required = array('customer', 'products', 'expires', 'issued', 'license_id');
	foreach ($required as $field) {
		if (empty($data[$field])) {
			$result['error'] = 'License key missing required field: ' . $field;
			cereus_license_store_cache($result, $current_hash);
			$cache = $result;
			$cache_key_hash = $current_hash;
			return $result;
		}
	}

	/* Sanity check: issued date should be in the past */
	$issued_ts = strtotime($data['issued']);
	if ($issued_ts === false || $issued_ts > time() + 86400) {
		$result['error'] = 'License key has invalid issue date';
		cereus_license_store_cache($result, $current_hash);
		$cache = $result;
		$cache_key_hash = $current_hash;
		return $result;
	}

	/* Check expiry */
	$days_remaining = cereus_license_days_remaining($data['expires']);
	$grace          = ($days_remaining < 0 && $days_remaining >= -CEREUS_LICENSE_GRACE_DAYS);
	$valid          = ($days_remaining >= -CEREUS_LICENSE_GRACE_DAYS);

	$result = array(
		'valid'          => $valid,
		'data'           => $data,
		'error'          => $valid ? '' : 'License expired',
		'grace'          => $grace,
		'days_remaining' => $days_remaining,
	);

	cereus_license_store_cache($result, $current_hash);
	$cache = $result;
	$cache_key_hash = $current_hash;

	return $result;
}

/**
 * Check if a product is licensed.
 *
 * @param  string $product_id Product identifier (e.g., 'mcap', 'restapi')
 * @return bool
 */
function cereus_license_is_licensed(string $product_id): bool {
	$result = cereus_license_validate();

	if (!$result['valid']) {
		return false;
	}

	return isset($result['data']['products'][$product_id]);
}

/**
 * Get license info for a specific product.
 *
 * @param  string $product_id Product identifier
 * @return array|null License data or null if not licensed
 */
function cereus_license_get_info(string $product_id): ?array {
	$result = cereus_license_validate();

	if (!$result['valid'] || !isset($result['data']['products'][$product_id])) {
		return null;
	}

	$product = $result['data']['products'][$product_id];

	return array(
		'customer'       => $result['data']['customer'] ?? '',
		'email'          => $result['data']['email'] ?? '',
		'license_id'     => $result['data']['license_id'] ?? '',
		'tier'           => $product['tier'] ?? CEREUS_TIER_COMMUNITY,
		'features'       => $product['features'] ?? array(),
		'max_devices'    => $result['data']['max_devices'] ?? 0,
		'expires'        => $result['data']['expires'] ?? '',
		'days_remaining' => $result['days_remaining'],
		'grace'          => $result['grace'],
	);
}

/**
 * Check if a specific feature is licensed for a product.
 *
 * @param  string $product_id Product identifier
 * @param  string $feature    Feature name
 * @return bool
 */
function cereus_license_check_feature(string $product_id, string $feature): bool {
	$info = cereus_license_get_info($product_id);

	if ($info === null) {
		return false;
	}

	return in_array($feature, $info['features'], true);
}

/**
 * Get the license tier for a product.
 *
 * @param  string $product_id Product identifier
 * @return string Tier name or 'unlicensed'
 */
function cereus_license_get_tier(string $product_id): string {
	$info = cereus_license_get_info($product_id);

	if ($info === null) {
		return 'unlicensed';
	}

	return $info['tier'];
}

/**
 * Get the current active device count.
 *
 * @return int
 */
function cereus_license_get_device_count(): int {
	return (int) db_fetch_cell("SELECT COUNT(*) FROM host WHERE disabled = ''");
}

/**
 * Check if current device count is within the license limit.
 *
 * @return array {within_limit: bool, current: int, max: int}
 */
function cereus_license_check_device_limit(): array {
	$result = cereus_license_validate();

	$max     = ($result['valid'] && isset($result['data']['max_devices'])) ? (int) $result['data']['max_devices'] : 0;
	$current = cereus_license_get_device_count();

	return array(
		'within_limit' => ($max <= 0 || $current <= $max),
		'current'      => $current,
		'max'          => $max,
	);
}

/**
 * Get a summary of the current license status for display.
 *
 * @return array {status: string, status_color: string, customer: string, products: array, expires: string, devices: array, license_id: string, error: string}
 */
function cereus_license_get_status(): array {
	$result = cereus_license_validate();
	$devices = cereus_license_check_device_limit();

	$status       = 'No License';
	$status_color = '#999';

	if (!empty($result['data'])) {
		if ($result['grace']) {
			$status       = 'Grace Period (' . abs($result['days_remaining']) . ' days past expiry)';
			$status_color = '#FF9800';
		} elseif ($result['valid']) {
			$status       = 'Valid (' . $result['days_remaining'] . ' days remaining)';
			$status_color = '#4CAF50';
		} else {
			$status       = 'Expired';
			$status_color = '#F44336';
		}
	} elseif (!empty($result['error']) && $result['error'] !== 'No license key configured') {
		$status       = 'Invalid';
		$status_color = '#F44336';
	}

	$products = array();
	if (!empty($result['data']['products']) && is_array($result['data']['products'])) {
		foreach ($result['data']['products'] as $pid => $pdata) {
			$products[$pid] = array(
				'tier'     => $pdata['tier'] ?? 'unknown',
				'features' => $pdata['features'] ?? array(),
			);
		}
	}

	return array(
		'status'       => $status,
		'status_color' => $status_color,
		'customer'     => $result['data']['customer'] ?? '',
		'products'     => $products,
		'expires'      => $result['data']['expires'] ?? '',
		'devices'      => $devices,
		'license_id'   => $result['data']['license_id'] ?? '',
		'error'        => $result['error'],
	);
}

/* -------------------------------------------------------------------------
 * Internal helpers
 * ---------------------------------------------------------------------- */

/**
 * Calculate days remaining until expiry.
 *
 * @param  string $expires_date Date string (YYYY-MM-DD)
 * @return int Days remaining (negative if expired)
 */
function cereus_license_days_remaining(string $expires_date): int {
	$expires_ts = strtotime($expires_date . ' 23:59:59');

	if ($expires_ts === false) {
		return -9999;
	}

	return (int) floor(($expires_ts - time()) / 86400);
}

/**
 * Store validation result in cross-request cache.
 *
 * @param array  $result   Validation result
 * @param string $key_hash SHA-256 of the license key
 */
function cereus_license_store_cache(array $result, string $key_hash): void {
	$cache_data = array(
		'key_hash'  => $key_hash,
		'timestamp' => time(),
		'result'    => $result,
	);

	set_config_option('cereus_license_cache', serialize($cache_data));
}

/**
 * Clear the license validation cache.
 * Call this when the license key setting changes.
 */
function cereus_license_clear_cache(): void {
	set_config_option('cereus_license_cache', '');
}
