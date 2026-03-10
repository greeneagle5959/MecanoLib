<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309113435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id_abonnement INT AUTO_INCREMENT NOT NULL, lib_abonnement VARCHAR(255) NOT NULL, tarif_abonnement NUMERIC(19, 4) NOT NULL, PRIMARY KEY (id_abonnement)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE associer (id_jour INT NOT NULL, id_horaire INT NOT NULL, INDEX IDX_FA230DB95137C5C7 (id_jour), INDEX IDX_FA230DB9655594C6 (id_horaire), PRIMARY KEY (id_jour, id_horaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avis (id_avis INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, commentaire LONGTEXT NOT NULL, date_publication DATETIME NOT NULL, id_garage INT NOT NULL, id_client INT NOT NULL, INDEX IDX_8F91ABF0B911D4E6 (id_garage), INDEX IDX_8F91ABF0E173B1B8 (id_client), PRIMARY KEY (id_avis)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom_categorie VARCHAR(50) NOT NULL, PRIMARY KEY (id_categorie)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE client (id_client INT AUTO_INCREMENT NOT NULL, nom_client VARCHAR(30) NOT NULL, prenom_client VARCHAR(30) NOT NULL, telephone_client VARCHAR(15) NOT NULL, consentement_client TINYINT NOT NULL, date_inscription DATETIME NOT NULL, id_utilisateur INT NOT NULL, INDEX IDX_C744045550EAE44 (id_utilisateur), PRIMARY KEY (id_client)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE files (id_files INT AUTO_INCREMENT NOT NULL, img VARCHAR(255) NOT NULL, commentaire LONGTEXT NOT NULL, id_rdv INT NOT NULL, INDEX IDX_63540598E67F7DC (id_rdv), PRIMARY KEY (id_files)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE garage (id_garage INT AUTO_INCREMENT NOT NULL, nom_garage VARCHAR(20) NOT NULL, email_garage VARCHAR(255) NOT NULL, telephone_garage VARCHAR(15) NOT NULL, adresse_garage VARCHAR(80) NOT NULL, date_creation DATETIME NOT NULL, siret VARCHAR(15) NOT NULL, tva VARCHAR(25) NOT NULL, img_garage VARCHAR(255) DEFAULT NULL, img_logo VARCHAR(255) DEFAULT NULL, id_utilisateur INT NOT NULL, id_ville INT NOT NULL, INDEX IDX_9F26610B50EAE44 (id_utilisateur), INDEX IDX_9F26610BAD4698F3 (id_ville), PRIMARY KEY (id_garage)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE historique (id_historique INT AUTO_INCREMENT NOT NULL, date_intervention DATE NOT NULL, compte_rendu LONGTEXT NOT NULL, id_rdv INT NOT NULL, INDEX IDX_EDBFD5EC8E67F7DC (id_rdv), PRIMARY KEY (id_historique)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE horaire (id_horaire INT AUTO_INCREMENT NOT NULL, hre_ouvre_matin TIME NOT NULL, hre_ferme_matin TIME NOT NULL, hre_ouvre_soir TIME NOT NULL, hre_ferme_soir TIME NOT NULL, id_garage INT NOT NULL, INDEX IDX_BBC83DB6B911D4E6 (id_garage), PRIMARY KEY (id_horaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jour (id_jour INT AUTO_INCREMENT NOT NULL, lib_jour VARCHAR(15) NOT NULL, PRIMARY KEY (id_jour)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lier (id_prestation INT NOT NULL, id_rdv INT NOT NULL, INDEX IDX_B133E8FAF4420F7A (id_prestation), INDEX IDX_B133E8FA8E67F7DC (id_rdv), PRIMARY KEY (id_prestation, id_rdv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE marque (id_marque INT AUTO_INCREMENT NOT NULL, nom_marque VARCHAR(50) NOT NULL, PRIMARY KEY (id_marque)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE modele (id_modele INT AUTO_INCREMENT NOT NULL, nom_modele VARCHAR(50) NOT NULL, id_marque INT NOT NULL, INDEX IDX_100285587C582423 (id_marque), PRIMARY KEY (id_modele)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notifications (id_notifications INT AUTO_INCREMENT NOT NULL, contenue LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, id_status_notif INT NOT NULL, id_rdv INT NOT NULL, INDEX IDX_6000B0D314E829E9 (id_status_notif), INDEX IDX_6000B0D38E67F7DC (id_rdv), PRIMARY KEY (id_notifications)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prestation (id_prestation INT AUTO_INCREMENT NOT NULL, nom_prestation VARCHAR(30) NOT NULL, description_prestation LONGTEXT NOT NULL, duree_prestation VARCHAR(10) NOT NULL, id_categorie INT NOT NULL, INDEX IDX_51C88FADC9486A13 (id_categorie), PRIMARY KEY (id_prestation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE proposer (prix NUMERIC(8, 2) DEFAULT NULL, id_garage INT NOT NULL, id_prestation INT NOT NULL, INDEX IDX_21866C15B911D4E6 (id_garage), INDEX IDX_21866C15F4420F7A (id_prestation), PRIMARY KEY (id_garage, id_prestation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rendez_vous (id_rdv INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, motif_refus LONGTEXT NOT NULL, commantaire_client LONGTEXT NOT NULL, id_garage INT NOT NULL, id_vehicule INT NOT NULL, id_status_rdv INT NOT NULL, INDEX IDX_65E8AA0AB911D4E6 (id_garage), INDEX IDX_65E8AA0A79F41388 (id_vehicule), INDEX IDX_65E8AA0A6B26CFF3 (id_status_rdv), PRIMARY KEY (id_rdv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reseau_sociaux (id_reseau INT AUTO_INCREMENT NOT NULL, nom_reseaux VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id_reseau)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (id_role INT AUTO_INCREMENT NOT NULL, nom_role VARCHAR(30) NOT NULL, PRIMARY KEY (id_role)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE souscription (id_souscription INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, prix NUMERIC(19, 4) NOT NULL, status TINYINT NOT NULL, id_garage INT NOT NULL, id_abonnement INT NOT NULL, INDEX IDX_2AED620DB911D4E6 (id_garage), INDEX IDX_2AED620D9098E86C (id_abonnement), PRIMARY KEY (id_souscription)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE status_notif (id_status_notif INT AUTO_INCREMENT NOT NULL, lib_status_notif VARCHAR(50) NOT NULL, PRIMARY KEY (id_status_notif)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE status_rdv (id_status_rdv INT AUTO_INCREMENT NOT NULL, lib_status_rdv VARCHAR(50) NOT NULL, PRIMARY KEY (id_status_rdv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, email_utilisateur VARCHAR(255) NOT NULL, mdp_utilisateur VARCHAR(255) NOT NULL, auth_2fa VARCHAR(255) DEFAULT NULL, is_2fa TINYINT NOT NULL, id_role INT NOT NULL, INDEX IDX_1D1C63B3DC499668 (id_role), PRIMARY KEY (id_utilisateur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE valeur (id_valeur INT AUTO_INCREMENT NOT NULL, lib_valeur VARCHAR(255) NOT NULL, qrcode VARCHAR(255) DEFAULT NULL, id_garage INT NOT NULL, id_reseau INT NOT NULL, INDEX IDX_1B44CD51B911D4E6 (id_garage), INDEX IDX_1B44CD51EBD29955 (id_reseau), PRIMARY KEY (id_valeur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicule (id_vehicule INT AUTO_INCREMENT NOT NULL, imatriculation_vehicule VARCHAR(10) NOT NULL, annee_vehicule VARCHAR(4) NOT NULL, id_client INT NOT NULL, id_marque INT NOT NULL, INDEX IDX_292FFF1DE173B1B8 (id_client), INDEX IDX_292FFF1D7C582423 (id_marque), PRIMARY KEY (id_vehicule)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ville (id_ville INT AUTO_INCREMENT NOT NULL, nom_ville VARCHAR(30) NOT NULL, code_postal VARCHAR(10) NOT NULL, code_inssee VARCHAR(10) NOT NULL, PRIMARY KEY (id_ville)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE associer ADD CONSTRAINT FK_FA230DB95137C5C7 FOREIGN KEY (id_jour) REFERENCES jour (id_jour)');
        $this->addSql('ALTER TABLE associer ADD CONSTRAINT FK_FA230DB9655594C6 FOREIGN KEY (id_horaire) REFERENCES horaire (id_horaire)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0E173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C744045550EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540598E67F7DC FOREIGN KEY (id_rdv) REFERENCES rendez_vous (id_rdv)');
        $this->addSql('ALTER TABLE garage ADD CONSTRAINT FK_9F26610B50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE garage ADD CONSTRAINT FK_9F26610BAD4698F3 FOREIGN KEY (id_ville) REFERENCES ville (id_ville)');
        $this->addSql('ALTER TABLE historique ADD CONSTRAINT FK_EDBFD5EC8E67F7DC FOREIGN KEY (id_rdv) REFERENCES rendez_vous (id_rdv)');
        $this->addSql('ALTER TABLE horaire ADD CONSTRAINT FK_BBC83DB6B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE lier ADD CONSTRAINT FK_B133E8FAF4420F7A FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
        $this->addSql('ALTER TABLE lier ADD CONSTRAINT FK_B133E8FA8E67F7DC FOREIGN KEY (id_rdv) REFERENCES rendez_vous (id_rdv)');
        $this->addSql('ALTER TABLE modele ADD CONSTRAINT FK_100285587C582423 FOREIGN KEY (id_marque) REFERENCES marque (id_marque)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D314E829E9 FOREIGN KEY (id_status_notif) REFERENCES status_notif (id_status_notif)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D38E67F7DC FOREIGN KEY (id_rdv) REFERENCES rendez_vous (id_rdv)');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_51C88FADC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT FK_21866C15B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE proposer ADD CONSTRAINT FK_21866C15F4420F7A FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AB911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A79F41388 FOREIGN KEY (id_vehicule) REFERENCES vehicule (id_vehicule)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A6B26CFF3 FOREIGN KEY (id_status_rdv) REFERENCES status_rdv (id_status_rdv)');
        $this->addSql('ALTER TABLE souscription ADD CONSTRAINT FK_2AED620DB911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE souscription ADD CONSTRAINT FK_2AED620D9098E86C FOREIGN KEY (id_abonnement) REFERENCES abonnement (id_abonnement)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
        $this->addSql('ALTER TABLE valeur ADD CONSTRAINT FK_1B44CD51B911D4E6 FOREIGN KEY (id_garage) REFERENCES garage (id_garage)');
        $this->addSql('ALTER TABLE valeur ADD CONSTRAINT FK_1B44CD51EBD29955 FOREIGN KEY (id_reseau) REFERENCES reseau_sociaux (id_reseau)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DE173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1D7C582423 FOREIGN KEY (id_marque) REFERENCES marque (id_marque)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE associer DROP FOREIGN KEY FK_FA230DB95137C5C7');
        $this->addSql('ALTER TABLE associer DROP FOREIGN KEY FK_FA230DB9655594C6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0B911D4E6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0E173B1B8');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C744045550EAE44');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540598E67F7DC');
        $this->addSql('ALTER TABLE garage DROP FOREIGN KEY FK_9F26610B50EAE44');
        $this->addSql('ALTER TABLE garage DROP FOREIGN KEY FK_9F26610BAD4698F3');
        $this->addSql('ALTER TABLE historique DROP FOREIGN KEY FK_EDBFD5EC8E67F7DC');
        $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB6B911D4E6');
        $this->addSql('ALTER TABLE lier DROP FOREIGN KEY FK_B133E8FAF4420F7A');
        $this->addSql('ALTER TABLE lier DROP FOREIGN KEY FK_B133E8FA8E67F7DC');
        $this->addSql('ALTER TABLE modele DROP FOREIGN KEY FK_100285587C582423');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D314E829E9');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D38E67F7DC');
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_51C88FADC9486A13');
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY FK_21866C15B911D4E6');
        $this->addSql('ALTER TABLE proposer DROP FOREIGN KEY FK_21866C15F4420F7A');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AB911D4E6');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A79F41388');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A6B26CFF3');
        $this->addSql('ALTER TABLE souscription DROP FOREIGN KEY FK_2AED620DB911D4E6');
        $this->addSql('ALTER TABLE souscription DROP FOREIGN KEY FK_2AED620D9098E86C');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3DC499668');
        $this->addSql('ALTER TABLE valeur DROP FOREIGN KEY FK_1B44CD51B911D4E6');
        $this->addSql('ALTER TABLE valeur DROP FOREIGN KEY FK_1B44CD51EBD29955');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DE173B1B8');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1D7C582423');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE associer');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE files');
        $this->addSql('DROP TABLE garage');
        $this->addSql('DROP TABLE historique');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE jour');
        $this->addSql('DROP TABLE lier');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE modele');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE prestation');
        $this->addSql('DROP TABLE proposer');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE reseau_sociaux');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE souscription');
        $this->addSql('DROP TABLE status_notif');
        $this->addSql('DROP TABLE status_rdv');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE valeur');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('DROP TABLE ville');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
