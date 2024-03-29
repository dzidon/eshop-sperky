<?php

namespace App\Controller\User;

use App\Entity\Address;
use App\Entity\Detached\Search\Atomic\Dropdown;
use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\Detached\Search\Composition\PhraseSortDropdown;
use App\Entity\Order;
use App\Entity\User;
use App\Form\FormType\User\ChangePasswordLoggedInFormType;
use App\Form\FormType\Search\Composition\PhraseSortDropdownFormType;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
use App\Form\FormType\User\HiddenTrueFormType;
use App\Form\FormType\User\PersonalInfoFormType;
use App\Form\FormType\User\AddressFormType;
use App\Service\Breadcrumbs;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/profil")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;
    private Breadcrumbs $breadcrumbs;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        $this->logger = $logger;
        $this->breadcrumbs = $breadcrumbs->addRoute('home')->addRoute('profile', [], 'Profil');
    }

    /**
     * @Route("", name="profile")
     */
    public function overview(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $form = $this->createForm(PersonalInfoFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            if ($user->getReview() !== null && !$user->fullNameIsSet())
            {
                $this->addFlash('warning', 'Vaše recenze se nebude zobrazovat, dokud nebudete mít nastavené křestní jméno a příjmení zároveň.');
            }

            $this->addFlash('success', 'Osobní údaje uloženy!');
            $this->logger->info(sprintf("User %s (ID: %s) has changed their personal information.", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile');
        }

        $this->breadcrumbs->setCurrentTitleByRoute('profile');

        return $this->render('profile/profile_overview.html.twig', [
            'personalDataForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/zmena-hesla", name="profile_change_password")
     */
    public function passwordChange(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user->getPassword() === null)
        {
            $this->addFlash('warning', 'Na tomto účtu nemáte nastavené heslo, takže si ho musíte změnit přes email.');
            return $this->redirectToRoute('forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordLoggedInFormType::class, $user);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Heslo změněno!');
            $this->logger->info(sprintf("User %s (ID: %s) has changed their password (via profile).", $user->getUserIdentifier(), $user->getId()));

            return $this->redirectToRoute('profile_change_password');
        }

        $this->breadcrumbs->addRoute('profile_change_password');

        return $this->render('profile/profile_change_password.html.twig', [
            'changeForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/adresy", name="profile_addresses")
     */
    public function addresses(FormFactoryInterface $formFactory, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $phrase = new Phrase('Hledejte podle aliasu.');
        $sort = new Sort(Address::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(Address::class)->getSearchPagination($user, $searchData);
        if ($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné adresy.');
        }

        $this->breadcrumbs->addRoute('profile_addresses');

        return $this->render('profile/profile_addresses.html.twig', [
            'searchForm' => $form->createView(),
            'addresses' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/adresa/{id}", name="profile_address", requirements={"id"="\d+"})
     */
    public function address(Request $request, $id = null): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $this->breadcrumbs->addRoute('profile_addresses');

        if ($id !== null) // zadal id do url, snazi se editovat existujici
        {
            $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy(['id' => $id]);
            if ($address === null) // nenaslo to zadnou adresu
            {
                throw new NotFoundHttpException('Adresa nenalezena.');
            }
            else if (!$this->isGranted('address_edit', $address)) // nalezena adresa neni uzivatele
            {
                throw new AccessDeniedHttpException('Tuto adresu nemůžete editovat.');
            }

            $this->breadcrumbs->addRoute('profile_address', ['id' => $address->getId()],'', 'edit');
        }
        else //nezadal id do url, vytvari novou adresu
        {
            $address = new Address($user);
            $this->breadcrumbs->addRoute('profile_address', ['id' => null],'', 'new');
        }

        $form = $this->createForm(AddressFormType::class, $address);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
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
        ]);
    }

    /**
     * @Route("/adresa/{id}/smazat", name="profile_address_delete", requirements={"id"="\d+"})
     */
    public function addressDelete(Request $request, $id): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $address = $this->getDoctrine()->getRepository(Address::class)->findOneBy(['id' => $id]);
        if ($address === null) //nenaslo to zadnou adresu
        {
            throw new NotFoundHttpException('Adresa nenalezena.');
        }
        else if (!$this->isGranted('address_delete', $address)) //nalezena adresa neni uzivatele
        {
            throw new AccessDeniedHttpException('Tuto adresu nemůžete smazat.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_address_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("User %s (ID: %s) has deleted their address %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $address->getAlias(), $address->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($address);
            $entityManager->flush();

            $this->addFlash('success', 'Adresa smazána!');
            return $this->redirectToRoute('profile_addresses');
        }

        $this->breadcrumbs
            ->addRoute('profile_addresses')
            ->addRoute('profile_address_delete', ['id' => $address->getId()]);

        return $this->render('profile/profile_address_delete.html.twig', [
            'addressDeleteForm' => $form->createView(),
            'addressInstance' => $address,
        ]);
    }

    /**
     * @Route("/objednavky", name="profile_orders")
     */
    public function orders(FormFactoryInterface $formFactory, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $lifecycleChapters = Order::LIFECYCLE_CHAPTERS;
        unset($lifecycleChapters[Order::LIFECYCLE_FRESH]);
        $dropdown = new Dropdown(array_flip($lifecycleChapters), 'Typ', '-- všechny --');
        $phraseSort = new PhraseSort(new Phrase('Hledejte podle ID.'), new Sort(Order::getSortData()));
        $searchData = new PhraseSortDropdown($phraseSort, $dropdown);

        $form = $formFactory->createNamed('', PhraseSortDropdownFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(Order::class)->getProfileSearchPagination($user->getEmail(), $user, $searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné objednávky.');
        }

        $this->breadcrumbs->addRoute('profile_orders');

        return $this->render('profile/profile_orders.html.twig', [
            'searchForm' => $form->createView(),
            'orders' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/objednavka/{id}", name="profile_order")
     */
    public function order(int $id): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        /** @var Order|null $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneForProfileOverview($id, $user->getEmail(), $user);
        if ($order === null)
        {
            throw new NotFoundHttpException('Objednávka nenalezena.');
        }

        $this->breadcrumbs->addRoute('profile_order', ['id' => $id]);

        return $this->render('profile/profile_order.html.twig', [
            'order' => $order,
        ]);
    }
}