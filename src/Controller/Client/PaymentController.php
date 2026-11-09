<?php

namespace Base\Booking\Controller\Client;

use Base\Booking\Checkout\BookingFlow;
use Base\Booking\Payment\BookingPayments;
use Base\Booking\Repository\BookingRepository;
use Base\Booking\Security\Voter\BookingVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Where the gateway sends the guest back, and where it tells the site
 * (its webhook): either way the payment is read back from the gateway and
 * the booking completed (BookingFlow::complete()); the guest then lands on
 * the site's page for the booking (booking.after_payment_route).
 */
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly BookingFlow $flow,
        private readonly BookingPayments $payments,
        #[Autowire('%booking.after_payment_route%')] private readonly string $afterPayment = 'booking_payment_done',
    ) {
    }

    #[Route('/booking/{reference}/payment/return', name: 'booking_payment_return', requirements: ['reference' => 'SL-[A-Z0-9]{6}'], methods: ['GET'])]
    public function return(string $reference, Request $request): Response
    {
        $booking = $this->bookings->findOneByReference($reference) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(BookingVoter::VIEW, $booking);
        if (!$request->query->has('cancel')) {
            $this->flow->complete($booking);
        }

        return $this->redirectToRoute($this->afterPayment, ['reference' => $reference]);
    }

    #[Route('/booking/payment/{gateway}/webhook', name: 'booking_payment_webhook', requirements: ['gateway' => '[a-z0-9_-]+'], methods: ['POST'])]
    public function webhook(string $gateway, Request $request): Response
    {
        try {
            $reference = $this->payments->notified($gateway, $request->getContent(), $request->headers->all());
        } catch (\Throwable) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }
        $booking = null === $reference ? null : $this->bookings->findOneByReference($reference);
        if (null !== $booking) {
            $this->flow->complete($booking);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /** Where a site without a page of its own for a booking lands: its reference, said plainly. */
    #[Route('/booking/{reference}/payment/done', name: 'booking_payment_done', requirements: ['reference' => 'SL-[A-Z0-9]{6}'], methods: ['GET'])]
    public function done(string $reference): Response
    {
        $booking = $this->bookings->findOneByReference($reference) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(BookingVoter::VIEW, $booking);

        return new Response($booking->getReference().' '.$booking->getStatus()->value, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
