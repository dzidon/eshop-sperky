<?php

namespace App\Controller\Admin;

use App\Service\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    protected BreadcrumbsService $breadcrumbs;

    public function __construct(BreadcrumbsService $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs
            ->addRoute('home')
            ->addRoute('admin_dashboard', [], 'Admin')
        ;
    }
}