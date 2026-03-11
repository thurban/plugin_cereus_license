<?php

include_once('../../include/auth.php');
include_once($config['base_path'] . '/plugins/cereus_license/includes/constants.php');
include_once($config['base_path'] . '/plugins/cereus_license/lib/license.php');

top_header();

$status = cereus_license_get_status();
$result = cereus_license_validate();

/* ---- License Status ---- */
html_start_box(__('Cereus License Status', 'cereus_license'), '100%', '', '3', 'center', '');

/* Status row */
$status_html = '<span style="color:' . $status['status_color'] . ';font-weight:bold;">'
	. html_escape($status['status']) . '</span>';

cereus_license_display_row(__('Status', 'cereus_license'), $status_html);

/* Customer */
if (!empty($status['customer'])) {
	cereus_license_display_row(__('Customer', 'cereus_license'), html_escape($status['customer']));
}

/* License ID */
if (!empty($status['license_id'])) {
	cereus_license_display_row(__('License ID', 'cereus_license'), '<code>' . html_escape($status['license_id']) . '</code>');
}

/* Expiry */
if (!empty($status['expires'])) {
	$expiry_color = '#333';
	if ($result['grace']) {
		$expiry_color = '#FF9800';
	} elseif ($result['days_remaining'] < 0) {
		$expiry_color = '#F44336';
	} elseif ($result['days_remaining'] <= 30) {
		$expiry_color = '#FF9800';
	}

	$expiry_text = html_escape($status['expires']);
	if ($result['days_remaining'] >= 0) {
		$expiry_text .= ' (' . $result['days_remaining'] . ' ' . __('days remaining', 'cereus_license') . ')';
	}

	cereus_license_display_row(
		__('Expires', 'cereus_license'),
		'<span style="color:' . $expiry_color . ';">' . $expiry_text . '</span>'
	);
}

/* Devices */
$device_color = $status['devices']['within_limit'] ? '#333' : '#F44336';
$device_text  = $status['devices']['current'];
$device_text .= ($status['devices']['max'] > 0) ? ' / ' . $status['devices']['max'] : ' / ' . __('Unlimited', 'cereus_license');
if (!$status['devices']['within_limit']) {
	$device_text .= ' — ' . __('Limit exceeded', 'cereus_license');
}

cereus_license_display_row(
	__('Devices', 'cereus_license'),
	'<span style="color:' . $device_color . ';">' . $device_text . '</span>'
);

html_end_box();

/* ---- Licensed Products ---- */
if (cacti_sizeof($status['products'])) {
	$product_labels = unserialize(CEREUS_PRODUCT_LABELS);

	html_start_box(__('Licensed Products', 'cereus_license'), '100%', '', '3', 'center', '');

	$display_text = array(
		__('Product', 'cereus_license'),
		__('Tier', 'cereus_license'),
	);

	html_header($display_text);

	$i = 0;
	foreach ($status['products'] as $pid => $pdata) {
		$tier_label = ucfirst($pdata['tier']);
		$tier_color = '#333';
		if ($pdata['tier'] === 'enterprise') {
			$tier_color = '#9C27B0';
		} elseif ($pdata['tier'] === 'professional') {
			$tier_color = '#1976D2';
		}

		$product_name = isset($product_labels[$pid]) ? $product_labels[$pid] : strtoupper($pid);

		form_alternate_row('product_' . $i, true);
		form_selectable_cell('<strong>' . html_escape($product_name) . '</strong>', $i);
		form_selectable_cell('<span style="color:' . $tier_color . ';">' . html_escape($tier_label) . '</span>', $i);
		form_end_row();
		$i++;
	}

	html_end_box();
}

/* ---- Error ---- */
if (!empty($status['error']) && $status['error'] !== 'No license key configured') {
	html_start_box(__('License Error', 'cereus_license'), '100%', '', '3', 'center', '');
	form_alternate_row('error_row', true);
	print '<td colspan="2" style="color:#B71C1C;">' . html_escape($status['error']) . '</td>';
	form_end_row();
	html_end_box();
}

/* ---- Settings link ---- */
html_start_box(__('Management', 'cereus_license'), '100%', '', '3', 'center', '');
form_alternate_row('settings_row', true);
print '<td><a href="' . html_escape($config['url_path'] . 'settings.php?tab=cereus_license') . '" class="linkEditMain">';
print __('Manage License Key in Settings', 'cereus_license') . ' &raquo;';
print '</a></td>';
form_end_row();
html_end_box();

