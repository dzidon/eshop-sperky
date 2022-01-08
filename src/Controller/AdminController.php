<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminMuteUserFormType;
use App\Form\AdminPermissionsFormType;
use App\Form\PersonalInfoFormType;
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
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($userEdited);
                $entityManager->flush();

                if ($user === $userEdited && $userEdited->getReview() !== null && !$userEdited->fullNameIsSet())
                {
                    $this->addFlash('warning', 'Vaše recenze se nebude zobrazovat, dokud nebudete mít nastavené křestní jméno a příjmení zároveň.');
                }
                $this->addFlash('success', 'Osobní údaje uživatele uloženy!');
                $this->logger->info(sprintf("User %s (ID: %s) has changed personal information of user %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $userEdited->getUserIdentifier(), $userEdited->getId()));

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
            $formMute = $this->createForm(AdminMuteUserFormType::class);
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
}
