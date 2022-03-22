<?php

namespace App\Service;

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

    public function setResponseStatus(int $responseStatus): void
    {
        $this->responseStatus = $responseStatus;
    }

    public function addResponseError(string $text): void
    {
        $this->responseStatus = Response::HTTP_NOT_FOUND;
        $this->responseData['errors'][] = $text;
    }

    public function setResponseHtml(?string $html): void
    {
        $this->responseData['html'] = $html;
    }

    public function createJsonResponse(): JsonResponse
    {
        return new JsonResponse($this->responseData, $this->responseStatus);
    }
}