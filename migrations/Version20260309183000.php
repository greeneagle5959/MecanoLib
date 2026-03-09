<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legacy no-op migration kept for compatibility with already migrated databases';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left empty.
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
    }
}
