<?php

namespace App\EventSubscriber;

use App\Controller\ProfileController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class ProfileControllerSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller))
        {
            $controller = $controller[0];
        }

        if ($controller instanceof ProfileController)
        {
            $user = $this->security->getUser();
            if($user)
            {
                if(!$user->isVerified())
                {
                    $messageText = 'Zatím jste si neověřili email, takže nemáte přístup ke všem funkcionalitám webu. Pokud vám nepřišel ověřovací email nebo pokud vypršel váš odkaz na ověření, můžete si nechat poslat nový (Profil > Ověření emailu).';
                    $flashBag = $event->getRequest()->getSession()->getFlashBag();

                    if(!in_array($messageText, $flashBag->peek('warning')))
                    {
                        $flashBag->add('warning', $messageText);
                    }
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}