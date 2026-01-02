<?php

namespace App\Controller\Admin;

use App\Entity\RewardRule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RewardRuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RewardRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Règle de récompense')
            ->setEntityLabelInPlural('Règles de récompense')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('uuid', 'UUID')->onlyOnDetail();
        yield TextField::new('name', 'Nom');

        yield ChoiceField::new('scope', 'Portée')
            ->setChoices([
                'Global' => 'GLOBAL',
                'Commerçant spécifique' => 'MERCHANT_SPECIFIC',
                'Catégorie' => 'CATEGORY',
            ]);

        yield NumberField::new('thresholdKg', 'Seuil (kg)')
            ->setHelp('Poids minimum pour déclencher la récompense');

        yield ChoiceField::new('rewardType', 'Type de récompense')
            ->setChoices([
                'Article gratuit' => 'FREE_ITEM',
                'Remise en %' => 'DISCOUNT_PERCENTAGE',
                'Montant fixe' => 'FIXED_AMOUNT',
            ]);

        yield TextField::new('productSku', 'SKU produit')->hideOnIndex();
        yield NumberField::new('valueAmount', 'Valeur')->hideOnIndex();
        yield NumberField::new('expiresInDays', 'Expire dans (jours)');
        yield NumberField::new('maxRedemptionsPerUser', 'Max utilisations/utilisateur')->hideOnIndex();
        yield NumberField::new('totalAvailable', 'Total disponible')->hideOnIndex();

        yield DateTimeField::new('validFrom', 'Valide à partir de')->hideOnIndex();
        yield DateTimeField::new('validUntil', 'Valide jusqu\'au')->hideOnIndex();

        yield BooleanField::new('isActive', 'Actif');

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();
    }
}

