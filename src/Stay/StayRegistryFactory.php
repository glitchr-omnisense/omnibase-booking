<?php

namespace Base\Booking\Stay;

use Omnistay\Amadeus\AmadeusFactory;
use Omnistay\Local\LocalFactory;
use Omnistay\ProviderFactoryInterface;
use Omnistay\Registry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The site's omnistay registry, from booking.providers: omnistay/local on
 * the Doctrine store (its service fee and hold from booking.service_fee and
 * booking.hold_minutes unless its options say otherwise), omnistay/amadeus
 * when installed, on the application's HTTP client. A provider whose
 * "enabled" is false - Amadeus without credentials - is left out.
 */
final class StayRegistryFactory
{
    /**
     * @param array<string, array{factory: string, options?: array<string, mixed>, enabled?: bool|string}> $providers
     * @param iterable<ProviderFactoryInterface>                                                           $factories more factories (tagged omnistay.provider_factory)
     */
    public function __construct(
        private readonly DoctrineListingStore $store,
        private readonly array $providers,
        private readonly float $serviceFee = 0.0,
        private readonly int $holdMinutes = 30,
        private readonly ?HttpClientInterface $http = null,
        private readonly iterable $factories = [],
    ) {
    }

    public function create(): Registry
    {
        $factories = [new LocalFactory($this->store)];
        if (class_exists(AmadeusFactory::class)) {
            $factories[] = new AmadeusFactory($this->http);
        }
        foreach ($this->factories as $factory) {
            $factories[] = $factory;
        }
        $config = [];
        foreach ($this->providers as $name => $provider) {
            $enabled = $provider['enabled'] ?? true;
            if (false === $enabled || \in_array(strtolower((string) $enabled), ['0', 'false', 'no', 'off', ''], true)) {
                continue;
            }
            $options = $provider['options'] ?? [];
            if ('local' === $provider['factory']) {
                $options += ['service_fee' => $this->serviceFee, 'hold_minutes' => $this->holdMinutes];
            }
            $config[(string) $name] = ['factory' => $provider['factory'], 'options' => $options];
        }

        return new Registry($factories, $config);
    }
}
