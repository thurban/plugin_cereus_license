<?php

function plugin_cereus_license_install() {
	api_plugin_register_hook('cereus_license', 'config_arrays',        'cereus_license_config_arrays',   'includes/arrays.php');
	api_plugin_register_hook('cereus_license', 'config_settings',      'cereus_license_config_settings', 'includes/settings.php');
	api_plugin_register_hook('cereus_license', 'draw_navigation_text', 'cereus_license_draw_navigation', 'setup.php');

	api_plugin_register_realm('cereus_license', 'cereus_license.php', 'Plugin: Manage Cereus License', 1);
}

function plugin_cereus_license_uninstall() {
	db_execute("DELETE FROM settings WHERE name IN ('cereus_license_key', 'cereus_license_cache')");
}

function plugin_cereus_license_version() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/cereus_license/INFO', true);

	return $info['info'];
}

function plugin_cereus_license_check_config() {
	return true;
}

function plugin_cereus_license_upgrade($info) {
	return true;
}

function cereus_license_draw_navigation($nav) {
	$nav['cereus_license.php:'] = array(
		'title'   => __('Cereus License Manager', 'cereus_license'),
		'mapping' => 'index.php:',
		'url'     => 'cereus_license.php',
		'level'   => '1',
	);

	return $nav;
}
