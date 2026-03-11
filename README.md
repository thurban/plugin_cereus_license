# Cereus License Manager for Cacti

A license validation plugin for the [Cacti](https://www.cacti.net/) network monitoring platform. Provides RSA-2048 signed license key validation with product-level tiering, feature gating, and device count enforcement.

## Purpose

Cereus License Manager is the shared license infrastructure for commercial Cacti plugins by Urban-Software.de. It validates a single license key that can cover multiple products, each with their own tier and feature set.

Plugins that use Cereus License Manager:
- **[MCAP](https://github.com/thurban/plugin_mcap)** — Multi-Channel Alerting Plugin
- **[REST API](https://github.com/thurban/plugin_restapi)** — REST API for Cacti
- **[Data Export](https://github.com/thurban/plugin_cereus_dataexport)** — Scheduled Graph Data Export
- **[IPAM](https://github.com/thurban/plugin_cereus_ipam)** — IP Address Management

## Requirements

- Cacti 1.2.0+
- PHP 8.1+ with OpenSSL extension

## Installation

1. Copy the `cereus_license/` directory into your Cacti `plugins/` folder:

   ```bash
   cp -r cereus_license /usr/share/cacti/plugins/
   chown -R www-data:www-data /usr/share/cacti/plugins/cereus_license
   ```

2. In the Cacti web UI, go to **Console > Configuration > Plugins**, find **Cereus License Manager**, and click **Install**, then **Enable**.

3. Navigate to **Console > Configuration > Settings > Cereus License** and paste your license key.

4. Verify the license status under **Console > Cereus Tools > License Manager**.

## How It Works

- License keys are Base64url-encoded JSON payloads with RSA-2048 digital signatures
- The embedded public key verifies signatures — no external license server needed
- Validation results are cached per-request (static) and cross-request (settings table) with configurable TTL
- A 7-day grace period allows continued operation after expiry
- Plugins call the public API functions to check tier, features, and device limits

## License Key Structure

Each license key encodes:
- **Customer** name and email
- **Products** — map of product IDs to tier + feature arrays
- **Expiry date** — license validity period
- **Max devices** — optional device count limit (0 = unlimited)
- **License ID** — unique identifier for support

## Public API

Plugins integrate by calling these functions (available after including `lib/license.php`):

```php
// Check if a product is licensed at all
cereus_license_is_licensed('mcap');  // bool

// Get the tier for a product
cereus_license_get_tier('restapi');  // 'enterprise', 'professional', 'community', or 'unlicensed'

// Check a specific feature
cereus_license_check_feature('mcap', 'escalation');  // bool

// Get full license info for a product
cereus_license_get_info('restapi');  // array or null

// Check device count against limit
cereus_license_check_device_limit();  // {within_limit, current, max}
```

## Admin UI

The license status page (**Cereus Tools > License Manager**) displays:
- License status with color-coded indicator (Valid / Grace / Expired / Invalid)
- Customer name and license ID
- Expiry date with days remaining
- Device count vs. limit
- Licensed products with tier and feature list
- Direct link to Settings page for key management

## File Structure

```
cereus_license/
  setup.php              # Plugin install/uninstall, hooks
  cereus_license.php     # Admin UI — license status page
  INFO                   # Plugin metadata
  includes/
    constants.php        # Tier names, cache TTL, grace period
    settings.php         # Plugin settings (license key textarea)
    arrays.php           # Cacti menu integration
  lib/
    license.php          # Core validation and public API
    crypto.php           # RSA signature verification, Base64url helpers
  images/
    tab.gif              # Tab icon
    tab_down.gif         # Active tab icon
```

## Security

- RSA-2048 signature verification — keys cannot be forged without the private key
- Public key is embedded in the plugin; no external calls required
- License key is stored in Cacti's settings table (same security as other credentials)
- Validation cache prevents repeated crypto operations on every page load
- No phone-home, no telemetry, no external dependencies

## License

GNU General Public License v2.0 or later (GPL-2.0-or-later) — see [LICENSE](LICENSE)

## Author

Thomas Urban — [Urban-Software.de](https://www.urban-software.com) — info@urban-software.de
