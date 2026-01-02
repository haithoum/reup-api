<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class AuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log d\'audit')
            ->setEntityLabelInPlural('Logs d\'audit')
            ->setSearchFields(['action', 'entityType', 'entityId'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield AssociationField::new('user', 'Utilisateur');

        yield TextField::new('action', 'Action');
        yield TextField::new('entityType', 'Type d\'entité')->hideOnIndex();
        yield TextField::new('entityId', 'ID entité')->hideOnIndex();
        yield TextField::new('ipAddress', 'Adresse IP')->hideOnIndex();
        yield TextField::new('userAgent', 'User Agent')->onlyOnDetail();
        yield TextareaField::new('changes', 'Changements')->onlyOnDetail();

        yield DateTimeField::new('createdAt', 'Créé le');
    }
}

