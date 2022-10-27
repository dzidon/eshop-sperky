<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\PaymentMethod;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
use App\Form\FormType\Admin\PaymentMethodFormType;
use App\Service\Breadcrumbs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class PaymentMethodController extends AbstractAdminController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        parent::__construct($breadcrumbs);

        $this->breadcrumbs->addRoute('admin_payment_methods');
        $this->logger = $logger;
    }

    /**
     * @Route("/platebni-metody", name="admin_payment_methods")
     *
     * @IsGranted("admin_payment_methods")
     */
    public function paymentMethods(FormFactoryInterface $formFactory, Request $request): Response
    {
        $phrase = new Phrase('Hledejte podle názvu.');
        $sort = new Sort(PaymentMethod::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(PaymentMethod::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné platební metody.');
        }

        return $this->render('admin/payment_methods/admin_payment_methods.html.twig', [
            'searchForm' => $form->createView(),
            'paymentMethods' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/platebni-metoda/{id}", name="admin_payment_method_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("payment_method_edit")
     */
    public function paymentMethod(Request $request, $id): Response
    {
        $user = $this->getUser();

        $paymentMethod = $this->getDoctrine()->getRepository(PaymentMethod::class)->findOneBy(['id' => $id]);
        if($paymentMethod === null)
        {
            throw new NotFoundHttpException('Platební metoda nenalezena.');
        }

        $this->breadcrumbs->addRoute('admin_payment_method_edit', ['id' => $paymentMethod->getId()]);

        $form = $this->createForm(PaymentMethodFormType::class, $paymentMethod);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($paymentMethod);
            $entityManager->flush();

            $this->addFlash('success', 'Platební metoda uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a payment method %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $paymentMethod->getType(), $paymentMethod->getId()));

            return $this->redirectToRoute('admin_payment_methods');
        }

        return $this->render('admin/payment_methods/admin_payment_method_edit.html.twig', [
            'paymentMethodForm' => $form->createView(),
            'paymentMethodInstance' => $paymentMethod,
        ]);
    }
}