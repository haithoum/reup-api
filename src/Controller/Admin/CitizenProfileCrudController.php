<?php

namespace App\Controller\Admin;

use App\Entity\CitizenProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CitizenProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CitizenProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil Citoyen')
            ->setEntityLabelInPlural('Profils Citoyens')
            ->setSearchFields(['firstName', 'lastName', 'phoneNumber', 'publicId'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('publicId', 'ID Public')->hideOnForm();

        yield AssociationField::new('user', 'Utilisateur');

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('phoneNumber', 'Téléphone');
        yield DateField::new('dateOfBirth', 'Date de naissance');

        yield TextField::new('addressStreet', 'Adresse')->hideOnIndex();
        yield AssociationField::new('addressCity', 'Ville');
        yield TextField::new('addressPostalCode', 'Code postal')->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
    }
}

