# omnibase/booking

Stays booking for [omnibase](https://github.com/glitchr-omnisense/omnibase) sites, built on
[omnisense/omnistay](https://github.com/glitchr-omnisense/omnistay): hosts rent out their places,
guests search them beside the hotels of other omnistay providers, book and pay, and both review
each other.

- **Entities**: `Listing` (what, where, how priced, what it accepts), `ListingPhoto`, `Season`,
  `CalendarNight` (a night blocked or priced on its own), `Booking`, `Review` (both directions),
  `Message` (a booking's thread), `HostProfile`, `Payout`.
- **The site's listings as an omnistay provider**: `Stay\DoctrineListingStore` is
  omnistay/local's store; `Stay\StayRegistryFactory` builds the site's `Omnistay\Registry` from
  `booking.providers` (local, amadeus...).
- **Calendar logic**: `Calendar\HostCalendar` (a month night by night, block, open, price nights);
  the rules - minimum stay, seasons, notice, check-in days - are omnistay/local's.
- **The booking flow**: `Checkout\BookingFlow` - quote, hold (under a lock per listing:
  symfony/lock; no night sold twice), pay (`Payment\BookingPayments`, omnitrade: Stripe Checkout),
  complete, a host's answer to a request, cancellation with the policy's penalty and refund;
  `booking:maintain` lets expired holds go, completes past stays, releases payouts.
- **Reviews** in both directions, blind until both are written (`Review\Reviews`); **messages**
  between guest and host (`Conversation\Conversations`); **payouts** less a host fee
  (`Payout\Payouts`).
- **Back office**: omnibase/admin screens for every entity (bookings, payouts and messages
  read-only); voters `BOOKING_VIEW`, `BOOKING_MESSAGE`, `BOOKING_CANCEL`, `BOOKING_RESPOND`,
  `LISTING_MANAGE`.
- **Translations**: English, French, Japanese.

The site's pages (search, a place, trips, the host space) are the application's: the bundle gives
the services, the entities, the payment's return and webhook routes and the back office. solstay
is the reference site. Manual: [docs/index.md](docs/index.md). License: MIT.

## Install

```bash
composer require omnibase/booking
```

Under Symfony it runs on the core's Symfony host: Composer brings `omnisense/omnibase` 5.1 and
`omnibridge/symfony` with it; `config/bundles.php` registers the core's bundle before this one
(see the core's README for the other bundles a site usually adds):

```php
// config/bundles.php
Omnibridge\Symfony\Omnibase\BaseBundle::class => ['all' => true],
Base\Admin\AdminBundle::class => ['all' => true],
Base\Booking\BookingBundle::class => ['all' => true],
```

A first use, once the schema is updated: a host's `Listing` written in the back office is a stay
omnistay/local finds, and `Checkout\BookingFlow::quote()` prices a stay at it - the routes,
the payment (omnitrade/stripe) and the configuration are in [docs/index.md](docs/index.md).

