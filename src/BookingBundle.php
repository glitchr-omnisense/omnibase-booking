<?php

namespace Base\Booking;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Stays booking on top of omnibase and omnistay: hosts' listings with their
 * calendars and seasons, bookings held without double booking and paid
 * through omnitrade, reviews both ways, guest-host messages, payouts - and
 * hotels of other omnistay providers searched beside them.
 */
class BookingBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /** The bundle root is the package root: ./templates is @Booking, ./src/Entity is mapped. */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->setMapping($this->getPath().'/src/Entity', 'Base\Booking\Entity', 'App\Entity\Booking');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Booking\Repository', 'App\Repository\Booking');
    }
}
