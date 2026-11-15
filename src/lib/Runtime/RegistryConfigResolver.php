<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;

/**
 * The settings of the siteaccesses of the active sites that the registry sets: the languages of each siteaccess, the root of the tree, the design, the
 * siteaccesses of the translations (for the fronts), the directory of the files and the session (for fronts and admins), and the
 * layout of masilia/ibexa-blocks. Everything else comes from the groups of the siteaccess (see DynamicSiteAccessProvider), so
 * this answers "not found" for it, and for every siteaccess the project declares: they are the static resolvers' business.
 */
final class RegistryConfigResolver implements ConfigResolverInterface, SiteAccessAware
{
    private ?SiteAccess $siteAccess = null;
    private string $defaultNamespace = 'ibexa.site_access.config';

    public function __construct(private readonly SiteSnapshot $sites)
    {
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    public function getParameter(string $paramName, ?string $namespace = null, ?string $scope = null)
    {
        $scope ??= $this->siteAccess?->name;
        $found = $scope === null ? null : $this->sites->bySiteaccess($scope);
        if ($found !== null) {
            [$site, $definition] = $found;
            $own = ($namespace ?? $this->defaultNamespace) === $this->defaultNamespace;
            $isAdmin = SiteGroups::isAdmin($scope);
            if ($own) {
                // What the files and the session of a site are the same for its fronts and its admins.
                if ($paramName === 'var_dir' && $site->varDir !== null) {
                    return $site->varDir;
                }
                if ($paramName === 'session' && $site->sessionCookieDomain !== null) {
                    return ['name' => 'IBX_SESSION_ID_' . md5($site->identifier), 'cookie_domain' => $site->sessionCookieDomain];
                }
                if (!$isAdmin) {
                    switch ($paramName) {
                        case 'languages':
                            return $definition->languages;
                        case 'content.tree_root.location_id':
                            return $site->rootLocationId;
                        case 'design':
                            return $site->design;
                        case 'translation_siteaccesses':
                            return $site->siteaccessNames();
                    }
                }
            } elseif ($namespace === 'masilia_ibexa_blocks' && $paramName === 'layout_remote_id' && !$isAdmin && $site->layoutRemoteId !== null) {
                return $site->layoutRemoteId;
            }
        }

        throw new ParameterNotFoundException($paramName, $namespace ?? $this->defaultNamespace, [$scope]);
    }

    public function hasParameter(string $paramName, ?string $namespace = null, ?string $scope = null): bool
    {
        try {
            $this->getParameter($paramName, $namespace, $scope);

            return true;
        } catch (ParameterNotFoundException) {
            return false;
        }
    }

    public function setDefaultNamespace(string $defaultNamespace): void
    {
        $this->defaultNamespace = $defaultNamespace;
    }

    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }
}
