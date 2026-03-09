<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop prerequis and ressource_requise from prestation';
    }

    public function up(Schema $schema): void
    {
        if ($this->columnExists('prestation', 'prerequis')) {
            $this->addSql('ALTER TABLE prestation DROP prerequis');
        }

        if ($this->columnExists('prestation', 'ressource_requise')) {
            $this->addSql('ALTER TABLE prestation DROP ressource_requise');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->columnExists('prestation', 'prerequis')) {
            $this->addSql('ALTER TABLE prestation ADD prerequis LONGTEXT NOT NULL');
        }

        if (!$this->columnExists('prestation', 'ressource_requise')) {
            $this->addSql('ALTER TABLE prestation ADD ressource_requise LONGTEXT NOT NULL');
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
