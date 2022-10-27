<?php

namespace App\Controller\Admin;

use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    protected Breadcrumbs $breadcrumbs;

    public function __construct(Breadcrumbs $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs
            ->addRoute('home')
            ->addRoute('admin_dashboard', [], 'Admin')
        ;
    }
}