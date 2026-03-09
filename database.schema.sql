-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : lun. 09 mars 2026 à 11:05
-- Version du serveur : 8.0.40
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mecanoLib`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnement`
--

CREATE TABLE `abonnement` (
  `id_abonnement` int NOT NULL,
  `lib_abonnement` varchar(255) NOT NULL,
  `tarif_abonnement` decimal(19,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `associer`
--

CREATE TABLE `associer` (
  `id_jour` int NOT NULL,
  `id_horaire` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int NOT NULL,
  `note` int NOT NULL,
  `commentaire` longtext NOT NULL,
  `date_publication` datetime NOT NULL,
  `id_garage` int NOT NULL,
  `id_client` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id_categorie` int NOT NULL,
  `nom_categorie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id_client` int NOT NULL,
  `nom_client` varchar(30) NOT NULL,
  `prenom_client` varchar(30) NOT NULL,
  `telephone_client` varchar(15) NOT NULL,
  `consentement_client` tinyint NOT NULL,
  `date_inscription` datetime NOT NULL,
  `id_utilisateur` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `files`
--

CREATE TABLE `files` (
  `id_files` int NOT NULL,
  `img` varchar(255) NOT NULL,
  `commentaire` longtext NOT NULL,
  `id_rdv` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `garage`
--

CREATE TABLE `garage` (
  `id_garage` int NOT NULL,
  `nom_garage` varchar(20) NOT NULL,
  `email_garage` varchar(255) NOT NULL,
  `telephone_garage` varchar(15) NOT NULL,
  `adresse_garage` varchar(80) NOT NULL,
  `date_creation` datetime NOT NULL,
  `siret` varchar(15) NOT NULL,
  `tva` varchar(25) NOT NULL,
  `img_garage` varchar(255) DEFAULT NULL,
  `img_logo` varchar(255) DEFAULT NULL,
  `id_utilisateur` int NOT NULL,
  `id_ville` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

CREATE TABLE `historique` (
  `id_historique` int NOT NULL,
  `date_intervention` date NOT NULL,
  `compte_rendu` longtext NOT NULL,
  `id_rdv` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `horaire`
--

CREATE TABLE `horaire` (
  `id_horaire` int NOT NULL,
  `hre_ouvre_matin` time NOT NULL,
  `hre_ferme_matin` time NOT NULL,
  `hre_ouvre_soir` time NOT NULL,
  `hre_ferme_soir` time NOT NULL,
  `id_garage` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jour`
--

CREATE TABLE `jour` (
  `id_jour` int NOT NULL,
  `lib_jour` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lier`
--

CREATE TABLE `lier` (
  `id_prestation` int NOT NULL,
  `id_rdv` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marque`
--

CREATE TABLE `marque` (
  `id_marque` int NOT NULL,
  `nom_marque` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modele`
--

CREATE TABLE `modele` (
  `id_modele` int NOT NULL,
  `nom_modele` varchar(50) NOT NULL,
  `id_marque` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id_notifications` int NOT NULL,
  `contenue` longtext NOT NULL,
  `date_envoi` datetime NOT NULL,
  `id_status_notif` int NOT NULL,
  `id_rdv` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prestation`
--

CREATE TABLE `prestation` (
  `id_prestation` int NOT NULL,
  `nom_prestation` varchar(100) NOT NULL,
  `description_prestation` longtext NOT NULL,
  `duree_prestation` varchar(10) NOT NULL,
  `id_categorie` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `proposer`
--

CREATE TABLE `proposer` (
  `id_garage` int NOT NULL,
  `id_prestation` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id_rdv` int NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `motif_refus` longtext NOT NULL,
  `commantaire_client` longtext NOT NULL,
  `id_garage` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `id_status_rdv` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reseau_sociaux`
--

CREATE TABLE `reseau_sociaux` (
  `id_reseau` int NOT NULL,
  `nom_reseaux` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id_role` int NOT NULL,
  `nom_role` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `souscription`
--

CREATE TABLE `souscription` (
  `id_souscription` int NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `prix` decimal(19,4) NOT NULL,
  `status` tinyint NOT NULL,
  `id_garage` int NOT NULL,
  `id_abonnement` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `status_notif`
--

CREATE TABLE `status_notif` (
  `id_status_notif` int NOT NULL,
  `lib_status_notif` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `status_rdv`
--

CREATE TABLE `status_rdv` (
  `id_status_rdv` int NOT NULL,
  `lib_status_rdv` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int NOT NULL,
  `email_utilisateur` varchar(255) NOT NULL,
  `mdp_utilisateur` varchar(255) NOT NULL,
  `auth_2fa` varchar(255) DEFAULT NULL,
  `is_2fa` tinyint NOT NULL,
  `id_role` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

-- --------------------------------------------------------

--
-- Structure de la table `valeur`
--

CREATE TABLE `valeur` (
  `id_valeur` int NOT NULL,
  `lib_valeur` varchar(255) NOT NULL,
  `qrcode` varchar(255) DEFAULT NULL,
  `id_garage` int NOT NULL,
  `id_reseau` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

CREATE TABLE `vehicule` (
  `id_vehicule` int NOT NULL,
  `imatriculation_vehicule` varchar(10) NOT NULL,
  `annee_vehicule` varchar(4) NOT NULL,
  `id_client` int NOT NULL,
  `id_marque` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ville`
--

CREATE TABLE `ville` (
  `id_ville` int NOT NULL,
  `nom_ville` varchar(30) NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `code_inssee` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `abonnement`
--
ALTER TABLE `abonnement`
  ADD PRIMARY KEY (`id_abonnement`);

--
-- Index pour la table `associer`
--
ALTER TABLE `associer`
  ADD PRIMARY KEY (`id_jour`,`id_horaire`),
  ADD KEY `IDX_FA230DB95137C5C7` (`id_jour`),
  ADD KEY `IDX_FA230DB9655594C6` (`id_horaire`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `IDX_8F91ABF0B911D4E6` (`id_garage`),
  ADD KEY `IDX_8F91ABF0E173B1B8` (`id_client`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`),
  ADD KEY `IDX_C744045550EAE44` (`id_utilisateur`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id_files`),
  ADD KEY `IDX_63540598E67F7DC` (`id_rdv`);

--
-- Index pour la table `garage`
--
ALTER TABLE `garage`
  ADD PRIMARY KEY (`id_garage`),
  ADD KEY `IDX_9F26610B50EAE44` (`id_utilisateur`),
  ADD KEY `IDX_9F26610BAD4698F3` (`id_ville`);

--
-- Index pour la table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `IDX_EDBFD5EC8E67F7DC` (`id_rdv`);

--
-- Index pour la table `horaire`
--
ALTER TABLE `horaire`
  ADD PRIMARY KEY (`id_horaire`),
  ADD KEY `IDX_BBC83DB6B911D4E6` (`id_garage`);

--
-- Index pour la table `jour`
--
ALTER TABLE `jour`
  ADD PRIMARY KEY (`id_jour`);

--
-- Index pour la table `lier`
--
ALTER TABLE `lier`
  ADD PRIMARY KEY (`id_prestation`,`id_rdv`),
  ADD KEY `IDX_B133E8FAF4420F7A` (`id_prestation`),
  ADD KEY `IDX_B133E8FA8E67F7DC` (`id_rdv`);

--
-- Index pour la table `marque`
--
ALTER TABLE `marque`
  ADD PRIMARY KEY (`id_marque`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Index pour la table `modele`
--
ALTER TABLE `modele`
  ADD PRIMARY KEY (`id_modele`),
  ADD KEY `IDX_100285587C582423` (`id_marque`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notifications`),
  ADD KEY `IDX_6000B0D314E829E9` (`id_status_notif`),
  ADD KEY `IDX_6000B0D38E67F7DC` (`id_rdv`);

--
-- Index pour la table `prestation`
--
ALTER TABLE `prestation`
  ADD PRIMARY KEY (`id_prestation`),
  ADD KEY `IDX_51C88FADC9486A13` (`id_categorie`);

--
-- Index pour la table `proposer`
--
ALTER TABLE `proposer`
  ADD PRIMARY KEY (`id_garage`,`id_prestation`),
  ADD KEY `proposer_id_prestation_FK` (`id_prestation`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id_rdv`),
  ADD KEY `IDX_65E8AA0AB911D4E6` (`id_garage`),
  ADD KEY `IDX_65E8AA0A79F41388` (`id_vehicule`),
  ADD KEY `IDX_65E8AA0A6B26CFF3` (`id_status_rdv`);

--
-- Index pour la table `reseau_sociaux`
--
ALTER TABLE `reseau_sociaux`
  ADD PRIMARY KEY (`id_reseau`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id_role`);

--
-- Index pour la table `souscription`
--
ALTER TABLE `souscription`
  ADD PRIMARY KEY (`id_souscription`),
  ADD KEY `IDX_2AED620DB911D4E6` (`id_garage`),
  ADD KEY `IDX_2AED620D9098E86C` (`id_abonnement`);

--
-- Index pour la table `status_notif`
--
ALTER TABLE `status_notif`
  ADD PRIMARY KEY (`id_status_notif`);

--
-- Index pour la table `status_rdv`
--
ALTER TABLE `status_rdv`
  ADD PRIMARY KEY (`id_status_rdv`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD KEY `IDX_1D1C63B3DC499668` (`id_role`);

--
-- Index pour la table `valeur`
--
ALTER TABLE `valeur`
  ADD PRIMARY KEY (`id_valeur`),
  ADD KEY `IDX_1B44CD51B911D4E6` (`id_garage`),
  ADD KEY `IDX_1B44CD51EBD29955` (`id_reseau`);

--
-- Index pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD PRIMARY KEY (`id_vehicule`),
  ADD KEY `IDX_292FFF1DE173B1B8` (`id_client`),
  ADD KEY `IDX_292FFF1D7C582423` (`id_marque`);

--
-- Index pour la table `ville`
--
ALTER TABLE `ville`
  ADD PRIMARY KEY (`id_ville`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `abonnement`
--
ALTER TABLE `abonnement`
  MODIFY `id_abonnement` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id_categorie` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `files`
--
ALTER TABLE `files`
  MODIFY `id_files` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `garage`
--
ALTER TABLE `garage`
  MODIFY `id_garage` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `historique`
--
ALTER TABLE `historique`
  MODIFY `id_historique` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `horaire`
--
ALTER TABLE `horaire`
  MODIFY `id_horaire` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `jour`
--
ALTER TABLE `jour`
  MODIFY `id_jour` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `marque`
--
ALTER TABLE `marque`
  MODIFY `id_marque` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `modele`
--
ALTER TABLE `modele`
  MODIFY `id_modele` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=379;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notifications` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prestation`
--
ALTER TABLE `prestation`
  MODIFY `id_prestation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  MODIFY `id_rdv` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reseau_sociaux`
--
ALTER TABLE `reseau_sociaux`
  MODIFY `id_reseau` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id_role` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `souscription`
--
ALTER TABLE `souscription`
  MODIFY `id_souscription` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `status_notif`
--
ALTER TABLE `status_notif`
  MODIFY `id_status_notif` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `status_rdv`
--
ALTER TABLE `status_rdv`
  MODIFY `id_status_rdv` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `valeur`
--
ALTER TABLE `valeur`
  MODIFY `id_valeur` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `vehicule`
--
ALTER TABLE `vehicule`
  MODIFY `id_vehicule` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ville`
--
ALTER TABLE `ville`
  MODIFY `id_ville` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `associer`
--
ALTER TABLE `associer`
  ADD CONSTRAINT `FK_FA230DB95137C5C7` FOREIGN KEY (`id_jour`) REFERENCES `jour` (`id_jour`),
  ADD CONSTRAINT `FK_FA230DB9655594C6` FOREIGN KEY (`id_horaire`) REFERENCES `horaire` (`id_horaire`);

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `FK_8F91ABF0B911D4E6` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`),
  ADD CONSTRAINT `FK_8F91ABF0E173B1B8` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`);

--
-- Contraintes pour la table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `FK_C744045550EAE44` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `FK_63540598E67F7DC` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`);

--
-- Contraintes pour la table `garage`
--
ALTER TABLE `garage`
  ADD CONSTRAINT `FK_9F26610B50EAE44` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`),
  ADD CONSTRAINT `FK_9F26610BAD4698F3` FOREIGN KEY (`id_ville`) REFERENCES `ville` (`id_ville`);

--
-- Contraintes pour la table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `FK_EDBFD5EC8E67F7DC` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`);

--
-- Contraintes pour la table `horaire`
--
ALTER TABLE `horaire`
  ADD CONSTRAINT `FK_BBC83DB6B911D4E6` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`);

--
-- Contraintes pour la table `lier`
--
ALTER TABLE `lier`
  ADD CONSTRAINT `FK_B133E8FA8E67F7DC` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`),
  ADD CONSTRAINT `FK_B133E8FAF4420F7A` FOREIGN KEY (`id_prestation`) REFERENCES `prestation` (`id_prestation`);

--
-- Contraintes pour la table `modele`
--
ALTER TABLE `modele`
  ADD CONSTRAINT `FK_100285587C582423` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`id_marque`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `FK_6000B0D314E829E9` FOREIGN KEY (`id_status_notif`) REFERENCES `status_notif` (`id_status_notif`),
  ADD CONSTRAINT `FK_6000B0D38E67F7DC` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`);

--
-- Contraintes pour la table `prestation`
--
ALTER TABLE `prestation`
  ADD CONSTRAINT `FK_51C88FADC9486A13` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`);

--
-- Contraintes pour la table `proposer`
--
ALTER TABLE `proposer`
  ADD CONSTRAINT `proposer_id_garage_FK` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`),
  ADD CONSTRAINT `proposer_id_prestation_FK` FOREIGN KEY (`id_prestation`) REFERENCES `prestation` (`id_prestation`);

--
-- Contraintes pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `FK_65E8AA0A6B26CFF3` FOREIGN KEY (`id_status_rdv`) REFERENCES `status_rdv` (`id_status_rdv`),
  ADD CONSTRAINT `FK_65E8AA0A79F41388` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicule` (`id_vehicule`),
  ADD CONSTRAINT `FK_65E8AA0AB911D4E6` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`);

--
-- Contraintes pour la table `souscription`
--
ALTER TABLE `souscription`
  ADD CONSTRAINT `FK_2AED620D9098E86C` FOREIGN KEY (`id_abonnement`) REFERENCES `abonnement` (`id_abonnement`),
  ADD CONSTRAINT `FK_2AED620DB911D4E6` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`);

--
-- Contraintes pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD CONSTRAINT `FK_1D1C63B3DC499668` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`);

--
-- Contraintes pour la table `valeur`
--
ALTER TABLE `valeur`
  ADD CONSTRAINT `FK_1B44CD51B911D4E6` FOREIGN KEY (`id_garage`) REFERENCES `garage` (`id_garage`),
  ADD CONSTRAINT `FK_1B44CD51EBD29955` FOREIGN KEY (`id_reseau`) REFERENCES `reseau_sociaux` (`id_reseau`);

--
-- Contraintes pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD CONSTRAINT `FK_292FFF1D7C582423` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`id_marque`),
  ADD CONSTRAINT `FK_292FFF1DE173B1B8` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


