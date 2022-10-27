<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\ProductOptionGroup;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
use App\Form\FormType\User\HiddenTrueFormType;
use App\Form\FormType\Admin\ProductOptionGroupFormType;
use App\Service\Breadcrumbs;
use App\Service\OrphanRemoval;
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
class ProductOptionController extends AbstractAdminController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        parent::__construct($breadcrumbs);

        $this->breadcrumbs->addRoute('admin_product_options');
        $this->logger = $logger;
    }

    /**
     * @Route("/skupiny-produktovych-voleb", name="admin_product_options")
     *
     * @IsGranted("admin_product_options")
     */
    public function productOptionGroups(FormFactoryInterface $formFactory, Request $request): Response
    {
        $phrase = new Phrase('Hledejte podle názvu.');
        $sort = new Sort(ProductOptionGroup::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(ProductOptionGroup::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné skupiny produktových voleb.');
        }

        return $this->render('admin/product_options/admin_product_options.html.twig', [
            'searchForm' => $form->createView(),
            'optionGroups' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/skupina-produktovych-voleb/{id}", name="admin_product_option_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_option_edit")
     */
    public function productOptionGroup(OrphanRemoval $orphanRemoval, Request $request, $id = null): Response
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

        $collectionMessenger = $orphanRemoval->createEntityCollectionsMessengerForOrphanRemoval($optionGroup);
        $form = $this->createForm(ProductOptionGroupFormType::class, $optionGroup);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $orphanRemoval->removeOrphans($collectionMessenger);
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
    public function productOptionGroupDelete(Request $request, $id): Response
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
        $form->handleRequest($request);

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