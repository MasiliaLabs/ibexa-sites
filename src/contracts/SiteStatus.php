<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/** Where a site is in its life: only an active site is served. */
enum SiteStatus: string
{
    /** Being prepared: not served, nothing of it is known to Ibexa. */
    case Draft = 'draft';
    /** Served: its siteaccesses exist, its hosts answer. */
    case Active = 'active';
    /** Kept, not served. */
    case Disabled = 'disabled';

    public function isServed(): bool
    {
        return $this === self::Active;
    }
}
