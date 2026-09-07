# Contributing

Thank you for helping keep Archive Pages Pro working. This project is maintained as a public fork-friendly codebase.

## Development setup

1. Clone the repo into a WordPress site's `wp-content/plugins/archive-pages-pro` directory.
2. Run `npm install`.
3. Run `npm start` while editing React/SCSS, or `npm run build` before committing UI changes.
4. Activate the plugin and use **Settings → Archive Mapping**.

PHP follows [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). JavaScript follows [WordPress JavaScript coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/). Put periods at the end of code comments.

Do not edit generated files by hand:

- `build/` — webpack output (`npm run build`)
- `lib/composer/` — Composer autoload (only change when regenerating)

## Pull requests

- Base PRs on the `development` branch.
- Open an issue first for larger changes.
- Sanitize and escape all input and output.
- Flush rewrite rules when a change affects permalinks (the settings save path already does this).
- Include screenshots for admin UI changes.
- Keep the text domain `archive-pages-pro`.

## Reporting bugs and ideas

Use the [bug](.github/ISSUE_TEMPLATE/bug_report.md) and [feature](.github/ISSUE_TEMPLATE/feature.md) templates. Security issues belong in [SECURITY.md](SECURITY.md), not public issues.

## Forking and taking over maintenance

If you fork this plugin as your own product:

| Item | Current value | Change if you rebrand |
| --- | --- | --- |
| PHP namespace | `DLXPlugins\APP` | Yes |
| Composer package | `dlxplugins/archive-pages-pro` | Yes |
| Text domain | `archive-pages-pro` | Yes |
| Option key | `dlx_app_options` | Yes (plan a data migration) |
| REST namespace | `dlxplugins/app/v1` | Yes |
| AJAX actions | `dlx_app_get_options`, `dlx_app_save_options`, `dlx_app_reset_options` | Yes |
| Plugin constants | `ARCHIVE_PAGES_PRO_*` | Yes |
| Admin page slug | `archive-pages-pro` | Optional |

Reading-screen mappings still use option names `post-type-archive-mapping` and `post-type-archive-mapping-404` for compatibility with Custom Query Blocks (PTAM). Changing those requires a migration.

Update `.github/CODEOWNERS`, `FUNDING.yml`, and this file with your GitHub handle when you take over.
