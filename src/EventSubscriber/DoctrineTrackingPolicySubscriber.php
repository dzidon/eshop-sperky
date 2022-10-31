<?php

namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Subscriber, který přepne Doctrine tracking policy na DEFERRED_EXPLICIT u všech entit. Díky tomu je nutné vždy
 * volat persist pro každou entitu, která má být uložena do DB.
 *
 * @package App\EventSubscriber
 */
class DoctrineTrackingPolicySubscriber implements EventSubscriber
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();
        $classMetadata->setChangeTrackingPolicy(
            ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT
        );
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata
        ];
    }
}