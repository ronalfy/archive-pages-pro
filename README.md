# Archive Pages Pro

WordPress plugin that maps archives to pages and lets you override how post types, taxonomies, and author URLs behave.

This repository is open-sourced so someone can **fork it and keep it maintained**. Issues and pull requests are welcome; there is no paid support.

## What it does

- Map **post type archives**, **term archives**, **author archives**, and the **404 page** to existing WordPress pages.
- Change the author archive base slug.
- Override custom post type and taxonomy arguments (REST API, `with_front`, `has_archive`, custom fields, page templates, block editor).
- Register selected custom fields for the REST API (useful for dynamic blocks).
- Keep mapped archive URLs compatible with **Yoast SEO**, **Rank Math**, **All in One SEO**, and **Breadcrumb NavXT**.

Settings live at **Settings → Archive Mapping**. Post type and 404 mapping also appear on **Settings → Reading**. Term mapping is on the term edit screen; author mapping is on the user profile screen.

## Requirements

- WordPress 6.0+
- PHP 7.2+
- Node.js (for building admin JavaScript and CSS)

## Install from source

1. Clone this repository into `wp-content/plugins/archive-pages-pro`.
2. Activate **Archive Pages Pro** in WordPress.
3. Open **Settings → Archive Mapping**.

Compiled assets in `build/` are committed so the plugin runs without a build step. Rebuild them if you change files under `src/`.

## Develop

```bash
npm install
npm start          # watch / development build
npm run build      # production build
```

PHP classes autoload via Composer PSR-4 (`DLXPlugins\APP\` → `php/`). Vendor files live in `lib/`. Regenerating the autoloader:

```bash
composer dump-autoload
```

PHP CodeSniffer config is in [`phpcs.xml.dist`](phpcs.xml.dist) (WordPress-Core and WordPress-Docs).

Do not edit `build/` by hand. Do not commit `node_modules/` or `.npmrc`.

## Documentation

- [CONTRIBUTING.md](CONTRIBUTING.md) — how to contribute or take over a fork
- [AGENTS.md](AGENTS.md) — instructions for AI coding agents
- [SECURITY.md](SECURITY.md) — how to report vulnerabilities
- [SUPPORT.md](SUPPORT.md) — where to get help
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

## License

[GPLv2 or later](LICENSE).
