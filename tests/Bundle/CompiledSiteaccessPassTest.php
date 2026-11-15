<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Bundle;

use Masilia\Bundle\IbexaSites\DependencyInjection\Compiler\CompiledSiteaccessPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class CompiledSiteaccessPassTest extends TestCase
{
    private const GROUPS_BY_SITEACCESS = ['technical' => ['technical_group']];

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('ibexa.site_access.groups', ['technical_group' => ['technical']]);
        $container->setParameter('ibexa.site_access.groups_by_site_access', self::GROUPS_BY_SITEACCESS);
        $container->setParameter('admin_group_name', 'admin_group');

        return $container;
    }

    public function testAServiceThatAsksForTheGroupsByParameterGetsTheCompletedOnes(): void
    {
        $container = $this->container();
        $container->setDefinition('a', new Definition('stdClass', ['%ibexa.site_access.groups_by_site_access%']));

        (new CompiledSiteaccessPass())->process($container);

        $argument = $container->getDefinition('a')->getArgument(0);
        self::assertInstanceOf(Reference::class, $argument);
        self::assertSame('masilia_ibexa_sites.groups_by_siteaccess', (string) $argument);
    }

    public function testAServiceThatAskedForThemWithAutowireAlreadyHoldsTheValueAndGetsTheCompletedOnesToo(): void
    {
        $container = $this->container();
        $container->setDefinition('b', new Definition('stdClass', [self::GROUPS_BY_SITEACCESS, 'admin_group']));

        (new CompiledSiteaccessPass())->process($container);

        self::assertSame('masilia_ibexa_sites.groups_by_siteaccess', (string) $container->getDefinition('b')->getArgument(0));
        self::assertSame('admin_group', $container->getDefinition('b')->getArgument(1), 'the other arguments stay as they are');
    }

    public function testAnotherArrayIsLeftAlone(): void
    {
        $container = $this->container();
        $container->setDefinition('c', new Definition('stdClass', [['x' => ['y']]]));

        (new CompiledSiteaccessPass())->process($container);

        self::assertSame(['x' => ['y']], $container->getDefinition('c')->getArgument(0));
    }
}
