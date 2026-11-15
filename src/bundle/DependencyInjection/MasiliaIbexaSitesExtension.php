<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class MasiliaIbexaSitesExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'masilia_ibexa_sites';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'MasiliaIbexaSites' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__ . '/../../lib/Persistence/Entity',
                        'prefix' => 'Masilia\IbexaSites\Persistence\Entity',
                        'alias' => 'MasiliaIbexaSites',
                    ],
                ],
            ],
        ]);
        // The matcher of the registry, next to the ones the project writes: Ibexa builds it by class name for each use.
        $container->prependExtensionConfig('ibexa', [
            'siteaccess' => ['match' => ['\\Masilia\\IbexaSites\\Runtime\\RegistryMatcher' => null]],
        ]);
        // What the Universal Discovery Widget offers for a site: a container to start from, and the content of its layout.
        $container->prependExtensionConfig('ibexa', [
            'system' => ['default' => ['universal_discovery_widget_module' => ['configuration' => [
                'masilia_site_root' => ['multiple' => false, 'containers_only' => true, 'allow_confirmation' => true, 'allow_redirects' => false],
                'masilia_site_layout' => ['multiple' => false, 'allowed_content_types' => ['layout_config'], 'allow_confirmation' => true, 'allow_redirects' => false],
            ]]]],
        ]);
        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => [
                'Masilia\\Bundle\\IbexaSites\\Migrations' => __DIR__ . '/../Migrations',
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config')))->load('services.yaml');
    }
}
