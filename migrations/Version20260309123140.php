<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309123140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lier DROP FOREIGN KEY `FK_B133E8FA8E67F7DC`');
        $this->addSql('ALTER TABLE lier DROP FOREIGN KEY `FK_B133E8FAF4420F7A`');
        $this->addSql('DROP TABLE lier');
        $this->addSql('ALTER TABLE garage ADD is_valide TINYINT DEFAULT 0 NOT NULL, CHANGE nom_garage nom_garage VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY `proposer_id_prestation_FK`');
        $this->addSql('DROP INDEX proposer_id_prestation_fk ON proposer');
        $this->addSql('CREATE INDEX IDX_21866C15F4420F7A ON proposer (id_prestation)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT `proposer_id_prestation_FK` FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
        $this->addSql('ALTER TABLE prestation ADD categorie_prestation VARCHAR(30) NOT NULL, ADD prerequis LONGTEXT NOT NULL, ADD ressource_requise LONGTEXT NOT NULL, CHANGE nom_prestation nom_prestation VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lier (id_prestation INT NOT NULL, id_rdv INT NOT NULL, INDEX IDX_B133E8FAF4420F7A (id_prestation), INDEX IDX_B133E8FA8E67F7DC (id_rdv), PRIMARY KEY (id_prestation, id_rdv)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE lier ADD CONSTRAINT `FK_B133E8FA8E67F7DC` FOREIGN KEY (id_rdv) REFERENCES rendez_vous (id_rdv)');
        $this->addSql('ALTER TABLE lier ADD CONSTRAINT `FK_B133E8FAF4420F7A` FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
        $this->addSql('ALTER TABLE garage DROP is_valide, CHANGE nom_garage nom_garage VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE prestation DROP categorie_prestation, DROP prerequis, DROP ressource_requise, CHANGE nom_prestation nom_prestation VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY FK_21866C15F4420F7A');
        $this->addSql('DROP INDEX idx_21866c15f4420f7a ON proposer');
        $this->addSql('CREATE INDEX proposer_id_prestation_FK ON proposer (id_prestation)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT FK_21866C15F4420F7A FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
    }
}
