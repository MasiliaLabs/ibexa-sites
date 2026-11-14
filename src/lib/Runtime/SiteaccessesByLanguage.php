<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

/**
 * Which siteaccesses have a language as their own, the table Ibexa compiles for its translation helper (the language switcher, the
 * alternate links): the project's, plus the siteaccesses of the sites of the registry.
 */
final class SiteaccessesByLanguage
{
    /**
     * @param array<string, list<string>> $compiled The table Ibexa built from the project's configuration
     *
     * @return array<string, list<string>>
     */
    public static function merge(array $compiled, SiteSnapshot $sites): array
    {
        foreach ($sites->active() as $site) {
            foreach ($site->siteaccesses as $siteaccess) {
                $main = $siteaccess->languages[0] ?? null;
                if ($main !== null && !in_array($siteaccess->name, $compiled[$main] ?? [], true)) {
                    $compiled[$main][] = $siteaccess->name;
                }
            }
        }

        return $compiled;
    }
}
