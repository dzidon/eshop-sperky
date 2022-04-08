<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220408004257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ ADD email VARCHAR(180) DEFAULT NULL, CHANGE address_delivery_country address_delivery_country VARCHAR(32) DEFAULT NULL, CHANGE address_delivery_street address_delivery_street VARCHAR(255) DEFAULT NULL, CHANGE address_delivery_town address_delivery_town VARCHAR(255) DEFAULT NULL, CHANGE address_delivery_zip address_delivery_zip VARCHAR(5) DEFAULT NULL, CHANGE address_delivery_name_first address_delivery_name_first VARCHAR(255) DEFAULT NULL, CHANGE address_delivery_name_last address_delivery_name_last VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ DROP email, CHANGE address_delivery_name_first address_delivery_name_first VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery_name_last address_delivery_name_last VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery_country address_delivery_country VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery_street address_delivery_street VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery_town address_delivery_town VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery_zip address_delivery_zip VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
