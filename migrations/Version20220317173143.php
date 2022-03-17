<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220317173143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_occurence ADD product_id INT DEFAULT NULL, ADD quantity INT NOT NULL, ADD price_without_vat DOUBLE PRECISION NOT NULL, ADD price_with_vat DOUBLE PRECISION NOT NULL, ADD name VARCHAR(255) NOT NULL, ADD options VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_occurence ADD CONSTRAINT FK_BE53EF114584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BE53EF114584665A ON cart_occurence (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_occurence DROP FOREIGN KEY FK_BE53EF114584665A');
        $this->addSql('DROP INDEX IDX_BE53EF114584665A ON cart_occurence');
        $this->addSql('ALTER TABLE cart_occurence DROP product_id, DROP quantity, DROP price_without_vat, DROP price_with_vat, DROP name, DROP options');
    }
}
