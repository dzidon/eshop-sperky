<?php

namespace App\EventSubscriber;

use App\TextContent\TextContentLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber pro přednačtení textového obsahu podle aktuální cesty.
 *
 * @package App\EventSubscriber
 */
class TextContentSubscriber implements EventSubscriberInterface
{
    private TextContentLoader $textContentLoader;
    private ParameterBagInterface $parameterBag;
    private RequestStack $requestStack;

    public function __construct(TextContentLoader $textContentLoader, ParameterBagInterface $parameterBag, RequestStack $requestStack)
    {
        $this->textContentLoader = $textContentLoader;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
    }

    public function onKernelController(): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest->isXmlHttpRequest())
        {
            return;
        }

        if (!$this->parameterBag->has('app_text_content'))
        {
            return;
        }

        $configData = $this->parameterBag->get('app_text_content');
        if (!array_key_exists('route_preloading', $configData) || !is_array($configData['route_preloading']))
        {
            return;
        }

        $namesToLoad = [];
        if (array_key_exists('_all', $configData['route_preloading']) && is_array($configData['route_preloading']['_all']))
        {
            foreach ($configData['route_preloading']['_all'] as $textContentName)
            {
                $namesToLoad[$textContentName] = $textContentName;
            }
        }

        $route = $currentRequest->attributes->get('_route');
        if (array_key_exists($route, $configData['route_preloading']) && is_array($configData['route_preloading'][$route]))
        {
            foreach ($configData['route_preloading'][$route] as $textContentName)
            {
                $namesToLoad[$textContentName] = $textContentName;
            }
        }

        $this->textContentLoader->preload($namesToLoad);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}