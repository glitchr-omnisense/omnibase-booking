<?php

namespace Base\Booking\DependencyInjection;

use Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BookingConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->floatNode('service_fee')->min(0)->max(50)->defaultValue(12)->info('The site\'s fee charged to the guest, in percent of what the host charges.')->end()
                ->floatNode('host_fee')->min(0)->max(50)->defaultValue(3)->info('Kept on each payout, in percent of the host\'s share.')->end()
                ->integerNode('hold_minutes')->min(1)->defaultValue(30)->info('How long a pending booking holds its nights while the guest pays.')->end()
                ->integerNode('review_window')->min(1)->defaultValue(14)->info('Days after the check-out during which both sides may review.')->end()
                ->scalarNode('after_payment_route')->defaultValue('booking_payment_done')->info('The site\'s page for a booking, given {reference}: where the guest lands after paying.')->end()
                ->arrayNode('payment')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('gateway')->defaultNull()->info('The omnitrade gateway bookings are paid through (omnitrade.gateways.<name>).')->end()
                        ->booleanNode('test')->defaultFalse()->info('Without a gateway: a button "pays" (nothing is charged). Never in production.')->end()
                    ->end()
                ->end()
                ->arrayNode('providers')
                    ->info('The omnistay providers searched, by name: the site\'s own listings (factory local) and others (amadeus).')
                    ->useAttributeAsKey('name')
                    ->defaultValue(['homes' => ['factory' => 'local', 'options' => [], 'enabled' => true]])
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('factory')->isRequired()->cannotBeEmpty()->end()
                            ->variableNode('options')->defaultValue([])->end()
                            ->scalarNode('enabled')->defaultTrue()->info('false, or an env var read as a boolean: left out of the registry.')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
