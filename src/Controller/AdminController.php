<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductCategoryGroup;
use App\Entity\ProductInformationGroup;
use App\Entity\ProductOption;
use App\Entity\ProductSection;
use App\Entity\User;
use App\Form\AdminPermissionsFormType;
use App\Form\HiddenTrueFormType;
use App\Form\PersonalInfoFormType;
use App\Form\ProductCategoryGroupFormType;
use App\Form\ProductFormType;
use App\Form\ProductInformationGroupFormType;
use App\Form\ProductOptionFormType;
use App\Form\ProductOptionParametersFormType;
use App\Form\ProductSectionFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use App\Service\EntityCollectionService;
use App\Service\EntityUpdatingService;
use App\Service\PaginatorService;
use Doctrine\Common\Collections\ArrayCollection;
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

        if($paginatorService->isCurrentPageOutOfBounds())
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

        if($paginatorService->isCurrentPageOutOfBounds())
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
    public function productSection(EntityUpdatingService $entityUpdater, $id = null): Response
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
            $entityUpdater->setMainInstance($section)
                          ->mainInstancePersistOrSetUpdated();
            $this->getDoctrine()->getManager()->flush();

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

        if($paginatorService->isCurrentPageOutOfBounds())
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
    public function productCategoryGroup(EntityUpdatingService $entityUpdater, $id = null): Response
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
            $entityUpdater->setMainInstance($categoryGroup)
                          ->setCollectionGetters(['getCategories'])
                          ->mainInstancePersistOrSetUpdated()
                          ->collectionItemsSetUpdated();
            $this->getDoctrine()->getManager()->flush();

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

    /**
     * @Route("/produktove-volby", name="admin_product_options")
     *
     * @IsGranted("admin_product_options")
     */
    public function productOptions(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductOption::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOption::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOption::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $options = $paginatorService
            ->initialize($queryForPagination, 3, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produktové volby.');
        }

        return $this->render('admin/admin_product_options.html.twig', [
            'searchForm' => $form->createView(),
            'options' => $options,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_options'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}", name="admin_product_option_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOption(EntityUpdatingService $entityUpdater, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
            if($option === null) //nenaslo to zadnou volbu
            {
                throw new NotFoundHttpException('Produktová volba nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_option_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou volbu
        {
            $option = new ProductOption();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_option_edit', 'new');
        }

        $oldOption = clone $option;
        $form = $this->createForm(ProductOptionFormType::class, $option);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit a pokračovat']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if ($option->getType() !== $oldOption->getType()) //došlo ke změně typu při editaci, takže smažeme parametry
            {
                $option->getParameters()->clear();
            }
            $entityUpdater->setMainInstance($option)
                ->mainInstancePersistOrSetUpdated();

            $option->setConfiguredIfValid();
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Produktová volba uložena! Nyní ji nakonfigurujte.');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            return $this->redirectToRoute('admin_product_option_configure', ['id' => $option->getId()]);
        }

        return $this->render('admin/admin_product_option_edit.html.twig', [
            'productOptionForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}/smazat", name="admin_product_option_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_delete")
     */
    public function productOptionDelete($id): Response
    {
        $user = $this->getUser();

        $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
        if($option === null) //nenaslo to zadnou volbu
        {
            throw new NotFoundHttpException('Produktová volba nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_option_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($option);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová volba smazána!');
            return $this->redirectToRoute('admin_product_options');
        }

        return $this->render('admin/admin_product_option_delete.html.twig', [
            'productOptionDeleteForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_option_delete'),
        ]);
    }

    /**
     * @Route("/produktova-volba/{id}/konfigurovat", name="admin_product_option_configure", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOptionConfigure(EntityUpdatingService $entityUpdater, $id): Response
    {
        $user = $this->getUser();

        /** @var ProductOption $option */
        $option = $this->getDoctrine()->getRepository(ProductOption::class)->findOneBy(['id' => $id]);
        if($option === null) //nenaslo to zadnou volbu
        {
            throw new NotFoundHttpException('Produktová volba nenalezena.');
        }

        $form = $this->createForm(ProductOptionParametersFormType::class, $option);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = [];
            if ($option->getType() === ProductOption::TYPE_NUMBER)
            {
                $data = [
                    'min' => $form->get('min')->getData(),
                    'max' => $form->get('max')->getData(),
                    'default' => $form->get('default')->getData(),
                    'step' => $form->get('step')->getData(),
                ];
            }

            $entityUpdater->setMainInstance($option)
                ->setCollectionGetters(['getParameters'])
                ->mainInstancePersistOrSetUpdated()
                ->collectionItemsSetUpdated();

            $option->configure($data)
                   ->setConfiguredIfValid();
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Produktová volba uložena a nakonfigurována!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has configured a product option %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $option->getName(), $option->getId()));

            return $this->redirectToRoute('admin_product_options');
        }

        return $this->render('admin/admin_product_option_configure.html.twig', [
            'productOptionConfigureForm' => $form->createView(),
            'productOptionInstance' => $option,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_option_configure'),
        ]);
    }

    /**
     * @Route("/skupiny-produktovych-informaci", name="admin_product_info")
     *
     * @IsGranted("admin_product_info")
     */
    public function productInfoGroups(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductInformationGroup::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->getQueryForSearchAndPagination($form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->getQueryForSearchAndPagination();
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $infoGroups = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových informací.');
        }

        return $this->render('admin/admin_product_info.html.twig', [
            'searchForm' => $form->createView(),
            'infoGroups' => $infoGroups,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_info'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-informaci/{id}", name="admin_product_info_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_info_edit")
     */
    public function productInfoGroup(EntityUpdatingService $entityUpdater, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $infoGroup = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->findOneBy(['id' => $id]);
            if($infoGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových informaci nenalezena.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_info_edit', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $infoGroup = new ProductInformationGroup();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_info_edit', 'new');
        }

        $form = $this->createForm(ProductInformationGroupFormType::class, $infoGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityUpdater->setMainInstance($infoGroup)
                ->mainInstancePersistOrSetUpdated();
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Skupina produktových informací uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product information group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $infoGroup->getName(), $infoGroup->getId()));

            return $this->redirectToRoute('admin_product_info');
        }

        return $this->render('admin/admin_product_info_edit.html.twig', [
            'productInfoGroupForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/skupina-produktovych-informaci/{id}/smazat", name="admin_product_info_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_info_delete")
     */
    public function productInfoGroupDelete($id): Response
    {
        $user = $this->getUser();

        $infoGroup = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->findOneBy(['id' => $id]);
        if($infoGroup === null) //nenaslo to zadnou skupinu
        {
            throw new NotFoundHttpException('Skupina produktových informací nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_info_group_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product information group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $infoGroup->getName(), $infoGroup->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($infoGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových informací smazána!');
            return $this->redirectToRoute('admin_product_info');
        }

        return $this->render('admin/admin_product_info_delete.html.twig', [
            'productInfoGroupDeleteForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_info_delete'),
        ]);
    }

    /**
     * @Route("/produkty", name="admin_products")
     *
     * @IsGranted("admin_products")
     */
    public function products(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => Product::getSortData()['admin']]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = true, $form->get('vyraz')->getData(), $form->get('razeni')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(Product::class)->getQueryForSearchAndPagination($inAdmin = true);
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $products = $paginatorService
            ->initialize($queryForPagination, 1, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné produkty.');
        }

        return $this->render('admin/admin_product_management.html.twig', [
            'searchForm' => $form->createView(),
            'products' => $products,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_products'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/produkt/{id}", name="admin_product_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_edit")
     */
    public function product(EntityUpdatingService $entityUpdater, EntityCollectionService $entityCollectionManager, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $product = $this->getDoctrine()->getRepository(Product::class)->findOneByIdAndFetchEverything($id);
            if($product === null) //nenaslo to zadny produkt
            {
                throw new NotFoundHttpException('Produkt nenalezen.');
            }
            $this->breadcrumbs->setPageTitleByRoute('admin_product_edit', 'edit');
        }
        else //nezadal id do url, vytvari novy produkt
        {
            $product = new Product();
            $this->breadcrumbs->setPageTitleByRoute('admin_product_edit', 'new');
        }

        $entityCollectionManager->loadCollections([
            ['type' => 'old', 'name' => 'info', 'collection' => $product->getInfo()]
        ]);
        
        $form = $this->createForm(ProductFormType::class, $product);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityUpdater
                ->setMainInstance($product)
                ->setCollectionGetters(['getInfo'])
                ->mainInstancePersistOrSetUpdated()
                ->collectionItemsSetUpdated();

            $entityCollectionManager
                ->loadCollections([
                    ['type' => 'new', 'name' => 'info', 'collection' => $product->getInfo()]
                ])
                ->removeElementsMissingFromNewCollection();

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Produkt uložen!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $product->getName(), $product->getId()));

            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/admin_product_management_specific.html.twig', [
            'productForm' => $form->createView(),
            'productInstance' => $product,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/produkt/{id}/smazat", name="admin_product_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_delete")
     */
    public function productDelete($id): Response
    {
        $user = $this->getUser();

        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['id' => $id]);
        if($product === null) //nenaslo to zadny produkt
        {
            throw new NotFoundHttpException('Produkt nenalezen.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $product->getName(), $product->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produkt smazán!');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/admin_product_management_delete.html.twig', [
            'productDeleteForm' => $form->createView(),
            'productInstance' => $product,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('admin_product_delete'),
        ]);
    }
}