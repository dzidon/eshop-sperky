<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331233652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ ADD delivery_price_without_vat DOUBLE PRECISION NOT NULL, ADD delivery_price_with_vat DOUBLE PRECISION NOT NULL, ADD payment_price_without_vat DOUBLE PRECISION NOT NULL, ADD payment_price_with_vat DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_ DROP delivery_price_without_vat, DROP delivery_price_with_vat, DROP payment_price_without_vat, DROP payment_price_with_vat');
    }
}
