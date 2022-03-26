<?php

namespace App\Controller\Admin;

use App\Entity\ProductOptionGroup;
use App\Form\HiddenTrueFormType;
use App\Form\ProductOptionGroupFormType;
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
class ProductOptionController extends AbstractController
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
            ->addRoute('admin_product_options');
    }

    /**
     * @Route("/skupiny-produktovych-voleb", name="admin_product_options")
     *
     * @IsGranted("admin_product_options")
     */
    public function productOptionGroups(FormFactoryInterface $formFactory, PaginatorService $paginatorService): Response
    {
        $form = $formFactory->createNamed('', SearchTextAndSortFormType::class, null, ['sort_choices' => ProductOptionGroup::getSortData()]);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOptionGroup::class)->getQueryForSearchAndPagination($form->get('searchPhrase')->getData(), $form->get('sortBy')->getData());
        }
        else
        {
            $queryForPagination = $this->getDoctrine()->getRepository(ProductOptionGroup::class)->getQueryForSearchAndPagination();
        }

        $optionGroups = $paginatorService
            ->initialize($queryForPagination, 3)
            ->getCurrentPageObjects();

        if($paginatorService->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových voleb.');
        }

        return $this->render('admin/product_options/admin_product_options.html.twig', [
            'searchForm' => $form->createView(),
            'optionGroups' => $optionGroups,
            'pagination' => $paginatorService->createViewData(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-voleb/{id}", name="admin_product_option_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOptionGroup($id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) // zadal id do url, snazi se editovat existujici
        {
            $optionGroup = $this->getDoctrine()->getRepository(ProductOptionGroup::class)->findOneBy(['id' => $id]);
            if($optionGroup === null) // nenaslo to zadnou skupinu
            {
                throw new NotFoundHttpException('Skupina produktových voleb nenalezena.');
            }

            $this->breadcrumbs->addRoute('admin_product_option_edit', ['id' => $optionGroup->getId()],'', 'edit');
        }
        else // nezadal id do url, vytvari novou volbu
        {
            $optionGroup = new ProductOptionGroup();
            $this->breadcrumbs->addRoute('admin_product_option_edit', ['id' => null],'', 'new');
        }

        $form = $this->createForm(ProductOptionGroupFormType::class, $optionGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($optionGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových voleb uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product option group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $optionGroup->getName(), $optionGroup->getId()));

            return $this->redirectToRoute('admin_product_options');
        }

        return $this->render('admin/product_options/admin_product_option_edit.html.twig', [
            'productOptionGroupForm' => $form->createView(),
            'productOptionGroupInstance' => $optionGroup,
        ]);
    }

    /**
     * @Route("/skupina-produktovych-voleb/{id}/smazat", name="admin_product_option_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_delete")
     */
    public function productOptionGroupDelete($id): Response
    {
        $user = $this->getUser();

        $optionGroup = $this->getDoctrine()->getRepository(ProductOptionGroup::class)->findOneBy(['id' => $id]);
        if($optionGroup === null) // nenaslo to zadnou skupinu
        {
            throw new NotFoundHttpException('Skupina produktových voleb nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_option_group_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product option group %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $optionGroup->getName(), $optionGroup->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($optionGroup);
            $entityManager->flush();

            $this->addFlash('success', 'Skupina produktových voleb smazána!');
            return $this->redirectToRoute('admin_product_options');
        }

        $this->breadcrumbs->addRoute('admin_product_option_delete', ['id' => $optionGroup->getId()]);

        return $this->render('admin/product_options/admin_product_option_delete.html.twig', [
            'productOptionGroupDeleteForm' => $form->createView(),
            'productOptionGroupInstance' => $optionGroup,
        ]);
    }
}