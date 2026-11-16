# Contributing to masilia/ibexa-sites

Thank you for taking the time to contribute. Bug reports, ideas and pull requests are welcome.

## Reporting a bug or asking for a feature

Open an [issue](https://github.com/MasiliaLabs/ibexa-sites/issues/new/choose) and fill the template. For a bug, give the versions of the package, PHP and Ibexa, what you did, what you expected and what happened. Security problems go through the [security policy](SECURITY.md), never through a public issue.

## Working on the code

```bash
git clone https://github.com/MasiliaLabs/ibexa-sites.git
cd ibexa-sites
composer install
```

Before you open a pull request, run what the continuous integration runs:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=2G --no-progress
cd src/bundle/Resources/public/admin
MASILIA_ADMIN_KIT=/path/to/ibexa-admin-kit/src/bundle/Resources/public/admin node --experimental-strip-types --import ./test-hooks/register.mjs --test sites/*.test.ts
```

The scripts of the admin are written in TypeScript. Their tests run with Node 24 as they are, without any package to install: the logic that is tested lives in `.ts` files, the components in `.tsx` files are checked by the build of the project that uses the package.

The components of the admin come from [`masilia/ibexa-admin-kit`](https://github.com/MasiliaLabs/ibexa-admin-kit): check it out next to this repository, or anywhere, and point `MASILIA_ADMIN_KIT` at its `src/bundle/Resources/public/admin` directory.

## Pull requests

- Target the `main` branch and keep the change focused on one thing.
- Add or update the tests that cover it.
- Describe what changes for the people who use the package, and update the `CHANGELOG.md` (the `Unreleased` section).
- Commit messages start with `feat:`, `fix:`, `refactor:`, `docs:` or `chore:`.

By contributing you agree that your contribution is released under the [MIT license](LICENSE) of the project.
