<?php

namespace App\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EntityCollectionAdditionSubscriber implements EventSubscriberInterface
{
    private array $collectionFormFields;

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function addFieldNameAndAdder(string $fieldName, string $adder): self
    {
        $this->collectionFormFields[$fieldName] = $adder;

        return $this;
    }

    public function postSubmit(FormEvent $event): void
    {
        $instance = $event->getData();
        if (!$instance)
        {
            return;
        }

        $form = $event->getForm();
        if ($form->isSubmitted() && $form->isValid())
        {
            foreach ($this->collectionFormFields as $collectionFormField => $instanceAdder)
            {
                $formFieldData = $form->get($collectionFormField)->getData();
                if ($formFieldData !== null)
                {
                    foreach ($formFieldData as $elementToBeAdded)
                    {
                        if($elementToBeAdded !== null)
                        {
                            $instance->$instanceAdder($elementToBeAdded);
                        }
                    }
                }
            }
        }
    }
}