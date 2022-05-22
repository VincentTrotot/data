<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519213439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE station_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE station (id INT NOT NULL, nom VARCHAR(30) NOT NULL, ville VARCHAR(30) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE plein ADD station_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE plein ADD CONSTRAINT FK_1E29CA1321BDB235 FOREIGN KEY (station_id) REFERENCES station (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1E29CA1321BDB235 ON plein (station_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE plein DROP CONSTRAINT FK_1E29CA1321BDB235');
        $this->addSql('DROP SEQUENCE station_id_seq CASCADE');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP INDEX IDX_1E29CA1321BDB235');
        $this->addSql('ALTER TABLE plein DROP station_id');
    }
}
