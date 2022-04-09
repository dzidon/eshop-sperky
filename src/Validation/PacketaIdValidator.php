<?php

namespace App\Validation;

use App\Entity\DeliveryMethod;
use App\Entity\Order;
use LogicException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class PacketaIdValidator extends ConstraintValidator
{
    /**
     * @param Order $order
     * @param Constraint $constraint
     */
    public function validate($order, Constraint $constraint)
    {
        if (!$constraint instanceof PacketaId)
        {
            throw new UnexpectedTypeException($constraint, PacketaId::class);
        }

        if (!$order instanceof Order)
        {
            throw new LogicException('PacketaIdValidator jde použít jen na objektech třídy App\Entity\Order.');
        }

        if ($order->getDeliveryMethod() === null || $order->getDeliveryMethod()->getType() !== DeliveryMethod::TYPE_PACKETA_CZ)
        {
            return;
        }

        $packetaBranchId = $order->getStaticAddressDeliveryAdditionalInfo();
        if ($packetaBranchId === null || !is_numeric($packetaBranchId))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('deliveryMethod')
                ->addViolation();
        }
    }
}