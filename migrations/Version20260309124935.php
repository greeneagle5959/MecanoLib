<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309124935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legacy placeholder for already executed migration version';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left empty: this version is already marked executed in database.
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
    }
}
