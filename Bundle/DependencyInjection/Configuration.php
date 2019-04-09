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
                        ->booleanNode('require_country_param_for_api')
                            ->defaultValue(false)
                        ->end()
                    ->end()
                ->end() //subscribers
                ->arrayNode('open_api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('categories')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('subscribers_limit')
                                    ->defaultValue(1000)
                                ->end()
                            ->end()
                        ->end() // categories
                    ->end()
                ->end() // open_api
            ->end();

        return $treeBuilder;
    }
}
