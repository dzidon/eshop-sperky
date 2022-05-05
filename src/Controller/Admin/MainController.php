<?php

namespace App\Controller\Admin;

use App\Entity\Order;
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
    public const ADMIN_ROUTE = 'admin_dashboard';

    private BreadcrumbsService $breadcrumbs;

    public function __construct(BreadcrumbsService $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
        $this->breadcrumbs->addRoute('home')->addRoute(self::ADMIN_ROUTE, [], self::ADMIN_TITLE);
    }

    /**
     * @Route("", name="admin_dashboard")
     *
     * @IsGranted("admin_dashboard")
     */
    public function overview(): Response
    {
        $orders = null;
        if ($this->isGranted('order_edit'))
        {
            $orders = $this->getDoctrine()->getManager()->getRepository(Order::class)->findAllForAdminDashboard();
        }

        $this->breadcrumbs->setPageTitleByRoute('admin_dashboard');

        return $this->render('admin/admin_dashboard.html.twig', [
            'permissionsGrouped' => $this->getUser()->getPermissionsGrouped(),
            'orders' => $orders,
        ]);
    }
}