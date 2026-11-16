<?php

namespace Base\Booking\Tests\Persistence;

use Base\Contract\Locales;
use Base\Contract\Runtime;
use Base\Testing\Engines;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Base\Booking\Entity\Booking;
use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Booking\Entity\Message;
use Base\Booking\Repository\BookingRepository;
use Base\Booking\Repository\MessageRepository;
use App\Entity\User; // the host's user, Base\Entity\User where it has none (ClassAliases, registered by Engines::of())
use Omnistay\Model\BookingStatus;
use Omnistay\Model\Stay;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The booking repositories' queries once written per persistence, now written once in the language: the same answers
 * on the same rows, on both engines.
 */
final class BookingStatementsParityTest extends TestCase
{
    private static Engines $engines;

    public static function backends(): array
    {
        return Engines::backends();
    }

    /** @var array<string, int> */
    private static array $ids = [];

    public static function setUpBeforeClass(): void
    {
        // The entities' constructors read the runtime's locale (User::setLocale()): one, as a host gives it.
        if (!Runtime::has(Locales::class)) {
            Runtime::set(Locales::class, new class implements Locales {
                public function getLocale(): string { return 'fr'; }
                public function getDefaultLocale(): string { return 'fr'; }
                public function getAvailableLocales(): array { return ['fr']; }
                public function getTimezone(): string { return 'UTC'; }
            });
        }
        self::$engines = Engines::of([Booking::class, Message::class], ['project_dir' => \dirname(__DIR__, 2)]);
        $seed = [];
        $host = self::user('host@example.org');
        $guest = self::user('guest@example.org');
        $other = self::user('other@example.org');
        $profile = new HostProfile($host, 'Jeanne Exemple');
        $listing = (new Listing($profile))->setTitle('Atelier Exemple')->setSlug('atelier-exemple');
        $stay = static fn (string $in, string $out) => new Stay(new \DateTimeImmutable($in), new \DateTimeImmutable($out));
        $past = (new Booking($guest, 'SL-PAST'))->setListing($listing)->setStay($stay('2026-01-01', '2026-01-04'))->setStatus(BookingStatus::Confirmed);
        $today = (new Booking($guest, 'SL-TODAY'))->setListing($listing)->setStay($stay('2026-01-07', '2026-01-10'))->setStatus(BookingStatus::Confirmed);
        $future = (new Booking($guest, 'SL-NEXT'))->setListing($listing)->setStay($stay('2026-02-01', '2026-02-03'))->setStatus(BookingStatus::Confirmed);
        $pending = (new Booking($other, 'SL-HOLD'))->setListing($listing)->setStay($stay('2026-01-02', '2026-01-03'))->setStatus(BookingStatus::Pending);
        $messages = [
            new Message($past, $guest, 'unread, to the host'),
            new Message($past, $host, 'unread, to the guest'),
            (new Message($past, $host, 'read, to the guest'))->markRead(new \DateTimeImmutable('2026-01-02')),
            new Message($pending, $other, 'unread, to the host'),
        ];
        foreach ([$host, $guest, $other, $profile, $listing, $past, $today, $future, $pending, ...$messages] as $entity) {
            $seed[] = $entity;
        }
        self::$engines->seed(...$seed);
        self::$ids = ['host' => (int) $host->getId(), 'guest' => (int) $guest->getId(), 'other' => (int) $other->getId()];
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testUnreadMessagesAsGuestAndAsHost(string $backend): void
    {
        $finders = self::$engines->finders($backend);
        $messages = (new MessageRepository($finders));
        $users = $finders->of(User::class);

        self::assertSame(1, $messages->countUnread($users->find(self::$ids['guest'])), 'the guest: the host\'s unread message');
        self::assertSame(2, $messages->countUnread($users->find(self::$ids['host'])), 'the host: one per booking of its listing, its own left out');
        self::assertSame(0, $messages->countUnread($users->find(self::$ids['other'])), 'the other guest wrote the only message of its booking');
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testCompleteStaysUpdatesTheConfirmedOnesOnly(string $backend): void
    {
        // Each backend starts from the same rows: the stays set back to confirmed (the other backend's run completed them).
        $bookings = self::$engines->description()->entities[Booking::class];
        self::$engines->pdo()->exec(sprintf("UPDATE %s SET %s = 'confirmed' WHERE %s IN ('SL-PAST', 'SL-TODAY', 'SL-NEXT')", $bookings->table, $bookings->fields['status']->column, $bookings->fields['reference']->column));

        $completed = (new BookingRepository(self::$engines->finders($backend)))->completeStays(new \DateTimeImmutable('2026-01-10'));

        self::assertSame(2, $completed, 'the two stays whose check-out is on or before the day');
        self::assertSame(['SL-HOLD' => 'pending', 'SL-NEXT' => 'confirmed', 'SL-PAST' => 'completed', 'SL-TODAY' => 'completed'], self::statuses((new BookingRepository(self::$engines->finders($backend)))));
    }

    /** @return array<string, string> reference => status, by reference */
    private static function statuses(BookingRepository $bookings): array
    {
        $statuses = [];
        foreach ($bookings->findBy([], ['reference' => 'ASC']) as $booking) {
            $statuses[$booking->getReference()] = $booking->getStatus()->value;
        }

        return $statuses;
    }

    /** A user as the host builds one: its own User may require more than an e-mail (a username, written when it has one). */
    private static function user(string $email): User
    {
        $user = new User($email);
        $accessor = PropertyAccess::createPropertyAccessor();
        if ($accessor->isWritable($user, 'username')) {
            $accessor->setValue($user, 'username', strstr($email, '@', true));
        }

        return $user;
    }
}
