<?php

namespace App\Controller;

use App\Entity\ProductCategoryGroup;
use App\Entity\ProductSection;
use App\Entity\User;
use App\Form\AdminPermissionsFormType;
use App\Form\HiddenTrueFormType;
use App\Form\PersonalInfoFormType;
use App\Form\ProductCategoryGroupFormType;
use App\Form\ProductSectionFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class AdminController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs->addRoute('home')->addRoute('admin', [], 'Admin');
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

    /**
     * @Route("/uzivatele", name="admin_user_management")
     *
     * @IsGranted("admin_user_management")
     */
    public function users(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => User::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(User::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(User::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $users = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isPageOutOfBounds($paginatorService->getCurrentPage()))
        {
            throw new NotFoundHttpException('Na této stránce nebyli nalezeni žádní uživatelé.');
        }

        return $this->render('admin/admin_user_management.html.twig', [
            'searchForm' => $form->createView(),
            'users' => $users,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_user_management'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/uzivatel/{id}", name="admin_user_management_specific", requirements={"id"="\d+"})
     *
     * @IsGranted("admin_user_management")
     */
    public function editUser($id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $userEdited = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $id]);
        if($userEdited === null) //nenaslo to zadneho uzivatele
        {
            throw new NotFoundHttpException('Uzivatel nenalezen.');
        }

        /*
         * Formulář - úprava osobních údajů
         */
        $formCredentialsView = null;
        if($this->isGranted('user_edit_credentials'))
        {
            $formCredentials = $this->createForm(PersonalInfoFormType::class, $userEdited);
            $formCredentials->add('submit', SubmitType::class, ['label' => 'Uložit', 'attr' => ['class' => 'btn-large light-blue left']]);
            $formCredentials->handleRequest($this->request);

            if ($formCredentials->isSubmitted() && $formCredentials->isValid())
            {
                $entityManager->flush();

                if ($user === $userEdited && $userEdited->getReview() !== null && !$userEdited->fullNameIsSet())
                {
                    $this->addFlash('warning', 'Vaše recenze se nebude zobrazovat, dokud nebudete mít nastavené křestní jméno a příjmení zároveň.');
                }
                $this->addFlash('success', 'Osobní údaje uživatele uloženy!');
                $this->logger->info(sprintf("Admin %s (ID: %s) has changed personal information of user %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $userEdited->getUserIdentifier(), $userEdited->getId()));

                return $this->redirectToRoute('admin_user_management_specific', ['id' => $userEdited->getId()]);
            }

            $formCredentialsView = $formCredentials->createView();
        }

        /*
         * Formulář - oprávnění
         */
        $formPermissionsView = null;
        if($this->isGranted('user_set_permissions'))
        {
            $formPermissions = $this->createForm(AdminPermissionsFormType::class, $userEdited);
            $formPermissions->add('submit', SubmitType::class, ['label' => 'Uložit', 'attr' => ['class' => 'btn-large light-blue left']]);
            $formPermissions->handleRequest($this->request);

            if ($formPermissions->isSubmitted() && $formPermissions->isValid())
            {
                $entityManager->flush();

                $this->addFlash('success', 'Oprávnění uživatele uloženy.');
                $this->logger->info(sprintf("Admin %s (ID: %s) has changed permissions of user %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $userEdited->getUserIdentifier(), $userEdited->getId()));

                return $this->redirectToRoute('admin_user_management_specific', ['id' => $userEdited->getId()]);
            }

            $formPermissionsView = $formPermissions->createView();
        }

        /*
         * Formulář - umlčení
         */
        $formMuteView = null;
        if($this->isGranted('user_block_reviews'))
        {
            $formMute = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_admin_mute_user']);
            if($userEdited->isMuted())
            {
                $formMute->add('submit', SubmitType::class, ['label' => 'Odmlčet', 'attr' => ['class' => 'btn-large green left']]);
            }
            else
            {
                $formMute->add('submit', SubmitType::class, ['label' => 'Umlčet', 'attr' => ['class' => 'btn-large red left']]);
            }
            $formMute->handleRequest($this->request);

            if($formMute->isSubmitted() && $formMute->isValid())
            {
                $userEdited->setIsMuted( !$userEdited->isMuted() );
                $entityManager->flush();

                if($userEdited->isMuted())
                {
                    $this->addFlash('success', 'Uživatel umlčen.');
                    $this->logger->info(sprintf("Admin %s (ID: %s) has muted user %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $userEdited->getUserIdentifier(), $userEdited->getId()));
                }
                else
                {
                    $this->addFlash('success', 'Uživatel odmlčen.');
                    $this->logger->info(sprintf("Admin %s (ID: %s) has unmuted user %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $userEdited->getUserIdentifier(), $userEdited->getId()));
                }
                return $this->redirectToRoute('admin_user_management_specific', ['id' => $userEdited->getId()]);
            }

            $formMuteView = $formMute->createView();
        }

        return $this->render('admin/admin_user_management_specific.html.twig', [
            'formCredentials' => $formCredentialsView,
            'formPermissions' => $formPermissionsView,
            'formMute' => $formMuteView,
            'userEdited' => $userEdited,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_user_management_specific')->appendToPageTitle( ($userEdited->fullNameIsSet() ? ' ' . $userEdited->getFullName() : '') ),
        ]);
    }

    /**
     * @Route("/produktove-sekce", name="admin_product_sections")
     *
     * @IsGranted("admin_product_sections")
     */
    public function productSections(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductSection::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductSection::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductSection::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $sections = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isPageOutOfBounds($paginatorService->getCurrentPage()))
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné sekce.');
        }

        return $this->render('admin/admin_product_sections.html.twig', [
            'searchForm' => $form->createView(),
            'sections' => $sections,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_sections'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}", name="admin_product_section_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_edit")
     */
    public function productSection($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
            if($section === null) //nenaslo to zadnou sekci
            {
                throw new NotFoundHttpException('Produktová sekce nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_section_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou sekci
        {
            $section = new ProductSection();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_section_edit', 'new');
        }

        $form = $this->createForm(ProductSectionFormType::class, $section);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            if ($section->getId() === null)
            {
                $entityManager->persist($section);
            }
            else
            {
                $section->setUpdated(new \DateTime('now'));
            }
            $entityManager->flush();

            $this->addFlash('success', 'Produktová sekce uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            return $this->redirectToRoute('admin_product_sections');
        }

        return $this->render('admin/admin_product_section_edit.html.twig', [
            'productSectionForm' => $form->createView(),
            'productSectionInstance' => $section,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}/smazat", name="admin_product_section_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_delete")
     */
    public function productSectionDelete($id): Response
    {
        $user = $this->getUser();

        $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
        if($section === null) //nenaslo to zadnou sekci
        {
            throw new NotFoundHttpException('Produktová sekce nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_section_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($section);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová sekce smazána!');

            return $this->redirectToRoute('admin_product_sections');
        }

        return $this->render('admin/admin_product_section_delete.html.twig', [
            'productSectionDeleteForm' => $form->createView(),
            'productSectionInstance' => $section,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_section_delete'),
        ]);
    }

    /**
     * @Route("/skupiny-produktovych-kategorii", name="admin_product_categories")
     *
     * @IsGranted("admin_product_categories")
     */
    public function productCategoryGroups(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductCategoryGroup::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $categoryGroups = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isPageOutOfBounds($paginatorService->getCurrentPage()))
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových kategorií.');
        }

        return $this->render('admin/admin_product_categories.html.twig', [
            'searchForm' => $form->createView(),
            'categoryGroups' => $categoryGroups,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_categories'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-kategorii/{id}", name="admin_product_category_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_category_edit")
     */
    public function productCategoryGroup($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $categoryGroup = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->findOneBy(['id' => $id]);
            if($categoryGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových sekcí nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_category_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $categoryGroup = new ProductCategoryGroup();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_category_edit', 'new');
        }

        $form = $this->createForm(ProductCategoryGroupFormType::class, $categoryGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            if ($categoryGroup->getId() === null)
            {
                $entityManager->persist($categoryGroup);
            }
            else
            {
                $categoryGroup->setUpdated(new \DateTime('now'));
            }
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových kategorií uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product category group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $categoryGroup->getName(), $categoryGroup->getId()));

            return $this->redirectToRoute('admin_product_categories');
        }

        return $this->render('admin/admin_product_category_edit.html.twig', [
            'productCategoryGroupForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/skupina-produktovych-kategorii/{id}/smazat", name="admin_product_category_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_category_delete")
     */
    public function productCategoryGroupDelete($id): Response
    {
        $user = $this->getUser();

        $categoryGroup = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->findOneBy(['id' => $id]);
        if($categoryGroup === null) //nenaslo to zadnou sekci
        {
            throw new NotFoundHttpException('Skupina produktových kategorií nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_category_group_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product category group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $categoryGroup->getName(), $categoryGroup->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($categoryGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových kategorií smazána!');

            return $this->redirectToRoute('admin_product_categories');
        }

        return $this->render('admin/admin_product_category_delete.html.twig', [
            'productCategoryGroupDeleteForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_category_delete'),
        ]);
    }
}
