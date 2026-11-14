<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Masilia\Bundle\IbexaSites\Form\SiteData;
use Masilia\Bundle\IbexaSites\Form\SiteType;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/** The sites created at run time, in the admin: seen with masilia_site/view, changed with masilia_site/manage. */
final class SitesController extends Controller
{
    public function __construct(
        private readonly SiteRegistry $registry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function performAccessCheck(): void
    {
        parent::performAccessCheck();
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'view'));
    }

    #[Route('/sites', name: 'masilia.ibexa_sites.list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('@ibexadesign/sites/list.html.twig', [
            'sites' => $this->registry->list(),
            'canManage' => $this->isGranted(new Attribute('masilia_site', 'manage')),
        ]);
    }

    #[Route('/sites/new', name: 'masilia.ibexa_sites.new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $form = $this->createForm(SiteType::class, new SiteData());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $site = $this->registry->create($form->getData()->toSite());
                $this->addFlash('success', $this->translator->trans('flash.created', ['%name%' => $site->name], 'masilia_ibexa_sites'));

                return $this->redirectToRoute('masilia.ibexa_sites.list');
            } catch (SiteInvalid $invalid) {
                $this->showProblems($form, $invalid);
            }
        }

        return $this->render('@ibexadesign/sites/form.html.twig', ['form' => $form->createView(), 'site' => null]);
    }

    #[Route('/sites/{identifier}/edit', name: 'masilia.ibexa_sites.edit', requirements: ['identifier' => '[a-z][a-z0-9_]+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, string $identifier): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $site = $this->registry->find($identifier) ?? throw $this->createNotFoundException();
        $form = $this->createForm(SiteType::class, SiteData::fromSite($site), ['editing' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $changed = $this->registry->update($form->getData()->toSite($site->status));
                $this->addFlash('success', $this->translator->trans('flash.updated', ['%name%' => $changed->name], 'masilia_ibexa_sites'));

                return $this->redirectToRoute('masilia.ibexa_sites.list');
            } catch (SiteInvalid $invalid) {
                $this->showProblems($form, $invalid);
            }
        }

        return $this->render('@ibexadesign/sites/form.html.twig', ['form' => $form->createView(), 'site' => $site]);
    }

    #[Route('/sites/{identifier}/status/{status}', name: 'masilia.ibexa_sites.status', requirements: ['identifier' => '[a-z][a-z0-9_]+', 'status' => 'active|disabled'], methods: ['POST'])]
    public function status(Request $request, string $identifier, string $status): RedirectResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $this->checkToken($request, 'masilia_site_' . $identifier);
        try {
            $site = $this->registry->setStatus($identifier, SiteStatus::from($status));
            $this->addFlash('success', $this->translator->trans('flash.status.' . $status, ['%name%' => $site->name], 'masilia_ibexa_sites'));
        } catch (SiteInvalid $invalid) {
            $this->addFlash('warning', implode(' ', $invalid->problems));
        } catch (SiteNotFound) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('masilia.ibexa_sites.list');
    }

    #[Route('/sites/{identifier}/delete', name: 'masilia.ibexa_sites.delete', requirements: ['identifier' => '[a-z][a-z0-9_]+'], methods: ['POST'])]
    public function delete(Request $request, string $identifier): RedirectResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $this->checkToken($request, 'masilia_site_' . $identifier);
        try {
            $this->registry->delete($identifier);
            $this->addFlash('success', $this->translator->trans('flash.deleted', ['%identifier%' => $identifier], 'masilia_ibexa_sites'));
        } catch (SiteNotFound) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('masilia.ibexa_sites.list');
    }

    private function checkToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function showProblems(\Symfony\Component\Form\FormInterface $form, SiteInvalid $invalid): void
    {
        foreach ($invalid->problems as $problem) {
            $form->addError(new \Symfony\Component\Form\FormError($problem));
        }
    }
}
