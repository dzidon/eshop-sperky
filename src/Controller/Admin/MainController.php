<?php

namespace App\Controller\Admin;

use App\Service\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class MainController extends AbstractController
{
    public const ADMIN_TITLE = 'Admin';
    private BreadcrumbsService $breadcrumbs;

    public function __construct(BreadcrumbsService $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
        $this->breadcrumbs->addRoute('home')->addRoute('admin_permission_overview', [], self::ADMIN_TITLE);
    }

    /**
     * @Route("", name="admin_permission_overview")
     *
     * @IsGranted("admin_permission_overview")
     */
    public function overview(): Response
    {
        return $this->render('admin/admin_permission_overview.html.twig', [
            'permissionsGrouped' => $this->getUser()->getPermissionsGrouped(),
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_permission_overview'),
        ]);
    }
}