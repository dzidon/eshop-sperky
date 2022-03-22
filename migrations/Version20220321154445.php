<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220321154445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE _product_optiongroup (product_id INT NOT NULL, product_option_group_id INT NOT NULL, INDEX IDX_8B829D24584665A (product_id), INDEX IDX_8B829D2ECA8AD7E (product_option_group_id), PRIMARY KEY(product_id, product_option_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_option_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE _product_optiongroup ADD CONSTRAINT FK_8B829D24584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _product_optiongroup ADD CONSTRAINT FK_8B829D2ECA8AD7E FOREIGN KEY (product_option_group_id) REFERENCES product_option_group (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE _product_option');
        $this->addSql('DROP TABLE product_option_parameter');
        $this->addSql('ALTER TABLE product_option ADD product_option_group_id INT NOT NULL, DROP type, DROP is_configured');
        $this->addSql('ALTER TABLE product_option ADD CONSTRAINT FK_38FA4114ECA8AD7E FOREIGN KEY (product_option_group_id) REFERENCES product_option_group (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_38FA4114ECA8AD7E ON product_option (product_option_group_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _product_optiongroup DROP FOREIGN KEY FK_8B829D2ECA8AD7E');
        $this->addSql('ALTER TABLE product_option DROP FOREIGN KEY FK_38FA4114ECA8AD7E');
        $this->addSql('CREATE TABLE _product_option (product_id INT NOT NULL, product_option_id INT NOT NULL, INDEX IDX_E4831C9B4584665A (product_id), INDEX IDX_E4831C9BC964ABE2 (product_option_id), PRIMARY KEY(product_id, product_option_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE product_option_parameter (id INT AUTO_INCREMENT NOT NULL, product_option_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, value VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_7580E56CC964ABE2 (product_option_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE _product_option ADD CONSTRAINT FK_E4831C9B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _product_option ADD CONSTRAINT FK_E4831C9BC964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_option_parameter ADD CONSTRAINT FK_7580E56CC964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE _product_optiongroup');
        $this->addSql('DROP TABLE product_option_group');
        $this->addSql('DROP INDEX IDX_38FA4114ECA8AD7E ON product_option');
        $this->addSql('ALTER TABLE product_option ADD type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD is_configured TINYINT(1) NOT NULL, DROP product_option_group_id');
    }
}
