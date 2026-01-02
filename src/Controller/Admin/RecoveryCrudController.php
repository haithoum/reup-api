<?php

namespace App\Controller\Admin;

use App\Entity\Recovery;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RecoveryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recovery::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Récupération')
            ->setEntityLabelInPlural('Récupérations')
            ->setSearchFields(['uuid', 'proUser.email', 'merchantUser.email'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('uuid', 'UUID')->hideOnForm();

        yield AssociationField::new('proUser', 'Acteur Pro');

        yield AssociationField::new('merchantUser', 'Commerçant');

        yield NumberField::new('qtyKg', 'Quantité (kg)')
            ->setNumDecimals(2);

        yield NumberField::new('actualQtyKg', 'Quantité réelle (kg)')
            ->setNumDecimals(2)
            ->hideOnIndex();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'PENDING',
                'Planifié' => 'PLANNED',
                'En cours' => 'IN_PROGRESS',
                'Terminé' => 'COMPLETED',
                'Annulé' => 'CANCELLED',
            ])
            ->renderAsBadges([
                'PENDING' => 'warning',
                'PLANNED' => 'info',
                'IN_PROGRESS' => 'primary',
                'COMPLETED' => 'success',
                'CANCELLED' => 'danger',
            ]);

        yield DateTimeField::new('scheduledAt', 'Planifié le')->hideOnIndex();
        yield DateTimeField::new('startedAt', 'Démarré le')->hideOnIndex();
        yield DateTimeField::new('completedAt', 'Terminé le');
        yield DateTimeField::new('cancelledAt', 'Annulé le')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->onlyOnDetail();
    }
}

