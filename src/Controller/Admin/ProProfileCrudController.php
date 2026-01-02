<?php

namespace App\Controller\Admin;

use App\Entity\ProProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class ProProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil Acteur Pro')
            ->setEntityLabelInPlural('Profils Acteurs Pro')
            ->setSearchFields(['firstName', 'lastName', 'companyName', 'siret', 'publicId'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('publicId', 'ID Public')->hideOnForm();

        yield AssociationField::new('user', 'Utilisateur');

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('companyName', 'Nom de l\'entreprise');
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
        yield TextField::new('phoneNumber', 'Téléphone')->hideOnIndex();

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
            ->add('addressCity');
    }
}

