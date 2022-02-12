<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220212010356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_category DROP FOREIGN KEY FK_CDFC7356FDAB6F6F');
        $this->addSql('ALTER TABLE product_category ADD CONSTRAINT FK_CDFC7356FDAB6F6F FOREIGN KEY (product_category_group_id) REFERENCES product_category_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_category DROP FOREIGN KEY FK_CDFC7356FDAB6F6F');
        $this->addSql('ALTER TABLE product_category ADD CONSTRAINT FK_CDFC7356FDAB6F6F FOREIGN KEY (product_category_group_id) REFERENCES product_category_group (id)');
    }
}
