<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\ProductCategoryGroup;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
use App\Form\FormType\User\HiddenTrueFormType;
use App\Form\FormType\Admin\ProductCategoryGroupFormType;
use App\Service\BreadcrumbsService;
use App\Service\EntityCollectionService;
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
class ProductCategoryController extends AbstractAdminController
{
    private LoggerInterface $logger;
    private $request;

    public function __construct(LoggerInterface $logger, BreadcrumbsService $breadcrumbs, RequestStack $requestStack)
    {
        parent::__construct($breadcrumbs);
        $this->breadcrumbs->addRoute('admin_product_categories');

        $this->logger = $logger;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/skupiny-produktovych-kategorii", name="admin_product_categories")
     *
     * @IsGranted("admin_product_categories")
     */
    public function productCategoryGroups(FormFactoryInterface $formFactory): Response
    {
        $phrase = new Phrase('Hledejte podle názvu.');
        $sort = new Sort(ProductCategoryGroup::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
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
    public function productCategoryGroup(EntityCollectionService $entityCollectionService, $id = null): Response
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

        $collectionMessenger = $entityCollectionService->createEntityCollectionsMessengerForOrphanRemoval($categoryGroup);
        $form = $this->createForm(ProductCategoryGroupFormType::class, $categoryGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityCollectionService->removeOrphans($collectionMessenger);
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