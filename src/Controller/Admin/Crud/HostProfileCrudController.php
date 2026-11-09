<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\HostProfile;
use Base\Field\AssociationField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/** The hosts: the member, the name guests see, a few words, the payout account's end. */
class HostProfileCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return HostProfile::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-key'; }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', '@booking.admin.host_profile.user')->setColumns(4);
        yield TextField::new('displayName', '@booking.admin.host_profile.display_name')->setColumns(4);
        yield TextareaField::new('about', '@booking.admin.host_profile.about')->setRequired(false)->hideOnIndex();
        yield TextField::new('payoutAccountEnd', '@booking.admin.host_profile.payout_account')->hideOnForm();
    }
}
