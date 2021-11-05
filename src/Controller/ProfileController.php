<?php

namespace App\Controller;

use App\Form\ChangePasswordLoggedInFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/profile")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("", name="profile")
     */
    public function overview(): Response
    {
        //TODO: zobrazení profilu
        $this->addFlash('failure', 'todo: overview');

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/change-password", name="profile_change_password")
     */
    public function passwordChange(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface): Response
    {
        $form = $this->createForm(ChangePasswordLoggedInFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();
            $user->setPassword(
                $userPasswordHasherInterface->hashPassword(
                    $user,
                    $form->get('newPlainPassword')->get('repeated')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Heslo změněno!');
            return $this->redirectToRoute('profile_change_password');
        }

        return $this->render('profile/change_password.html.twig', [
            'changeForm' => $form->createView(),
        ]);
    }
}
