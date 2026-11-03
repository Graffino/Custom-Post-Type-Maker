# Contracts — Media

> Provided contracts. Index: `docs/agents/CONTRACTS.md`.

### C-004 · Image size cptm_icon and the wp_prepare_attachment_for_js filter
- **Status:** superseded-by C-007
- **Interface:** image size `cptm_icon` (16×16, hard crop) and a filter on `wp_prepare_attachment_for_js` for all attachments.
- **Consumers:** js/add-edit.js reads `attachment.sizes.cptm_icon`; all media modals on the site get the extra sizes.
- **Shape:** `response.sizes[<size>] = { height, width, url, orientation }`.
- **Rules:** Keep the size name. The filter runs site-wide.
- **Evidence:** class-cptm.php:77, class-cptm.php:164, class-cptm.php:1578-1601, js/add-edit.js:27
- **Source:** code · confidence high

### C-007 · Image size cptm_icon, made on demand, and the wp_prepare_attachment_for_js filter
- **Status:** approved
- **Interface:** the `cptm_icon` size (16×16, hard crop) in the metadata of the image that the icon picker selects; a filter on `wp_prepare_attachment_for_js` for all attachments.
- **Consumers:** js/add-edit.js reads `attachment.sizes.cptm_icon`; menu icons store the size URL in `cptm_icon_url`.
- **Shape:** `metadata['sizes']['cptm_icon'] = { file, width, height, mime-type }`; `response.sizes[<size>] = { height, width, url, orientation }`.
- **Rules:** The size is not made for every upload. `Cptm_Definition::icon_url()` makes it when a definition saves an icon URL. With `CPTM_DONT_GENERATE_ICON` (C-003) it is never made. Icons made by older versions keep working.
- **Evidence:** includes/class-cptm-definition.php `icon_url()`, class-cptm.php `wp_prepare_attachment_for_js()`, tests/smoke/run.php section 8
- **Source:** user (approved 2026-11-03) · confidence high
