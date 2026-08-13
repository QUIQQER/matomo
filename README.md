![QUIQQER Matomo](bin/images/Readme.jpg)

# Matomo for QUIQQER

Integrates the open-source Matomo analytics platform into QUIQQER projects.

## Features

- Adds Matomo tracking to QUIQQER projects.
- Supports project-wide and language-specific Matomo site IDs.
- Integrates Matomo Tag Manager and the QUIQQER data layer.
- Provides optional ecommerce and user tracking integrations.
- Displays Matomo statistics in the QUIQQER administration.

## Installation

Install the package through QUIQQER's package management or Composer:

```shell
composer require quiqqer/matomo
```

Run the QUIQQER setup after installation so the package configuration and event registrations are imported.

## Configuration

Open the Matomo section in the project settings and configure the Matomo server URL and site ID. Optional settings
include language-specific site IDs, an API access token, Tag Manager container code, consent categories, user tracking,
and the data-layer bridge.

The access token is only required when Matomo statistics should be displayed in the administration. Treat it as a secret
and do not expose it in frontend code.

## Usage

Once enabled for a project, the package injects the configured Matomo tracking or Tag Manager code into rendered pages.
The Matomo entry in the administration opens the statistics view when the required server URL, site ID, and optional
access token are configured.

Ecommerce and frontend-user tracking are activated automatically when the corresponding optional QUIQQER packages are
installed.

## Development

Initialize and run the package-local quality tools with:

```shell
composer dev:init
composer test
```

- [Issue tracker](https://dev.quiqqer.com/quiqqer/matomo/-/issues)
- [Source code](https://dev.quiqqer.com/quiqqer/matomo)

## Support

For errors, feature requests, or other feedback, contact `support@pcsg.de` or use the issue tracker.

## License

GPL-3.0-or-later
