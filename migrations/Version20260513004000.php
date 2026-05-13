<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513004000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les noms d index proposer/vehicule avec le mapping Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY proposer_id_prestation_FK');
        $this->addSql('DROP INDEX proposer_id_prestation_fk ON proposer');
        $this->addSql('CREATE INDEX IDX_21866C15F4420F7A ON proposer (id_prestation)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT proposer_id_prestation_FK FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');

        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_VEHICULE_MODELE');
        $this->addSql('DROP INDEX fk_vehicule_modele ON vehicule');
        $this->addSql('CREATE INDEX IDX_292FFF1D363530B5 ON vehicule (id_modele)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_VEHICULE_MODELE FOREIGN KEY (id_modele) REFERENCES modele (id_modele)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY proposer_id_prestation_FK');
        $this->addSql('DROP INDEX IDX_21866C15F4420F7A ON proposer');
        $this->addSql('CREATE INDEX proposer_id_prestation_fk ON proposer (id_prestation)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT proposer_id_prestation_FK FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');

        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_VEHICULE_MODELE');
        $this->addSql('DROP INDEX IDX_292FFF1D363530B5 ON vehicule');
        $this->addSql('CREATE INDEX fk_vehicule_modele ON vehicule (id_modele)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_VEHICULE_MODELE FOREIGN KEY (id_modele) REFERENCES modele (id_modele)');
    }
}
