<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use Traversable;

/**
 * The siteaccesses of the active sites, next to the ones the project declares: as many as the site declares. A siteaccess belongs to the
 * front group, to the group of its site (the settings the site shares) and to the group it takes the rest from (its "base"), which is how it gets everything the registry does not set.
 */
final readonly class DynamicSiteAccessProvider implements SiteAccessProviderInterface
{
    public function __construct(private SiteSnapshot $sites, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%admin_group_name%')] private string $adminGroup)
    {
    }

    public function isDefined(string $name): bool
    {
        return $this->sites->bySiteaccess($name) !== null;
    }

    public function getSiteAccess(string $name): SiteAccess
    {
        $found = $this->sites->bySiteaccess($name) ?? throw new NotFoundException('Site Access', $name);

        return new SiteAccess(
            $name,
            SiteAccess::DEFAULT_MATCHING_TYPE,
            null,
            self::class,
            array_map(static fn (string $group): SiteAccessGroup => new SiteAccessGroup($group), SiteGroups::of($found[0], $name, $this->adminGroup)),
        );
    }

    public function getSiteAccesses(): Traversable
    {
        foreach ($this->sites->active() as $site) {
            foreach ($site->allSiteaccessNames() as $name) {
                yield $this->getSiteAccess($name);
            }
        }
    }
}
