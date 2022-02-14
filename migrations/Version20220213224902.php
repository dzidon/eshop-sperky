<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220213224902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_information ADD product_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_information ADD CONSTRAINT FK_8556869C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8556869C4584665A ON product_information (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_information DROP FOREIGN KEY FK_8556869C4584665A');
        $this->addSql('DROP INDEX IDX_8556869C4584665A ON product_information');
        $this->addSql('ALTER TABLE product_information DROP product_id');
    }
}
