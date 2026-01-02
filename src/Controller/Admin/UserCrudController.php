<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['email', 'uuid', 'googleSub'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('uuid', 'UUID')->hideOnForm();
        yield EmailField::new('email', 'Email');

        yield ChoiceField::new('role', 'Rôle')
            ->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'Citoyen' => 'ROLE_CITIZEN',
                'Commerçant' => 'ROLE_MERCHANT',
                'Acteur Pro' => 'ROLE_PRO',
            ])
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_CITIZEN' => 'primary',
                'ROLE_MERCHANT' => 'success',
                'ROLE_PRO' => 'warning',
            ]);

        yield BooleanField::new('isActive', 'Actif');
        yield TextField::new('googleSub', 'Google ID')->onlyOnIndex();
        yield DateTimeField::new('emailVerifiedAt', 'Email vérifié le')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('role')->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'Citoyen' => 'ROLE_CITIZEN',
                'Commerçant' => 'ROLE_MERCHANT',
                'Acteur Pro' => 'ROLE_PRO',
            ]))
            ->add(BooleanFilter::new('isActive'));
    }
}

