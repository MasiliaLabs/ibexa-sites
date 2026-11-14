<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\DependencyInjection\Compiler;

use Ibexa\Core\Helper\TranslationHelper;
use Masilia\IbexaSites\Runtime\SiteaccessesByLanguage;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ibexa gives its translation helper the siteaccess of each language as a table compiled from the YAML: the siteaccesses of the
 * registry are not in it, so the language switcher and the alternate links would not find them. The helper gets the table with them.
 */
final class TranslationHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(TranslationHelper::class)) {
            return;
        }
        $table = (new Definition('array'))
            ->setFactory([SiteaccessesByLanguage::class, 'merge'])
            ->setArguments(['%ibexa.site_access.by_language%', new Reference(SiteSnapshot::class)])
            ->setShared(false);
        $container->setDefinition('masilia_ibexa_sites.siteaccesses_by_language', $table);
        $container->getDefinition(TranslationHelper::class)->replaceArgument(2, new Reference('masilia_ibexa_sites.siteaccesses_by_language'));
    }
}
