<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220212010849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_information DROP FOREIGN KEY FK_8556869C6719F8B6');
        $this->addSql('ALTER TABLE product_information ADD CONSTRAINT FK_8556869C6719F8B6 FOREIGN KEY (product_information_group_id) REFERENCES product_information_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_information DROP FOREIGN KEY FK_8556869C6719F8B6');
        $this->addSql('ALTER TABLE product_information ADD CONSTRAINT FK_8556869C6719F8B6 FOREIGN KEY (product_information_group_id) REFERENCES product_information_group (id)');
    }
}
