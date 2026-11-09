<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Booking;
use Base\Field\AssociationField;
use Base\Field\DateField;
use Base\Field\DateTimeField;
use Base\Field\IntegerField;
use Base\Field\TextField;

/**
 * The bookings, read-only: a booking changes through its flow (payment,
 * the host's answer, a cancellation), never by hand.
 */
class BookingCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Booking::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-calendar-check'; }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', '@booking.admin.booking.reference');
        yield TextField::new('propertyName', '@booking.admin.booking.property');
        yield AssociationField::new('guest', '@booking.admin.booking.guest');
        yield DateField::new('checkIn', '@booking.admin.booking.check_in');
        yield DateField::new('checkOut', '@booking.admin.booking.check_out');
        yield TextField::new('status', '@booking.admin.booking.status')->formatValue(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);
        yield IntegerField::new('total', '@booking.admin.booking.total')->formatValue(fn ($value, ?Booking $b) => self::amount($b?->getTotal()).' '.$b?->getCurrency());
        yield TextField::new('paymentState', '@booking.admin.booking.payment')->formatValue(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);
        yield TextField::new('provider', '@booking.admin.booking.provider')->hideOnIndex();
        yield TextField::new('paymentReference', '@booking.admin.booking.payment_reference')->hideOnIndex();
        yield DateTimeField::new('createdAt', '@booking.admin.booking.created_at')->hideOnIndex();
    }
}
