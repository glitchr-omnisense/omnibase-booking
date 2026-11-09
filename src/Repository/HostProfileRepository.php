<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HostProfile> */
class HostProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HostProfile::class);
    }

    public function findOneByUser(User $user): ?HostProfile
    {
        return $this->findOneBy(['user' => $user]);
    }
}
