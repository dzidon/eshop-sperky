<?php

namespace App\Form\FormType\User;

use App\Entity\Detached\ContactEmail;
use App\Form\EventSubscriber\DefaultEmailSubscriber;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomOrderDemandFormType extends AbstractType
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
                'label' => 'Váš email',
            ])
            ->add('text', TextareaType::class, [
                'attr' => [
                    'data-length' => 4096,
                ],
                'label' => 'Text',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
                'label' => false,
            ])
            ->addEventSubscriber($this->emailSubscriber)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactEmail::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_custom_order_demand',
        ]);
    }
}