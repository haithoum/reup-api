<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Coupon')
            ->setEntityLabelInPlural('Coupons')
            ->setSearchFields(['publicId', 'productSku'])
            ->setDefaultSort(['issuedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('publicId', 'ID Public')->hideOnForm();

        yield AssociationField::new('citizenUser', 'Citoyen');

        yield AssociationField::new('merchantUser', 'Commerçant');

        yield AssociationField::new('rewardRule', 'Règle de récompense');

        yield AssociationField::new('issuedFromDeposit', 'Dépôt associé')->hideOnIndex();

        yield TextField::new('productSku', 'SKU Produit')->hideOnIndex();

        yield TextField::new('valueAmount', 'Montant')->hideOnIndex();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Émis' => 'ISSUED',
                'Utilisé' => 'REDEEMED',
                'Expiré' => 'EXPIRED',
                'Annulé' => 'CANCELLED',
            ])
            ->renderAsBadges([
                'ISSUED' => 'success',
                'REDEEMED' => 'primary',
                'EXPIRED' => 'danger',
                'CANCELLED' => 'secondary',
            ]);

        yield DateTimeField::new('issuedAt', 'Émis le');
        yield DateTimeField::new('expiresAt', 'Expire le');
        yield DateTimeField::new('redeemedAt', 'Utilisé le')->hideOnIndex();

        yield AssociationField::new('redeemedByMerchantUser', 'Validé par')->hideOnIndex();
    }
}

