<?php
namespace KwcNewsletter\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kwc_newsletter');

        $rootNode
            ->children()
                ->arrayNode('subscribers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('delete_not_activated_after_days')
                            ->defaultValue(7)
                        ->end()
                        ->integerNode('delete_unsubscribed_after_days')
                            ->defaultValue(365)
                        ->end()
                    ->end()
                ->end() //subscribers
            ->end();

        return $treeBuilder;
    }
}
