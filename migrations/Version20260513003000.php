<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la nullabilite des champs texte rendez_vous sur le mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rendez_vous CHANGE motif_refus motif_refus LONGTEXT DEFAULT NULL, CHANGE commantaire_client commantaire_client LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rendez_vous CHANGE motif_refus motif_refus LONGTEXT NOT NULL, CHANGE commantaire_client commantaire_client LONGTEXT NOT NULL');
    }
}
