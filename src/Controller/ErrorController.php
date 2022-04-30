<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

class ErrorController extends AbstractController
{
    public function showError(FlattenException $exception): Response
    {
        $code = $exception->getStatusCode();
        $message = $exception->getStatusText();

        $path = 'bundles/TwigBundle/Exception/';
        $template = $path . 'error.html.twig';
        switch ($code)
        {
            case 403:
            {
                $template = $path . 'error403.html.twig';
                break;
            }
            case 404:
            {
                $template = $path . 'error404.html.twig';
                break;
            }
        }

        return $this->render($template, [
            'status_code'   => $code,
            'error_message' => $message,
            'is_error_page' => true,
        ]);
    }
}