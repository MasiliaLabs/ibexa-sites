---
name: masilia-ibexa-sites
description: Create, change, activate and troubleshoot sites that Ibexa 5 serves at run time with masilia/ibexa-sites (registry of sites in the database - hosts, languages, root location, design, admin siteaccesses, session cookie, layout). Use whenever the user wants to add or move a site or siteaccess, replace siteaccess YAML, import static sites, activate/disable a site, fix a host that does not answer, a 404 on a site, the default siteaccess, `masilia:sites:*` commands, the /sites admin page, or mentions masilia/ibexa-sites, even without naming the package.
---

# masilia/ibexa-sites

A site is normally a siteaccess group in YAML (edit, deploy). This package keeps sites in the `masilia_site` table and makes Ibexa serve them at run time: matcher by host and prefix, siteaccess provider, groups, default siteaccess, session and per-siteaccess settings are completed from the registry. States: **draft** (never served), **active** (hosts answer, Anonymous role updated), **disabled**.

## Install
`composer require masilia/ibexa-sites`; enable `MasiliaIbexaAdminKitBundle` and `MasiliaIbexaSitesBundle`; import `@MasiliaIbexaSitesBundle/Resources/config/routes.yaml`; `doctrine:migrations:migrate`; `cache:clear`; `yarn ibexa-generate-tsconfig`; `php bin/console ibexa:encore:compile` (not `yarn encore dev --config-name ibexa`: that config does not exist on a blank project); give a role `masilia_site/view` and `masilia_site/manage` (manage lets someone open a siteaccess to visitors: treat it like a role change). The project must keep a minimal technical siteaccess in YAML (`list: [technical]`, `default_siteaccess: technical`, groups `technical_group`, and `front_group`/`admin_group` declared even when empty).

With the Masilia recipes endpoint in the `composer.json` (`https://api.github.com/repos/MasiliaLabs/recipes/contents/index.json?ref=flex/main` in `extra.symfony.endpoint`, after Ibexa's), `composer require` already enables the bundle and writes the route and config files (see the README, section *With Symfony Flex*); then do what is left. Without the endpoint, do everything by hand.

## Everyday work
- Admin: **Sites manager** > New site (name, languages, host, root via the content browser, design) > save > Activate. Confirmation dialogs say what changes.
- Console: `masilia:sites:import-static [--dry-run]` imports YAML site groups as drafts; `masilia:sites:set-status active|disabled <group>|--all`.
- Code: read/write through `Masilia\Contracts\IbexaSites\SiteRegistry` (`check()` returns problems by field, `create()`, `update()`, `setStatus()`); react with the `SiteChanged` event. Never write the table directly.
- Moving a site from YAML: import as draft, remove its siteaccesses from YAML (the registry refuses addresses the YAML still declares), clear caches, activate.
- The design of a site must be a key of `ibexa_design_engine.design_list` (that list stays in YAML).

## After any change made outside the registry (SQL, restore)
Clear `cache:clear` and `cache:pool:clear --all` (the registry snapshot lives in `cache.app`), and reload PHP-FPM if you changed PHP files (opcache). Without it a site "does not change".

## Troubleshooting
- Host answers 404: the site is not `active`, the host or prefix differs from the request, or DNS/server does not reach Ibexa. `masilia:sites:set-status` and `debug:router`/`debug:config` help; check `masilia_site` in the database.
- Registry refuses a save: read the problem path (`siteaccesses[1].host`...): host/prefix already taken (by YAML too), language not installed in Ibexa, unknown design, cookie domain not covering every host. A cookie domain that also reaches another site is only a warning.
- A default siteaccess site cannot be disabled or deleted: change the default first.
- Admin session lost after a move: the session cookie name/domain changed with the site; the user signs in again (never enter their password).

## Don't
Do not activate a site the user did not ask to serve (it exposes its hosts). Do not delete production data from the dev database as if it were shipped: sites, admins and cookies typed in dev do not travel with a release.
