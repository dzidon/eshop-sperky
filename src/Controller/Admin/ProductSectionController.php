<?php

namespace App\Controller\Admin;

use App\Entity\Detached\Search\Atomic\Phrase;
use App\Entity\Detached\Search\Atomic\Sort;
use App\Entity\Detached\Search\Composition\PhraseSort;
use App\Entity\ProductSection;
use App\Form\FormType\Search\Composition\PhraseSortFormType;
use App\Form\FormType\User\HiddenTrueFormType;
use App\Form\FormType\Admin\ProductSectionFormType;
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
class ProductSectionController extends AbstractAdminController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        parent::__construct($breadcrumbs);

        $this->breadcrumbs->addRoute('admin_product_sections');
        $this->logger = $logger;
    }

    /**
     * @Route("/produktove-sekce", name="admin_product_sections")
     *
     * @IsGranted("admin_product_sections")
     */
    public function productSections(FormFactoryInterface $formFactory, Request $request): Response
    {
        $phrase = new Phrase('Hledejte podle názvu nebo názvu v odkazu.');
        $sort = new Sort(ProductSection::getSortData());
        $searchData = new PhraseSort($phrase, $sort);

        $form = $formFactory->createNamed('', PhraseSortFormType::class, $searchData);
        //button je přidáván v šabloně, aby se nezobrazoval v odkazu
        $form->handleRequest($request);

        $pagination = $this->getDoctrine()->getRepository(ProductSection::class)->getSearchPagination($searchData);
        if($pagination->isCurrentPageOutOfBounds())
        {
            throw new NotFoundHttpException('Na této stránce nebyly nalezeny žádné sekce.');
        }

        return $this->render('admin/product_sections/admin_product_sections.html.twig', [
            'searchForm' => $form->createView(),
            'sections' => $pagination->getCurrentPageObjects(),
            'pagination' => $pagination->createView(),
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}", name="admin_product_section_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_edit")
     */
    public function productSection(Request $request, $id = null): Response
    {
        $user = $this->getUser();

        if($id !== null) //zadal id do url, snazi se editovat existujici
        {
            $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
            if($section === null) //nenaslo to zadnou sekci
            {
                throw new NotFoundHttpException('Produktová sekce nenalezena.');
            }

            $this->breadcrumbs->addRoute('admin_product_section_edit', ['id' => $section->getId()],'', 'edit');
        }
        else //nezadal id do url, vytvari novou sekci
        {
            $section = new ProductSection();
            $this->breadcrumbs->addRoute('admin_product_section_edit', ['id' => null],'', 'new');
        }

        $form = $this->createForm(ProductSectionFormType::class, $section);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová sekce uložena!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            return $this->redirectToRoute('admin_product_sections');
        }

        return $this->render('admin/product_sections/admin_product_section_edit.html.twig', [
            'productSectionForm' => $form->createView(),
            'productSectionInstance' => $section,
        ]);
    }

    /**
     * @Route("/produktova-sekce/{id}/smazat", name="admin_product_section_delete", requirements={"id"="\d+"})
     *
     * @IsGranted("product_section_delete")
     */
    public function productSectionDelete(Request $request, $id): Response
    {
        $user = $this->getUser();

        $section = $this->getDoctrine()->getRepository(ProductSection::class)->findOneBy(['id' => $id]);
        if($section === null) //nenaslo to zadnou sekci
        {
            throw new NotFoundHttpException('Produktová sekce nenalezena.');
        }

        $form = $this->createForm(HiddenTrueFormType::class, null, ['csrf_token_id' => 'form_product_section_delete']);
        $form->add('submit', SubmitType::class, [
            'label' => 'Smazat',
            'attr' => ['class' => 'btn-large red left'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->logger->info(sprintf("Admin %s (ID: %s) has deleted a product section %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $section->getName(), $section->getId()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($section);
            $entityManager->flush();

            $this->addFlash('success', 'Produktová sekce smazána!');
            return $this->redirectToRoute('admin_product_sections');
        }

        $this->breadcrumbs->addRoute('admin_product_section_delete', ['id' => $section->getId()]);

        return $this->render('admin/product_sections/admin_product_section_delete.html.twig', [
            'productSectionDeleteForm' => $form->createView(),
            'productSectionInstance' => $section,
        ]);
    }
}