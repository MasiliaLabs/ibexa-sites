<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\IbexaSites\Validation\SiteFacts;

/** The facts of a small project: a front site "main" (root 62) and the admin. */
final class FakeFacts implements SiteFacts
{
    public function locationExists(int $locationId): bool
    {
        return in_array($locationId, [2, 62, 500, 501], true);
    }

    public function languageExists(string $code): bool
    {
        return in_array($code, ['eng-GB', 'fre-FR', 'ara-MA'], true);
    }

    public function languages(): array
    {
        return ['ara-MA', 'eng-GB', 'fre-FR'];
    }

    public function designs(): array
    {
        return ['imal', 'mattcch'];
    }

    public function baseGroups(): array
    {
        return ['front_group', 'main_group'];
    }

    public function adminSiteaccesses(): array
    {
        return ['admin'];
    }

    public function reservedNames(): array
    {
        return ['main', 'main_fr', 'admin', 'front_group', 'main_group'];
    }

    public function staticHosts(): array
    {
        return ['main.example.org', 'admin.example.org'];
    }

    public function staticRoots(): array
    {
        return [62];
    }
}
