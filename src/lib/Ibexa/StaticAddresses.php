<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

/**
 * Where each siteaccess of the project answers, read from its siteaccess.match configuration: the host (Map\Host) and, when a
 * compound rule adds one, the prefix of the path (Map\URI). A siteaccess without a prefix is at the root of its host.
 */
final class StaticAddresses
{
    /**
     * @param array<string, mixed> $matchConfig The siteaccess.match configuration
     *
     * @return array<string, array{host: string, prefix: ?string}> By siteaccess name
     */
    public static function from(array $matchConfig): array
    {
        $found = [];
        foreach ($matchConfig as $type => $rules) {
            if (!is_array($rules)) {
                continue;
            }
            if ($type === 'Map\Host') {
                foreach ($rules as $host => $siteaccess) {
                    if (is_string($siteaccess)) {
                        $found[$siteaccess] ??= ['host' => strtolower((string) $host), 'prefix' => null];
                    }
                }
            } elseif ($type === 'Compound\LogicalAnd') {
                foreach ($rules as $rule) {
                    $address = self::compound($rule);
                    if ($address !== null) {
                        $found[$address[0]] = $address[1];
                    }
                }
            }
        }

        return $found;
    }

    /** @return array{0: string, 1: array{host: string, prefix: ?string}}|null */
    private static function compound(mixed $rule): ?array
    {
        if (!is_array($rule) || !is_string($rule['match'] ?? null) || !is_array($rule['matchers'] ?? null)) {
            return null;
        }
        $hosts = array_keys((array) ($rule['matchers']['Map\Host'] ?? []));
        $prefixes = array_keys((array) ($rule['matchers']['Map\URI'] ?? []));
        if ($hosts === []) {
            return null;
        }

        return [$rule['match'], ['host' => strtolower((string) $hosts[0]), 'prefix' => $prefixes === [] ? null : strtolower((string) $prefixes[0])]];
    }
}
