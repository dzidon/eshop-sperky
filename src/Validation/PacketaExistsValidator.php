<?php

namespace App\Validation;

use App\Entity\DeliveryMethod;
use App\Entity\Order;
use App\Service\Packeta;
use LogicException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class PacketaExistsValidator extends ConstraintValidator
{
    private Packeta $packeta;

    public function __construct(Packeta $packeta)
    {
        $this->packeta = $packeta;
    }

    /**
     * @param Order $order
     * @param Constraint $constraint
     */
    public function validate($order, Constraint $constraint)
    {
        if (!$constraint instanceof PacketaExists)
        {
            throw new UnexpectedTypeException($constraint, PacketaExists::class);
        }

        if (!$order instanceof Order)
        {
            throw new LogicException('PacketaExistsValidator jde použít jen na objektech třídy App\Entity\Order.');
        }

        if ($order->getDeliveryMethod() === null || $order->getDeliveryMethod()->getType() !== DeliveryMethod::TYPE_PACKETA_CZ)
        {
            return;
        }

        if ($order->getLifecycleChapter() === Order::LIFECYCLE_SHIPPED && !$this->packeta->packetExists($order))
        {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('lifecycleChapter')
                ->addViolation()
            ;
        }
    }
}