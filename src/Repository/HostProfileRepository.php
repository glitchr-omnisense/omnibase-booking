<?php

namespace Base\Booking\Repository;

use Base\Booking\Entity\HostProfile;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Entity\User;

/** @extends ServiceEntityRepository<HostProfile> */
class HostProfileRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, HostProfile::class);
    }

    public function findOneByUser(User $user): ?HostProfile
    {
        return $this->findOneBy(['user' => Key::of($user)]);
    }
}
