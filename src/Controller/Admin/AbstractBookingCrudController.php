<?php

namespace Base\Booking\Controller\Admin;

use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * The booking screens: run by the site's administrators (ROLE_ADMIN), not
 * only the super-administrator the admin bundle asks by default for
 * anything that writes. Labels are this bundle's translations
 * (booking.admin.<screen>).
 */
abstract class AbstractBookingCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        $screen = strtolower((string) preg_replace('~(?<!^)[A-Z]~', '_$0', (new \ReflectionClass(static::getEntityFqcn()))->getShortName()));

        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('@booking.admin.'.$screen.'.singular')
            ->setEntityLabelInPlural('@booking.admin.'.$screen.'.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)->setPermissions(array_fill_keys(
            [Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE, Action::SAVE_AND_RETURN, Action::SAVE_AND_CONTINUE, Action::SAVE_AND_ADD_ANOTHER],
            'ROLE_ADMIN',
        ));
    }

    /** @param class-string<\BackedEnum> $enum */
    protected function enum(string $property, string $label, string $enum): TextField
    {
        return TextField::new($property, $label)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => $enum])
            ->formatValue(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);
    }

    /** Minor units of the record's currency, shown as decimals. */
    protected static function amount(?int $minor): string
    {
        return null === $minor ? '—' : number_format($minor / 100, 2, '.', ' ');
    }
}
