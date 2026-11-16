<?php

namespace Base\Booking\Command;

use Base\Booking\Checkout\BookingFlow;
use Base\Booking\Payout\Payouts;
use Base\Booking\Repository\BookingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The bookings' daily round, from the cron container: pending bookings past
 * their hold let go, stays whose check-out has passed marked completed,
 * the payouts due marked paid.
 */
#[AsCommand(name: 'booking:maintain', description: 'Lets expired holds go, completes past stays, releases the payouts due.')]
final class MaintainCommand extends Command
{
    public function __construct(private readonly BookingFlow $flow, private readonly Payouts $payouts, private readonly BookingRepository $bookings)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $expired = $this->flow->expire($now);
        $completed = $this->bookings->completeStays($now->setTime(0, 0));
        $released = $this->payouts->release($now);
        $output->writeln(\sprintf('%d hold(s) let go, %d stay(s) completed, %d payout(s) released.', $expired, $completed, $released));

        return Command::SUCCESS;
    }
}
