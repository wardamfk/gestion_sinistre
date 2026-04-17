-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 17 avr. 2026 à 17:01
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_sinistre`
--

-- --------------------------------------------------------

--
-- Structure de la table `agence`
--

DROP TABLE IF EXISTS `agence`;
CREATE TABLE IF NOT EXISTS `agence` (
  `id_agence` int NOT NULL AUTO_INCREMENT,
  `nom_agence` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type_agence` enum('CRMA','CNMA') COLLATE utf8mb4_general_ci NOT NULL,
  `wilaya` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `agence`
--

INSERT INTO `agence` (`id_agence`, `nom_agence`, `type_agence`, `wilaya`) VALUES
(1, 'CRMA Alger', 'CRMA', 'Alger'),
(2, 'CRMA Oran', 'CRMA', 'Oran'),
(3, 'CRMA Constantine', 'CRMA', 'Constantine'),
(4, 'CNMA Direction', 'CNMA', 'Alger'),
(5, 'CRMA Ouargla', 'CRMA', 'Ouargla');

-- --------------------------------------------------------

--
-- Structure de la table `assure`
--

DROP TABLE IF EXISTS `assure`;
CREATE TABLE IF NOT EXISTS `assure` (
  `id_assure` int NOT NULL AUTO_INCREMENT,
  `id_personne` int DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `num_permis` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_delivrance_permis` date DEFAULT NULL,
  `lieu_delivrance_permis` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_permis` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `piece_identite` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_assure`),
  UNIQUE KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `assure`
--

INSERT INTO `assure` (`id_assure`, `id_personne`, `date_creation`, `actif`, `num_permis`, `date_delivrance_permis`, `lieu_delivrance_permis`, `type_permis`, `piece_identite`) VALUES
(2, 7, '2026-03-24', 1, '', '0000-00-00', '<br /><font size=\'1\'><table class=\'xdebug-error xe-deprecated\' dir=\'ltr\' border=\'1\' cellspacing=\'0\' ', 'A', NULL),
(3, 2, '2026-03-24', 1, NULL, NULL, NULL, NULL, NULL),
(4, 19, '2026-04-12', 1, '234567', '2026-02-05', 'LOIN', 'B', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `contrat`
--

DROP TABLE IF EXISTS `contrat`;
CREATE TABLE IF NOT EXISTS `contrat` (
  `id_contrat` int NOT NULL AUTO_INCREMENT,
  `numero_police` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_assure` int DEFAULT NULL,
  `date_effet` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `prime_base` decimal(12,2) DEFAULT NULL,
  `reduction` decimal(12,2) DEFAULT NULL,
  `majoration` decimal(12,2) DEFAULT NULL,
  `prime_nette` decimal(12,2) DEFAULT NULL,
  `complement` decimal(12,2) DEFAULT NULL,
  `total_taxes` decimal(12,2) DEFAULT NULL,
  `total_timbres` decimal(12,2) DEFAULT NULL,
  `net_a_payer` decimal(12,2) DEFAULT NULL,
  `statut` enum('actif','expire','suspendu') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `id_vehicule` int DEFAULT NULL,
  `id_agence` int DEFAULT NULL,
  `id_formule` int DEFAULT NULL,
  PRIMARY KEY (`id_contrat`),
  UNIQUE KEY `numero_police` (`numero_police`),
  KEY `id_assure` (`id_assure`),
  KEY `fk_contrat_vehicule` (`id_vehicule`),
  KEY `fk_contrat_agence` (`id_agence`),
  KEY `id_formule` (`id_formule`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contrat`
--

INSERT INTO `contrat` (`id_contrat`, `numero_police`, `id_assure`, `date_effet`, `date_expiration`, `prime_base`, `reduction`, `majoration`, `prime_nette`, `complement`, `total_taxes`, `total_timbres`, `net_a_payer`, `statut`, `date_creation`, `id_vehicule`, `id_agence`, `id_formule`) VALUES
(1, 'C001', 3, '2026-03-14', '2027-06-29', 30000.00, 355.00, 7888.00, 37533.00, 455.00, 7131.27, 1500.00, 46619.27, 'actif', '2026-03-29', 1, 1, 2),
(2, 'C002', 2, '2026-03-03', '2027-10-30', 2999.00, 299.00, 499.00, 3199.00, 299.00, 607.81, 1500.00, 5605.81, 'actif', '2026-03-30', 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `contrat_garantie`
--

DROP TABLE IF EXISTS `contrat_garantie`;
CREATE TABLE IF NOT EXISTS `contrat_garantie` (
  `id_contrat` int NOT NULL,
  `id_garantie` int NOT NULL,
  PRIMARY KEY (`id_contrat`,`id_garantie`),
  KEY `id_garantie` (`id_garantie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contrat_garantie`
--

INSERT INTO `contrat_garantie` (`id_contrat`, `id_garantie`) VALUES
(1, 1),
(2, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

DROP TABLE IF EXISTS `document`;
CREATE TABLE IF NOT EXISTS `document` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_upload` date DEFAULT NULL,
  `upload_par` int DEFAULT NULL,
  `id_type_document` int DEFAULT NULL,
  PRIMARY KEY (`id_document`),
  KEY `id_dossier` (`id_dossier`),
  KEY `upload_par` (`upload_par`),
  KEY `fk_type_document` (`id_type_document`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `document`
--

INSERT INTO `document` (`id_document`, `id_dossier`, `nom_fichier`, `date_upload`, `upload_par`, `id_type_document`) VALUES
(9, 6, 'Chapitre 3 - Cours.pdf', '2026-03-30', 9, 1),
(10, 5, 'BDAU9862.JPG', '2026-03-31', 9, 1),
(11, 8, 'BDAU9862.JPG', '2026-04-01', 9, 1),
(12, 9, 'BDAU9862.JPG', '2026-04-02', 9, 1),
(13, 10, 'BDAU9862.JPG', '2026-04-02', 9, 3),
(14, 11, 'DING2023.JPG', '2026-04-02', 9, 1),
(15, 7, 'BDAU9862.JPG', '2026-04-02', 2, 1),
(16, 6, 'BDAU9862.JPG', '2026-04-03', 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `dossier`
--

DROP TABLE IF EXISTS `dossier`;
CREATE TABLE IF NOT EXISTS `dossier` (
  `id_dossier` int NOT NULL AUTO_INCREMENT,
  `numero_dossier` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `cree_par` int DEFAULT NULL,
  `date_transmission` date DEFAULT NULL,
  `transmis_par` int DEFAULT NULL,
  `info_complementaire` text COLLATE utf8mb4_general_ci,
  `id_etat` int DEFAULT NULL,
  `id_contrat` int DEFAULT NULL,
  `id_tiers` int DEFAULT NULL,
  `date_sinistre` date DEFAULT NULL,
  `lieu_sinistre` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `delai_declaration` int DEFAULT NULL,
  `total_reserve` decimal(10,2) DEFAULT NULL,
  `statut_validation` enum('non_soumis','en_attente','valide','refuse') COLLATE utf8mb4_general_ci DEFAULT 'non_soumis',
  `date_validation` date DEFAULT NULL,
  `date_refus` date DEFAULT NULL,
  `date_cloture` date DEFAULT NULL,
  `commentaire_cnma` text COLLATE utf8mb4_general_ci,
  `valide_par` int DEFAULT NULL,
  `id_expert` int DEFAULT NULL,
  PRIMARY KEY (`id_dossier`),
  UNIQUE KEY `numero_dossier` (`numero_dossier`),
  KEY `cree_par` (`cree_par`),
  KEY `transmis_par` (`transmis_par`),
  KEY `fk_etat_dossier` (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossier`
--

INSERT INTO `dossier` (`id_dossier`, `numero_dossier`, `date_creation`, `cree_par`, `date_transmission`, `transmis_par`, `info_complementaire`, `id_etat`, `id_contrat`, `id_tiers`, `date_sinistre`, `lieu_sinistre`, `description`, `delai_declaration`, `total_reserve`, `statut_validation`, `date_validation`, `date_refus`, `date_cloture`, `commentaire_cnma`, `valide_par`, `id_expert`) VALUES
(4, 'DOS-2026-0001', '2026-03-30', 9, NULL, NULL, 'HH', 8, 1, 5, '2026-03-14', 'ALGER', 'Accident matériel', 4, 5350.00, 'valide', '2026-04-05', NULL, '2026-04-03', NULL, NULL, 2),
(5, 'DOS-2026-0002', '2026-03-30', 9, NULL, NULL, 'HKJ', 2, 2, 5, '2026-03-14', 'ALGER', 'Accident matériel', 7, 5555.00, '', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'DOS-2026-0003', '2026-03-30', 9, '2026-04-02', 2, '', 2, 1, 7, '2026-03-06', 'ALGER', 'ACCIDE', 20, 600867.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(7, 'DOS-2026-0004', '2026-03-31', 9, NULL, NULL, '', 4, 1, 5, '2026-03-12', 'ALGER', 'ACCCIDENT', 2, 5050000.00, 'valide', '2026-04-02', NULL, NULL, NULL, 2, 1),
(8, 'DOS-2026-0005', '2026-03-31', 9, NULL, NULL, '', 8, 2, 5, '2026-03-03', 'ALGER', 'S', 16, 11991.00, 'valide', NULL, NULL, NULL, NULL, NULL, 2),
(9, 'DOS-2026-0006', '2026-04-02', 9, NULL, NULL, '', 2, 1, 5, '2026-04-14', 'ALGER', 'ASCC', 2, 55.00, 'valide', NULL, NULL, NULL, NULL, NULL, 2),
(10, 'DOS-2026-0007', '2026-04-02', 9, NULL, NULL, '', 8, 2, 6, '2026-03-30', 'ALGER', 'JK', 2, 0.00, 'valide', '2026-04-04', NULL, NULL, NULL, NULL, 3),
(11, 'DOS-2026-0008', '2026-04-02', 9, NULL, NULL, '', 7, 1, 7, '2026-04-08', 'BIRTOTA', 'BR', 3, 10410.00, 'valide', NULL, NULL, NULL, NULL, NULL, 5),
(12, 'DOS-2026-0009', '2026-04-03', 10, NULL, NULL, '', 9, 1, 5, '2026-03-30', 'ALGER', 'HG', 2, 0.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 4);

-- --------------------------------------------------------

--
-- Structure de la table `encaissement`
--

DROP TABLE IF EXISTS `encaissement`;
CREATE TABLE IF NOT EXISTS `encaissement` (
  `id_encaissement` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_encaissement` date DEFAULT NULL,
  `id_tiers` int DEFAULT NULL,
  `type` enum('recours','franchise','epave','autre') COLLATE utf8mb4_general_ci DEFAULT 'recours',
  `commentaire` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_encaissement`),
  KEY `id_dossier` (`id_dossier`),
  KEY `fk_encaissement_tiers` (`id_tiers`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `etat_dossier`
--

DROP TABLE IF EXISTS `etat_dossier`;
CREATE TABLE IF NOT EXISTS `etat_dossier` (
  `id_etat` int NOT NULL AUTO_INCREMENT,
  `nom_etat` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `motif_obligatoire` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etat_dossier`
--

INSERT INTO `etat_dossier` (`id_etat`, `nom_etat`, `motif_obligatoire`) VALUES
(1, 'Brouillon', 0),
(2, 'En cours CRMA', 0),
(3, 'Transmis CNMA', 0),
(4, 'Validé CNMA', 0),
(5, 'Refusé CNMA', 0),
(6, 'Complément demandé', 0),
(7, 'Règlement partiel', 0),
(8, 'Règlement définitif amiable', 0),
(9, 'En cours d\'expertise', 0),
(11, 'Classé sans suite', 1),
(12, 'Classé après rejet', 0),
(13, 'Classé en attente recours', 0),
(14, 'Clôturé', 0),
(15, 'Repris', 1),
(16, 'En cours de contre-expertise', 0),
(17, 'Règlement définitif judiciaire', 0),
(18, 'Repris pour recours abouti', 0),
(19, 'Classé après recours abouti', 0),
(20, 'Gestion pour recours', 1);

-- --------------------------------------------------------

--
-- Structure de la table `expert`
--

DROP TABLE IF EXISTS `expert`;
CREATE TABLE IF NOT EXISTS `expert` (
  `id_expert` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activite` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_personne` int DEFAULT NULL,
  PRIMARY KEY (`id_expert`),
  KEY `fk_expert_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `expert`
--

INSERT INTO `expert` (`id_expert`, `nom`, `prenom`, `telephone`, `email`, `activite`, `id_personne`) VALUES
(1, 'Brahimi', 'Ahmed', '0550000001', 'expert1@mail.dz', 'Expert automobile', 15),
(2, 'Benali', 'Karim', '0550000002', 'expert2@mail.dz', 'Expert automobile', NULL),
(3, 'Mansouri', 'Nadia', '0550000003', 'expert3@mail.dz', 'Expert automobile', NULL),
(4, 'Ferhat', 'Samir', '0550000004', 'expert4@mail.dz', 'Expert automobile', 17),
(5, 'Saadi', 'Lina', '0550000005', 'expert5@mail.dz', 'Expert automobile', 18),
(7, 'warda', 'gjhkj', '0541775494', 'warda.moufouki@esst-sup.com', 'Expert automobile', 29);

-- --------------------------------------------------------

--
-- Structure de la table `expertise`
--

DROP TABLE IF EXISTS `expertise`;
CREATE TABLE IF NOT EXISTS `expertise` (
  `id_expertise` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `id_expert` int DEFAULT NULL,
  `date_expertise` date DEFAULT NULL,
  `rapport_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `montant_indemnite` decimal(12,2) DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_expertise`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_expert` (`id_expert`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `expertise`
--

INSERT INTO `expertise` (`id_expertise`, `id_dossier`, `id_expert`, `date_expertise`, `rapport_pdf`, `montant_indemnite`, `commentaire`) VALUES
(8, 4, 1, NULL, NULL, NULL, NULL),
(9, 5, 1, NULL, NULL, NULL, NULL),
(10, 6, 2, NULL, NULL, NULL, NULL),
(11, 6, 1, '2026-03-04', 'BDAU9862.JPG', 100000.00, ''),
(12, 7, 3, NULL, NULL, NULL, NULL),
(13, 8, 2, '2026-03-05', 'BDAU9862.JPG', 10000.00, ''),
(14, 8, 2, '2026-03-26', 'BDAU9862.JPG', 100.00, ''),
(15, 8, 2, '2026-04-02', 'BDAU9862.JPG', 100.00, 'APRES EXPERTISE 2'),
(16, 8, 2, '2026-04-01', 'BDAU9862.JPG', 30.00, 'GH'),
(17, 7, 1, '2026-04-23', 'BDAU9862.JPG', 5000000.00, ''),
(18, 4, 2, '2026-05-02', 'BDAU9862.JPG', 300.00, ''),
(19, 9, 2, '2026-04-23', 'BDAU9862.JPG', 45.00, ''),
(20, 11, 5, '2026-04-02', 'BDAU9862.JPG', 10000.00, '');

-- --------------------------------------------------------

--
-- Structure de la table `formule`
--

DROP TABLE IF EXISTS `formule`;
CREATE TABLE IF NOT EXISTS `formule` (
  `id_formule` int NOT NULL AUTO_INCREMENT,
  `nom_formule` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_formule`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formule`
--

INSERT INTO `formule` (`id_formule`, `nom_formule`) VALUES
(1, 'RC'),
(2, 'RC + Défense'),
(3, 'Tiers étendu'),
(4, 'Tous risques');

-- --------------------------------------------------------

--
-- Structure de la table `formule_garantie`
--

DROP TABLE IF EXISTS `formule_garantie`;
CREATE TABLE IF NOT EXISTS `formule_garantie` (
  `id_formule` int NOT NULL,
  `id_garantie` int NOT NULL,
  PRIMARY KEY (`id_formule`,`id_garantie`),
  KEY `id_garantie` (`id_garantie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formule_garantie`
--

INSERT INTO `formule_garantie` (`id_formule`, `id_garantie`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(2, 2),
(4, 2),
(3, 3),
(4, 3),
(3, 4),
(4, 4),
(4, 5),
(4, 6);

-- --------------------------------------------------------

--
-- Structure de la table `garantie`
--

DROP TABLE IF EXISTS `garantie`;
CREATE TABLE IF NOT EXISTS `garantie` (
  `id_garantie` int NOT NULL AUTO_INCREMENT,
  `nom_garantie` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `code_garantie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_garantie`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `garantie`
--

INSERT INTO `garantie` (`id_garantie`, `nom_garantie`, `description`, `code_garantie`) VALUES
(1, 'Responsabilité civile', 'Couvre les dommages causés aux tiers', 'RC'),
(2, 'Défense recours', 'Frais d avocat et recours', 'DR'),
(3, 'Vol', 'Vol du véhicule', 'VOL'),
(4, 'Incendie', 'Incendie du véhicule', 'INC'),
(5, 'Bris de glace', 'Vitres et pare-brise', 'BG'),
(6, 'Tous risques', 'Tous les dommages', 'TR');

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

DROP TABLE IF EXISTS `historique`;
CREATE TABLE IF NOT EXISTS `historique` (
  `id_historique` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_action` datetime DEFAULT NULL,
  `fait_par` int DEFAULT NULL,
  `ancien_etat` int DEFAULT NULL,
  `nouvel_etat` int DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `id_motif` int DEFAULT NULL,
  PRIMARY KEY (`id_historique`),
  KEY `id_dossier` (`id_dossier`),
  KEY `fait_par` (`fait_par`),
  KEY `fk_ancien_etat` (`ancien_etat`),
  KEY `fk_nouvel_etat` (`nouvel_etat`),
  KEY `fk_historique_motif` (`id_motif`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique`
--

INSERT INTO `historique` (`id_historique`, `id_dossier`, `action`, `date_action`, `fait_par`, `ancien_etat`, `nouvel_etat`, `commentaire`, `id_motif`) VALUES
(8, 4, 'Création dossier', '2026-03-30 18:42:41', 9, NULL, 2, NULL, NULL),
(9, 5, 'Création dossier', '2026-03-30 20:47:42', 9, NULL, 2, NULL, NULL),
(10, 5, 'Ajout réserve: 34 DA', '2026-03-30 00:00:00', 9, NULL, NULL, NULL, NULL),
(11, 5, 'Ajout réserve: 899 DA', '2026-03-30 00:00:00', 9, NULL, NULL, NULL, NULL),
(12, 6, 'Création dossier', '2026-03-30 22:53:42', 9, NULL, 2, NULL, NULL),
(14, 5, 'Ajout réserve: 345 DA', '2026-03-31 11:42:25', 9, NULL, NULL, NULL, NULL),
(15, 5, 'Ajout règlement: 555 DA', '2026-03-31 11:42:55', 9, NULL, NULL, NULL, NULL),
(16, 5, 'Validation automatique CRMA', '2026-03-31 12:49:37', 9, NULL, NULL, NULL, NULL),
(17, 5, 'Ajout réserve: 100 DA', '2026-03-31 12:49:37', 9, NULL, NULL, NULL, NULL),
(20, 6, 'Expertise + Transmission CNMA', '2026-03-31 16:15:04', 9, NULL, NULL, NULL, NULL),
(21, 7, 'Création dossier', '2026-03-31 16:41:17', 9, NULL, 2, NULL, NULL),
(22, 8, 'Création dossier', '2026-03-31 17:09:37', 9, NULL, 1, NULL, NULL),
(23, 8, 'Affectation expert', '2026-03-31 17:09:37', 9, NULL, 2, NULL, NULL),
(24, 8, 'Expertise + Transmission CNMA', '2026-03-31 17:10:59', 9, NULL, NULL, NULL, NULL),
(25, 8, 'Expertise validée CRMA', '2026-03-31 19:38:37', 9, NULL, NULL, NULL, NULL),
(26, 8, 'Règlement partiel', '2026-03-31 20:09:39', 9, NULL, 7, NULL, NULL),
(27, 8, 'Règlement partiel', '2026-03-31 23:10:04', 9, NULL, 7, NULL, NULL),
(28, 8, 'Ajout réserve', '2026-03-31 23:24:01', 9, 7, 7, NULL, NULL),
(29, 8, 'Ajout réserve', '2026-03-31 23:27:35', 9, 7, 7, NULL, NULL),
(30, 8, 'Ajout réserve', '2026-03-31 23:27:53', 9, 7, 7, NULL, NULL),
(31, 8, 'Ajout réserve', '2026-03-31 23:31:29', 9, 7, 7, NULL, NULL),
(32, 8, 'Ajout réserve', '2026-03-31 23:32:29', 9, 7, 7, NULL, NULL),
(33, 8, 'Ajout réserve', '2026-03-31 23:32:51', 9, 7, 7, NULL, NULL),
(34, 8, 'Ajout réserve', '2026-03-31 23:33:23', 9, 7, 7, NULL, NULL),
(35, 8, 'Ajout réserve', '2026-03-31 23:35:58', 9, 7, 7, NULL, NULL),
(36, 8, 'Ajout réserve', '2026-03-31 23:37:00', 9, 7, 7, NULL, NULL),
(37, 8, 'Ajout réserve', '2026-03-31 23:37:48', 9, 7, 7, NULL, NULL),
(38, 8, 'Ajout réserve', '2026-03-31 23:38:08', 9, 7, 7, NULL, NULL),
(39, 8, 'Ajout réserve', '2026-03-31 23:38:48', 9, 7, 7, NULL, NULL),
(40, 8, 'Ajout réserve', '2026-03-31 23:58:06', 9, 7, 7, NULL, NULL),
(41, 8, 'Ajout réserve', '2026-04-01 00:08:36', 9, 7, 7, NULL, NULL),
(42, 8, 'Ajout réserve', '2026-04-01 00:08:50', 9, 7, 7, NULL, NULL),
(43, 8, 'Ajout réserve', '2026-04-01 00:12:11', 9, 7, 7, NULL, NULL),
(45, 8, 'Ajout réserve', '2026-04-01 00:25:25', 9, 7, 7, NULL, NULL),
(46, 8, 'Modification expertise', '2026-04-01 00:55:52', 9, NULL, NULL, NULL, NULL),
(47, 8, 'Modification règlement', '2026-04-01 00:58:55', 9, NULL, NULL, NULL, NULL),
(48, 8, 'Ajout réserve', '2026-04-01 12:10:40', 9, 7, 7, NULL, NULL),
(49, 8, 'Modification réserve', '2026-04-01 12:10:55', 9, NULL, NULL, NULL, NULL),
(50, 8, 'Expertise validée CRMA', '2026-04-01 12:12:13', 9, NULL, NULL, NULL, NULL),
(51, 8, 'Expertise validée CRMA', '2026-04-01 13:06:31', 9, NULL, NULL, NULL, NULL),
(52, 8, 'Modification réserve', '2026-04-01 13:07:21', 9, NULL, NULL, NULL, NULL),
(53, 8, 'Ajout réserve', '2026-04-01 13:08:17', 9, 2, 2, NULL, NULL),
(54, 8, 'Règlement partiel', '2026-04-01 13:16:31', 9, NULL, 7, NULL, NULL),
(55, 7, 'Expertise + Transmission CNMA', '2026-04-01 13:19:11', 9, NULL, NULL, NULL, NULL),
(56, 8, 'Règlement total', '2026-04-01 20:56:09', 9, NULL, 8, NULL, NULL),
(57, 4, 'Expertise validée CRMA', '2026-04-01 22:44:30', 9, NULL, NULL, NULL, NULL),
(58, 4, 'Règlement total', '2026-04-01 22:59:28', 9, NULL, 8, NULL, NULL),
(59, 4, 'Expertise validée CRMA', '2026-04-01 23:40:33', 9, NULL, NULL, NULL, NULL),
(60, 4, 'Règlement total', '2026-04-01 23:44:13', 9, NULL, 8, NULL, NULL),
(61, 6, 'Transmission CNMA - Dépassement seuil', '2026-04-01 23:50:05', 9, 2, 3, NULL, NULL),
(62, 9, 'Création dossier', '2026-04-02 00:32:19', 9, NULL, 2, NULL, NULL),
(63, 9, 'Affectation expert', '2026-04-02 00:32:19', 9, NULL, 2, NULL, NULL),
(64, 9, 'Expertise validée CRMA', '2026-04-02 00:32:58', 9, 2, 2, NULL, NULL),
(66, 10, 'Création dossier', '2026-04-02 00:50:54', 9, NULL, 2, NULL, NULL),
(67, 10, 'Affectation expert', '2026-04-02 00:50:54', 9, NULL, 2, NULL, NULL),
(68, 11, 'Création dossier', '2026-04-02 12:02:08', 9, NULL, 2, NULL, NULL),
(69, 11, 'Affectation expert', '2026-04-02 12:02:08', 9, 2, 9, NULL, NULL),
(70, 11, 'Expertise validée CRMA', '2026-04-02 12:02:57', 9, 9, 2, NULL, NULL),
(71, 11, 'Ajout réserve', '2026-04-02 12:03:19', 9, 2, 2, NULL, NULL),
(72, 11, 'Règlement partiel', '2026-04-02 12:03:44', 9, 2, 7, NULL, NULL),
(75, 7, 'Validation CNMA', '2026-04-02 20:39:20', 2, 3, 4, NULL, NULL),
(76, 6, 'Demande de complément CNMA', '2026-04-02 20:40:11', 2, 3, 2, NULL, NULL),
(77, 6, 'Transmission CNMA - Dépassement seuil', '2026-04-02 21:13:27', 2, 2, 3, NULL, NULL),
(78, 6, 'Demande de complément CNMA', '2026-04-03 00:40:45', 2, 3, 2, 'Dossier renvoyé au CRMA pour complément de documents', NULL),
(80, 4, 'Clôture dossier CNMA', '2026-04-03 00:50:06', 2, 8, 14, 'Dossier clôturé définitivement par la CNMA', NULL),
(81, 12, 'Création dossier', '2026-04-03 12:31:35', 10, NULL, 2, NULL, NULL),
(82, 12, 'Affectation expert', '2026-04-03 12:31:35', 10, 2, 9, NULL, NULL),
(83, 10, 'Règlement total', '2026-04-04 14:10:51', 9, 2, 8, NULL, NULL),
(84, 4, 'Règlement total', '2026-04-05 19:44:32', 13, 14, 8, NULL, NULL),
(85, 4, 'Suppression règlement', '2026-04-05 20:19:36', 9, 8, 8, NULL, NULL),
(86, 11, 'Suppression règlement', '2026-04-05 20:22:13', 13, 7, 7, NULL, NULL),
(87, 10, 'Suppression règlement', '2026-04-07 15:20:50', 9, 8, 8, NULL, NULL),
(88, 11, 'Règlement partiel', '2026-04-17 14:34:41', 9, 7, 7, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `motif`
--

DROP TABLE IF EXISTS `motif`;
CREATE TABLE IF NOT EXISTS `motif` (
  `id_motif` int NOT NULL AUTO_INCREMENT,
  `id_etat` int DEFAULT NULL,
  `nom_motif` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_motif`),
  KEY `id_etat` (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `motif`
--

INSERT INTO `motif` (`id_motif`, `id_etat`, `nom_motif`) VALUES
(18, 11, 'Absence de garantie couvrant le sinistre'),
(19, 11, 'Sinistre survenu hors période de couverture'),
(20, 11, 'Exclusions de garanties mentionnées sur les conditions générales et particulières'),
(21, 11, 'Prescription'),
(22, 11, 'Absence de dégâts matériels engendrés par le sinistre'),
(23, 11, 'Dégâts sous franchise'),
(24, 11, 'Absence de PV d\'expertise ou de photos d\'expertise (matérialité non prouvée)'),
(25, 11, 'Fausse déclaration (cas fraude à l\'assurance avéré)'),
(26, 11, 'Absence de la réclamation du tiers dans le cas d\'un règlement au titre de la garantie RC'),
(27, 15, 'Réception d\'une citation à comparaître'),
(28, 15, 'Réclamation fondée de l\'assuré ou d\'une victime du sinistre'),
(29, 15, 'Réception d\'un jugement par défaut'),
(30, 15, 'Repris pour recours abouti'),
(31, 15, 'Réouverture pour recours'),
(32, 15, 'Réouverture pour erreur de classement'),
(33, 19, 'Encaissement du recours'),
(34, 20, 'Responsabilité de l\'assuré dégagée entièrement');

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `id_notification` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int NOT NULL,
  `id_expediteur` int NOT NULL,
  `id_destinataire` int NOT NULL,
  `type` enum('validation','refus','complement','reglement','cloture') COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `date_notification` datetime DEFAULT CURRENT_TIMESTAMP,
  `lu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_notification`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_expediteur` (`id_expediteur`),
  KEY `id_destinataire` (`id_destinataire`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`id_notification`, `id_dossier`, `id_expediteur`, `id_destinataire`, `type`, `message`, `date_notification`, `lu`) VALUES
(1, 6, 2, 9, 'complement', 'Complément demandé pour le dossier DOS-2026-0003. Veuillez compléter les documents manquants et re-transmettre.', '2026-04-03 00:40:45', 1),
(2, 4, 2, 9, 'cloture', 'Le dossier DOS-2026-0001 a été clôturé définitivement par la CNMA.', '2026-04-03 00:50:06', 1),
(3, 10, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0007. Veuillez vous présenter à votre agence CRMA pour le récupérer.', '2026-04-04 14:15:18', 1),
(4, 4, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0001. Veuillez vous présenter à votre agence CRMA pour le récupérer.', '2026-04-05 20:19:54', 1),
(5, 4, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0001. Veuillez vous présenter à votre agence CRMA pour le récupérer.', '2026-04-05 20:19:59', 1),
(6, 8, 8, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0005. Veuillez vous présenter à votre agence CRMA pour le récupérer.', '2026-04-05 20:32:51', 0);

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

DROP TABLE IF EXISTS `parametre`;
CREATE TABLE IF NOT EXISTS `parametre` (
  `id_parametre` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valeur` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_parametre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parametre`
--

INSERT INTO `parametre` (`id_parametre`, `nom`, `valeur`) VALUES
(1, 'taxe', '0.19'),
(2, 'timbre', '1500');

-- --------------------------------------------------------

--
-- Structure de la table `personne`
--

DROP TABLE IF EXISTS `personne`;
CREATE TABLE IF NOT EXISTS `personne` (
  `id_personne` int NOT NULL AUTO_INCREMENT,
  `type_personne` enum('physique','morale') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `raison_sociale` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `num_identite` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nin` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nif` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `num_id_fiscal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activite` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `statut_personne` enum('assure','expert','adversaire') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_personne`),
  UNIQUE KEY `num_identite` (`num_identite`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `personne`
--

INSERT INTO `personne` (`id_personne`, `type_personne`, `nom`, `prenom`, `raison_sociale`, `num_identite`, `telephone`, `adresse`, `email`, `date_naissance`, `lieu_naissance`, `nin`, `nif`, `num_id_fiscal`, `activite`, `statut_personne`) VALUES
(2, 'physique', 'warda', 'mf', '', '026730618', '0541775494', 'alger', 'warda.moufouki@esst-sup.com', NULL, NULL, NULL, NULL, NULL, NULL, 'assure'),
(7, 'physique', 'Moufouki', 'Aida', '', '026737644', '0541775499', 'alger', 'medecin@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'assure'),
(9, 'physique', 'Ali', 'Karim', NULL, '026737693', '0551111111', 'Alger', 'ali@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(10, 'physique', 'Ben', 'Salah', NULL, '026737690', '0552222222', 'Blida', 'ben@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(11, 'physique', 'Kaci', 'Nadia', NULL, NULL, '0553333333', 'Oran', 'kaci@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(15, 'physique', 'Brahimi', 'Ahmed', NULL, NULL, '0550000001', 'Alger', 'expert1@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(17, 'physique', 'Ferhat', 'Samir', NULL, NULL, '0550000004', 'Alger', 'expert4@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(18, 'physique', 'Saadi', 'Lina', NULL, '026737600', '0550000005', 'Alger', 'expert5@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(19, 'physique', 'mfk', 'fadia', '', '937693688', '0541775498', 'alger', 'fadia.moufouki@gmail.com', '2004-03-08', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(26, 'physique', 'warda', 'faten', '', '026736614', '0541775499', 'alger', 'faten.moufouki@esst-sup.com', '2004-03-10', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(27, 'physique', 'SALM', 'SLMNI', NULL, NULL, '0541775494', 'ALGER', 'SALIM.moufouki@esst-sup.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(29, 'physique', 'warda', 'gjhkj', '', '026737698', '0541775494', 'ALGER', 'warda.moufouki@esst-sup.com', '2004-03-10', 'H', NULL, NULL, NULL, NULL, 'expert'),
(30, 'physique', 'kalem', 'zohra', '', '737693617', '0541775494', 'ALGER', 'kalemzohra70@gmail.com', '2000-04-16', 'biar', NULL, NULL, NULL, NULL, 'assure');

-- --------------------------------------------------------

--
-- Structure de la table `reglement`
--

DROP TABLE IF EXISTS `reglement`;
CREATE TABLE IF NOT EXISTS `reglement` (
  `id_reglement` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `id_garantie` int DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_reglement` date DEFAULT NULL,
  `mode_paiement` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `saisi_par` int DEFAULT NULL,
  `statut` enum('en_attente','disponible','remis') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `reference_paiement` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_reglement`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_garantie` (`id_garantie`),
  KEY `saisi_par` (`saisi_par`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reglement`
--

INSERT INTO `reglement` (`id_reglement`, `id_dossier`, `id_garantie`, `montant`, `date_reglement`, `mode_paiement`, `saisi_par`, `statut`, `reference_paiement`, `commentaire`) VALUES
(1, 5, 1, 555.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(2, 8, NULL, 2000.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(3, 8, NULL, 6.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(4, 8, NULL, 1000.00, '2026-04-01', 'Chèque', 9, 'en_attente', NULL, ''),
(5, 8, NULL, 8985.00, '2026-04-01', 'Chèque', 9, 'disponible', NULL, ''),
(6, 4, NULL, 5050.00, '2026-04-01', 'Chèque', 9, 'disponible', NULL, ''),
(7, 4, NULL, 300.00, '2026-04-01', 'Chèque', 9, 'disponible', NULL, ''),
(11, 11, NULL, 10.00, '2026-04-17', 'Chèque', 9, 'en_attente', NULL, '');

-- --------------------------------------------------------

--
-- Structure de la table `reserve`
--

DROP TABLE IF EXISTS `reserve`;
CREATE TABLE IF NOT EXISTS `reserve` (
  `id_reserve` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `id_garantie` int DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_reserve` date DEFAULT NULL,
  `type_reserve` enum('initiale','expertise','ajustement') COLLATE utf8mb4_general_ci NOT NULL,
  `cree_par` int DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `statut` enum('actif','annule') COLLATE utf8mb4_general_ci DEFAULT 'actif',
  PRIMARY KEY (`id_reserve`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_garantie` (`id_garantie`),
  KEY `cree_par` (`cree_par`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reserve`
--

INSERT INTO `reserve` (`id_reserve`, `id_dossier`, `id_garantie`, `montant`, `date_reserve`, `type_reserve`, `cree_par`, `date_creation`, `commentaire`, `statut`) VALUES
(7, 4, 1, 5000.00, '2026-03-30', 'initiale', 9, '2026-03-30', NULL, 'actif'),
(8, 5, 1, 5555.00, '2026-03-30', 'initiale', 9, '2026-03-30', NULL, 'actif'),
(9, 6, 1, 777.00, '2026-03-30', 'initiale', 9, '2026-03-30', NULL, 'actif'),
(10, 5, 1, 345.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(11, 5, 1, 100.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(12, 6, 1, 100000.00, '2026-03-31', 'expertise', 9, '2026-03-31', 'Réserve après expertise', 'actif'),
(13, 7, 1, 50000.00, '2026-03-31', 'initiale', 9, '2026-03-31', NULL, 'actif'),
(15, 8, 1, 10000.00, '2026-03-31', 'expertise', 9, '2026-03-31', 'Réserve après expertise', 'actif'),
(16, 8, 1, 1000.00, '2026-03-31', 'expertise', 9, '2026-03-31', 'Réserve après expertise', 'actif'),
(17, 8, 1, 10.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(18, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(19, 8, 1, 5.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(20, 8, 1, 2.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(21, 8, 1, 4.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(22, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(23, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(24, 8, 1, 3.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(25, 8, 1, 4.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(26, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(27, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(28, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(29, 8, 1, 1.00, '2026-03-31', '', 9, '2026-03-31', '', 'actif'),
(30, 8, 1, 2.00, '2026-04-01', '', 9, '2026-04-01', '', 'actif'),
(31, 8, 1, 444.00, '2026-04-01', '', 9, '2026-04-01', '', 'actif'),
(32, 8, 1, 5.00, '2026-04-01', '', 9, '2026-04-01', '', 'actif'),
(33, 8, 1, 3.00, '2026-04-01', '', 9, '2026-04-01', '', 'actif'),
(34, 8, 1, 23.00, '2026-04-01', '', 9, '2026-04-01', '', 'actif'),
(35, 8, 1, 100.00, '2026-04-01', 'expertise', 9, '2026-04-01', 'Réserve après expertise', 'actif'),
(36, 8, 1, 34.00, '2026-04-01', 'expertise', 9, '2026-04-01', 'AJUSTEMENT', 'actif'),
(37, 8, 1, 345.00, '2026-04-01', 'ajustement', 9, '2026-04-01', 'AJUSTEMENT', 'actif'),
(38, 7, 1, 5000000.00, '2026-04-01', 'expertise', 9, '2026-04-01', 'Réserve après expertise', 'actif'),
(39, 4, 1, 50.00, '2026-04-01', 'expertise', 9, '2026-04-01', 'Réserve après expertise', 'actif'),
(40, 4, 1, 300.00, '2026-04-01', 'expertise', 9, '2026-04-01', 'Réserve après expertise', 'actif'),
(41, 6, 1, 500000.00, '2026-04-01', 'ajustement', 9, '2026-04-01', '', 'actif'),
(42, 9, 1, 10.00, '2026-04-02', 'initiale', 9, '2026-04-02', NULL, 'actif'),
(43, 9, 1, 45.00, '2026-04-02', 'expertise', 9, '2026-04-02', 'Réserve après expertise', 'actif'),
(44, 11, 1, 400.00, '2026-04-02', 'initiale', 9, '2026-04-02', NULL, 'actif'),
(45, 11, 1, 10000.00, '2026-04-02', 'expertise', 9, '2026-04-02', 'Réserve après expertise', 'actif'),
(46, 11, 1, 10.00, '2026-04-02', 'ajustement', 9, '2026-04-02', '', 'actif'),
(47, 6, 1, 90.00, '2026-04-02', 'ajustement', 2, '2026-04-02', 'UNPEU', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `seuil_validation`
--

DROP TABLE IF EXISTS `seuil_validation`;
CREATE TABLE IF NOT EXISTS `seuil_validation` (
  `id_seuil` int NOT NULL AUTO_INCREMENT,
  `montant_min` decimal(12,2) DEFAULT NULL,
  `montant_max` decimal(12,2) DEFAULT NULL,
  `niveau_validation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_seuil`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `seuil_validation`
--

INSERT INTO `seuil_validation` (`id_seuil`, `montant_min`, `montant_max`, `niveau_validation`) VALUES
(1, 0.00, 500000.00, 'Gestionnaire'),
(3, 500000.00, 999999999.00, 'CNMA');

-- --------------------------------------------------------

--
-- Structure de la table `tiers`
--

DROP TABLE IF EXISTS `tiers`;
CREATE TABLE IF NOT EXISTS `tiers` (
  `id_tiers` int NOT NULL AUTO_INCREMENT,
  `id_personne` int DEFAULT NULL,
  `compagnie_assurance` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_police` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable` enum('oui','non','partiel') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_tiers`),
  KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tiers`
--

INSERT INTO `tiers` (`id_tiers`, `id_personne`, `compagnie_assurance`, `numero_police`, `responsable`) VALUES
(5, 9, 'SAA', 'SAA123456', 'non'),
(6, 10, 'CAAR', 'CAAR654321', 'oui'),
(7, 11, 'GAM', 'GAM456123', 'oui'),
(8, 27, 'SAA', 'saa23E', 'non');

-- --------------------------------------------------------

--
-- Structure de la table `type_document`
--

DROP TABLE IF EXISTS `type_document`;
CREATE TABLE IF NOT EXISTS `type_document` (
  `id_type_document` int NOT NULL AUTO_INCREMENT,
  `nom_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_type_document`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `type_document`
--

INSERT INTO `type_document` (`id_type_document`, `nom_type`) VALUES
(1, 'Constat'),
(2, 'PV police'),
(3, 'Photos accident'),
(4, 'Carte grise'),
(5, 'Permis conduire'),
(6, 'Facture réparation'),
(7, 'Rapport expertise');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('CNMA','CRMA','ASSURE') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_agence` int DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `id_personne` int DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`),
  KEY `id_agence` (`id_agence`),
  KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `id_agence`, `actif`, `id_personne`) VALUES
(2, 'Admin CNMA', NULL, 'admin@cnma.dz', '$2y$10$SGV3kl4Q1PAdY6lKs3XTRefMCmq.IYZ2OcmGijgxQ2DhEHnNajrou', 'CNMA', NULL, 1, NULL),
(8, NULL, NULL, 'medecin@gmail.com', '$2y$10$0s91L7PPUpFwEdwuMKLXS.a2q8UFt5gDmaqRB4ib3FGPfsQBj6Ccq', 'ASSURE', NULL, 1, 7),
(9, 'Agent Alger', NULL, 'alger@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 1, 1, NULL),
(10, 'Agent Oran', NULL, 'oran@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 2, 1, NULL),
(11, 'Agent Constantine', NULL, 'constantine@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 3, 1, NULL),
(12, 'Agent Ouargla', NULL, 'ouargla@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 5, 1, NULL),
(13, NULL, NULL, 'warda.moufouki@esst-sup.com', '$2y$10$a/lCJPeGNRJS07fCPJA4cOPn5sZtS9i93kyaEZM1qChFjsTWeEJ7C', 'ASSURE', NULL, 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

DROP TABLE IF EXISTS `vehicule`;
CREATE TABLE IF NOT EXISTS `vehicule` (
  `id_vehicule` int NOT NULL AUTO_INCREMENT,
  `marque` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modele` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `couleur` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre_places` int DEFAULT NULL,
  `matricule` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_chassis` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `annee` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `carrosserie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_vehicule`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicule`
--

INSERT INTO `vehicule` (`id_vehicule`, `marque`, `modele`, `couleur`, `nombre_places`, `matricule`, `numero_chassis`, `numero_serie`, `annee`, `type`, `carrosserie`) VALUES
(1, 'KL', 'KL', 'KL', 4, '567899', '89', '8', 2005, 'Tourisme', 'Berline');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `assure`
--
ALTER TABLE `assure`
  ADD CONSTRAINT `assure_ibfk_1` FOREIGN KEY (`id_personne`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD CONSTRAINT `contrat_ibfk_1` FOREIGN KEY (`id_assure`) REFERENCES `assure` (`id_assure`),
  ADD CONSTRAINT `contrat_ibfk_2` FOREIGN KEY (`id_formule`) REFERENCES `formule` (`id_formule`),
  ADD CONSTRAINT `fk_contrat_agence` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`),
  ADD CONSTRAINT `fk_contrat_vehicule` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicule` (`id_vehicule`);

--
-- Contraintes pour la table `contrat_garantie`
--
ALTER TABLE `contrat_garantie`
  ADD CONSTRAINT `contrat_garantie_ibfk_1` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id_contrat`),
  ADD CONSTRAINT `contrat_garantie_ibfk_2` FOREIGN KEY (`id_garantie`) REFERENCES `garantie` (`id_garantie`);

--
-- Contraintes pour la table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `document_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `document_ibfk_2` FOREIGN KEY (`upload_par`) REFERENCES `utilisateur` (`id_user`),
  ADD CONSTRAINT `fk_type_document` FOREIGN KEY (`id_type_document`) REFERENCES `type_document` (`id_type_document`);

--
-- Contraintes pour la table `dossier`
--
ALTER TABLE `dossier`
  ADD CONSTRAINT `dossier_ibfk_3` FOREIGN KEY (`cree_par`) REFERENCES `utilisateur` (`id_user`),
  ADD CONSTRAINT `dossier_ibfk_4` FOREIGN KEY (`transmis_par`) REFERENCES `utilisateur` (`id_user`),
  ADD CONSTRAINT `fk_etat_dossier` FOREIGN KEY (`id_etat`) REFERENCES `etat_dossier` (`id_etat`);

--
-- Contraintes pour la table `encaissement`
--
ALTER TABLE `encaissement`
  ADD CONSTRAINT `encaissement_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `fk_encaissement_tiers` FOREIGN KEY (`id_tiers`) REFERENCES `tiers` (`id_tiers`);

--
-- Contraintes pour la table `expert`
--
ALTER TABLE `expert`
  ADD CONSTRAINT `fk_expert_personne` FOREIGN KEY (`id_personne`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `expertise`
--
ALTER TABLE `expertise`
  ADD CONSTRAINT `expertise_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `expertise_ibfk_2` FOREIGN KEY (`id_expert`) REFERENCES `expert` (`id_expert`);

--
-- Contraintes pour la table `formule_garantie`
--
ALTER TABLE `formule_garantie`
  ADD CONSTRAINT `formule_garantie_ibfk_1` FOREIGN KEY (`id_formule`) REFERENCES `formule` (`id_formule`),
  ADD CONSTRAINT `formule_garantie_ibfk_2` FOREIGN KEY (`id_garantie`) REFERENCES `garantie` (`id_garantie`);

--
-- Contraintes pour la table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `fk_ancien_etat` FOREIGN KEY (`ancien_etat`) REFERENCES `etat_dossier` (`id_etat`),
  ADD CONSTRAINT `fk_historique_motif` FOREIGN KEY (`id_motif`) REFERENCES `motif` (`id_motif`),
  ADD CONSTRAINT `fk_nouvel_etat` FOREIGN KEY (`nouvel_etat`) REFERENCES `etat_dossier` (`id_etat`),
  ADD CONSTRAINT `historique_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `historique_ibfk_2` FOREIGN KEY (`fait_par`) REFERENCES `utilisateur` (`id_user`);

--
-- Contraintes pour la table `motif`
--
ALTER TABLE `motif`
  ADD CONSTRAINT `motif_ibfk_1` FOREIGN KEY (`id_etat`) REFERENCES `etat_dossier` (`id_etat`);

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateur` (`id_user`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`id_destinataire`) REFERENCES `utilisateur` (`id_user`);

--
-- Contraintes pour la table `reglement`
--
ALTER TABLE `reglement`
  ADD CONSTRAINT `reglement_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `reglement_ibfk_2` FOREIGN KEY (`id_garantie`) REFERENCES `garantie` (`id_garantie`),
  ADD CONSTRAINT `reglement_ibfk_3` FOREIGN KEY (`saisi_par`) REFERENCES `utilisateur` (`id_user`);

--
-- Contraintes pour la table `reserve`
--
ALTER TABLE `reserve`
  ADD CONSTRAINT `reserve_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier` (`id_dossier`),
  ADD CONSTRAINT `reserve_ibfk_2` FOREIGN KEY (`id_garantie`) REFERENCES `garantie` (`id_garantie`),
  ADD CONSTRAINT `reserve_ibfk_3` FOREIGN KEY (`cree_par`) REFERENCES `utilisateur` (`id_user`);

--
-- Contraintes pour la table `tiers`
--
ALTER TABLE `tiers`
  ADD CONSTRAINT `tiers_ibfk_1` FOREIGN KEY (`id_personne`) REFERENCES `personne` (`id_personne`);

--
-- Contraintes pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD CONSTRAINT `utilisateur_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`),
  ADD CONSTRAINT `utilisateur_ibfk_2` FOREIGN KEY (`id_personne`) REFERENCES `personne` (`id_personne`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
