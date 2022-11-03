<?php

namespace App\Form\FormType\Admin;

use App\Entity\TextContent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextContentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var TextContent $textContent */
        $textContent = $builder->getData();

        $attr = [
            'data-length' => 10000,
        ];

        if ($textContent->isHtmlAllowed())
        {
            $attr['class'] = 'tinymce-editor';
        }

        $builder
            ->add('text', TextareaType::class, [
                'attr' => $attr,
                'label' => 'Text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => TextContent::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_text_content',
        ]);
    }
}