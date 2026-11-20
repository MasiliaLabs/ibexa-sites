# Changelog

All notable changes to `masilia/ibexa-sites` are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/) (before 1.0, a minor version may contain breaking changes).

## [Unreleased]

### Added
- Docs: a *Stay informed* section in the README: how to be told of new releases (GitHub Watch, the releases feed, `composer outdated`, Dependabot) and where to read what changes.

## [0.2.6] - 2026-11-20

### Changed
- The page of the sites is no longer wrapped in the `container ibexa-container` section (0.2.5 added it): it is back to the root of the React application in the content block.

## [0.2.5] - 2026-11-20

### Fixed
- The page of the sites is wrapped in the `container ibexa-container` section that every Ibexa admin list uses, so that it has the margins of the other pages.

## [0.2.4] - 2026-11-19

### Added
- Docs: a *With Symfony Flex* section in the README and in the skill: the Masilia recipes (`MasiliaLabs/recipes`) enable the bundle and write its route and config files on `composer require`.

## [0.2.3] - 2026-11-19

### Fixed
- `doctrine:schema:update --dump-sql` no longer wants to convert the columns of `masilia_site` and `masilia_setting`: they were created with the `ascii` character set, which the mapping does not declare. A migration gives them the character set of their table (utf8mb4); the data is ascii text and does not change. Additive and idempotent.

## [0.2.2] - 2026-11-19

### Fixed
- Install docs: the admin is built with `php bin/console ibexa:encore:compile` (`yarn encore dev --config-name ibexa` does not exist on a blank project); README and skill corrected.

## [0.2.1] - 2026-11-19

### Added
- An agent skill, `skills/masilia-ibexa-sites`, for AI coding agents that work with the package; the README says how to install it.

### Changed
- The Masilia dependencies come from Packagist: the `repositories` entries of `composer.json` are gone.

## [0.2.0] - 2026-11-17

### Added
- A site has a contact email and an address (`masilia_site` migration `Version20261116000000`). The registry answers `site_name`, `site_url`, `site_email` and `site_address` in the `masilia_ibexa_sites` namespace of the configuration resolver, and the admin siteaccess of a site in the `masilia_editorial_toolbox` namespace.
- A validation problem can be a **warning**: a cookie domain that also reaches another site no longer prevents saving.
- The list shows Site, Design, Status, Addresses and Admin, and takes the whole width.

### Fixed
- Services that ask for the siteaccess groups with `#[Autowire]` now get the groups of the registry too (the compiled groups are replaced after autowiring).
- Notifications go through Ibexa's notification helper, and the panel closes before the toast.

### Changed
- Requires `masilia/ibexa-admin-kit` `^0.1.1`, and declares the dependencies the code uses directly.
- Examples and test fixtures are neutral (no project names).

## [0.1.1] - 2026-11-15

### Fixed
- Accepts `doctrine/orm` `^2.20 || ^3.0` (Ibexa 5 runs ORM 2.20).

## [0.1.0] - 2026-11-15

First release.

### Added
- The registry of sites (`masilia_site`, `masilia_setting`), served at run time: host and prefix matcher, siteaccess provider, groups, default siteaccess, session and per-siteaccess settings.
- Draft, active and disabled sites; activating a site lets visitors in through the Anonymous role.
- Field-by-field validation of names, hosts, prefixes, languages, addresses and the cookie domain.
- The admin page `/sites` (React and the Universal Discovery Widget), the JSON API under `/api/masilia/sites`, and the policies `masilia_site/view` and `masilia_site/manage`.
- Commands `masilia:sites:import-static` and `masilia:sites:set-status`.
- The contracts in `Masilia\Contracts\IbexaSites`.

[Unreleased]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.6...HEAD
[0.2.6]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.5...0.2.6
[0.2.5]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.4...0.2.5
[0.2.4]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/MasiliaLabs/ibexa-sites/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/MasiliaLabs/ibexa-sites/releases/tag/0.1.0
