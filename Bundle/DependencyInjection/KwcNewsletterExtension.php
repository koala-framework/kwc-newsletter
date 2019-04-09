<?php
namespace KwcNewsletter\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class KwcNewsletterExtension extends Extension implements PrependExtensionInterface
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

    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['KwfBundle'])) { // Add specific urls needed as get-request in browser to kwf csrf-ignore list
            $container->prependExtensionConfig('kwf', array(
                'csrf_protection' => array(
                    'ignore_paths' => array(
                        '^/api/v1/subscribers',
                        '^/api/v1/open',
                        '^/api/v1/carlog/subscribers',
                    )
                )
            ));
        }

        if (isset($bundles['FOSRestBundle'])) { // Add serializer config for FOS rest bundle
            $container->prependExtensionConfig('fos_rest', array(
                'routing_loader' => array(
                    'default_format' => 'json',
                    'include_format' => false,
                ),
                'format_listener' => array(
                    'enabled' => true,
                    'rules' => array(
                        array(
                            'path' => '^/api/v1',
                            'fallback_format' => 'json',
                        ),
                    ),
                )
            ));
        }

        if (isset($bundles['SecurityBundle'])) { // Add API endpoint to security config
            $container->prependExtensionConfig('security', array(

                'providers' => array(
                    'api_key_user_provider' => array(
                        'id' => 'api_key_user_provider',
                    )
                ),
            ));
        }
    }

    public function getAlias()
    {
        return 'kwc_newsletter';
    }

}
