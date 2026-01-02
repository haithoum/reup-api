<?php

namespace App\Controller\Admin;

use App\Entity\MerchantProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class MerchantProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MerchantProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil Commerçant')
            ->setEntityLabelInPlural('Profils Commerçants')
            ->setSearchFields(['firstName', 'lastName', 'shopName', 'siret', 'publicId'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('publicId', 'ID Public')->hideOnForm();

        yield AssociationField::new('user', 'Utilisateur');

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('shopName', 'Nom du commerce');
        yield AssociationField::new('category', 'Catégorie');
        yield TextField::new('siret', 'SIRET')->hideOnIndex();

        yield ChoiceField::new('validationStatus', 'Statut validation')
            ->setChoices([
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Rejeté' => 'rejected',
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ]);

        yield TextField::new('addressStreet', 'Adresse')->hideOnIndex();
        yield AssociationField::new('addressCity', 'Ville');
        yield TextField::new('addressPostalCode', 'Code postal')->hideOnIndex();

        yield NumberField::new('latitude', 'Latitude')->hideOnIndex();
        yield NumberField::new('longitude', 'Longitude')->hideOnIndex();
        yield TextField::new('phoneNumber', 'Téléphone')->hideOnIndex();

        yield NumberField::new('maxStorageCapacityKg', 'Capacité stockage (kg)')->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('validationStatus')->setChoices([
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Rejeté' => 'rejected',
            ]))
            ->add('category')
            ->add('addressCity');
    }
}

