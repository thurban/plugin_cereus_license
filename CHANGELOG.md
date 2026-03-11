# Changelog

All notable changes to the Cereus License Manager plugin for Cacti.

## [1.1.0] - 2026-03-11

### Added
- Cereus IPAM product support (`cereus_ipam`) with full feature tier matrix
  - Community: IPv4 subnets (max 10), IP address CRUD, CSV import/export, device auto-link, dashboard, subnet calculator, tag management, changelog (30 days)
  - Professional: Unlimited subnets, IPv6/dual-stack, VLANs, VRFs, network scanning (fping/TCP), DNS integration, custom fields, NAT mapping, per-section RBAC, threshold & conflict alerts, reports with scheduled emails, bulk IP range fill, subnet nesting/hierarchy, advanced search (regex), column filtering, unlimited audit trail
  - Enterprise: LDAP/AD authentication, multi-tenancy, DHCP scope monitoring, automated reconciliation, capacity forecasting, rack/location visualization, webhook callbacks, REST API endpoints, maintenance windows
- Collapsible feature matrix UI — each product's feature table can be collapsed/expanded with click toggle, state persisted in localStorage (collapsed by default)
- `CEREUS_PRODUCT_IPAM` constant and display label

## [1.0.0] - 2026-03-10

### Initial Release
- RSA-2048 signed license key validation
- Multi-product support — single key covers multiple plugins
- Three-tier licensing: Community, Professional, Enterprise
- Per-product feature gating via feature arrays
- Device count enforcement with configurable limits
- 7-day grace period after license expiry
- Cross-request validation caching with configurable TTL
- Admin status page with color-coded indicators
- Public API for plugin integration (is_licensed, get_tier, check_feature, get_info, check_device_limit)
- Cacti Settings tab for license key management
- Menu integration under Cereus Tools
- Permission realm for license management access
