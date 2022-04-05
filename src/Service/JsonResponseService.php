<?php

namespace App\Service;

use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Třída řešící JSON odpověď
 *
 * @package App\Service
 */
class JsonResponseService
{
    private int $responseStatus = Response::HTTP_OK;
    private array $responseData = [
        'errors' => [],
        'warnings' => [],
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

    public function addResponseWarning(string $text): self
    {
        $this->responseData['warnings'][] = $text;

        return $this;
    }

    public function setResponseHtml(?string $html): self
    {
        $this->responseData['html'] = $html;

        return $this;
    }

    public function setResponseData(string $key, $data): self
    {
        if($key === 'errors' || $key === 'html' || $key === 'warnings')
        {
            throw new LogicException('Do metody setResponseData v JsonResponseService nepatří klíče errors, html a warnings. Použijte metody addResponseError, addResponseWarning a setResponseHtml.');
        }

        $this->responseData[$key] = $data;

        return $this;
    }

    public function createJsonResponse(): JsonResponse
    {
        return new JsonResponse($this->responseData, $this->responseStatus);
    }
}