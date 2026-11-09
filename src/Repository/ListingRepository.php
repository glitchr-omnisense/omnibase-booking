<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Booking\Entity\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Omnistay\Model\StayQuery;

/** @extends ServiceEntityRepository<Listing> */
class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
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
        $qb = $this->createQueryBuilder('l')->andWhere('l.published = true');
        $places = [];
        if (null !== $query->place && '' !== trim($query->place)) {
            $places[] = 'LOWER(l.title) LIKE :place OR LOWER(l.city) LIKE :place OR LOWER(l.area) LIKE :place';
            $qb->setParameter('place', '%'.mb_strtolower(trim($query->place)).'%');
        }
        if ($query->hasPoint()) {
            $lat = (float) $query->latitude;
            $lng = (float) $query->longitude;
            $dLat = $query->radius / 111.0;
            $dLng = $query->radius / max(1.0, 111.0 * cos(deg2rad($lat)));
            $places[] = 'l.latitude BETWEEN :latMin AND :latMax AND l.longitude BETWEEN :lngMin AND :lngMax';
            $qb->setParameter('latMin', $lat - $dLat)->setParameter('latMax', $lat + $dLat)->setParameter('lngMin', $lng - $dLng)->setParameter('lngMax', $lng + $dLng);
        }
        if ([] === $places) {
            return [];
        }
        $qb->andWhere('('.implode(') OR (', $places).')');
        if ($query->kinds) {
            $qb->andWhere('l.kind IN (:kinds)')->setParameter('kinds', array_map(static fn ($k) => $k->value, $query->kinds));
        }
        $qb->andWhere('l.maxGuests >= :guests')->setParameter('guests', $query->guests->count());

        /** @var list<Listing> */
        return $qb->orderBy('l.rating', 'DESC')->addOrderBy('l.id', 'ASC')->setMaxResults($max)->getQuery()->getResult();
    }

    /** @return list<Listing> */
    public function findPublished(int $limit = 12): array
    {
        /** @var list<Listing> */
        return $this->createQueryBuilder('l')->andWhere('l.published = true')->orderBy('l.rating', 'DESC')->addOrderBy('l.id', 'ASC')->setMaxResults($limit)->getQuery()->getResult();
    }

    /** @return list<Listing> */
    public function findByHost(HostProfile $host): array
    {
        return $this->findBy(['host' => $host], ['id' => 'ASC']);
    }
}
