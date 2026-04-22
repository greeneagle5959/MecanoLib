<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deplace le lien garage vers associer (PK garage+jour) et supprime id_garage de horaire';
    }

    public function up(Schema $schema): void
    {
        // Si on a plusieurs lignes pour le meme (garage, jour), on garde celle avec le plus petit id_horaire.
        $this->addSql(<<<'SQL'
            DELETE a1
            FROM associer a1
            INNER JOIN associer a2
              ON a1.id_garage = a2.id_garage
             AND a1.id_jour = a2.id_jour
             AND a1.id_horaire > a2.id_horaire
        SQL);

        // PK: avant (id_jour, id_horaire) -> apres (id_garage, id_jour)
        $this->addSql('ALTER TABLE associer DROP PRIMARY KEY, ADD PRIMARY KEY (id_garage, id_jour)');

        // Supprime le lien garage dans horaire (FK + index + colonne)
        $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB6B911D4E6');
        $this->addSql('DROP INDEX IDX_BBC83DB6B911D4E6 ON horaire');
        $this->addSql('ALTER TABLE horaire DROP id_garage');
    }

    public function down(Schema $schema): void
    {
        // Remet id_garage sur horaire
        $this->addSql('ALTER TABLE horaire ADD id_garage INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_BBC83DB6B911D4E6 ON horaire (id_garage)');
        $this->addSql('ALTER TABLE horaire ADD CONSTRAINT FK_BBC83DB6B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');

        // Remet la PK initiale
        $this->addSql('ALTER TABLE associer DROP PRIMARY KEY, ADD PRIMARY KEY (id_jour, id_horaire)');
    }
}

