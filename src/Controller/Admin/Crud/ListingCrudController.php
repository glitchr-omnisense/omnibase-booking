<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Listing;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\NumberField;
use Base\Field\SlugField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Omnistay\Local\CancellationTerms;
use Omnistay\Model\PropertyKind;

/** The places hosts rent out: what, where, how priced (in cents), what they accept, published or not. */
class ListingCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Listing::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-house-chimney-window'; }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('host', '@booking.admin.listing.host')->setColumns(4);
        yield TextField::new('title', '@booking.admin.listing.title')->setColumns(5);
        yield SlugField::new('slug')->setTargetFieldName('title')->setColumns(3)->hideOnIndex();
        yield $this->enum('kind', '@booking.admin.listing.kind', PropertyKind::class)->setColumns(3);
        yield TextField::new('city', '@booking.admin.listing.city')->setColumns(3);
        yield TextField::new('area', '@booking.admin.listing.area')->setRequired(false)->setColumns(3)->hideOnIndex();
        yield TextField::new('countryCode', '@booking.admin.listing.country')->setColumns(1)->hideOnIndex();
        yield TextField::new('address', '@booking.admin.listing.address')->setRequired(false)->setColumns(6)->hideOnIndex();
        yield TextField::new('postcode', '@booking.admin.listing.postcode')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield NumberField::new('latitude', '@booking.admin.listing.latitude')->setRequired(false)->setNumDecimals(6)->setColumns(2)->hideOnIndex();
        yield NumberField::new('longitude', '@booking.admin.listing.longitude')->setRequired(false)->setNumDecimals(6)->setColumns(2)->hideOnIndex();
        yield TextareaField::new('description', '@booking.admin.listing.description')->setRequired(false)->hideOnIndex();
        yield IntegerField::new('maxGuests', '@booking.admin.listing.max_guests')->setColumns(2);
        yield IntegerField::new('bedrooms', '@booking.admin.listing.bedrooms')->setColumns(2)->hideOnIndex();
        yield IntegerField::new('beds', '@booking.admin.listing.beds')->setColumns(2)->hideOnIndex();
        yield TextField::new('currency', '@booking.admin.listing.currency')->setColumns(2)->hideOnIndex();
        yield IntegerField::new('nightlyPrice', '@booking.admin.listing.nightly_price')->setColumns(2)->setHelp('@booking.admin.cents');
        yield IntegerField::new('weekendPrice', '@booking.admin.listing.weekend_price')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('cleaningFee', '@booking.admin.listing.cleaning_fee')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('touristTax', '@booking.admin.listing.tourist_tax')->setRequired(false)->setColumns(2)->hideOnIndex();
        yield IntegerField::new('minStay', '@booking.admin.listing.min_stay')->setColumns(2)->hideOnIndex();
        yield IntegerField::new('weeklyDiscount', '@booking.admin.listing.weekly_discount')->setColumns(2)->hideOnIndex();
        yield $this->enum('cancellation', '@booking.admin.listing.cancellation', CancellationTerms::class)->setColumns(3)->hideOnIndex();
        yield BooleanField::new('instantBook', '@booking.admin.listing.instant_book')->setColumns(2);
        yield BooleanField::new('published', '@booking.admin.listing.published')->setColumns(2);
        yield NumberField::new('rating', '@booking.admin.listing.rating')->hideOnForm();
    }
}
