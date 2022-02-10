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
     * Gettery, které se mají použít pro automatické vygenerování slugu, když je uživatelem zadaný slug null
     *
     * @var array
     */
    private array $gettersForAutoGenerate = [];

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::SUBMIT => 'createSlug'];
    }

    public function setGettersForAutoGenerate(array $gettersForAutoGenerate): self
    {
        $this->gettersForAutoGenerate = $gettersForAutoGenerate;

        return $this;
    }

    public function createSlug(FormEvent $event): void
    {
        $instance = $event->getData();
        if (!$instance)
        {
            return;
        }

        if ($instance->getSlug() === null)
        {
            $stringToConvert = '';
            foreach ($this->gettersForAutoGenerate as $getData)
            {
                $stringToConvert .= $instance->$getData() . ' ';
            }

            $slug = strtolower($this->slugger->slug($stringToConvert));
            if(mb_strlen($slug, 'utf-8') > 0)
            {
                $instance->setSlug($slug);
            }
        }
        else
        {
            $instance->setSlug( strtolower($this->slugger->slug($instance->getSlug())) );
        }

        $event->setData($instance);
    }
}