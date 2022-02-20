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

    /**
     * Data navíc vkládaná do slugu v případě automatického generování
     *
     * @var array
     */
    private array $extraDataForAutoGenerate = [];

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::SUBMIT => 'submit'];
    }

    /**
     * Nastaví gettery, které se mají použít pro automatické vygenerování slugu, když je uživatelem zadaný slug null
     *
     * @param array $gettersForAutoGenerate
     * @return $this
     */
    public function setGettersForAutoGenerate(array $gettersForAutoGenerate): self
    {
        $this->gettersForAutoGenerate = $gettersForAutoGenerate;

        return $this;
    }

    /**
     * Nastaví data navíc vkládaná do slugu v případě automatického generování
     *
     * @param array $extraDataForAutoGenerate
     * @return $this
     */
    public function setExtraDataForAutoGenerate(array $extraDataForAutoGenerate): self
    {
        $this->extraDataForAutoGenerate = $extraDataForAutoGenerate;

        return $this;
    }

    public function submit(FormEvent $event): void
    {
        $instance = $event->getData();
        if ($instance)
        {
            if ($instance->getSlug() === null)
            {
                $stringToConvert = '';

                // Data z instance
                foreach ($this->gettersForAutoGenerate as $getData)
                {
                    $stringToConvert .= $instance->$getData() . ' ';
                }

                // Extra data
                foreach ($this->extraDataForAutoGenerate as $extraData)
                {
                    $stringToConvert .= $extraData . ' ';
                }

                // Vytvoření slugu
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
}