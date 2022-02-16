<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220216173523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D69A75A05E237E06 ON product_category_group (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA62B2465E237E06 ON product_information_group (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D34A04AD989D9B62 ON product');
        $this->addSql('DROP INDEX UNIQ_D69A75A05E237E06 ON product_category_group');
        $this->addSql('DROP INDEX UNIQ_BA62B2465E237E06 ON product_information_group');
    }
}
