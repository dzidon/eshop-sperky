<?php

namespace App\ArgumentResolver;

use App\Entity\Detached\CartInsert;
use App\Exception\RequestTransformerException;
use App\Request\Transformer\RequestToCartInsertTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Třída pro vložení objektu třídy App\Entity\Detached\CartInsert do controllerové akce.
 *
 * @package App\ArgumentResolver
 */
class CartInsertResolver implements ArgumentValueResolverInterface
{
    private ValidatorInterface $validator;
    private RequestToCartInsertTransformer $requestToCartInsertTransformer;

    public function __construct(ValidatorInterface $validator, RequestToCartInsertTransformer $requestToCartInsertTransformer)
    {
        $this->validator = $validator;
        $this->requestToCartInsertTransformer = $requestToCartInsertTransformer;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === CartInsert::class;
    }

    /**
     * @inheritDoc
     * @throws RequestTransformerException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $cartInsertRequest = $this->requestToCartInsertTransformer->createCartInsert($request);
        $errors = $this->validator->validate($cartInsertRequest);

        if (count($errors) > 0)
        {
            throw new RequestTransformerException('Neplatný požadavek na vložení produktu do košíku.', $errors);
        }

        yield $cartInsertRequest;
    }
}