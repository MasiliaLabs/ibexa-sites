<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\EventListener;

use Masilia\Contracts\IbexaSites\Event\SiteChanged;
use Masilia\IbexaSites\Access\AnonymousLoginAccess;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** What follows a change of the registry: the run time forgets the old sites, and visitors may enter (or no longer) the siteaccesses. */
final readonly class SiteChangedListener
{
    public function __construct(private SiteSnapshot $sites, private AnonymousLoginAccess $anonymous)
    {
    }

    #[AsEventListener]
    public function onSiteChanged(SiteChanged $event): void
    {
        $this->sites->invalidate();
        $this->anonymous->sync($event->before, $event->after);
    }
}
