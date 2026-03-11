<?php

function cereus_license_config_settings() {
	global $tabs, $settings, $config;

	include_once(__DIR__ . '/constants.php');
	include_once($config['base_path'] . '/plugins/cereus_license/lib/license.php');

	$tabs['cereus_license'] = __('Cereus License', 'cereus_license');

	/* Build status display string (plain text — spacer friendly_name is escaped by Cacti) */
	$status = cereus_license_get_status();
	$status_text = $status['status'];

	if (!empty($status['customer'])) {
		$status_text .= ' — ' . $status['customer'];
	}

	if (!empty($status['license_id'])) {
		$status_text .= ' (' . $status['license_id'] . ')';
	}

	if (cacti_sizeof($status['products'])) {
		$product_parts = array();
		foreach ($status['products'] as $pid => $pdata) {
			$product_parts[] = $pid . ': ' . ucfirst($pdata['tier']);
		}
		$status_text .= ' | ' . __('Products:', 'cereus_license') . ' ' . implode(', ', $product_parts);
	}

	$status_text .= ' | ' . __('Devices:', 'cereus_license') . ' '
		. $status['devices']['current']
		. ($status['devices']['max'] > 0 ? ' / ' . $status['devices']['max'] : ' / ' . __('Unlimited', 'cereus_license'));

	if (!empty($status['error']) && $status['error'] !== 'No license key configured') {
		$status_text .= ' | ' . __('Error:', 'cereus_license') . ' ' . $status['error'];
	}

	$settings['cereus_license'] = array(
		'cereus_license_status_header' => array(
			'friendly_name' => __('License Status', 'cereus_license') . ': ' . $status_text,
			'method'        => 'spacer',
		),
		'cereus_license_key_header' => array(
			'friendly_name' => __('License Key', 'cereus_license'),
			'method'        => 'spacer',
		),
		'cereus_license_key' => array(
			'friendly_name' => __('License Key', 'cereus_license'),
			'description'   => __('Paste your Cereus license key here. Keys are issued at purchase and cover one or more products.', 'cereus_license'),
			'method'        => 'textarea',
			'textarea_rows' => 4,
			'textarea_cols' => 80,
			'max_length'    => 2048,
			'default'       => '',
		),
	);
}
