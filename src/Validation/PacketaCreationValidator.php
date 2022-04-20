<?php

namespace App\Validation;

use App\Entity\DeliveryMethod;
use App\Entity\Order;
use App\Service\PacketaApiService;
use LogicException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class PacketaCreationValidator extends ConstraintValidator
{
    private PacketaApiService $packetaApiService;

    public function __construct(PacketaApiService $packetaApiService)
    {
        $this->packetaApiService = $packetaApiService;
    }

    /**
     * @param Order $order
     * @param Constraint $constraint
     */
    public function validate($order, Constraint $constraint)
    {
        if (!$constraint instanceof PacketaCreation)
        {
            throw new UnexpectedTypeException($constraint, PacketaCreation::class);
        }

        if (!$order instanceof Order)
        {
            throw new LogicException('PacketaCreationValidator jde použít jen na objektech třídy App\Entity\Order.');
        }

        if ($order->getDeliveryMethod() === null || $order->getDeliveryMethod()->getType() !== DeliveryMethod::TYPE_PACKETA_CZ)
        {
            return;
        }

        $this->packetaApiService->setOrder($order);
        if ($order->getLifecycleChapter() === Order::LIFECYCLE_SHIPPED && !$this->packetaApiService->packetStatus())
        {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('lifecycleChapter')
                ->addViolation()
            ;
        }
    }
}