# omnibase/booking

## Installation

```sh
composer require omnibase/booking omnisense/omnitrade omnitrade/stripe   # omnistay/amadeus for hotels
```

```php
// config/bundles.php
Omnitrade\Bridge\Symfony\OmnitradeBundle::class => ['all' => true],
Base\Booking\BookingBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml - the payment's return and webhook
booking_controller:
    resource: "@BookingBundle/src/Controller/Client"
    type: attribute
```

Then a migration (`make:migration`): the tables `booking_*`.

## Configuration

```yaml
booking:
    service_fee: 12          # the site's fee charged to the guest, % of what the host charges
    host_fee: 3              # kept on each payout, % of the host's share
    hold_minutes: 30         # a pending booking holds its nights this long while the guest pays
    review_window: 14        # days after the check-out to review
    after_payment_route: app_trip   # the site's page for a booking, given {reference}
    payment:
        gateway: stripe      # an omnitrade gateway (omnitrade.gateways.stripe)
        test: false          # without a gateway: a button "pays" - development and demos only
    providers:
        homes: { factory: local }
        hotels:
            factory: amadeus
            enabled: '%env(AMADEUS_ENABLED)%'
            options: { client_id: '%env(AMADEUS_CLIENT_ID)%', client_secret: '%env(AMADEUS_CLIENT_SECRET)%' }
```

The first `local` provider is the site's own listings (`booking.local_provider`); its refs are
`homes:<listing id>`.

## The flow

```php
$availability = $flow->quote($mapper->ref($listing), Stay::of('2026-12-02', '2026-12-05'), new Guests(2));
$booking = $flow->start($user, $mapper->ref($listing), $stay, $guests, 'A word to the host');  // pending, nights held
return $this->redirect($flow->pay($booking, $returnUrl, $cancelUrl, $locale));                 // Stripe Checkout
// /booking/{reference}/payment/return and /booking/payment/{gateway}/webhook call complete()
$flow->respond($booking, $host, accept: true);   // a request
$flow->cancel($booking, $user);                  // the guest keeps the penalty's complement, the host refunds all
```

Double booking: `DoctrineListingStore::hold()` takes the lock `omnibase-booking-listing-<id>`
(`LockFactory`; use a store shared by every PHP process: flock on one host, Redis or the database
beyond), reads the nights taken and writes the booking in one transaction.

## Testing

```sh
php vendor/bin/phpunit -c vendor/omnibase/booking/phpunit.xml.dist     # from the application
```

The bundle's tests: the listing as omnistay/local reads it and the price of a stay, a booking's
hold and parties, the payout's fee, the back office's required fields. The flow end to end (search,
a place, Stripe in test mode with its requests recorded, the same nights refused to a second guest,
reviews, the calendar, messages, a request answered) is tested in solstay
(`tests/Functional/BookingTest.php`). omnisense/omnibase's harness does not list this bundle.
