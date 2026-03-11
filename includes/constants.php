<?php

define('CEREUS_LICENSE_VERSION', '1.0.0');
define('CEREUS_LICENSE_GRACE_DAYS', 14);
define('CEREUS_LICENSE_CACHE_TTL', 3600);

/* License tiers */
define('CEREUS_TIER_COMMUNITY',    'community');
define('CEREUS_TIER_PROFESSIONAL', 'professional');
define('CEREUS_TIER_ENTERPRISE',   'enterprise');

/* Known product IDs */
define('CEREUS_PRODUCT_MCAP',       'mcap');
define('CEREUS_PRODUCT_RESTAPI',    'restapi');
define('CEREUS_PRODUCT_DATAEXPORT', 'cereus_dataexport');
define('CEREUS_PRODUCT_IPAM',       'cereus_ipam');

/* Product display labels */
define('CEREUS_PRODUCT_LABELS', serialize(array(
	'mcap'              => 'MCAP — Multi-Channel Alerting',
	'restapi'           => 'REST API',
	'cereus_dataexport' => 'Data Export',
	'cereus_ipam'       => 'IPAM — IP Address Manager',
)));
