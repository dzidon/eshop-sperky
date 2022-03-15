<?php

namespace App\Form;

use App\Entity\User;
use App\Form\EventSubscriber\DefaultEmailSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordRequestFormType extends AbstractType
{
    private DefaultEmailSubscriber $emailSubscriber;

    public function __construct(DefaultEmailSubscriber $emailSubscriber)
    {
        $this->emailSubscriber = $emailSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autofocus' => 'autofocus'],
                'label' => 'Email',
                'help' => 'Zadejte e-mail, přes který jste zaregistrovali svůj účet a my vám na něj pošleme odkaz pro resetování hesla.',
            ])
            ->addEventSubscriber($this->emailSubscriber)
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