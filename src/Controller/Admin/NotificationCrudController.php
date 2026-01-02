<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NotificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Notification')
            ->setEntityLabelInPlural('Notifications')
            ->setSearchFields(['title', 'message'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield AssociationField::new('user', 'Utilisateur');

        yield TextField::new('title', 'Titre');
        yield TextareaField::new('message', 'Message')->hideOnIndex();

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Info' => 'info',
                'Succès' => 'success',
                'Avertissement' => 'warning',
                'Erreur' => 'error',
            ])
            ->renderAsBadges([
                'info' => 'primary',
                'success' => 'success',
                'warning' => 'warning',
                'error' => 'danger',
            ]);

        yield BooleanField::new('isRead', 'Lu');

        yield DateTimeField::new('readAt', 'Lu le')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
    }
}

