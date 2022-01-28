<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressFormType;
use App\Form\ChangePasswordLoggedInFormType;
use App\Form\PersonalInfoFormType;
use App\Form\AddressDeleteFormType;
use App\Form\RegistrationVerifyEmailSendAgainType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/profil")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;
    private BreadcrumbsService $breadcrumbs;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs;
        $this->request = $requestStack->getCurrentRequest();

        $this->breadcrumbs->addRoute('home')->addRoute('profile', [], 'Profil');
    }

    /**
     * @Route("", name="profile")
     */
    public function overview(): Response
    {
        $user = $this->getUser();
        $formView = null;

        if($user->isVerified())
        {
            $form = $this->createForm(PersonalInfoFormType::class, $user);
            $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                if ($user->getReview() !== null && !$user->fullNameIsSet())
                {
                    $this->addFlash('warning', 'Vaše recenze se nebude zobrazovat, dokud nebudete mít nastavené křestní jméno a příjmení zároveň.');
                }
                $this->addFlash('success', 'Osobní údaje uloženy!');
                $this->logger->info(sprintf("User %s (ID: %s) has changed their personal information.", $user->getUserIdentifier(), $user->getId()));

                return $this->redirectToRoute('profile');
            }

            $formView = $form->createView();
        }

        $this->isUserNotVerified($user, false);

        return $this->render('profile/profile_overview.html.twig', [
            'personalDataForm' => $formView,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile'),
        ]);
    }

    /**
     * @Route("/zmena-hesla", name="profile_change_password")
     */
    public function passwordChange(UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        $user = $this->getUser();
        if ($user->getPassword() === null)
        {
            $this->addFlash('failure', 'Na tomto účtu nemáte nastavené heslo, takže si ho musíte změnit přes email.');
            return $this->redirectToRoute('forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordLoggedInFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword(
                $userPasswordHasherInterface->hashPassword(
                    $user,
                    $user->getPlainPassword()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', 'Heslo změněno!');
            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via profile).", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile_change_password');
        }

        $this->isUserNotVerified($user, false);

        return $this->render('profile/profile_change_password.html.twig', [
            'changeForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_change_password'),
        ]);
    }

    /**
     * @Route("/overeni-emailu", name="profile_verify")
     */
    public function verify(EmailVerifier $emailVerifier, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if ($user->isVerified())
        {
            $this->addFlash('failure', 'Váš email už je ověřený.');
            return $this->redirectToRoute('profile');
        }

        $form = $this->createForm(RegistrationVerifyEmailSendAgainType::class);
        $form->add('submit', SubmitType::class, ['label' => 'Poslat znovu', 'attr' => ['class' => 'btn-large light-blue left']]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $emailVerifier->sendEmailConfirmation('verify_email', $user);
                $this->addFlash('success', 'E-mail odeslán!');
                $this->logger->info(sprintf("User %s (ID: %s) has requested a new email verification link.", $user->getUserIdentifier(), $user->getId()));
            }
            catch (\Exception | TransportExceptionInterface $exception)
            {
                $this->addFlash('failure', $translator->trans($exception->getMessage()));
            }
            return $this->redirectToRoute('profile_verify');
        }

        $this->isUserNotVerified($user, false);

        return $this->render('profile/profile_verify.html.twig', [
            'sendAgainForm' => $form->createView(),
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_verify'),
        ]);
    }

    /**
     * @Route("/adresy", name="profile_addresses")
     */
    public function addresses(PaginatorService $paginatorService): Response
    {
        $user = $this->getUser();
        if($this->isUserNotVerified($user, true))
        {
            return $this->redirectToRoute('profile');
        }

        $page = (int) $this->request->query->get(PaginatorService::QUERY_PARAMETER_PAGE_NAME, '1');
        $queryForPagination = $this->getDoctrine()->getRepository(Address::class)->getQueryForPagination($user);
        $addresses = $paginatorService
            ->initialize($queryForPagination, 5, $page)
            ->getCurrentPageObjects();

        if($paginatorService->isPageOutOfBounds($paginatorService->getCurrentPage()))
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné adresy.');
        }

        return $this->render('profile/profile_addresses.html.twig', [
            'addresses' => $addresses,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_addresses'),
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/adresa/{id}", name="profile_address", requirements={"id"="\d+"})
     */
    public function address($id = null): Response
    {
        $user = $this->getUser();
        if($this->isUserNotVerified($user, true))
        {
            return $this->redirectToRoute('profile');
        }

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy(['id' => $id]);
            if($address === null) //nenaslo to zadnou adresu
            {
                throw new NotFoundHttpException('Adresa nenalezena.');
            }
            else if(!$this->isGranted('address_edit', $address)) //nalezena adresa neni uzivatele
            {
                throw new AccessDeniedHttpException('Tuto adresu nemůžete editovat.');
            }

            $this->breadcrumbs->setPageTitleByRoute('profile_address', 'edit');
        }
        else //nezadal id do url, vytvari novou adresu
        {
            $address = new Address($user);
            $this->breadcrumbs->setPageTitleByRoute('profile_address', 'new');
        }

        $form = $this->createForm(AddressFormType::class, $address);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            if ($address->getId() === null)
            {
                $entityManager->persist($address);
            }
            else
            {
                $address->setUpdated(new \DateTime('now'));
            }
            $entityManager->flush();

            $this->addFlash('success', 'Adresa uložena!');
            $this->logger->info(sprintf("User %s (ID: %s) has saved their address %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $address->getAlias(), $address->getId()));

            return $this->redirectToRoute('profile_addresses');
        }

        return $this->render('profile/profile_address.html.twig', [
            'addressForm' => $form->createView(),
            'addressInstance' => $address,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }

    /**
     * @Route("/adresa/{id}/smazat", name="profile_address_delete", requirements={"id"="\d+"})
     */
    public function addressDelete($id): Response
    {
        $user = $this->getUser();
        if($this->isUserNotVerified($user, true))
        {
            return $this->redirectToRoute('profile');
        }

        $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy(['id' => $id]);
        if($address === null) //nenaslo to zadnou adresu
        {
            throw new NotFoundHttpException('Adresa nenalezena.');
        }
        else if(!$this->isGranted('address_delete', $address)) //nalezena adresa neni uzivatele
        {
            throw new AccessDeniedHttpException('Tuto adresu nemůžete smazat.');
        }

        $form = $this->createForm(AddressDeleteFormType::class);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => [
                'class' => 'btn-large red left',
            ],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("User %s (ID: %s) has deleted their address %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $address->getAlias(), $address->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($address);
            $entityManager->flush();

            $this->addFlash('success', 'Adresa smazána!');

            return $this->redirectToRoute('profile_addresses');
        }

        return $this->render('profile/profile_address_delete.html.twig', [
            'addressDeleteForm' => $form->createView(),
            'addressInstance' => $address,
            'breadcrumbs' => $this->breadcrumbs->setPageTitleByRoute('profile_address_delete'),
        ]);
    }

    private function isUserNotVerified($user, bool $restrictAccess): bool
    {
        if (!$user->isVerified())
        {
            if ($restrictAccess)
            {
                $this->addFlash('failure', 'Nemáte ověřený účet.');
            }
            else
            {
                $messageText = 'Zatím jste si neověřili email, takže nemáte přístup ke všem funkcionalitám webu. Pokud vám nepřišel ověřovací email nebo pokud vypršel váš odkaz na ověření, můžete si nechat poslat nový (Profil > Ověření emailu).';
                $flashBag = $this->request->getSession()->getFlashBag();

                if(!in_array($messageText, $flashBag->peek('warning')))
                {
                    $flashBag->add('warning', $messageText);
                }
            }
            return true;
        }
        return false;
    }
}