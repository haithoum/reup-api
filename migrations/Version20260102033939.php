<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102033939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pro_profiles ADD address_city_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE pro_profiles ADD address_street VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pro_profiles ADD address_postal_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE pro_profiles ADD CONSTRAINT FK_9AC427AAD0499537 FOREIGN KEY (address_city_id) REFERENCES cities (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9AC427AAD0499537 ON pro_profiles (address_city_id)');
        $this->addSql('ALTER TABLE recoveries ADD actual_qty_kg NUMERIC(8, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE recoveries ADD started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE recoveries ADD cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE recoveries ADD cancellation_reason TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pro_profiles DROP CONSTRAINT FK_9AC427AAD0499537');
        $this->addSql('DROP INDEX IDX_9AC427AAD0499537');
        $this->addSql('ALTER TABLE pro_profiles DROP address_city_id');
        $this->addSql('ALTER TABLE pro_profiles DROP address_street');
        $this->addSql('ALTER TABLE pro_profiles DROP address_postal_code');
        $this->addSql('ALTER TABLE recoveries DROP actual_qty_kg');
        $this->addSql('ALTER TABLE recoveries DROP started_at');
        $this->addSql('ALTER TABLE recoveries DROP cancelled_at');
        $this->addSql('ALTER TABLE recoveries DROP cancellation_reason');
    }
}
