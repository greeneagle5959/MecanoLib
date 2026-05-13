<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deduplique les horaires identiques et ajoute une contrainte unique pour eviter les doublons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE associer a
            INNER JOIN horaire h ON a.id_horaire = h.id_horaire
            INNER JOIN (
                SELECT
                    hre_ouvre_matin,
                    hre_ferme_matin,
                    hre_ouvre_soir,
                    hre_ferme_soir,
                    MIN(id_horaire) AS keep_id
                FROM horaire
                GROUP BY hre_ouvre_matin, hre_ferme_matin, hre_ouvre_soir, hre_ferme_soir
            ) keepers
              ON h.hre_ouvre_matin = keepers.hre_ouvre_matin
             AND h.hre_ferme_matin = keepers.hre_ferme_matin
             AND h.hre_ouvre_soir = keepers.hre_ouvre_soir
             AND h.hre_ferme_soir = keepers.hre_ferme_soir
            SET a.id_horaire = keepers.keep_id
            WHERE h.id_horaire <> keepers.keep_id
        SQL);

        $this->addSql(<<<'SQL'
            DELETE h
            FROM horaire h
            INNER JOIN (
                SELECT
                    hre_ouvre_matin,
                    hre_ferme_matin,
                    hre_ouvre_soir,
                    hre_ferme_soir,
                    MIN(id_horaire) AS keep_id
                FROM horaire
                GROUP BY hre_ouvre_matin, hre_ferme_matin, hre_ouvre_soir, hre_ferme_soir
            ) keepers
              ON h.hre_ouvre_matin = keepers.hre_ouvre_matin
             AND h.hre_ferme_matin = keepers.hre_ferme_matin
             AND h.hre_ouvre_soir = keepers.hre_ouvre_soir
             AND h.hre_ferme_soir = keepers.hre_ferme_soir
            WHERE h.id_horaire <> keepers.keep_id
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_horaire_creneau ON horaire (hre_ouvre_matin, hre_ferme_matin, hre_ouvre_soir, hre_ferme_soir)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_horaire_creneau ON horaire');
    }
}
