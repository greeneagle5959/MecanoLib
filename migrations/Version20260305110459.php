<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305110459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marque DROP FOREIGN KEY `FK_5A6F91CE363530B5`');
        $this->addSql('DROP INDEX IDX_5A6F91CE363530B5 ON marque');
        $this->addSql('ALTER TABLE marque DROP id_modele');
        $this->addSql('ALTER TABLE modele ADD id_marque INT NOT NULL');
        $this->addSql('ALTER TABLE modele ADD CONSTRAINT FK_100285587C582423 FOREIGN KEY (id_marque) REFERENCES marque (id_marque)');
        $this->addSql('CREATE INDEX IDX_100285587C582423 ON modele (id_marque)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marque ADD id_modele INT NOT NULL');
        $this->addSql('ALTER TABLE marque ADD CONSTRAINT `FK_5A6F91CE363530B5` FOREIGN KEY (id_modele) REFERENCES modele (id_modele) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5A6F91CE363530B5 ON marque (id_modele)');
        $this->addSql('ALTER TABLE modele DROP FOREIGN KEY FK_100285587C582423');
        $this->addSql('DROP INDEX IDX_100285587C582423 ON modele');
        $this->addSql('ALTER TABLE modele DROP id_marque');
    }
}
