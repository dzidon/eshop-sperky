<?php

namespace App\Service;

use App\Entity\ProductSection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Tato třída je Twig global a řeší zobrazování obsahu v navigaci (horní, profilová a v navigaci)
 *
 * @package App\Service
 */
class NavbarService
{
    private $currentRoute;
    private array $sections = [];
    private bool $sectionsLoaded = false;

    private Security $security;
    private UrlGeneratorInterface $router;
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack, ParameterBagInterface $parameterBag, UrlGeneratorInterface $router, Security $security)
    {
        $this->router = $router;
        $this->security = $security;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->currentRoute = $requestStack->getCurrentRequest()->attributes->get('_route');
    }

    /**
     * Vrátí všechny viditelné produktové sekce. První zavolání selectuje z databáze a ukládá výsledky
     * do pole $sections. Další zavolání vrátí pole $sections.
     *
     * @return array
     */
    public function getVisibleSections(): array
    {
        if (!$this->sectionsLoaded)
        {
            $this->sections = $this->entityManager->getRepository(ProductSection::class)->findAllVisible();
            $this->sectionsLoaded = true;
        }

        return $this->sections;
    }

    /**
     * Vrátí array obsahující data pro vykreslení navigace na profilu
     *
     * @return array[]
     */
    public function getProfileNavigationData(): array
    {
        return
        [
            [
                'path' => $this->router->generate('profile'),
                'isActive' => $this->currentRoute === 'profile',
                'title' => $this->parameterBag->get('app_page_title.profile')
            ],
            [
                'path' => $this->router->generate('profile_addresses'),
                'isActive' => $this->currentRoute === 'profile_addresses' || $this->currentRoute === 'profile_address' || $this->currentRoute === 'profile_address_delete',
                'title' => $this->parameterBag->get('app_page_title.profile_addresses')
            ],
            [
                'path' => $this->router->generate('profile_change_password'),
                'isActive' => $this->currentRoute === 'profile_change_password',
                'title' => $this->parameterBag->get('app_page_title.profile_change_password')
            ],
        ];
    }

    /**
     * Vrátí array obsahující data pro vykreslení navigace v administraci
     *
     * @return array[]
     */
    public function getAdminNavigationData(): array
    {
        return
        [
            [
                'granted' => $this->security->isGranted('admin_permission_overview'),
                'path' => $this->router->generate('admin_permission_overview'),
                'isActive' => $this->currentRoute === 'admin_permission_overview',
                'title' => $this->parameterBag->get('app_page_title.admin_permission_overview')
            ],
            [
                'granted' => $this->security->isGranted('admin_user_management'),
                'path' => $this->router->generate('admin_user_management'),
                'isActive' => $this->currentRoute === 'admin_user_management' || $this->currentRoute === 'admin_user_management_specific',
                'title' => $this->parameterBag->get('app_page_title.admin_user_management')
            ],
            [
                'granted' => $this->security->isGranted('admin_products'),
                'path' => $this->router->generate('admin_products'),
                'isActive' => $this->currentRoute === 'admin_products' || $this->currentRoute === 'admin_product_edit' || $this->currentRoute === 'admin_product_delete',
                'title' => $this->parameterBag->get('app_page_title.admin_products')
            ],
            [
                'granted' => $this->security->isGranted('admin_product_sections'),
                'path' => $this->router->generate('admin_product_sections'),
                'isActive' => $this->currentRoute === 'admin_product_sections' || $this->currentRoute === 'admin_product_section_edit' || $this->currentRoute === 'admin_product_section_delete',
                'title' => $this->parameterBag->get('app_page_title.admin_product_sections')
            ],
            [
                'granted' => $this->security->isGranted('admin_product_categories'),
                'path' => $this->router->generate('admin_product_categories'),
                'isActive' => $this->currentRoute === 'admin_product_categories' || $this->currentRoute === 'admin_product_category_edit' || $this->currentRoute === 'admin_product_category_delete',
                'title' => $this->parameterBag->get('app_page_title.admin_product_categories')
            ],
            [
                'granted' => $this->security->isGranted('admin_product_options'),
                'path' => $this->router->generate('admin_product_options'),
                'isActive' => $this->currentRoute === 'admin_product_options' || $this->currentRoute === 'admin_product_option_edit' || $this->currentRoute === 'admin_product_option_delete',
                'title' => $this->parameterBag->get('app_page_title.admin_product_options')
            ],
            [
                'granted' => $this->security->isGranted('admin_product_info'),
                'path' => $this->router->generate('admin_product_info'),
                'isActive' => $this->currentRoute === 'admin_product_info' || $this->currentRoute === 'admin_product_info_edit' || $this->currentRoute === 'admin_product_info_delete',
                'title' => $this->parameterBag->get('app_page_title.admin_product_info')
            ],
        ];
    }
}