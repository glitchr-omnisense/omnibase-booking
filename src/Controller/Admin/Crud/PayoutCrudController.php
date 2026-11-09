<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Payout;
use Base\Field\AssociationField;
use Base\Field\DateField;
use Base\Field\IntegerField;
use Base\Field\TextField;

/** What hosts are owed and were paid; booking:maintain releases the due ones. */
class PayoutCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Payout::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-money-bill-transfer'; }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('host', '@booking.admin.payout.host');
        yield AssociationField::new('booking', '@booking.admin.payout.booking');
        yield IntegerField::new('amount', '@booking.admin.payout.amount')->formatValue(fn ($value, ?Payout $p) => self::amount($p?->getAmount()).' '.$p?->getCurrency());
        yield IntegerField::new('fee', '@booking.admin.payout.fee')->formatValue(fn ($value, ?Payout $p) => self::amount($p?->getFee()));
        yield DateField::new('dueOn', '@booking.admin.payout.due_on');
        yield TextField::new('status', '@booking.admin.payout.status')->formatValue(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);
    }
}
