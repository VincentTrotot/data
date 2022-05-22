<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519160415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE plein_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE voiture_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE plein (id INT NOT NULL, voiture_id INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, quantite INT NOT NULL, prix INT NOT NULL, kilometrage INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1E29CA13181A8BA ON plein (voiture_id)');
        $this->addSql('CREATE TABLE voiture (id INT NOT NULL, nom VARCHAR(30) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE plein ADD CONSTRAINT FK_1E29CA13181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE plein DROP CONSTRAINT FK_1E29CA13181A8BA');
        $this->addSql('DROP SEQUENCE plein_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE voiture_id_seq CASCADE');
        $this->addSql('DROP TABLE plein');
        $this->addSql('DROP TABLE voiture');
    }
}
