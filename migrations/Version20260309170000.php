<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align schema for proposer relation and marque/modele relation changes';
    }

    public function up(Schema $schema): void
    {
        // Prestation no longer stores garage directly.
        $this->dropForeignKeysForColumn('prestation', 'id_garage', 'garage');

        if ($this->columnExists('prestation', 'id_garage')) {
            $this->addSql('ALTER TABLE prestation DROP id_garage');
        }

        if (!$this->tableExists('proposer')) {
            $this->addSql('CREATE TABLE proposer (id_garage INT NOT NULL, id_prestation INT NOT NULL, INDEX IDX_7A0D4706B911D4E6 (id_garage), INDEX IDX_7A0D4706F4420F7A (id_prestation), PRIMARY KEY(id_garage, id_prestation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE proposer ADD CONSTRAINT FK_7A0D4706B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
            $this->addSql('ALTER TABLE proposer ADD CONSTRAINT FK_7A0D4706F4420F7A FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
        }

        // Move relation from marque.id_modele -> modele.id_marque.
        $this->dropForeignKeysForColumn('marque', 'id_modele', 'modele');
        if ($this->columnExists('marque', 'id_modele')) {
            $this->addSql('ALTER TABLE marque DROP id_modele');
        }

        if (!$this->columnExists('modele', 'id_marque')) {
            $this->addSql('ALTER TABLE modele ADD id_marque INT NOT NULL');
            $this->addSql('CREATE INDEX IDX_DB539BBC7C582423 ON modele (id_marque)');
            $this->addSql('ALTER TABLE modele ADD CONSTRAINT FK_DBB539BBC7C582423 FOREIGN KEY (id_marque) REFERENCES marque (id_marque)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('proposer')) {
            $this->dropForeignKeysForColumn('proposer', 'id_garage', 'garage');
            $this->dropForeignKeysForColumn('proposer', 'id_prestation', 'prestation');
            $this->addSql('DROP TABLE proposer');
        }

        if (!$this->columnExists('prestation', 'id_garage')) {
            $this->addSql('ALTER TABLE prestation ADD id_garage INT NOT NULL');
            $this->addSql('CREATE INDEX IDX_51C88FADB911D4E6 ON prestation (id_garage)');
            $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_51C88FADB911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        }

        $this->dropForeignKeysForColumn('modele', 'id_marque', 'marque');
        if ($this->columnExists('modele', 'id_marque')) {
            $this->addSql('ALTER TABLE modele DROP id_marque');
        }

        if (!$this->columnExists('marque', 'id_modele')) {
            $this->addSql('ALTER TABLE marque ADD id_modele INT NOT NULL');
            $this->addSql('CREATE INDEX IDX_5A6F91CE363530B5 ON marque (id_modele)');
            $this->addSql('ALTER TABLE marque ADD CONSTRAINT FK_5A6F91CE363530B5 FOREIGN KEY (id_modele) REFERENCES modele (id_modele)');
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function dropForeignKeysForColumn(string $tableName, string $columnName, ?string $referencedTable = null): void
    {
        $sql = 'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL';
        $params = [$tableName, $columnName];

        if ($referencedTable !== null) {
            $sql .= ' AND REFERENCED_TABLE_NAME = ?';
            $params[] = $referencedTable;
        }

        $constraintNames = $this->connection->fetchFirstColumn($sql, $params);

        foreach ($constraintNames as $constraintName) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY `%s`', $tableName, $constraintName));
        }
    }
}
