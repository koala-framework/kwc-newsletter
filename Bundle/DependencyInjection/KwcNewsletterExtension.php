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
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $def = $container->getDefinition('kwc_newsletter.maintenance_job.delete_unsubscribed');
        $def->addArgument($config['subscribers']['delete_unsubscribed_after_days']);

        $def = $container->getDefinition('kwc_newsletter.maintenance_job.delete_not_activated');
        $def->addArgument($config['subscribers']['delete_not_activated_after_days']);
    }

}
