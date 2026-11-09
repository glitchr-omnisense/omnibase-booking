<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\ListingPhoto;
use Base\Field\AssociationField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\TextField;

/** The listings' photographs, their order and their credit. */
class ListingPhotoCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return ListingPhoto::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-images'; }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('listing', '@booking.admin.listing_photo.listing')->setColumns(4);
        yield ImageField::new('image', '@booking.admin.listing_photo.image')->setRequired(false)->setColumns(4);
        yield TextField::new('url', '@booking.admin.listing_photo.url')->setRequired(false)->setColumns(4)->hideOnIndex();
        yield TextField::new('caption', '@booking.admin.listing_photo.caption')->setRequired(false)->setColumns(4);
        yield TextField::new('credit', '@booking.admin.listing_photo.credit')->setRequired(false)->setColumns(4);
        yield IntegerField::new('position', '@booking.admin.listing_photo.position')->setColumns(2);
    }
}
