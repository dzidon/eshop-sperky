<?php

namespace App\Form\EventSubscriber\SlugGeneratorSubscriber;

use App\Entity\Abstraction\EntitySlugInterface;
use App\SlugGenerator\AbstractSlugGenerator;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Abstraktní třída pro subscribery řešící generování slugu ve formuláři.
 *
 * @package App\Form\EventSubscriber\SlugGeneratorSubscriber
 */
abstract class AbstractSlugGeneratorSubscriber implements EventSubscriberInterface
{
    protected AbstractSlugGenerator $slugGenerator;

    public function __construct(AbstractSlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::SUBMIT => 'submit'];
    }

    public function submit(FormEvent $event): void
    {
        /** @var EntitySlugInterface $instance */
        $instance = $event->getData();
        if (!$instance instanceof EntitySlugInterface)
        {
            throw new LogicException(sprintf('%s musí dostat objekt třídy, která implementuje %s.', get_class($this), EntitySlugInterface::class));
        }

        // Uživatel nezadal žádný string pro slug, takže se vygeneruje automaticky
        if ($instance->getSlug() === null)
        {
            $slug = $this->slugGenerator->generateAutomatically($instance);
        }
        // Uživatel zadal string, převede se na slug a nastaví do instance
        else
        {
            $slug = $this->slugGenerator->generateFromString($instance->getSlug());
        }

        $instance->setSlug($slug);
        $event->setData($instance);
    }
}