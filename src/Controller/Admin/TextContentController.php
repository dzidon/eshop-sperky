<?php

namespace App\Controller\Admin;

use App\Entity\TextContent;
use App\Form\FormType\Admin\TextContentFormType;
use App\Service\Breadcrumbs;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
class TextContentController extends AbstractController
{
    private LoggerInterface $logger;
    private Breadcrumbs $breadcrumbs;

    public function __construct(LoggerInterface $logger, Breadcrumbs $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs->addRoute('home');
        $this->logger = $logger;
    }

    /**
     * @Route("/textovy-obsah/{id}", name="admin_text_content_edit", requirements={"id"="\d+"})
     *
     * @IsGranted("text_content_edit")
     */
    public function textContent(Request $request, $id): Response
    {
        $user = $this->getUser();

        /** @var TextContent|null $textContent */
        $textContent = $this->getDoctrine()->getRepository(TextContent::class)->findOneBy(['id' => $id]);
        if($textContent === null)
        {
            throw new NotFoundHttpException('Textový obsah nenalezen.');
        }

        $this->breadcrumbs->addRoute('admin_text_content_edit', ['id' => $textContent->getId()]);

        $form = $this->createForm(TextContentFormType::class, $textContent);
        $form->add('submit', SubmitType::class, ['label' => 'Uložit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($textContent);
            $entityManager->flush();

            $this->addFlash('success', 'Textový obsah uložen!');
            $this->logger->info(sprintf("Admin %s (ID: %s) has saved a text content entity %s (ID: %s).", $user->getUserIdentifier(), $user->getId(), $textContent->getName(), $textContent->getId()));

            return $this->redirectToRoute('home');
        }

        return $this->render('admin/text_content/admin_text_content_edit.html.twig', [
            'textContentForm' => $form->createView(),
            'textContentInstance' => $textContent,
        ]);
    }
}