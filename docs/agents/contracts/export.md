# Contracts — Export and import

> Provided contracts. Index: `docs/agents/CONTRACTS.md`.

### C-008 · Export file format
- **Status:** approved
- **Interface:** JSON file from Post Types → Tools → Export and from `wp cptm export`; read by the Tools import and `wp cptm import`.
- **Consumers:** site owners who move definitions between sites; backups.
- **Shape:** `{ "generator": "custom-post-type-maker", "format": 1, "post_types": [ { "title", "status", "meta": { <C-001 keys> } } ], "taxonomies": [ … ] }`. Meta values are strings, except the list keys (`cptm_supports`, `cptm_builtin_taxonomies`, `cptm_tax_post_types`), which are lists of strings.
- **Rules:** Import accepts format 1 and runs every item through `Cptm_Definition::sanitize()`. Unknown keys are ignored. New definitions are drafts unless the user selects "Publish". A changed shape needs a new `format` number, and import must keep reading format 1.
- **Evidence:** includes/class-cptm-definition.php `export()`, `import()`; tests/smoke/run.php section 5
- **Source:** code · confidence high
