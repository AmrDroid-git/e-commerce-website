<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530134704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__product AS SELECT id, name, price, rating, image_url, description, quantity FROM product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(6, 2) NOT NULL, rating INTEGER NOT NULL, image_url VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, quantity INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO product (id, name, price, rating, image_url, description, quantity) SELECT id, name, price, rating, image_url, description, quantity FROM __temp__product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__product
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__product AS SELECT id, name, price, rating, image_url, quantity, description FROM product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(6, 2) NOT NULL, rating INTEGER NOT NULL, image_url VARCHAR(255) NOT NULL, quantity INTEGER DEFAULT 0 NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO product (id, name, price, rating, image_url, quantity, description) SELECT id, name, price, rating, image_url, quantity, description FROM __temp__product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__product
        SQL);
    }
}
