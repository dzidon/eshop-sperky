<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminPermissionsFormType;
use App\Form\HiddenTrueFormType;
use App\Form\PersonalInfoFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class UserController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs
            ->addRoute('home')
            ->addRoute('admin_permission_overview', [], MainController::ADMIN_TITLE)
            ->addRoute('admin_user_management');
    }

    /**
     * @Route("/uzivatele", name="admin_user_management")
     *
     * @IsGranted("admin_user_management")
     */
    public function users(FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => User::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $pagination = $this->getDoctrine()->getRepository(User::class)->getSearchPagination($form->get('searchPhrase')->getData(), $form->get('sortBy')->getData());
        }
        else
        {
            $pagination = $this->getDoctrine()->getRepository(User::class)->getSearchPagination();
        }

        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyli nalezeni žádní uživatelé.');
        }

        return $this->render('admin/users/admin_user_management.html.twig', [
            'searchForm' => $form->createView(),
            'userAdmin' => $this->getUser(),
            'userAdminCanEditThemself' => $this->getParameter('kernel.environment') === 'dev',
            'users' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
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

        //nenaslo to zadneho uzivatele
        if($userEdited === null)
        {
            throw new NotFoundHttpException('Uzivatel nenalezen.');
        }

        //admin nemuze editovat sam sebe mimo dev
        if($this->getParameter('kernel.environment') !== 'dev' && $user === $userEdited)
        {
            throw new AccessDeniedHttpException('Nemůžete editovat sami sebe.');
        }

        /*
         * Formulář - úprava osobních údajů
         */
        $formCredentialsView = null;
        if($this->isGranted('user_edit_credentials'))
        {
            $formCredentials = $this->createForm(PersonalInfoFormType::class, $userEdited);
            $formCredentials->add('submit', SubmitType::class, ['label' => 'Uložit', 'attr' => ['class' => 'btn-large blue left']]);
            $formCredentials->handleRequest($this->request);

            if ($formCredentials->isSubmitted() && $formCredentials->isValid())
            {
                $entityManager->persist($userEdited);
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
            $formPermissions->add('submit', SubmitType::class, ['label' => 'Uložit', 'attr' => ['class' => 'btn-large blue left']]);
            $formPermissions->handleRequest($this->request);

            if ($formPermissions->isSubmitted() && $formPermissions->isValid())
            {
                $entityManager->persist($userEdited);
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
                $entityManager->persist($userEdited);
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

        $this->breadcrumbs->addRoute('admin_user_management_specific', ['id' => $userEdited], '', '', ($userEdited->fullNameIsSet() ? $userEdited->getFullName() : ''));

        return $this->render('admin/users/admin_user_management_specific.html.twig', [
            'formCredentials' => $formCredentialsView,
            'formPermissions' => $formPermissionsView,
            'formMute' => $formMuteView,
            'userEdited' => $userEdited,
        ]);
    }
}