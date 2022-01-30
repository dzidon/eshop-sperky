<?php

namespace App\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * EventSubscriber pro formulář s entitou, která má mít slug. Pokud je slug entity null, vygeneruje se automaticky z
 * názvu. Pokud má entita nějaký slug nastavený, pro jistotu se vyvolá metoda slug na existující slug, protože uživatel
 * mohl do formuláře zadat nebezpečné znaky.
 *
 * @package App\Form\EventSubscriber
 */
class SlugSubscriber implements EventSubscriberInterface
{
    /**
     * @var SluggerInterface
     */
    private SluggerInterface $slugger;

    /**
     * Getter, který se má použít pro automatické vygenerování slugu, když je slug null
     *
     * @var string
     */
    private string $getterForAutoGenerate = 'undefined';

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::SUBMIT => 'submit'];
    }

    public function setGetterForAutoGenerate(string $getterForAutoGenerate): self
    {
        $this->getterForAutoGenerate = $getterForAutoGenerate;

        return $this;
    }

    public function submit(FormEvent $event): void
    {
        $instance = $event->getData();

        if (!$instance)
        {
            return;
        }

        $getDataForAutoGenerate = $this->getterForAutoGenerate;

        if ($instance->getSlug() === null && $instance->$getDataForAutoGenerate() !== null)
        {
            $instance->setSlug( strtolower($this->slugger->slug($instance->$getDataForAutoGenerate())) );
        }
        else if ($instance->getSlug() !== null)
        {
            $instance->setSlug( strtolower($this->slugger->slug($instance->getSlug())) );
        }

        $event->setData($instance);
    }
}