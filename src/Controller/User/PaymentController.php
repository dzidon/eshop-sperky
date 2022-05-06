<?php

namespace App\Controller\User;

use GoPay\Payments;
use App\Entity\Payment;
use App\Service\PaymentService;
use Psr\Log\LoggerInterface;
use App\Exception\PaymentException;
use App\Service\BreadcrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/platba")
 */
class PaymentController extends AbstractController
{
    private Payments $payments;
    private LoggerInterface $logger;
    private PaymentService $paymentService;
    private $request;

    public function __construct(LoggerInterface $logger, PaymentService $paymentService, Payments $payments, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->payments = $payments;
        $this->paymentService = $paymentService;
        $this->request = $requestStack->getCurrentRequest();
    }

    private function validateRequestAndGetData(): array
    {
        $paymentId = $this->request->query->get('id');
        if ($paymentId === null)
        {
            throw $this->createNotFoundException('V odkazu chybí id platby.');
        }

        $response = $this->payments->getStatus($paymentId);
        if (!$response->hasSucceed())
        {
            throw $this->createNotFoundException($this->paymentService->getErrorString($response));
        }

        $payment = $this->getDoctrine()->getRepository(Payment::class)->findOneForStatus($paymentId);
        if ($payment === null)
        {
            throw $this->createNotFoundException('Platba nenalezena.');
        }

        return [
            'payment' => $payment,
            'gopayResponse' => $response,
        ];
    }

    /**
     * @Route("", name="payment_return")
     */
    public function returnAction(BreadcrumbsService $breadcrumbs): Response
    {
        $data = $this->validateRequestAndGetData();

        /** @var Payment $payment */
        $payment = $data['payment'];
        $response = $data['gopayResponse'];

        try
        {
            $this->paymentService->updatePaymentState($payment, $response->json['state']);
        }
        catch (PaymentException $exception)
        {
            $this->addFlash('failure', $exception->getMessage());
        }

        $breadcrumbs
            ->addRoute('home')
            ->addRoute('payment_return', ['id' => $payment->getExternalId()]);

        return $this->render('payments/payment_status.html.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * @Route("/notifikace", name="payment_notification")
     */
    public function notificationAction(): Response
    {
        $data = $this->validateRequestAndGetData();

        /** @var Payment $payment */
        $payment = $data['payment'];
        $response = $data['gopayResponse'];

        try
        {
            $this->paymentService->updatePaymentState($payment, $response->json['state']);
        }
        catch (PaymentException $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new Response('OK', 200);
    }
}