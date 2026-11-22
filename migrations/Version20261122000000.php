<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261122000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE carton (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, numero VARCHAR(255) NOT NULL, emplacement VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE categorie_objet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE mouvement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATETIME NOT NULL, sens VARCHAR(255) NOT NULL, objet_id INTEGER NOT NULL, utilisateur_id INTEGER NOT NULL, CONSTRAINT FK_5B51FC3EF520CF5A FOREIGN KEY (objet_id) REFERENCES objet (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5B51FC3EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5B51FC3EF520CF5A ON mouvement (objet_id)');
        $this->addSql('CREATE INDEX IDX_5B51FC3EFB88E14F ON mouvement (utilisateur_id)');
        $this->addSql('CREATE TABLE objet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, categorie_id INTEGER DEFAULT NULL, carton_id INTEGER DEFAULT NULL, CONSTRAINT FK_46CD4C38BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_objet (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_46CD4C382D79FBB1 FOREIGN KEY (carton_id) REFERENCES carton (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_46CD4C38BCF5E72D ON objet (categorie_id)');
        $this->addSql('CREATE INDEX IDX_46CD4C382D79FBB1 ON objet (carton_id)');
        $this->addSql('CREATE TABLE plein (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, quantite INTEGER NOT NULL, prix INTEGER NOT NULL, kilometrage INTEGER NOT NULL, voiture_id INTEGER NOT NULL, station_id INTEGER DEFAULT NULL, CONSTRAINT FK_1E29CA13181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1E29CA1321BDB235 FOREIGN KEY (station_id) REFERENCES station (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1E29CA13181A8BA ON plein (voiture_id)');
        $this->addSql('CREATE INDEX IDX_1E29CA1321BDB235 ON plein (station_id)');
        $this->addSql('CREATE TABLE station (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(30) NOT NULL, ville VARCHAR(30) NOT NULL)');
        $this->addSql('CREATE TABLE utilisateur (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('CREATE TABLE voiture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, marque VARCHAR(30) NOT NULL, modele VARCHAR(30) NOT NULL, immatriculation VARCHAR(10) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE carton');
        $this->addSql('DROP TABLE categorie_objet');
        $this->addSql('DROP TABLE mouvement');
        $this->addSql('DROP TABLE objet');
        $this->addSql('DROP TABLE plein');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE voiture');
    }
}
