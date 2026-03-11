<?php

function cereus_license_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $menu;

	$mcap_config_realm = db_fetch_cell("SELECT id + 100
		FROM plugin_realms
		WHERE plugin = 'cereus_license'
		AND file LIKE '%cereus_license.php%'");

	if ($mcap_config_realm) {
		$user_auth_realm_filenames['cereus_license.php'] = $mcap_config_realm;
	}

	$menu[__('Cereus Tools')]['plugins/cereus_license/cereus_license.php'] = __('License Manager', 'cereus_license');
}
