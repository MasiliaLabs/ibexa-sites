<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

/**
 * The hosts the project's own siteaccess matching answers on: the keys of its Map\Host matchers, however deep they sit (in a
 * Compound\LogicalAnd, with a URI matcher beside them).
 */
final class StaticHosts
{
    /**
     * @param array<string, mixed> $matchConfig The siteaccess.match configuration
     *
     * @return list<string>
     */
    public static function from(array $matchConfig): array
    {
        $hosts = [];
        self::collect($matchConfig, $hosts);

        return array_values(array_unique($hosts));
    }

    /**
     * @param array<mixed> $config
     * @param list<string> $hosts
     */
    private static function collect(array $config, array &$hosts): void
    {
        foreach ($config as $key => $value) {
            if ($key === 'Map\Host' && is_array($value)) {
                foreach (array_keys($value) as $host) {
                    $hosts[] = strtolower((string) $host);
                }
                continue;
            }
            if (is_array($value)) {
                self::collect($value, $hosts);
            }
        }
    }
}
