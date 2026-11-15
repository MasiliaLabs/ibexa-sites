<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Masilia\Contracts\IbexaSites\Site;

/**
 * The siteaccess groups of the siteaccesses of the registry, as the project's YAML would declare them: a front siteaccess is in
 * "front_group", in the group of its site and in its base group; an admin one is in the admin group and nowhere else.
 *
 * Ibexa compiles the groups into its container (about fifteen services receive them): the same table, completed, is given to those
 * services by a compiler pass, so that a siteaccess of the registry is known to them like any other.
 */
final class SiteGroups
{
    /** SPIKE: an admin siteaccess is told by its name until the model has them. */
    public static function isAdmin(string $siteaccess): bool
    {
        return str_starts_with($siteaccess, 'admin_');
    }

    /** @return list<string> */
    public static function of(Site $site, string $siteaccess, string $adminGroup): array
    {
        if (self::isAdmin($siteaccess)) {
            return [$adminGroup];
        }

        return array_values(array_unique(array_filter(['front_group', $site->identifier, $site->base])));
    }

    /**
     * @param array<string, list<string>> $compiled ibexa.site_access.groups
     *
     * @return array<string, list<string>>
     */
    public static function groups(array $compiled, SiteSnapshot $sites, string $adminGroup): array
    {
        foreach (self::all($sites, $adminGroup) as $siteaccess => $groups) {
            foreach ($groups as $group) {
                if (!in_array($siteaccess, $compiled[$group] ?? [], true)) {
                    $compiled[$group][] = $siteaccess;
                }
            }
        }

        return $compiled;
    }

    /**
     * @param array<string, list<string>> $compiled ibexa.site_access.groups_by_site_access
     *
     * @return array<string, list<string>>
     */
    public static function bySiteaccess(array $compiled, SiteSnapshot $sites, string $adminGroup): array
    {
        foreach (self::all($sites, $adminGroup) as $siteaccess => $groups) {
            $compiled[$siteaccess] = array_values(array_unique([...($compiled[$siteaccess] ?? []), ...$groups]));
        }

        return $compiled;
    }

    /** @return array<string, list<string>> The groups of each siteaccess of the served sites */
    private static function all(SiteSnapshot $sites, string $adminGroup): array
    {
        $all = [];
        foreach ($sites->active() as $site) {
            foreach ($site->siteaccessNames() as $name) {
                $all[$name] = self::of($site, $name, $adminGroup);
            }
        }

        return $all;
    }
}
