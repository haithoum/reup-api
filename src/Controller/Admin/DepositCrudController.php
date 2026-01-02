<?php

namespace App\Controller\Admin;

use App\Entity\Deposit;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DepositCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Deposit::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Dépôt')
            ->setEntityLabelInPlural('Dépôts')
            ->setSearchFields(['uuid', 'citizenUser.email', 'merchantUser.email'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('uuid', 'UUID')->hideOnForm();

        yield AssociationField::new('citizenUser', 'Citoyen');

        yield AssociationField::new('merchantUser', 'Commerçant');

        yield NumberField::new('weightKg', 'Poids (kg)')
            ->setNumDecimals(2);

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'PENDING',
                'Accepté' => 'ACCEPTED',
                'Refusé' => 'REFUSED',
                'Annulé' => 'CANCELLED',
            ])
            ->renderAsBadges([
                'PENDING' => 'warning',
                'ACCEPTED' => 'success',
                'REFUSED' => 'danger',
                'CANCELLED' => 'secondary',
            ]);

        yield DateTimeField::new('createdAt', 'Créé le');
        yield DateTimeField::new('acceptedAt', 'Accepté le')->hideOnIndex();
        yield DateTimeField::new('refusedAt', 'Refusé le')->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->onlyOnDetail();
    }
}

