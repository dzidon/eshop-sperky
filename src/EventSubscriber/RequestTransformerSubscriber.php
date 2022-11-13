<?php

namespace App\EventSubscriber;

use App\Exception\RequestTransformerException;
use App\Response\Json;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Třída řešící odchycení výjimky App\Exception\RequestTransformerException a vrácení JSON odpovědi.
 *
 * @package App\EventSubscriber
 */
class RequestTransformerSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof RequestTransformerException)
        {
            return;
        }

        $jsonResponse = new Json();
        $validatorErrors = $exception->getValidatorErrors();
        if ($validatorErrors === null)
        {
            $jsonResponse->addResponseError($exception->getMessage());
        }
        else
        {
            $jsonResponse->addResponseValidatorErrors($validatorErrors);
        }

        $event->setResponse($jsonResponse->create());
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}