<?php
namespace KwcNewsletter\Bundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class KwcNewsletterExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('kwc_newsletter.subscribers.delete_unsubscribed_after_days', $config['subscribers']['delete_unsubscribed_after_days']);
        $container->setParameter('kwc_newsletter.subscribers.delete_not_activated_after_days', $config['subscribers']['delete_not_activated_after_days']);
        $container->setParameter('kwc_newsletter.subscribers.require_country_param_for_api', $config['subscribers']['require_country_param_for_api']);

        $container->setParameter('kwc_newsletter.open_api.categories.subscribers_limit', $config['open_api']['categories']['subscribers_limit']);
    }
}
