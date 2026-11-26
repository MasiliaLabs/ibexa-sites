# Ibexa Sites

[![CI](https://github.com/MasiliaLabs/ibexa-sites/actions/workflows/ci.yml/badge.svg)](https://github.com/MasiliaLabs/ibexa-sites/actions/workflows/ci.yml)
[![Latest version](https://img.shields.io/packagist/v/masilia/ibexa-sites.svg)](https://packagist.org/packages/masilia/ibexa-sites)
[![Downloads](https://img.shields.io/packagist/dt/masilia/ibexa-sites.svg)](https://packagist.org/packages/masilia/ibexa-sites/stats)
[![PHP](https://img.shields.io/packagist/php-v/masilia/ibexa-sites.svg)](https://packagist.org/packages/masilia/ibexa-sites)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Create and change sites at run time: a registry of sites (hosts, languages, root, design) that Ibexa serves without a deploy.

`masilia/ibexa-sites` is part of a family of packages for [Ibexa](https://www.ibexa.co) that work together or alone. See [the other packages](#the-masilia-packages).

## What it is

In Ibexa, a site is a siteaccess group written in YAML: adding a site means editing files and deploying. `masilia/ibexa-sites` keeps the sites in the database instead, in a **registry**, and makes Ibexa serve them at run time. An administrator creates a site in the admin, chooses where its content starts, and activates it: its hosts answer at once.

## Features

- **A registry of sites** (`masilia_site` table): each site has a name, a root location, a design, its front siteaccesses (host, optional path prefix such as `/fr`, languages in fallback order), its admin siteaccesses, an optional base group it takes its settings from, a directory for its files, the domain of its session cookie, the remote id of its layout, and a contact email and address.
- **Served at run time**: a matcher answers by host and prefix, the siteaccess provider, the groups, the default siteaccess, the session and the per-siteaccess settings (languages, root, design, translations) are completed from the registry.
- **Draft, active, disabled**: a draft is never served. Activating a site serves its hosts and lets visitors in (the Anonymous role is updated); disabling it takes them out. A site that holds the default siteaccess cannot be disabled or deleted.
- **Checked field by field**: names, hosts, prefixes, languages, addresses taken by another site or by your YAML, the cookie domain that must cover every host of the site. A cookie domain that also reaches another site is a **warning**, not a refusal.
- **An admin page** (`/sites`), built with React and the Ibexa Universal Discovery Widget: a filterable list, a side panel to edit, live validation, confirmation before activating or deleting, and a card for the default siteaccess.
- **A JSON API** under `/api/masilia/sites` (admin siteaccesses only, CSRF-protected) and the policies `masilia_site/view` and `masilia_site/manage`, given by role.
- **Settings for the other packages**: the registry answers `site_name`, `site_url`, `site_email` and `site_address` in the `masilia_ibexa_sites` namespace of the configuration resolver, the layout of the site for `masilia/ibexa-blocks`, and the admin siteaccess of the site for `masilia/editorial-toolbox`.
- **Contracts** (`Masilia\Contracts\IbexaSites`): `SiteRegistry`, `Site`, `SiteAccessDefinition`, `SiteStatus`, `Problem`, `SiteOptions`, `SiteSettings` and the `SiteChanged` event, for packages that work with sites.

## Requirements

- PHP 8.3 or later
- Ibexa 5.0 (Symfony 7.4)
- Node.js 24 and Yarn, to build the admin assets with the Ibexa Encore setup of your project

Installed with it by Composer:

- [`masilia/ibexa-admin-kit`](https://github.com/MasiliaLabs/ibexa-admin-kit) for the React components of the admin page

Works better with (optional):

- [`masilia/ibexa-blocks`](https://github.com/MasiliaLabs/ibexa-blocks) uses the layout of each site
- [`masilia/editorial-toolbox`](https://github.com/MasiliaLabs/editorial-toolbox) finds the admin siteaccess of each site
- [`masilia/ibexa-ai-craft`](https://github.com/MasiliaLabs/ibexa-ai-craft) the assistant creates and changes sites

## Installation

```bash
composer require masilia/ibexa-sites
```

1. Enable the bundle in `config/bundles.php` (and `MasiliaIbexaAdminKitBundle`, which it uses for its admin page):

   ```php
   Masilia\Bundle\IbexaAdminKit\MasiliaIbexaAdminKitBundle::class => ['all' => true],
   Masilia\Bundle\IbexaSites\MasiliaIbexaSitesBundle::class => ['all' => true],
   ```

2. Import the routes (`config/routes/masilia_ibexa_sites.yaml`):

   ```yaml
   masilia_ibexa_sites:
       resource: '@MasiliaIbexaSitesBundle/Resources/config/routes.yaml'
   ```

3. Create the tables and build the admin assets:

   ```bash
   php bin/console doctrine:migrations:migrate
   php bin/console cache:clear
   yarn ibexa-generate-tsconfig
   php bin/console ibexa:encore:compile
   ```

   Inside DDEV, prefix each command with `ddev exec`. The admin build is `ibexa:encore:compile`: `yarn encore dev --config-name ibexa` does not work ("Configuration with the name ibexa was not found"), and a plain `yarn encore dev` does not build the admin on a project that has not merged the Ibexa configuration into its `webpack.config.js`.

4. Give a role the policies `masilia_site / view` (see the sites) and `masilia_site / manage` (create, change, activate, delete). *Manage* also decides who may let visitors into a siteaccess, so give it with the care of a role change. The entry **Sites manager** appears in the **Admin** menu.

### What your project keeps in YAML

Ibexa needs at least one siteaccess and a default one before the registry is read. Keep a minimal technical siteaccess, and declare the two groups the registry fills:

```yaml
# config/packages/ibexa.yaml
ibexa:
    siteaccess:
        list: [technical]
        groups:
            technical_group: [technical]
        default_siteaccess: technical
    system:
        technical:
            languages: [eng-GB]
```

The siteaccesses of the registry join the groups `front_group` (declare it, even empty, wherever your configuration reads it) and `admin_group`, plus the group of their site and its base group.

### With Symfony Flex

The Masilia recipes do the first steps for you. Add their endpoint once to the `composer.json` of your project, after the one of Ibexa:

```json
"extra": {
    "symfony": {
        "endpoint": [
            "https://api.github.com/repos/ibexa/recipes/contents/index.json?ref=flex/main",
            "https://api.github.com/repos/MasiliaLabs/recipes/contents/index.json?ref=flex/main",
            "flex://defaults"
        ]
    }
}
```

Then `composer require masilia/ibexa-sites` enables the bundle and writes `config/routes/masilia_ibexa_sites.yaml`. What is left is yours: the migrations, the admin build, the policies `masilia_site / view` and `manage`, and the minimal technical siteaccess. The manual steps above and below stay valid.

## Usage

Open **Sites** in the admin: **New site**, give a name, the languages and the host, choose the root location in the content browser, pick a design, save, then **Activate**. The hosts must also answer at the server (DNS, web server, certificate, cache): nothing here changes that.

From the console:

```bash
# Import the site groups of your YAML as drafts (nothing is overwritten)
php bin/console masilia:sites:import-static [--dry-run]

# Activate or disable sites, by group name or all of them
php bin/console masilia:sites:set-status active mysite_group
php bin/console masilia:sites:set-status disabled --all
```

**Moving a site from YAML to the registry** — import it as a draft, remove its siteaccesses from the YAML (the registry refuses addresses your YAML still declares), clear the caches, then activate it.

In code, read and write sites through `Masilia\Contracts\IbexaSites\SiteRegistry`; listen to `SiteChanged` to react to a change.

## Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=2G --no-progress
```

The tests of the admin scripts run with Node (see [CONTRIBUTING.md](CONTRIBUTING.md)).

## AI agent skill

This package ships a skill for AI coding agents (Claude Code and other agents that read `SKILL.md` skills): how to install, configure, operate and troubleshoot `masilia/ibexa-sites`. It lives in [`skills/masilia-ibexa-sites`](skills/masilia-ibexa-sites/SKILL.md) and comes with the package.

```bash
# For the project (committed with it): from the installed package
mkdir -p .claude/skills && cp -r vendor/masilia/ibexa-sites/skills/masilia-ibexa-sites .claude/skills/

# For all your projects: global
mkdir -p ~/.claude/skills && cp -r vendor/masilia/ibexa-sites/skills/masilia-ibexa-sites ~/.claude/skills/
```

Restart the agent (or reload its skills) to pick it up. Update the copy after `composer update`.

## The Masilia packages

| Package | What it does |
| --- | --- |
| [`masilia/ibexa-admin-kit`](https://github.com/MasiliaLabs/ibexa-admin-kit) | React components in the design of the Ibexa admin, for admin pages |
| [`masilia/ibexa-sites`](https://github.com/MasiliaLabs/ibexa-sites) | A registry of sites that Ibexa serves without a deploy |
| [`masilia/ibexa-ai`](https://github.com/MasiliaLabs/ibexa-ai) | The AI assistant for Ibexa: capabilities, providers, chat, approval flow |
| [`masilia/ibexa-ai-craft`](https://github.com/MasiliaLabs/ibexa-ai-craft) | The assistant builds sites and pages out of blocks |
| [`masilia/ibexa-ai-editor`](https://github.com/MasiliaLabs/ibexa-ai-editor) | AI suggestions and image generation in the content editing form |
| [`masilia/ibexa-blocks`](https://github.com/MasiliaLabs/ibexa-blocks) | Front-office rendering of pages made of blocks, with themes |
| [`masilia/editorial-toolbox`](https://github.com/MasiliaLabs/editorial-toolbox) | In-context editing toolbar for the front office |

**What requires what** (Composer installs it for you):

- `ibexa-sites` requires `ibexa-admin-kit`
- `ibexa-ai` requires `ibexa-admin-kit`
- `ibexa-ai-editor` requires `ibexa-ai`
- `ibexa-ai-craft` requires `ibexa-ai`, `ibexa-blocks` and `ibexa-sites`
- `ibexa-blocks` and `editorial-toolbox` require none of the others; they are happy to find `ibexa-sites` (and each other) when installed.

## Compatibility

| Package version | PHP | Ibexa |
| --- | --- | --- |
| `latest` | 8.3+ | 5.0 |

## Download

- **Composer** (recommended): `composer require masilia/ibexa-sites` — [ibexa-sites on Packagist](https://packagist.org/packages/masilia/ibexa-sites)
- **Archive** of a release: [GitHub releases](https://github.com/MasiliaLabs/ibexa-sites/releases) (each release lists what changed)
- **Source**: `git clone https://github.com/MasiliaLabs/ibexa-sites.git`
- What changed between versions: [CHANGELOG.md](CHANGELOG.md)

## Stay informed

New versions are announced as **GitHub releases**, with their notes taken from the [CHANGELOG](CHANGELOG.md):

- **On GitHub**: *Watch* this repository, *Custom*, *Releases*, to be notified of each release; or follow the feed `https://github.com/MasiliaLabs/ibexa-sites/releases.atom` in your reader.
- **In your project**: `composer outdated "masilia/*"` lists what is behind. To be told by a pull request, let Dependabot (or Renovate) watch the Masilia packages:

  ```yaml
  # .github/dependabot.yml
  version: 2
  updates:
    - package-ecosystem: composer
      directory: /
      schedule: { interval: weekly }
      allow:
        - dependency-name: "masilia/*"
  ```

- **Before updating**: the CHANGELOG says what changes; a change that breaks something is marked **Breaking** and explained in `UPGRADE.md` when there is one. Before 1.0, a minor version (`0.x`) may contain breaking changes: read the notes of the versions you skip. Security problems are announced as GitHub security advisories (see [SECURITY.md](SECURITY.md)).

## Support, contributing and security

- Questions and bug reports: [GitHub issues](https://github.com/MasiliaLabs/ibexa-sites/issues)
- Pull requests are welcome, see [CONTRIBUTING.md](CONTRIBUTING.md)
- Security problems: please follow [SECURITY.md](SECURITY.md) and do not open a public issue

## License

Released under the [MIT license](LICENSE). Copyright © 2026 MasiliaLabs.

Ibexa itself is distributed under its own licenses (Ibexa OSS: GPL-2.0), which apply to the project that uses this package. This package is an independent project, not affiliated with or endorsed by Ibexa AS; "Ibexa" is a trademark of its owner.
