<?php

namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineTrackingPolicySubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();
        $classMetadata->setChangeTrackingPolicy(
            ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT
        );
    }
}