<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressFormType;
use App\Form\ChangePasswordLoggedInFormType;
use App\Form\PersonalInfoFormType;
use App\Form\HiddenTrueFormType;
use App\Security\EmailVerifier;
use App\Service\BreadcrumbsService;
use App\Service\PaginatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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

        $this->breadcrumbs->addRoute('home')->addRoute('profile');
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
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Osobní údaje uloženy!');
                $this->logger->info(sprintf("User %s (ID: %s) has changed their personal information.", $user->getUserIdentifier(), $user->getId()));

                return $this->redirectToRoute('profile');
            }

            $formView = $form->createView();
        }

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
        if ($this->getUser()->getPassword() === null)
        {
            $this->addFlash('failure', 'Na tomto účtu nemáte nastavené heslo, takže si ho musíte změnit přes email.');
            return $this->redirectToRoute('forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordLoggedInFormType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user = $this->getUser();
            $user->setPassword(
                $userPasswordHasherInterface->hashPassword(
                    $user,
                    $form->get('newPlainPassword')->get('repeated')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Heslo změněno!');
            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via profile).", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile_change_password');
        }

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
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(HiddenTrueFormType::class);
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
        if(!$user->isVerified())
        {
            $this->addFlash('failure', 'Nemáte ověřený účet.');
            return $this->redirectToRoute('profile');
        }

        $page = (int) $this->request->query->get('page', '1');
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function address($id = null): Response
    {
        $user = $this->getUser();
        if(!$user->isVerified())
        {
            $this->addFlash('failure', 'Nemáte ověřený účet.');
            return $this->redirectToRoute('profile');
        }

        $address = new Address();
        $this->breadcrumbs->setPageTitle('Nová adresa');

        if($id !== null) //zadal id do url
        {
            $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy([
                'id' => $id,
            ]);

            if($address === null || $address->getUser() !== $user) //nenaslo to zadnou adresu, nebo to nejakou adresu naslo, ale ta neni uzivatele
            {
                throw new NotFoundHttpException('Adresa nenalezena.');
            }

            $this->breadcrumbs->setPageTitle('Upravit adresu');
        }

        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $address->setUser($user);
            $address->setUpdated(new \DateTime('now'));
            if($address->getId() === null)
            {
                $address->setCreated(new \DateTime('now'));
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($address);
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function addressDelete($id): Response
    {
        $user = $this->getUser();
        if (!$user->isVerified()) {
            $this->addFlash('failure', 'Nemáte ověřený účet.');
            return $this->redirectToRoute('profile');
        }

        $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy([
            'id' => $id,
        ]);

        if($address === null || $address->getUser() !== $user) //nenaslo to zadnou adresu, nebo to nejakou adresu naslo, ale ta neni uzivatele
        {
            throw new NotFoundHttpException('Adresa nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class);
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
}