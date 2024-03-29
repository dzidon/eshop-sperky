<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryMethod;
use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Form\FormType\Admin\DeliveryMethodFormType;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
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
class DeliveryMethodController extends AbstractAdminController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        parent::__construct($breadcrumbs);

        $this->breadcrumbs->addRoute('admin_delivery_methods');
        $this->logger = $logger;
    }

    /**
     * @Route("/dorucovaci-metody", name="admin_delivery_methods")
     *
     * @IsGranted("admin_payment_methods")
     */
    public function deliveryMethods(FormFactoryInterface $formFactory, Request $request): Response
    {
        $phrase = new Phrase('Hledejte podle názvu.');
        $sort = new Sort(DeliveryMethod::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(DeliveryMethod::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné doručovací metody.');
        }

        return $this->render('admin/delivery_methods/admin_delivery_methods.html.twig', [
            'searchForm' => $form->createView(),
            'deliveryMethods' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/dorucovaci-metoda/{id}", name="admin_delivery_method_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("delivery_method_edit")
     */
    public function deliveryMethod(Request $request, $id): Response
    {
        $user = $this->getUser();

        $deliveryMethod = $this->getDoctrine()->getRepository(DeliveryMethod::class)->findOneBy(['id' => $id]);
        if($deliveryMethod === null)
        {
            throw new NotFoundHttpException('Doručovací metoda nenalezena.');
        }

        $this->breadcrumbs->addRoute('admin_delivery_method_edit', ['id' => $deliveryMethod->getId()]);

        $form = $this->createForm(DeliveryMethodFormType::class, $deliveryMethod);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($deliveryMethod);
            $entityManager->flush();

            $this->addFlash('success', 'Doručovací metoda uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a delivery method %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $deliveryMethod->getType(), $deliveryMethod->getId()));

            return $this->redirectToRoute('admin_delivery_methods');
        }

        return $this->render('admin/delivery_methods/admin_delivery_method_edit.html.twig', [
            'deliveryMethodForm' => $form->createView(),
            'deliveryMethodInstance' => $deliveryMethod,
        ]);
    }
}