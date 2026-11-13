# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

### Changed

- Installs with the default minimum-stability: stable requirements (`omnisense/omnibase` ^5.1, `omnibridge/symfony` ^1.1, `omnibase/admin` ^1.0, `omnisense/omnistay` and
  `omnistay/local` ^1.0), a branch alias,
  no `minimum-stability: dev` left to the sites.
- CI: glitchr-studio/ci's package workflow (the bundle installed alone, composer validate, PHPUnit,
  PHPStan against its baseline, a consumer install without require-dev) and its release workflow.
- `doctrine/doctrine-bundle` required: the repositories extend its `ServiceEntityRepository`.
- From the reserved name to the bundle: listings, calendars, seasons, bookings held under a lock
  and paid through omnitrade, reviews both ways, messages, payouts, the back office; omnistay's
  registry with the site's listings as its local provider. MIT (the stub said LGPL-3.0-or-later:
  every omnibase/* package is MIT since 2026-10-09).
