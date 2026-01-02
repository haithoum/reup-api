<?php

namespace App\Controller\Admin;

use App\Entity\Stock;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class StockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Stock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Stock')
            ->setEntityLabelInPlural('Stocks')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield AssociationField::new('merchantUser', 'Commerçant');

        yield NumberField::new('availableKg', 'Quantité disponible (kg)')
            ->setNumDecimals(2);

        yield NumberField::new('alertThresholdKg', 'Seuil d\'alerte (kg)')
            ->setNumDecimals(2)
            ->hideOnIndex();

        yield DateTimeField::new('lastRecoveryAt', 'Dernière récupération')->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Modifié le');
    }
}

