<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\SearchAndSort;
use App\Entity\ProductInformationGroup;
use App\Form\HiddenTrueFormType;
use App\Form\ProductInformationGroupFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
use Psr\Log\LoggerInterface;
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
class ProductInfoController extends AbstractAdminController
{
    private LoggerInterface $logger;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        parent::__construct($breadcrumbs);
        $this->breadcrumbs->addRoute('admin_product_info');

        $this->logger = $logger;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/skupiny-produktovych-informaci", name="admin_product_info")
     *
     * @IsGranted("admin_product_info")
     */
    public function productInfoGroups(FormFactoryInterface $formFactory): Response
    {
        $searchData = new SearchAndSort(ProductInformationGroup::getSortData(), 'Hledejte podle názvu.');
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        $pagination = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových informací.');
        }

        return $this->render('admin/product_info/admin_product_info.html.twig', [
            'searchForm' => $form->createView(),
            'infoGroups' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-informaci/{id}", name="admin_product_info_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_info_edit")
     */
    public function productInfoGroup($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $infoGroup = $this->getDoctrine()->getRepository(ProductInformationGroup::class)->findOneBy(['id' => $id]);
            if($infoGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových informaci nenalezena.');
            }

            $this->breadcrumbs->addRoute('admin_product_info_edit', ['id' => $infoGroup->getId()],'', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $infoGroup = new ProductInformationGroup();
            $this->breadcrumbs->addRoute('admin_product_info_edit', ['id' => null],'', 'new');
        }

        $form = $this->createForm(ProductInformationGroupFormType::class, $infoGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($infoGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových informací uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product information group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $infoGroup->getName(), $infoGroup->getId()));

            return $this->redirectToRoute('admin_product_info');
        }

        return $this->render('admin/product_info/admin_product_info_edit.html.twig', [
            'productInfoGroupForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
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

        $this->breadcrumbs->addRoute('admin_product_info_delete', ['id' => $infoGroup->getId()]);

        return $this->render('admin/product_info/admin_product_info_delete.html.twig', [
            'productInfoGroupDeleteForm' => $form->createView(),
            'productInfoGroupInstance' => $infoGroup,
        ]);
    }
}