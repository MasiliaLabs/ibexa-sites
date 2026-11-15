<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Input;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;

/**
 * The site a script of the admin describes, as the registry wants it: text trimmed, hosts and prefixes in lower case (the prefix
 * without its slashes), an empty value as no value, numbers as numbers. The identifier and the status are not the script's to give:
 * they come from the address and from the server, so that a change cannot also serve a site by the way.
 *
 * The values are not checked here (the registry does it, field by field): what the script wrote is kept as far as it can be.
 */
final class SiteInput
{
    /** @param array<string, mixed> $data The shape of {@see Site::toArray()} */
    public static function site(array $data, string $identifier, SiteStatus $status): Site
    {
        return new Site(
            trim($identifier),
            self::text($data['name'] ?? '') ?? '',
            $status,
            is_numeric($data['rootLocationId'] ?? null) ? (int) $data['rootLocationId'] : 0,
            self::text($data['design'] ?? '') ?? '',
            self::definitions($data['siteaccesses'] ?? []),
            self::text($data['base'] ?? null),
            self::text($data['sessionCookieDomain'] ?? null),
            self::text($data['adminSiteaccess'] ?? null),
            self::text($data['varDir'] ?? null),
            self::text($data['layoutRemoteId'] ?? null),
            self::definitions($data['adminSiteaccesses'] ?? []),
            self::text($data['email'] ?? null),
            self::text($data['address'] ?? null),
        );
    }

    private static function text(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /** @return list<SiteAccessDefinition> */
    private static function definitions(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $definitions = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $prefix = trim((string) (is_scalar($row['uriPrefix'] ?? null) ? $row['uriPrefix'] : ''), " /\t");
            $languages = is_array($row['languages'] ?? null) ? $row['languages'] : [];
            $definitions[] = new SiteAccessDefinition(
                self::text($row['name'] ?? '') ?? '',
                array_values(array_filter(array_map(static fn (mixed $l): string => is_scalar($l) ? trim((string) $l) : '', $languages), static fn (string $l): bool => $l !== '')),
                strtolower(self::text($row['host'] ?? '') ?? ''),
                $prefix === '' ? null : strtolower($prefix),
            );
        }

        return $definitions;
    }
}
