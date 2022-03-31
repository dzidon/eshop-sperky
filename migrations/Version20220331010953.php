<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331010953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, price_without_vat DOUBLE PRECISION NOT NULL, price_with_vat DOUBLE PRECISION NOT NULL, vat DOUBLE PRECISION NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_7B61A1F68CDE5729 (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_ ADD payment_method_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_ ADD CONSTRAINT FK_D7F7910D5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D7F7910D5AA1164F ON order_ (payment_method_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ DROP FOREIGN KEY FK_D7F7910D5AA1164F');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP INDEX IDX_D7F7910D5AA1164F ON order_');
        $this->addSql('ALTER TABLE order_ DROP payment_method_id');
    }
}
