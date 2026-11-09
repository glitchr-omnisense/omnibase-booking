<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Message;
use Base\Field\AssociationField;
use Base\Field\DateTimeField;
use Base\Field\TextareaField;

/** Guests' and hosts' messages, read-only: for the site's team when a stay goes wrong. */
class MessageCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Message::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-comments'; }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->disable(Action::NEW, Action::EDIT, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('booking', '@booking.admin.message.booking');
        yield AssociationField::new('sender', '@booking.admin.message.sender');
        yield TextareaField::new('body', '@booking.admin.message.body');
        yield DateTimeField::new('sentAt', '@booking.admin.message.sent_at');
    }
}
