<?php

namespace App\Controller\User;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/kosik")
 */
class CartController extends AbstractController
{
    private $request;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->logger = $logger;
    }

    /**
     * @Route("/vlozit", name="cart_insert", methods={"POST"})
     */
    public function insert(): Response
    {
        if (!$this->request->isXmlHttpRequest())
        {
            throw new NotFoundHttpException();
        }


    }
}