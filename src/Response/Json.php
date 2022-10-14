<?php

namespace App\Response;

use LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Třída řešící JSON odpověď.
 *
 * @package App\Response
 */
class Json
{
    private int $responseStatus = Response::HTTP_OK;
    private array $responseData = [
        'errors' => [],
        'html' => null,
    ];

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(int $responseStatus): self
    {
        $this->responseStatus = $responseStatus;

        return $this;
    }

    public function addResponseError(string $text): self
    {
        $this->responseStatus = Response::HTTP_UNPROCESSABLE_ENTITY;
        $this->responseData['errors'][] = $text;

        return $this;
    }

    public function addResponseFormErrors(FormInterface $form): self
    {
        foreach ($form->getErrors() as $formError)
        {
            $this->addResponseError($formError->getMessage());
        }

        return $this;
    }

    public function setResponseHtml(?string $html): self
    {
        $this->responseData['html'] = $html;

        return $this;
    }

    public function setResponseData(string $key, $data): self
    {
        if ($key === 'errors' || $key === 'html')
        {
            throw new LogicException('Do metody setResponseData v App\Response\Json nepatří klíče errors a html. Použijte metody addResponseError a setResponseHtml.');
        }

        $this->responseData[$key] = $data;

        return $this;
    }

    public function create(): JsonResponse
    {
        return new JsonResponse($this->responseData, $this->responseStatus);
    }
}