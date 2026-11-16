<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Omnistay\Model\StayQuery;

/** @extends ServiceEntityRepository<Listing> */
class ListingRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Listing::class);
    }

    /**
     * The published listings that may answer a query: a word of the title,
     * town or area; or within the box around its point (the provider then
     * checks the distance itself); kinds, capacity.
     *
     * @return list<Listing>
     */
    public function findForQuery(StayQuery $query, int $max = 200): array
    {
        $places = [];
        if (null !== $query->place && '' !== trim($query->place)) {
            // LOWER(column) LIKE the lowered word, as the hand-written query compared it.
            $place = '%'.mb_strtolower(trim($query->place)).'%';
            $places[] = Group::of(Parser::SEPARATOR_OR,
                Criterion::of('title', $place, Parser::OPTION_INSENSITIVE, Parser::OPTION_PARTIAL),
                Criterion::of('city', $place, Parser::OPTION_INSENSITIVE, Parser::OPTION_PARTIAL),
                Criterion::of('area', $place, Parser::OPTION_INSENSITIVE, Parser::OPTION_PARTIAL),
            );
        }
        if ($query->hasPoint()) {
            $lat = (float) $query->latitude;
            $lng = (float) $query->longitude;
            $dLat = $query->radius / 111.0;
            $dLng = $query->radius / max(1.0, 111.0 * cos(deg2rad($lat)));
            // BETWEEN, bounds included.
            $places[] = Group::of(Parser::SEPARATOR_AND,
                Criterion::of('latitude', $lat - $dLat, Parser::OPTION_GREATER_EQUAL),
                Criterion::of('latitude', $lat + $dLat, Parser::OPTION_LOWER_EQUAL),
                Criterion::of('longitude', $lng - $dLng, Parser::OPTION_GREATER_EQUAL),
                Criterion::of('longitude', $lng + $dLng, Parser::OPTION_LOWER_EQUAL),
            );
        }
        if ([] === $places) {
            return [];
        }

        $criteria = [
            'published' => true,
            Group::of(Parser::SEPARATOR_OR, ...$places),
            Group::of(Parser::SEPARATOR_AND, Criterion::of('maxGuests', $query->guests->count(), Parser::OPTION_GREATER_EQUAL)),
        ];
        if ($query->kinds) {
            $criteria['kind'] = array_map(static fn ($k) => $k->value, $query->kinds);
        }

        /** @var list<Listing> */
        return $this->findBy($criteria, ['rating' => 'DESC', 'id' => 'ASC'], $max);
    }

    /** @return list<Listing> */
    public function findPublished(int $limit = 12): array
    {
        /** @var list<Listing> */
        return $this->findBy(['published' => true], ['rating' => 'DESC', 'id' => 'ASC'], $limit);
    }

    /** @return list<Listing> */
    public function findByHost(HostProfile $host): array
    {
        return $this->findBy(['host' => Key::of($host)], ['id' => 'ASC']);
    }
}
