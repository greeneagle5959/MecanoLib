<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417081404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE associer ADD id_garage INT NOT NULL');
        $this->addSql('ALTER TABLE associer ADD CONSTRAINT FK_FA230DB9B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('CREATE INDEX IDX_FA230DB9B911D4E6 ON associer (id_garage)');
        $this->addSql('ALTER TABLE garage CHANGE nom_garage nom_garage VARCHAR(50) NOT NULL, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL');
        $this->addSql('ALTER TABLE proposer RENAME INDEX proposer_id_prestation_fk TO IDX_21866C15F4420F7A');
        $this->addSql('ALTER TABLE vehicule CHANGE id_modele id_modele INT NOT NULL');
        $this->addSql('ALTER TABLE vehicule RENAME INDEX fk_vehicule_modele TO IDX_292FFF1D363530B5');
        $this->addSql('ALTER TABLE ville CHANGE nom_ville nom_ville VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE associer DROP FOREIGN KEY FK_FA230DB9B911D4E6');
        $this->addSql('DROP INDEX IDX_FA230DB9B911D4E6 ON associer');
        $this->addSql('ALTER TABLE associer DROP id_garage');
        $this->addSql('ALTER TABLE garage CHANGE nom_garage nom_garage VARCHAR(20) NOT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL');
        $this->addSql('ALTER TABLE proposer RENAME INDEX idx_21866c15f4420f7a TO proposer_id_prestation_FK');
        $this->addSql('ALTER TABLE vehicule CHANGE id_modele id_modele INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicule RENAME INDEX idx_292fff1d363530b5 TO FK_VEHICULE_MODELE');
        $this->addSql('ALTER TABLE ville CHANGE nom_ville nom_ville VARCHAR(60) NOT NULL');
    }
}
