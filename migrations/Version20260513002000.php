<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513002000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des creneaux horaires supplementaires plus proches des choix souhaites';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO horaire (hre_ouvre_matin, hre_ferme_matin, hre_ouvre_soir, hre_ferme_soir)
            VALUES
                ('11:00:00', '12:00:00', '14:00:00', '18:00:00'),
                ('14:00:00', '18:00:00', '14:00:00', '18:00:00')
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM horaire
            WHERE (hre_ouvre_matin, hre_ferme_matin, hre_ouvre_soir, hre_ferme_soir) IN (
                ('11:00:00', '12:00:00', '14:00:00', '18:00:00'),
                ('14:00:00', '18:00:00', '14:00:00', '18:00:00')
            )
        SQL);
    }
}
