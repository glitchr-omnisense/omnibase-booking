<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Season;
use Base\Field\AssociationField;
use Base\Field\DateField;
use Base\Field\IntegerField;
use Base\Field\TextField;

/** A listing's seasons: dates, the price of a night then (cents), a longer minimum stay. */
class SeasonCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Season::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-sun'; }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('listing', '@booking.admin.season.listing')->setColumns(4);
        yield TextField::new('name', '@booking.admin.season.name')->setRequired(false)->setColumns(4);
        yield DateField::new('startsOn', '@booking.admin.season.starts_on')->setColumns(2);
        yield DateField::new('endsOn', '@booking.admin.season.ends_on')->setColumns(2);
        yield IntegerField::new('nightlyPrice', '@booking.admin.season.nightly_price')->setColumns(2)->setHelp('@booking.admin.cents');
        yield IntegerField::new('minStay', '@booking.admin.season.min_stay')->setRequired(false)->setColumns(2);
    }
}
