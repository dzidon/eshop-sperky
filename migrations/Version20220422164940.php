<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220422164940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, country VARCHAR(32) NOT NULL, street VARCHAR(255) NOT NULL, town VARCHAR(255) NOT NULL, zip VARCHAR(5) NOT NULL, company VARCHAR(255) DEFAULT NULL, ic VARCHAR(8) DEFAULT NULL, dic VARCHAR(12) DEFAULT NULL, alias VARCHAR(255) NOT NULL, additional_info VARCHAR(255) DEFAULT NULL, name_first VARCHAR(255) NOT NULL, name_last VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_D4E6F81A76ED395 (user_id), INDEX search_idx (alias, created), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cart_occurence (id INT AUTO_INCREMENT NOT NULL, order__id INT NOT NULL, product_id INT DEFAULT NULL, quantity INT NOT NULL, price_without_vat DOUBLE PRECISION NOT NULL, price_with_vat DOUBLE PRECISION NOT NULL, name VARCHAR(255) NOT NULL, options_string VARCHAR(500) DEFAULT NULL, INDEX IDX_BE53EF11251A8A50 (order__id), INDEX IDX_BE53EF114584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _cartoccurence_productoption (cart_occurence_id INT NOT NULL, product_option_id INT NOT NULL, INDEX IDX_566514E55021E3A4 (cart_occurence_id), INDEX IDX_566514E5C964ABE2 (product_option_id), PRIMARY KEY(cart_occurence_id, product_option_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE delivery_method (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, price_without_vat DOUBLE PRECISION NOT NULL, price_with_vat DOUBLE PRECISION NOT NULL, vat DOUBLE PRECISION NOT NULL, locks_delivery_address TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_4048C3EE8CDE5729 (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_ (id INT AUTO_INCREMENT NOT NULL, delivery_method_id INT DEFAULT NULL, payment_method_id INT DEFAULT NULL, user_id INT DEFAULT NULL, token BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', expire_at DATETIME DEFAULT NULL, created_manually TINYINT(1) NOT NULL, cash_on_delivery DOUBLE PRECISION DEFAULT NULL, finished_at DATETIME DEFAULT NULL, lifecycle_chapter INT NOT NULL, delivery_price_without_vat DOUBLE PRECISION NOT NULL, delivery_price_with_vat DOUBLE PRECISION NOT NULL, payment_price_without_vat DOUBLE PRECISION NOT NULL, payment_price_with_vat DOUBLE PRECISION NOT NULL, delivery_method_name VARCHAR(255) DEFAULT NULL, payment_method_name VARCHAR(255) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone_number VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\', address_delivery_locked TINYINT(1) NOT NULL, address_delivery_name_first VARCHAR(255) DEFAULT NULL, address_delivery_name_last VARCHAR(255) DEFAULT NULL, address_delivery_country VARCHAR(32) DEFAULT NULL, address_delivery_street VARCHAR(255) DEFAULT NULL, address_delivery_town VARCHAR(255) DEFAULT NULL, address_delivery_zip VARCHAR(5) DEFAULT NULL, address_delivery_additional_info VARCHAR(255) DEFAULT NULL, address_billing_company VARCHAR(255) DEFAULT NULL, address_billing_ic VARCHAR(8) DEFAULT NULL, address_billing_dic VARCHAR(12) DEFAULT NULL, address_billing_name_first VARCHAR(255) DEFAULT NULL, address_billing_name_last VARCHAR(255) DEFAULT NULL, address_billing_country VARCHAR(32) DEFAULT NULL, address_billing_street VARCHAR(255) DEFAULT NULL, address_billing_additional_info VARCHAR(255) DEFAULT NULL, address_billing_town VARCHAR(255) DEFAULT NULL, address_billing_zip VARCHAR(5) DEFAULT NULL, note VARCHAR(500) DEFAULT NULL, cancellation_reason VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_D7F7910D5DED75F5 (delivery_method_id), INDEX IDX_D7F7910D5AA1164F (payment_method_id), INDEX IDX_D7F7910DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, price_without_vat DOUBLE PRECISION NOT NULL, price_with_vat DOUBLE PRECISION NOT NULL, vat DOUBLE PRECISION NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_7B61A1F68CDE5729 (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_E04992AA77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, section_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, price_without_vat DOUBLE PRECISION NOT NULL, price_with_vat DOUBLE PRECISION NOT NULL, vat DOUBLE PRECISION NOT NULL, description_short VARCHAR(250) DEFAULT NULL, description VARCHAR(4096) DEFAULT NULL, is_hidden TINYINT(1) NOT NULL, hide_when_sold_out TINYINT(1) NOT NULL, available_since DATETIME DEFAULT NULL, inventory INT NOT NULL, main_image_name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04ADD823E37A (section_id), INDEX search_idx (name, created, price_with_vat, inventory), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _product_category (product_id INT NOT NULL, product_category_id INT NOT NULL, INDEX IDX_643D06E24584665A (product_id), INDEX IDX_643D06E2BE6903FD (product_category_id), PRIMARY KEY(product_id, product_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _product_optiongroup (product_id INT NOT NULL, product_option_group_id INT NOT NULL, INDEX IDX_8B829D24584665A (product_id), INDEX IDX_8B829D2ECA8AD7E (product_option_group_id), PRIMARY KEY(product_id, product_option_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_category (id INT AUTO_INCREMENT NOT NULL, product_category_group_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_CDFC7356FDAB6F6F (product_category_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_category_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_D69A75A05E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_image (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, priority DOUBLE PRECISION NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_64617F034584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_information (id INT AUTO_INCREMENT NOT NULL, product_information_group_id INT NOT NULL, product_id INT NOT NULL, value VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_8556869C6719F8B6 (product_information_group_id), INDEX IDX_8556869C4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_information_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_BA62B2465E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_option (id INT AUTO_INCREMENT NOT NULL, product_option_group_id INT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_38FA4114ECA8AD7E (product_option_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_option_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_section (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, is_hidden TINYINT(1) NOT NULL, available_since DATETIME DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_FCAA615F989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, stars DOUBLE PRECISION NOT NULL, text VARCHAR(1000) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_794381C6A76ED395 (user_id), INDEX search_idx (created, stars), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, facebook_id VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, verify_link_last_sent DATETIME DEFAULT NULL, registered DATETIME NOT NULL, name_first VARCHAR(255) DEFAULT NULL, name_last VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\', is_muted TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _user_permission (user_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_3AF5B3AAA76ED395 (user_id), INDEX IDX_3AF5B3AAFED90CCA (permission_id), PRIMARY KEY(user_id, permission_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_occurence ADD CONSTRAINT FK_BE53EF11251A8A50 FOREIGN KEY (order__id) REFERENCES order_ (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_occurence ADD CONSTRAINT FK_BE53EF114584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE _cartoccurence_productoption ADD CONSTRAINT FK_566514E55021E3A4 FOREIGN KEY (cart_occurence_id) REFERENCES cart_occurence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _cartoccurence_productoption ADD CONSTRAINT FK_566514E5C964ABE2 FOREIGN KEY (product_option_id) REFERENCES product_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_ ADD CONSTRAINT FK_D7F7910D5DED75F5 FOREIGN KEY (delivery_method_id) REFERENCES delivery_method (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_ ADD CONSTRAINT FK_D7F7910D5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_ ADD CONSTRAINT FK_D7F7910DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADD823E37A FOREIGN KEY (section_id) REFERENCES product_section (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE _product_category ADD CONSTRAINT FK_643D06E24584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _product_category ADD CONSTRAINT FK_643D06E2BE6903FD FOREIGN KEY (product_category_id) REFERENCES product_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _product_optiongroup ADD CONSTRAINT FK_8B829D24584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _product_optiongroup ADD CONSTRAINT FK_8B829D2ECA8AD7E FOREIGN KEY (product_option_group_id) REFERENCES product_option_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_category ADD CONSTRAINT FK_CDFC7356FDAB6F6F FOREIGN KEY (product_category_group_id) REFERENCES product_category_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_information ADD CONSTRAINT FK_8556869C6719F8B6 FOREIGN KEY (product_information_group_id) REFERENCES product_information_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_information ADD CONSTRAINT FK_8556869C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_option ADD CONSTRAINT FK_38FA4114ECA8AD7E FOREIGN KEY (product_option_group_id) REFERENCES product_option_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _user_permission ADD CONSTRAINT FK_3AF5B3AAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE _user_permission ADD CONSTRAINT FK_3AF5B3AAFED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _cartoccurence_productoption DROP FOREIGN KEY FK_566514E55021E3A4');
        $this->addSql('ALTER TABLE order_ DROP FOREIGN KEY FK_D7F7910D5DED75F5');
        $this->addSql('ALTER TABLE cart_occurence DROP FOREIGN KEY FK_BE53EF11251A8A50');
        $this->addSql('ALTER TABLE order_ DROP FOREIGN KEY FK_D7F7910D5AA1164F');
        $this->addSql('ALTER TABLE _user_permission DROP FOREIGN KEY FK_3AF5B3AAFED90CCA');
        $this->addSql('ALTER TABLE cart_occurence DROP FOREIGN KEY FK_BE53EF114584665A');
        $this->addSql('ALTER TABLE _product_category DROP FOREIGN KEY FK_643D06E24584665A');
        $this->addSql('ALTER TABLE _product_optiongroup DROP FOREIGN KEY FK_8B829D24584665A');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('ALTER TABLE product_information DROP FOREIGN KEY FK_8556869C4584665A');
        $this->addSql('ALTER TABLE _product_category DROP FOREIGN KEY FK_643D06E2BE6903FD');
        $this->addSql('ALTER TABLE product_category DROP FOREIGN KEY FK_CDFC7356FDAB6F6F');
        $this->addSql('ALTER TABLE product_information DROP FOREIGN KEY FK_8556869C6719F8B6');
        $this->addSql('ALTER TABLE _cartoccurence_productoption DROP FOREIGN KEY FK_566514E5C964ABE2');
        $this->addSql('ALTER TABLE _product_optiongroup DROP FOREIGN KEY FK_8B829D2ECA8AD7E');
        $this->addSql('ALTER TABLE product_option DROP FOREIGN KEY FK_38FA4114ECA8AD7E');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADD823E37A');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE order_ DROP FOREIGN KEY FK_D7F7910DA76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('ALTER TABLE _user_permission DROP FOREIGN KEY FK_3AF5B3AAA76ED395');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE cart_occurence');
        $this->addSql('DROP TABLE _cartoccurence_productoption');
        $this->addSql('DROP TABLE delivery_method');
        $this->addSql('DROP TABLE order_');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE _product_category');
        $this->addSql('DROP TABLE _product_optiongroup');
        $this->addSql('DROP TABLE product_category');
        $this->addSql('DROP TABLE product_category_group');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('DROP TABLE product_information');
        $this->addSql('DROP TABLE product_information_group');
        $this->addSql('DROP TABLE product_option');
        $this->addSql('DROP TABLE product_option_group');
        $this->addSql('DROP TABLE product_section');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE _user_permission');
    }
}
