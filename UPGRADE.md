# Upgrade guide

What to change in your project when you move to a version that breaks something. The full list of changes is in the [CHANGELOG](CHANGELOG.md).

## From 0.2 to 0.3

- Requires `masilia/ibexa-admin-kit` `^0.2` (it was `^0.1`). Update the two together: `composer update masilia/ibexa-sites masilia/ibexa-admin-kit`. A project that requires the kit itself has to raise its constraint.
- Rebuild the admin after updating (`php bin/console ibexa:encore:compile`) and hard-reload the page: the admin scripts of the page changed.
- The entry of the **Admin** menu is **Sites manager** (it was "Sites") and so is the title of the page. Nothing to change unless a document or a test of yours names the old label.
