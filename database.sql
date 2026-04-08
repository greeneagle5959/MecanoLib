-- ----------------------------------------------------------
-- Script MYSQL pour mcd 
-- ----------------------------------------------------------


-- ----------------------------
-- Table: role
-- ----------------------------
CREATE TABLE role (
  id_role INT NOT NULL AUTO_INCREMENT,
  nom_role VARCHAR(30) NOT NULL,
  CONSTRAINT role_PK PRIMARY KEY (id_role)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: jour
-- ----------------------------
CREATE TABLE jour (
  id_jour INT NOT NULL AUTO_INCREMENT,
  lib_jour VARCHAR(15) NOT NULL,
  CONSTRAINT jour_PK PRIMARY KEY (id_jour)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: modele
-- ----------------------------
CREATE TABLE modele (
  id_modele INT NOT NULL AUTO_INCREMENT,
  nom_modele VARCHAR(50) NOT NULL,
  CONSTRAINT modele_PK PRIMARY KEY (id_modele)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: abonnement
-- ----------------------------
CREATE TABLE abonnement (
  id_abonnement INT NOT NULL AUTO_INCREMENT,
  lib_abonnement VARCHAR(255) NOT NULL,
  tarif_abonnement DECIMAL(19,4) NOT NULL,
  CONSTRAINT abonnement_PK PRIMARY KEY (id_abonnement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: status_notif
-- ----------------------------
CREATE TABLE status_notif (
  id_status_notif INT NOT NULL AUTO_INCREMENT,
  lib_status_notif VARCHAR(50) NOT NULL,
  CONSTRAINT status_notif_PK PRIMARY KEY (id_status_notif)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: reseau_sociaux
-- ----------------------------
CREATE TABLE reseau_sociaux (
  id_reseau INT NOT NULL AUTO_INCREMENT,
  nom_reseaux VARCHAR(255),
  CONSTRAINT reseau_sociaux_PK PRIMARY KEY (id_reseau)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: ville
-- ----------------------------
CREATE TABLE ville (
  id_ville INT NOT NULL AUTO_INCREMENT,
  nom_ville VARCHAR(30) NOT NULL,
  code_postal VARCHAR(10) NOT NULL,
  code_inssee VARCHAR(10) NOT NULL,
  CONSTRAINT ville_PK PRIMARY KEY (id_ville)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: categorie
-- ----------------------------
CREATE TABLE categorie (
  id_categorie INT NOT NULL AUTO_INCREMENT,
  nom_categorie VARCHAR(50) NOT NULL,
  CONSTRAINT categorie_PK PRIMARY KEY (id_categorie)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: status_rdv
-- ----------------------------
CREATE TABLE status_rdv (
  id_status_rdv INT NOT NULL AUTO_INCREMENT,
  lib_status_rdv VARCHAR(50) NOT NULL,
  CONSTRAINT status_rdv_PK PRIMARY KEY (id_status_rdv)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: utilisateur
-- ----------------------------
CREATE TABLE utilisateur (
  id_utilisateur INT NOT NULL AUTO_INCREMENT,
  email_utilisateur VARCHAR(255) NOT NULL,
  mdp_utilisateur VARCHAR(255) NOT NULL,
  auth_2fa VARCHAR(255),
  is_2fa TINYINT(1) NOT NULL,
  id_role INT NOT NULL,
  CONSTRAINT utilisateur_PK PRIMARY KEY (id_utilisateur),
  CONSTRAINT utilisateur_id_role_FK FOREIGN KEY (id_role) REFERENCES role (id_role)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: marque
-- ----------------------------
CREATE TABLE marque (
  id_marque INT NOT NULL AUTO_INCREMENT,
  nom_marque VARCHAR(50) NOT NULL,
  id_modele INT NOT NULL,
  CONSTRAINT marque_PK PRIMARY KEY (id_marque),
  CONSTRAINT marque_id_modele_FK FOREIGN KEY (id_modele) REFERENCES modele (id_modele)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: client
-- ----------------------------
CREATE TABLE client (
  id_client INT NOT NULL AUTO_INCREMENT,
  nom_client VARCHAR(30) NOT NULL,
  prenom_client VARCHAR(30) NOT NULL,
  telephone_client VARCHAR(15) NOT NULL,
  consentement_client TINYINT(1) NOT NULL,
  date_inscription DATETIME NOT NULL,
  id_utilisateur INT NOT NULL,
  CONSTRAINT client_PK PRIMARY KEY (id_client),
  CONSTRAINT client_id_utilisateur_FK FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: garage
-- ----------------------------
CREATE TABLE garage (
  id_garage INT NOT NULL AUTO_INCREMENT,
  nom_garage VARCHAR(20) NOT NULL,
  email_garage VARCHAR(255) NOT NULL,
  telephone_garage VARCHAR(15) NOT NULL,
  adresse_garage VARCHAR(80) NOT NULL,
  date_creation DATETIME NOT NULL,
  siret VARCHAR(15) NOT NULL,
  tva VARCHAR(25) NOT NULL,
  img_garage VARCHAR(255),
  img_logo VARCHAR(255),
  id_utilisateur INT NOT NULL,
  id_ville INT NOT NULL,
  CONSTRAINT garage_PK PRIMARY KEY (id_garage),
  CONSTRAINT garage_id_utilisateur_FK FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
  CONSTRAINT garage_id_ville_FK FOREIGN KEY (id_ville) REFERENCES ville (id_ville)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: horaire
-- ----------------------------
CREATE TABLE horaire (
  id_horaire INT NOT NULL AUTO_INCREMENT,
  hre_ouvre_matin TIME NOT NULL,
  hre_ferme_matin TIME NOT NULL,
  hre_ouvre_soir TIME NOT NULL,
  hre_ferme_soir TIME NOT NULL,
  id_garage INT NOT NULL,
  CONSTRAINT horaire_PK PRIMARY KEY (id_horaire),
  CONSTRAINT horaire_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: souscription
-- ----------------------------
CREATE TABLE souscription (
  id_souscription INT NOT NULL AUTO_INCREMENT,
  date_debut DATETIME NOT NULL,
  date_fin DATETIME NOT NULL,
  prix DECIMAL(19,4) NOT NULL,
  status TINYINT(1) NOT NULL,
  id_garage INT NOT NULL,
  id_abonnement INT NOT NULL,
  CONSTRAINT souscription_PK PRIMARY KEY (id_souscription),
  CONSTRAINT souscription_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT souscription_id_abonnement_FK FOREIGN KEY (id_abonnement) REFERENCES abonnement (id_abonnement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: vehicule
-- ----------------------------
CREATE TABLE vehicule (
  id_vehicule INT NOT NULL AUTO_INCREMENT,
  imatriculation_vehicule VARCHAR(10) NOT NULL,
  annee_vehicule VARCHAR(4) NOT NULL,
  id_client INT NOT NULL,
  id_marque INT NOT NULL,
  CONSTRAINT vehicule_PK PRIMARY KEY (id_vehicule),
  CONSTRAINT vehicule_id_client_FK FOREIGN KEY (id_client) REFERENCES client (id_client),
  CONSTRAINT vehicule_id_marque_FK FOREIGN KEY (id_marque) REFERENCES marque (id_marque)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: valeur
-- ----------------------------
CREATE TABLE valeur (
  id_valeur INT NOT NULL AUTO_INCREMENT,
  lib_valeur VARCHAR(255) NOT NULL,
  qrcode VARCHAR(255),
  id_garage INT NOT NULL,
  id_reseau INT NOT NULL,
  CONSTRAINT valeur_PK PRIMARY KEY (id_valeur),
  CONSTRAINT valeur_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT valeur_id_reseau_FK FOREIGN KEY (id_reseau) REFERENCES reseau_sociaux (id_reseau)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: prestation
-- ----------------------------
CREATE TABLE prestation (
  id_prestation INT NOT NULL AUTO_INCREMENT,
  nom_prestation VARCHAR(30) NOT NULL,
  description_prestation TEXT NOT NULL,
  duree_prestation VARCHAR(10) NOT NULL,
  categorie_prestation VARCHAR(30) NOT NULL,
  prerequis TEXT NOT NULL,
  Ressource_requise TEXT NOT NULL,
  id_garage INT NOT NULL,
  id_categorie INT NOT NULL,
  CONSTRAINT prestation_PK PRIMARY KEY (id_prestation),
  CONSTRAINT prestation_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT prestation_id_categorie_FK FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: proposer
-- ----------------------------
CREATE TABLE proposer (
  id_garage INT NOT NULL,
  id_prestation INT NOT NULL,
  prix DECIMAL(8,2) DEFAULT NULL,
  CONSTRAINT proposer_PK PRIMARY KEY (id_garage, id_prestation),
  CONSTRAINT proposer_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT proposer_id_prestation_FK FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: avis
-- ----------------------------
CREATE TABLE avis (
  id_avis INT NOT NULL AUTO_INCREMENT,
  note INT NOT NULL,
  commentaire TEXT NOT NULL,
  date_publication DATETIME NOT NULL,
  id_garage INT NOT NULL,
  id_client INT NOT NULL,
  CONSTRAINT avis_PK PRIMARY KEY (id_avis),
  CONSTRAINT avis_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT avis_id_client_FK FOREIGN KEY (id_client) REFERENCES client (id_client)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: associer
-- ----------------------------
CREATE TABLE associer (
  id_jour INT NOT NULL,
  id_horaire INT NOT NULL,
  CONSTRAINT associer_PK PRIMARY KEY (id_jour, id_horaire),
  CONSTRAINT associer_id_jour_FK FOREIGN KEY (id_jour) REFERENCES jour (id_jour),
  CONSTRAINT associer_id_horaire_FK FOREIGN KEY (id_horaire) REFERENCES horaire (id_horaire)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: rendez_vous
-- ----------------------------
CREATE TABLE rendez_vous (
  id_rdv INT NOT NULL AUTO_INCREMENT,
  date_debut DATETIME NOT NULL,
  date_fin DATETIME NOT NULL,
  motif_refus TEXT NOT NULL,
  commantaire_client TEXT NOT NULL,
  id_garage INT NOT NULL,
  id_vehicule INT NOT NULL,
  id_status_rdv INT NOT NULL,
  CONSTRAINT rendez_vous_PK PRIMARY KEY (id_rdv),
  CONSTRAINT rendez_vous_id_garage_FK FOREIGN KEY (id_garage) REFERENCES garage (id_garage),
  CONSTRAINT rendez_vous_id_vehicule_FK FOREIGN KEY (id_vehicule) REFERENCES vehicule (id_vehicule),
  CONSTRAINT rendez_vous_id_status_rdv_FK FOREIGN KEY (id_status_rdv) REFERENCES status_rdv (id_status_rdv)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: lier
-- ----------------------------
CREATE TABLE lier (
  id_prestation INT NOT NULL,
  id_rdv INT NOT NULL,
  CONSTRAINT lier_PK PRIMARY KEY (id_prestation, id_rdv),
  CONSTRAINT lier_id_prestation_FK FOREIGN KEY (id_prestation) REFERENCES prestation (id_prestation)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: files
-- ----------------------------
CREATE TABLE files (
  id_files INT NOT NULL AUTO_INCREMENT,
  img VARCHAR(255) NOT NULL,
  commentaire TEXT NOT NULL,
  id_rdv INT NOT NULL,
  CONSTRAINT files_PK PRIMARY KEY (id_files)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: notifications
-- ----------------------------
CREATE TABLE notifications (
  id_notifications INT NOT NULL AUTO_INCREMENT,
  contenue TEXT NOT NULL,
  date_envoi DATETIME NOT NULL,
  id_rdv INT NOT NULL,
  id_status_notif INT NOT NULL,
  CONSTRAINT notifications_PK PRIMARY KEY (id_notifications),
  CONSTRAINT notifications_id_status_notif_FK FOREIGN KEY (id_status_notif) REFERENCES status_notif (id_status_notif)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: historique
-- ----------------------------
CREATE TABLE historique (
  id_historique INT NOT NULL AUTO_INCREMENT,
  date_intervention DATE NOT NULL,
  compte_rendu TEXT NOT NULL,
  id_rdv INT NOT NULL,
  CONSTRAINT historique_PK PRIMARY KEY (id_historique)
)ENGINE=InnoDB;


-- ===== FOREIGN KEYS =====

ALTER TABLE lier
  ADD CONSTRAINT lier_id_rdv_FK FOREIGN KEY (id_rdv)
  REFERENCES rendez_vous (id_rdv);

ALTER TABLE files
  ADD CONSTRAINT files_id_rdv_FK FOREIGN KEY (id_rdv)
  REFERENCES rendez_vous (id_rdv);

ALTER TABLE notifications
  ADD CONSTRAINT notifications_id_rdv_FK FOREIGN KEY (id_rdv)
  REFERENCES rendez_vous (id_rdv);

ALTER TABLE historique
  ADD CONSTRAINT historique_id_rdv_FK FOREIGN KEY (id_rdv)
  REFERENCES rendez_vous (id_rdv);
