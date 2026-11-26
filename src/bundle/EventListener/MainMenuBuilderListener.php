<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\EventListener;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/** "Sites manager" under the Admin group of the Ibexa sidebar, for those who may see the sites. */
final readonly class MainMenuBuilderListener implements EventSubscriberInterface
{
    public function __construct(private AuthorizationCheckerInterface $authorization)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConfigureMenuEvent::MAIN_MENU => 'onMainMenuBuild'];
    }

    public function onMainMenuBuild(ConfigureMenuEvent $event): void
    {
        $admin = $event->getMenu()->getChild('main__admin');
        if (!$admin instanceof ItemInterface || !$this->authorization->isGranted(new Attribute('masilia_site', 'view'))) {
            return;
        }

        $admin->addChild('main__admin__masilia_sites', ['route' => 'masilia.ibexa_sites.list'])
            ->setLabel('menu.sites')
            ->setExtra('translation_domain', 'masilia_ibexa_sites')
            ->setExtra('icon', 'sections')
            ->setExtra('orderNumber', 152);
    }
}
