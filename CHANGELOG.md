## 5.0.0

### Features

- Maintain compatibility with Shopware 6.5

### Bug Fixes

- Final image jumps when placeholder is replaced (tinect)

## 4.1.0

### Features

- Remove existing blurhashes via the media browser in the administration
- Exclude certain folders directly via the media browser in the administration
- CLI command `ec:blurhash:remove` to remove existing blurhashes
- When the plugin is uninstalled, all existing blurhash metadata is removed
- Admin-API resource to remove existing blurhashes

### Bug Fixes

- Misspelling and typo in german translation (tinect)
- Compatibility issues with PHP 8.0

## 4.0.1

This patch solves critical issues with the emulated integration.

### Bug Fixes

- Already decoded elements may not display final image (e.g. offcanvas cart)
- Deferred loading on images with same blurhash (e.g. product-detail page)
- Responsive image preload
- Spinner icon was fetched twice

# 4.0.0

### Features

This first version contains all the basic conceptual features

- Generation of Blurhashes straight from the media browser
- Preview on matching images, throughout the administration
- Advanced configuration options: Performance, quality, Inclusions / Exclusions and the storefront integration
- Always up-to-date: new or changed media will be processed automatically
- Control also from command line
- Emulated integration in any storefront theme
