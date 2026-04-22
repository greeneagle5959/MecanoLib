<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420090940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE associer DROP FOREIGN KEY `FK_FA230DB9B911D4E6`');
        $this->addSql('DROP INDEX IDX_FA230DB9B911D4E6 ON associer');
        $this->addSql('ALTER TABLE associer DROP id_garage');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE associer ADD id_garage INT NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              associer
            ADD
              CONSTRAINT `FK_FA230DB9B911D4E6` FOREIGN KEY (id_garage) REFERENCES garage (id_garage) ON
            UPDATE
              NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql('CREATE INDEX IDX_FA230DB9B911D4E6 ON associer (id_garage)');
    }
}
