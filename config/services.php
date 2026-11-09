<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Base\Booking\Payment\BookingPayments;
use Base\Booking\Payout\Payouts;
use Base\Booking\Review\Reviews;
use Base\Booking\Stay\DoctrineListingStore;
use Base\Booking\Stay\ListingMapper;
use Base\Booking\Stay\StayRegistryFactory;
use Omnistay\Registry;

return function (ContainerConfigurator $configurator) {
    $src = dirname(__DIR__).'/src';

    $services = $configurator->services();
    $services->defaults()->autowire(true)->autoconfigure(true)->public(false);

    $services->load('Base\\Booking\\', $src.'/')
        ->exclude([$src.'/DependencyInjection/', $src.'/Entity/', $src.'/Enum/', $src.'/Exception/', $src.'/Controller/Admin/', $src.'/BookingBundle.php']);

    $services->load('Base\\Booking\\Controller\\Client\\', $src.'/Controller/Client/')
        ->tag('controller.service_arguments');

    if (class_exists('Base\\Admin\\Controller\\AbstractCrudController')) {
        $services->load('Base\\Booking\\Controller\\Admin\\', $src.'/Controller/Admin/')->tag('controller.service_arguments');
    }

    $services->set(ListingMapper::class)->args(['%booking.local_provider%']);
    $services->set(DoctrineListingStore::class)->public();
    $services->set(StayRegistryFactory::class)->args([
        service(DoctrineListingStore::class), '%booking.providers%', '%booking.service_fee%', '%booking.hold_minutes%',
        service('http_client')->nullOnInvalid(), tagged_iterator('omnistay.provider_factory'),
    ]);
    $services->set(Registry::class)->factory([service(StayRegistryFactory::class), 'create'])->public();

    $services->set(BookingPayments::class)->args([
        service('doctrine.orm.entity_manager'),
        class_exists('Omnitrade\\Registry') ? service('Omnitrade\\Registry')->nullOnInvalid() : null,
        '%booking.payment.gateway%',
        '%booking.payment.test%',
    ])->public();
    $services->set(Payouts::class)->arg('$hostFee', '%booking.host_fee%');
    $services->set(Reviews::class)->arg('$window', '%booking.review_window%');
};
