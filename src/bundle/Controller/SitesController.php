<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The sites page of the admin: a shell that loads a React application, which reads and writes through the JSON API
 * (see SitesApiController). Seen with masilia_site/view; what it changes needs masilia_site/manage.
 */
final class SitesController extends Controller
{
    public function performAccessCheck(): void
    {
        parent::performAccessCheck();
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'view'));
    }

    #[Route('/sites', name: 'masilia.ibexa_sites.list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('@ibexadesign/sites/list.html.twig');
    }
}
