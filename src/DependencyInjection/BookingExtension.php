<?php

namespace Base\Booking\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class BookingExtension extends AbstractBaseExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container): BookingConfiguration
    {
        return new BookingConfiguration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new BookingConfiguration();
        /** @var array{providers: array<string, array{factory: string}>} $config */
        $config = (new Processor())->processConfiguration($configuration, $configs);
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());

        // The name of the provider the site's own listings answer under: the first "local" one.
        $local = 'homes';
        foreach ($config['providers'] as $name => $provider) {
            if ('local' === $provider['factory']) {
                $local = (string) $name;
                break;
            }
        }
        $container->setParameter('booking.local_provider', $local);

        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))->load('services.php');
    }
}
