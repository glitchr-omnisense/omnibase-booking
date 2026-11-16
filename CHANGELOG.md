# Changelog

## [Unreleased]

omnibase/booking 1.0.0: stays booking for omnibase sites on omnisense/omnistay (listings, calendars, bookings held
without double booking, payments, reviews, messages, payouts) on the agnostic persistence. Its repositories extend
`Base\Database\Repository\ServiceEntityRepository` and query in omnibase's finder language, the bulk update of the
completed stays included (`updateBy()`), each query written once and compiled by the engine of the persistence chosen
(omnibridge/doctrine or omnibridge/eloquent); its services write through `Base\Contract\UnitOfWork`. `Stay\ListingStore`
is the site's listings as an omnistay provider. The code names `Base\` and its own `Base\Booking\` only. Requires
omnisense/omnibase and omnibridge/symfony `^1.0`.

## [1.0.2] - 2026-11-14

### Changed

- Accepts omnisense/omnibase 6.0: `omnisense/omnibase` ^5.1 || ^6.0, `omnibridge/symfony` ^1.1 || ^2.0. The
  same code runs on both lines: it names no former name and no entry point 6.0 removed.

## [1.0.1] - 2026-11-14

### Changed

- The classes omnisense/omnibase 5.0 moved to omnibridge/symfony named by their new names:
  `Omnibridge\Symfony\Omnibase\Bundle\{AbstractBaseBundle, AbstractBaseExtension, AbstractBaseConfiguration}`
  in place of `Base\Bundle\*`, former names the core keeps only as aliases until 6.0. No behaviour change.

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
