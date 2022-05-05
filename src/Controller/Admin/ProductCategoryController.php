<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\SearchAndSort;
use App\Entity\ProductCategoryGroup;
use App\Form\HiddenTrueFormType;
use App\Form\ProductCategoryGroupFormType;
use App\Form\SearchTextAndSortFormType;
use App\Service\BreadcrumbsService;
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
class ProductCategoryController extends AbstractController
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
            ->addRoute(MainController::ADMIN_ROUTE, [], MainController::ADMIN_TITLE)
            ->addRoute('admin_product_categories');
    }

    /**
     * @Route("/skupiny-produktovych-kategorii", name="admin_product_categories")
     *
     * @IsGranted("admin_product_categories")
     */
    public function productCategoryGroups(FormFactoryInterface $formFactory): Response
    {
        $searchData = new SearchAndSort(ProductCategoryGroup::getSortData(), 'Hledejte podle názvu.');
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        $pagination = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových kategorií.');
        }

        return $this->render('admin/product_categories/admin_product_categories.html.twig', [
            'searchForm' => $form->createView(),
            'categoryGroups' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
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
            $categoryGroup = $this->getDoctrine()->getRepository(ProductCategoryGroup::class)->findOneByIdAndFetchCategories($id);
            if($categoryGroup === null) //nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových sekcí nenalezena.');
            }

            $this->breadcrumbs->addRoute('admin_product_category_edit', ['id' => $categoryGroup->getId()],'', 'edit');
        }
        else //nezadal id do url, vytvari novou skupinu
        {
            $categoryGroup = new ProductCategoryGroup();
            $this->breadcrumbs->addRoute('admin_product_category_edit', ['id' => null],'', 'new');
        }

        $form = $this->createForm(ProductCategoryGroupFormType::class, $categoryGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($categoryGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových kategorií uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product category group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $categoryGroup->getName(), $categoryGroup->getId()));

            return $this->redirectToRoute('admin_product_categories');
        }

        return $this->render('admin/product_categories/admin_product_category_edit.html.twig', [
            'productCategoryGroupForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
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

        $this->breadcrumbs->addRoute('admin_product_category_delete', ['id' => $categoryGroup->getId()]);

        return $this->render('admin/product_categories/admin_product_category_delete.html.twig', [
            'productCategoryGroupDeleteForm' => $form->createView(),
            'productCategoryGroupInstance' => $categoryGroup,
        ]);
    }
}