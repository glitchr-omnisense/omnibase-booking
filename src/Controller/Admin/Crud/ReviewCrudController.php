<?php

namespace Base\Booking\Controller\Admin\Crud;

use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Booking\Controller\Admin\AbstractBookingCrudController;
use Base\Booking\Entity\Review;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\IntegerField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/** Reviews both ways: read, and hidden when moderation asks; never rewritten. */
class ReviewCrudController extends AbstractBookingCrudController
{
    public static function getEntityFqcn(): string { return Review::class; }
    public static function getPreferredIcon(): ?string { return 'fa-solid fa-star-half-stroke'; }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('booking', '@booking.admin.review.booking')->hideOnForm();
        yield TextField::new('direction', '@booking.admin.review.direction')->hideOnForm()->formatValue(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);
        yield AssociationField::new('author', '@booking.admin.review.author')->hideOnForm();
        yield IntegerField::new('rating', '@booking.admin.review.rating')->hideOnForm();
        yield TextareaField::new('comment', '@booking.admin.review.comment')->hideOnForm();
        yield BooleanField::new('hidden', '@booking.admin.review.hidden');
    }
}
