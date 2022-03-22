<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220322010835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE _cartoccurence_productoption (cart_occurence_id INT NOT NULL, product_option_id INT NOT NULL, INDEX IDX_566514E55021E3A4 (cart_occurence_id), INDEX IDX_566514E5C964ABE2 (product_option_id), PRIMARY KEY(cart_occurence_id, product_option_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE _cartoccurence_productoption ADD CONSTRAINT FK_566514E55021E3A4 FOREIGN KEY (cart_occurence_id) REFERENCES cart_occurence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _cartoccurence_productoption ADD CONSTRAINT FK_566514E5C964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_occurence DROP options');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE _cartoccurence_productoption');
        $this->addSql('ALTER TABLE cart_occurence ADD options VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
