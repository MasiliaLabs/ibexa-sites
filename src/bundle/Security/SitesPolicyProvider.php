<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Security;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

/** Who may see the sites and who may change them, as Ibexa policies of the module "masilia_site": given by role in the admin. */
final class SitesPolicyProvider extends YamlPolicyProvider
{
    protected function getFiles(): array
    {
        return [__DIR__ . '/../Resources/config/policies.yaml'];
    }
}
