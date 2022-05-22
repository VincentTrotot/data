<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519160838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plein ALTER date SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE voiture ADD modele VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE voiture ADD immatriculation VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE voiture RENAME COLUMN nom TO marque');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE voiture ADD nom VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE voiture DROP marque');
        $this->addSql('ALTER TABLE voiture DROP modele');
        $this->addSql('ALTER TABLE voiture DROP immatriculation');
        $this->addSql('ALTER TABLE plein ALTER date DROP DEFAULT');
    }
}
