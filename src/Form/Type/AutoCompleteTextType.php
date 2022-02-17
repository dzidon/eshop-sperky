<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoCompleteTextType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'block_prefix' => 'autocomplete_text',
            'data_autocomplete' => [],
            'attr' => [
                'class' => 'autocomplete',
                'autocomplete' => "off",
            ],
        ]);

        $resolver->setAllowedTypes('data_autocomplete', 'array');
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $dataTransformed = [];
        foreach ($options['data_autocomplete'] as $value)
        {
            $dataTransformed[$value] = null;
            // null kvuli tomu jak funguje autocomplete v materialize
            // (kdybychom v autocomplete chteli mit obrazek, dame tam misto null odkaz)
        }

        $json = json_encode($dataTransformed);
        if($json)
        {
            $view->vars['json_autocomplete'] = $json;
        }
        else
        {
            $view->vars['json_autocomplete'] = '{}';
        }
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}