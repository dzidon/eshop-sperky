<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ResetPasswordRequestFormType extends AbstractType
{
    private string $defaultEmail = '';

    public function __construct(Security $security)
    {
        $user = $security->getUser();
        if($user)
        {
            $this->defaultEmail = $user->getEmail();
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'data' => $this->defaultEmail,
                'label' => 'Email',
                'help' => 'Zadejte e-mail, přes který jste zaregistrovali svůj účet a my vám na něj pošleme odkaz pro resetování hesla.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => User::class,
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            'csrf_token_id'     => 'form_password_reset_email',
            'validation_groups' => ['validateEmail'],
        ]);
    }
}