<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102010338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs firstName et lastName aux profils MerchantProfile et ProProfile';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE merchant_profiles ADD first_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE merchant_profiles ADD last_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE pro_profiles ADD first_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE pro_profiles ADD last_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE merchant_profiles DROP first_name');
        $this->addSql('ALTER TABLE merchant_profiles DROP last_name');
        $this->addSql('ALTER TABLE pro_profiles DROP first_name');
        $this->addSql('ALTER TABLE pro_profiles DROP last_name');
    }
}

