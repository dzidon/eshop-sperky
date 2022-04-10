<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220409220538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ ADD address_billing_name_first VARCHAR(255) DEFAULT NULL, ADD address_billing_name_last VARCHAR(255) DEFAULT NULL, ADD address_billing_country VARCHAR(32) DEFAULT NULL, ADD address_billing_street VARCHAR(255) DEFAULT NULL, ADD address_billing_additional_info VARCHAR(255) DEFAULT NULL, ADD address_billing_town VARCHAR(255) DEFAULT NULL, ADD address_billing_zip VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ DROP address_billing_name_first, DROP address_billing_name_last, DROP address_billing_country, DROP address_billing_street, DROP address_billing_additional_info, DROP address_billing_town, DROP address_billing_zip');
    }
}
