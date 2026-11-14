<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\EventListener;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/** "Sites" under the Admin group of the Ibexa sidebar. */
final class MainMenuBuilderListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ConfigureMenuEvent::MAIN_MENU => 'onMainMenuBuild'];
    }

    public function onMainMenuBuild(ConfigureMenuEvent $event): void
    {
        $admin = $event->getMenu()->getChild('main__admin');
        if (!$admin instanceof ItemInterface) {
            return;
        }

        $admin->addChild('main__admin__masilia_sites', ['route' => 'masilia.ibexa_sites.list'])
            ->setLabel('menu.sites')
            ->setExtra('translation_domain', 'masilia_ibexa_sites')
            ->setExtra('icon', 'sections')
            ->setExtra('orderNumber', 152);
    }
}
