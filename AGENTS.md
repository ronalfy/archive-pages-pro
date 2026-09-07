# AGENTS.md

Instructions for AI coding agents working in this WordPress plugin.

## Project

Archive Pages Pro maps post type, term, author, and 404 archives to pages, and can override CPT/taxonomy args. PHP namespace is `DLXPlugins\APP`. Text domain is `archive-pages-pro`. Option key is `dlx_app_options`.

Bootstrap: [`archive-pages-pro.php`](archive-pages-pro.php). Composer PSR-4 maps `DLXPlugins\APP\` to `php/`; autoload output is `lib/`.

## Layout

| Path | Role |
| --- | --- |
| `php/Admin.php` | Settings screen (Settings → Archive Mapping). AJAX: `dlx_app_get_options`, `dlx_app_save_options`, `dlx_app_reset_options`. |
| `php/Options.php` | Load/save/sanitize options. |
| `php/Enqueue.php` | Scripts on Reading, term edit, and user profile. |
| `php/Rest.php` | REST `POST /dlxplugins/app/v1/search/pages` (page picker). |
| `php/Functions.php` | Helpers, settings URL, post type/taxonomy data. |
| `php/Yoast.php`, `php/RankMath.php`, `php/AIOSEO.php`, `php/Breadcrumb_NavXT.php` | Keep mapped archives' original URLs in SEO/breadcrumb output. |
| `src/react/views/` | Admin UI. |
| `src/scss/admin.scss` | Admin styles. |
| `build/` | Webpack output. Do not edit by hand. |
| `webpack.config.js` | Entries: `app-admin-settings`, `app-settings-reading`, `app-term-edit`, `app-profile-edit`, `app-admin-css`. |

Reading-screen options `post-type-archive-mapping` and `post-type-archive-mapping-404` are legacy names kept for Custom Query Blocks (PTAM) compatibility.

## Commands

```bash
npm install
npm start
npm run build
composer dump-autoload
```

PHPCS: [`phpcs.xml.dist`](phpcs.xml.dist) (WordPress-Core, WordPress-Docs, PHP 7.2+). After PHP edits, run PHP Code Beautifier (`phpcbf`).

## Rules

- Follow WordPress PHP and JavaScript coding standards.
- End code comments with a period.
- Sanitize input and escape output. Verify AJAX/REST nonces and capabilities (`manage_options` for settings; `publish_posts` for page search).
- Use `lucide-react` for admin icons. Do not add Font Awesome or commit `.npmrc` / npm tokens.
- Do not commit `node_modules/`. Do not hand-edit `build/` or `lib/composer/` unless regenerating autoload.
- Localized script object for the main settings screen is `dlxAppSettings`.
- Base pull requests on `development`.
- Human docs: [README.md](README.md), [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md).
