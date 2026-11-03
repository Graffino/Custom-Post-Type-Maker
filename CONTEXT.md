# CONTEXT — Custom-Post-Type-Maker vocabulary

<!-- graffino-agentic:begin v1 -->
> The approved terms for this project. Use each term exactly as defined, in the part
> of speech given. Never swap in a synonym; never use a word on an _Avoid_ line.
<!-- graffino-agentic:end -->

**Definition** (noun) — a `cptm` or `cptm_tax` post whose post meta describes one post type or one taxonomy that the plugin registers.
_Avoid_: config, entry, record

**Post type definition** (noun) — a definition of post type `cptm`.
_Avoid_: CPT post, maker post

**Taxonomy definition** (noun) — a definition of post type `cptm_tax`.
_Avoid_: tax post

**Custom post type** (noun) — a post type that WordPress registers from a post type definition.
_Avoid_: content type, CPT (in user-facing text)

**Custom taxonomy** (noun) — a taxonomy that WordPress registers from a taxonomy definition.
_Avoid_: custom category, tax

**Built-in taxonomy** (noun) — the WordPress taxonomies `category` and `post_tag`.
_Avoid_: default taxonomy, core taxonomy

**Other registered post type** (noun) — a post type that another plugin or theme registers; the plugin lists it but cannot edit it.
_Avoid_: external post type, foreign post type

**Menu icon** (noun) — the icon of a custom post type in the wp-admin menu: a Dashicon slug or an uploaded image (D-004).
_Avoid_: CPT icon, logo

**Options meta box** (noun) — the "Options" form on the edit screen of a definition.
_Avoid_: settings panel, options page

**Rewrite flush** (noun) — the call to `flush_rewrite_rules()` that the plugin makes after a definition changes (PATTERN-004).
_Avoid_: permalink reset, refresh

**SVN release** (noun) — the copy of a Git release to the wordpress.org SVN `trunk/` and `tags/X.Y.Z/`.
_Avoid_: deploy, publish (for this step)

**Stable tag** (noun) — the `readme.txt` header that tells wordpress.org which SVN tag to serve.
_Avoid_: current version, live version
