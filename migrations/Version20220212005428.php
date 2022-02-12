<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220212005428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_option_parameter DROP FOREIGN KEY FK_7580E56CC964ABE2');
        $this->addSql('ALTER TABLE product_option_parameter ADD CONSTRAINT FK_7580E56CC964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_option_parameter DROP FOREIGN KEY FK_7580E56CC964ABE2');
        $this->addSql('ALTER TABLE product_option_parameter ADD CONSTRAINT FK_7580E56CC964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id)');
    }
}