/* ---- Product Feature Matrix ---- */
$product_labels = isset($product_labels) ? $product_labels : unserialize(CEREUS_PRODUCT_LABELS);

/* Feature matrices per product: feature_label => minimum tier required */
$product_features = array(
	'mcap' => array(
		__('Email Channel', 'cereus_license')               => CEREUS_TIER_COMMUNITY,
		__('Slack Channel', 'cereus_license')                => CEREUS_TIER_COMMUNITY,
		__('Webhook Channel', 'cereus_license')              => CEREUS_TIER_COMMUNITY,
		__('Severity & Event Filtering', 'cereus_license')   => CEREUS_TIER_COMMUNITY,
		__('Max 2 Channels / 1 Profile', 'cereus_license')   => CEREUS_TIER_COMMUNITY,
		__('Teams Channel', 'cereus_license')                => CEREUS_TIER_PROFESSIONAL,
		__('Telegram Channel', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Deduplication', 'cereus_license')                => CEREUS_TIER_PROFESSIONAL,
		__('Graph Thumbnails / CSV', 'cereus_license')       => CEREUS_TIER_PROFESSIONAL,
		__('Advanced Routing', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Max 10 Channels / 10 Profiles', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('PagerDuty Channel', 'cereus_license')            => CEREUS_TIER_ENTERPRISE,
		__('OpsGenie Channel', 'cereus_license')             => CEREUS_TIER_ENTERPRISE,
		__('Twilio SMS Channel', 'cereus_license')           => CEREUS_TIER_ENTERPRISE,
		__('Maintenance Windows', 'cereus_license')          => CEREUS_TIER_ENTERPRISE,
		__('Escalation', 'cereus_license')                   => CEREUS_TIER_ENTERPRISE,
		__('Unlimited Channels / Profiles', 'cereus_license') => CEREUS_TIER_ENTERPRISE,
	),
	'restapi' => array(
		__('GET Endpoints', 'cereus_license')                => CEREUS_TIER_COMMUNITY,
		__('1 Token / Viewer Role', 'cereus_license')        => CEREUS_TIER_COMMUNITY,
		__('Full CRUD (GET/POST/PUT/DELETE)', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('All 5 Roles', 'cereus_license')                  => CEREUS_TIER_PROFESSIONAL,
		__('Device Actions / Graph Render', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('Data Source Values', 'cereus_license')            => CEREUS_TIER_PROFESSIONAL,
		__('IP Restrictions', 'cereus_license')               => CEREUS_TIER_PROFESSIONAL,
		__('Unlimited Tokens', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('User Management API', 'cereus_license')          => CEREUS_TIER_ENTERPRISE,
		__('Token Management API', 'cereus_license')         => CEREUS_TIER_ENTERPRISE,
		__('Poller Info / Rebuild', 'cereus_license')        => CEREUS_TIER_ENTERPRISE,
	),
	'cereus_dataexport' => array(
		__('CSV Export', 'cereus_license')                    => CEREUS_TIER_COMMUNITY,
		__('Poller Cycle Scheduling', 'cereus_license')      => CEREUS_TIER_COMMUNITY,
		__('Local File Delivery', 'cereus_license')          => CEREUS_TIER_COMMUNITY,
		__('Max 5 Definitions / 100 Graphs', 'cereus_license') => CEREUS_TIER_COMMUNITY,
		__('JSON / XML Export', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Hourly / Daily / Weekly / Monthly', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('Email Delivery', 'cereus_license')               => CEREUS_TIER_PROFESSIONAL,
		__('Column Filtering', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Max 50 Definitions / Unlimited Graphs', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('SFTP Delivery', 'cereus_license')                => CEREUS_TIER_ENTERPRISE,
		__('Unlimited Definitions', 'cereus_license')        => CEREUS_TIER_ENTERPRISE,
	),
	'cereus_ipam' => array(
		__('IPv4 Subnet Management', 'cereus_license')       => CEREUS_TIER_COMMUNITY,
		__('Manual IP Address CRUD', 'cereus_license')       => CEREUS_TIER_COMMUNITY,
		__('CSV Import (500 rows) / Export', 'cereus_license') => CEREUS_TIER_COMMUNITY,
		__('Cacti Device Auto-Link', 'cereus_license')       => CEREUS_TIER_COMMUNITY,
		__('Utilization Display', 'cereus_license')          => CEREUS_TIER_COMMUNITY,
		__('Dashboard & Subnet Calculator', 'cereus_license') => CEREUS_TIER_COMMUNITY,
		__('Tag Management', 'cereus_license')               => CEREUS_TIER_COMMUNITY,
		__('Changelog (30 days)', 'cereus_license')          => CEREUS_TIER_COMMUNITY,
		__('Max 10 Subnets', 'cereus_license')               => CEREUS_TIER_COMMUNITY,
		__('Unlimited Subnets', 'cereus_license')            => CEREUS_TIER_PROFESSIONAL,
		__('Full IPv6 / Dual-Stack', 'cereus_license')       => CEREUS_TIER_PROFESSIONAL,
		__('VLAN Management', 'cereus_license')              => CEREUS_TIER_PROFESSIONAL,
		__('VRF Support', 'cereus_license')                  => CEREUS_TIER_PROFESSIONAL,
		__('Network Scanning (fping / TCP)', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('DNS Integration', 'cereus_license')              => CEREUS_TIER_PROFESSIONAL,
		__('Custom Fields', 'cereus_license')                => CEREUS_TIER_PROFESSIONAL,
		__('NAT Mapping', 'cereus_license')                  => CEREUS_TIER_PROFESSIONAL,
		__('Per-Section RBAC', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Threshold & Conflict Alerts', 'cereus_license')  => CEREUS_TIER_PROFESSIONAL,
		__('Reports (CSV) / Scheduled Emails', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('Bulk IP Range Fill', 'cereus_license')           => CEREUS_TIER_PROFESSIONAL,
		__('Subnet Nesting / Hierarchy', 'cereus_license')   => CEREUS_TIER_PROFESSIONAL,
		__('Advanced Search (Regex)', 'cereus_license')      => CEREUS_TIER_PROFESSIONAL,
		__('Column Filtering', 'cereus_license')             => CEREUS_TIER_PROFESSIONAL,
		__('Unlimited Import / Full Audit Trail', 'cereus_license') => CEREUS_TIER_PROFESSIONAL,
		__('LDAP/AD Authentication', 'cereus_license')       => CEREUS_TIER_ENTERPRISE,
		__('Multi-Tenancy', 'cereus_license')                => CEREUS_TIER_ENTERPRISE,
		__('DHCP Scope Monitoring', 'cereus_license')        => CEREUS_TIER_ENTERPRISE,
		__('Automated Reconciliation', 'cereus_license')     => CEREUS_TIER_ENTERPRISE,
		__('Capacity Forecasting', 'cereus_license')         => CEREUS_TIER_ENTERPRISE,
		__('Rack / Location Visualization', 'cereus_license') => CEREUS_TIER_ENTERPRISE,
		__('Webhook Callbacks', 'cereus_license')            => CEREUS_TIER_ENTERPRISE,
		__('REST API Endpoints', 'cereus_license')           => CEREUS_TIER_ENTERPRISE,
		__('Maintenance Windows', 'cereus_license')          => CEREUS_TIER_ENTERPRISE,
	),
);

$all_tiers = array(
	CEREUS_TIER_COMMUNITY    => __('Community', 'cereus_license'),
	CEREUS_TIER_PROFESSIONAL => __('Professional', 'cereus_license'),
	CEREUS_TIER_ENTERPRISE   => __('Enterprise', 'cereus_license'),
);

$tier_rank = array(CEREUS_TIER_COMMUNITY => 0, CEREUS_TIER_PROFESSIONAL => 1, CEREUS_TIER_ENTERPRISE => 2);

foreach ($product_features as $pid => $features) {
	$product_name = isset($product_labels[$pid]) ? $product_labels[$pid] : strtoupper($pid);

	/* Get current licensed tier for this product */
	$current_tier = CEREUS_TIER_COMMUNITY;
	if (isset($status['products'][$pid])) {
		$current_tier = $status['products'][$pid]['tier'];
	}
	$current_rank = isset($tier_rank[$current_tier]) ? $tier_rank[$current_tier] : 0;

	html_start_box(__('%s — Feature Matrix', $product_name, 'cereus_license'), '100%', '', '3', 'center', '');

	/* Header row with tier columns, highlighting the active tier */
	print '<tr class="tableHeader">';
	print '<th class="tableSubHeaderColumn" style="text-align:left;">' . __('Feature', 'cereus_license') . '</th>';
	foreach ($all_tiers as $tier_key => $tier_label) {
		$is_current = ($tier_key === $current_tier);
		$bg = $is_current ? 'background-color:rgba(25,118,210,0.12);' : '';
		$fw = $is_current ? 'font-weight:bold;' : '';
		print '<th class="tableSubHeaderColumn" style="text-align:center;' . $bg . $fw . '">' . $tier_label;
		if ($is_current) {
			print ' *';
		}
		print '</th>';
	}
	print '</tr>';

	/* Feature rows */
	$row_ct = 0;
	foreach ($features as $feature_label => $min_tier) {
		$min_rank = isset($tier_rank[$min_tier]) ? $tier_rank[$min_tier] : 0;
		$row_class = ($row_ct % 2 == 0) ? 'odd' : 'even';

		print '<tr class="' . $row_class . '">';
		print '<td style="padding:4px 8px;">' . html_escape($feature_label) . '</td>';

		foreach ($all_tiers as $tier_key => $tier_label) {
			$t_rank = $tier_rank[$tier_key];
			$is_current_col = ($tier_key === $current_tier);
			$bg = $is_current_col ? 'background-color:rgba(25,118,210,0.08);' : '';

			if ($t_rank >= $min_rank) {
				/* Feature available at this tier */
				$is_licensed = ($current_rank >= $t_rank);
				if ($is_licensed && $is_current_col) {
					$icon = '<span style="color:#4CAF50;font-weight:bold;">&#10003;</span>';
				} elseif ($t_rank >= $min_rank) {
					$icon = '<span style="color:#999;">&#10003;</span>';
				}
			} else {
				$icon = '<span style="color:#ddd;">&mdash;</span>';
			}

			print '<td style="text-align:center;padding:4px 8px;' . $bg . '">' . $icon . '</td>';
		}

		print '</tr>';
		$row_ct++;
	}

	/* Legend row */
	print '<tr class="' . (($row_ct % 2 == 0) ? 'odd' : 'even') . '">';
	print '<td colspan="4" style="padding:6px 8px;font-style:italic;color:#666;">';
	print __('* Current licensed tier. Green checkmarks indicate features available with your license.', 'cereus_license');
	print '</td></tr>';

	html_end_box();
}

?>
<script type='text/javascript'>
$(function() {
	var storage = Storages.localStorage;

	$('.cactiTableTitle span').each(function() {
		var titleText = $(this).text();
		if (titleText.indexOf('Feature Matrix') === -1) {
			return;
		}

		var $titleDiv = $(this).closest('.cactiTableTitle');
		var $box = $(this).closest('.cactiTable');
		var boxId = $box.attr('id');
		var storageKey = 'cereus_fm_' + boxId;

		/* The content table is the sibling with id ending in _child */
		var $contentTable = $('#' + boxId + '_child');

		/* Add toggle icon to the button area */
		$titleDiv.css('cursor', 'pointer');
		$box.find('.cactiTableButton').prepend('<span style="padding-right:4px;"><i class="fa fa-angle-double-down"></i></span>');

		var $icon = $box.find('.cactiTableButton i.fa');

		/* Determine initial state: collapsed by default */
		var state = storage.isSet(storageKey) ? storage.get(storageKey) : 'hide';

		if (state === 'hide') {
			$contentTable.hide();
			$icon.removeClass('fa-angle-double-up').addClass('fa-angle-double-down');
		} else {
			$contentTable.show();
			$icon.removeClass('fa-angle-double-down').addClass('fa-angle-double-up');
		}

		/* Click handler on the title bar */
		$titleDiv.on('click', function() {
			if ($icon.hasClass('fa-angle-double-down')) {
				$contentTable.slideDown('fast');
				$icon.removeClass('fa-angle-double-down').addClass('fa-angle-double-up');
				storage.set(storageKey, 'show');
			} else {
				$contentTable.slideUp('fast');
				$icon.removeClass('fa-angle-double-up').addClass('fa-angle-double-down');
				storage.set(storageKey, 'hide');
			}
		});
	});
});
</script>
<?php

bottom_footer();

/**
 * Display a label/value row using Cacti's standard form rendering.
 *
 * @param string $label Left column label
 * @param string $value Right column value (may contain HTML)
 */
function cereus_license_display_row(string $label, string $value): void {
	static $row_ct = 0;

	form_alternate_row('license_row_' . $row_ct, true);
	form_selectable_cell('<strong>' . $label . '</strong>', $row_ct);
	form_selectable_cell($value, $row_ct);
	form_end_row();
	$row_ct++;
}
