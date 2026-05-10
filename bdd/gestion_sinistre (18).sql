-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 10 mai 2026 à 08:41
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
  `nom_agence` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_agence` enum('CRMA','CNMA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `wilaya` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `agence`
--

INSERT INTO `agence` (`id_agence`, `nom_agence`, `type_agence`, `wilaya`, `code`) VALUES
(1, 'CRMA Alger', 'CRMA', 'Alger', 'ALG'),
(2, 'CRMA Oran', 'CRMA', 'Oran', 'ORA'),
(3, 'CRMA Constantine', 'CRMA', 'Constantine', 'CST'),
(4, 'CNMA Direction', 'CNMA', 'Alger', NULL),
(5, 'CRMA Ouargla', 'CRMA', 'Ouargla', 'OUA');

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
  `num_permis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_delivrance_permis` date DEFAULT NULL,
  `lieu_delivrance_permis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_permis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `piece_identite` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chauffeur_nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chauffeur_prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chauffeur_permis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `chauffeur_type_permis` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_assure`),
  UNIQUE KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `assure`
--

INSERT INTO `assure` (`id_assure`, `id_personne`, `date_creation`, `actif`, `num_permis`, `date_delivrance_permis`, `lieu_delivrance_permis`, `type_permis`, `piece_identite`, `chauffeur_nom`, `chauffeur_prenom`, `chauffeur_permis`, `chauffeur_type_permis`) VALUES
(2, 7, '2026-03-24', 1, '', '0000-00-00', '<br /><font size=\'1\'><table class=\'xdebug-error xe-deprecated\' dir=\'ltr\' border=\'1\' cellspacing=\'0\' ', 'A', NULL, NULL, NULL, NULL, NULL),
(3, 2, '2026-03-24', 1, '234568', '0000-00-00', '', 'A', NULL, NULL, NULL, NULL, NULL),
(4, 19, '2026-04-12', 1, '234567', '2026-02-05', 'LOIN', 'B', NULL, NULL, NULL, NULL, NULL),
(8, 32, '2026-05-04', 1, 'A09065422', '2020-05-05', 'biar', 'B', NULL, NULL, NULL, NULL, NULL),
(9, 34, '2026-05-06', 1, 'ET1637897', '2024-05-07', 'kouba', 'B', NULL, NULL, NULL, NULL, NULL),
(10, 36, '2026-05-07', 1, NULL, NULL, NULL, NULL, NULL, 'maleki', 'malek', 'ALG987654', 'C'),
(11, 37, '2026-05-07', 1, 'HB1567783', '2023-05-06', 'kouba', 'B', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `contrat`
--

DROP TABLE IF EXISTS `contrat`;
CREATE TABLE IF NOT EXISTS `contrat` (
  `id_contrat` int NOT NULL AUTO_INCREMENT,
  `numero_police` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_assure` int DEFAULT NULL,
  `date_effet` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `prime_base` decimal(12,2) DEFAULT NULL,
  `reduction` decimal(12,2) DEFAULT NULL,
  `majoration` decimal(12,2) DEFAULT NULL,
  `prime_nette` decimal(12,2) DEFAULT NULL,
  `complement` decimal(12,2) DEFAULT NULL,
  `net_a_payer` decimal(12,2) DEFAULT NULL,
  `statut` enum('actif','expire','suspendu') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `id_vehicule` int DEFAULT NULL,
  `id_agence` int DEFAULT NULL,
  `duree` int NOT NULL,
  `capital` decimal(12,2) NOT NULL,
  `taxe` decimal(5,2) DEFAULT NULL,
  `timbre` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_contrat`),
  UNIQUE KEY `numero_police` (`numero_police`),
  KEY `id_assure` (`id_assure`),
  KEY `fk_contrat_vehicule` (`id_vehicule`),
  KEY `fk_contrat_agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contrat`
--

INSERT INTO `contrat` (`id_contrat`, `numero_police`, `id_assure`, `date_effet`, `date_expiration`, `prime_base`, `reduction`, `majoration`, `prime_nette`, `complement`, `net_a_payer`, `statut`, `date_creation`, `id_vehicule`, `id_agence`, `duree`, `capital`, `taxe`, `timbre`) VALUES
(1, 'CRMA-ALG-2026-001', 3, '2026-03-14', '2026-08-29', 30000.00, 355.00, 7888.00, 37533.00, 455.00, 46619.27, 'actif', '2026-03-29', 1, 1, 6, 14000000.00, 0.19, 100.00),
(2, 'CRMA-ALG-2026-002', 2, '2026-03-03', '2026-08-30', 2999.00, 299.00, 499.00, 3199.00, 299.00, 5605.81, 'actif', '2026-03-30', 2, 1, 12, 13000000.00, 0.19, 100.00),
(3, 'CRMA-ORA-2026-001', 4, '2026-05-02', '2026-11-02', 11000.00, 0.00, 0.00, 11500.00, 500.00, 15185.00, 'actif', '2026-05-01', 2, 2, 6, 1200000.00, 0.19, NULL),
(4, 'CRMA-ALG-2026-003', 8, '2026-05-07', '2026-08-07', 19000.00, 0.00, 0.00, 19500.00, 500.00, 23305.00, 'actif', '2026-05-06', 3, 1, 3, 1400000.00, 0.19, 100.00),
(6, 'CRMA-CST-2026-001', 9, '2026-05-07', '2026-08-07', 14500.00, 0.00, 400.00, 15400.00, 500.00, 18476.00, 'actif', '2026-05-06', 6, 3, 3, 1200000.00, 0.19, 150.00),
(7, 'CRMA-CST-2026-002', 10, '2026-05-08', '2026-08-08', 11500.00, 5000.00, 0.00, 7000.00, 500.00, 8430.00, 'actif', '2026-05-07', 7, 3, 3, 8490000.00, 0.19, 100.00),
(8, 'CRMA-CST-2026-003', 4, '2026-05-08', '2026-11-08', 21000.00, 2000.00, 0.00, 19500.00, 500.00, 23305.00, 'actif', '2026-05-07', 8, 3, 6, 2000000.00, 0.19, 100.00);

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
(3, 1),
(4, 1),
(6, 1),
(7, 1),
(8, 1),
(1, 2),
(3, 2),
(3, 3),
(4, 3),
(7, 3),
(8, 3),
(6, 4),
(8, 4),
(6, 5),
(7, 5),
(4, 7),
(6, 7),
(8, 7),
(4, 8),
(8, 8);

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

DROP TABLE IF EXISTS `document`;
CREATE TABLE IF NOT EXISTS `document` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `nom_fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_upload` date DEFAULT NULL,
  `upload_par` int DEFAULT NULL,
  `id_type_document` int DEFAULT NULL,
  PRIMARY KEY (`id_document`),
  KEY `id_dossier` (`id_dossier`),
  KEY `upload_par` (`upload_par`),
  KEY `fk_type_document` (`id_type_document`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(16, 6, 'BDAU9862.JPG', '2026-04-03', 2, 1),
(17, 13, 'BDAU9862.JPG', '2026-04-27', 9, 1),
(18, 14, 'BDAU9862.JPG', '2026-04-27', 9, 1),
(19, 15, 'BDAU9862.JPG', '2026-04-27', 9, 1),
(21, 17, 'BDAU9862.JPG', '2026-04-27', 9, 1);

-- --------------------------------------------------------

--
-- Structure de la table `dossier`
--

DROP TABLE IF EXISTS `dossier`;
CREATE TABLE IF NOT EXISTS `dossier` (
  `id_dossier` int NOT NULL AUTO_INCREMENT,
  `numero_dossier` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `cree_par` int DEFAULT NULL,
  `date_transmission` date DEFAULT NULL,
  `transmis_par` int DEFAULT NULL,
  `info_complementaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id_etat` int DEFAULT NULL,
  `id_contrat` int DEFAULT NULL,
  `id_tiers` int DEFAULT NULL,
  `date_sinistre` date DEFAULT NULL,
  `lieu_sinistre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `delai_declaration` int DEFAULT NULL,
  `total_reserve` decimal(10,2) DEFAULT NULL,
  `statut_validation` enum('non_soumis','en_attente','valide','refuse') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'non_soumis',
  `date_validation` date DEFAULT NULL,
  `date_refus` date DEFAULT NULL,
  `date_cloture` date DEFAULT NULL,
  `commentaire_cnma` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `valide_par` int DEFAULT NULL,
  `id_expert` int DEFAULT NULL,
  PRIMARY KEY (`id_dossier`),
  UNIQUE KEY `numero_dossier` (`numero_dossier`),
  KEY `cree_par` (`cree_par`),
  KEY `transmis_par` (`transmis_par`),
  KEY `fk_etat_dossier` (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossier`
--

INSERT INTO `dossier` (`id_dossier`, `numero_dossier`, `date_creation`, `cree_par`, `date_transmission`, `transmis_par`, `info_complementaire`, `id_etat`, `id_contrat`, `id_tiers`, `date_sinistre`, `lieu_sinistre`, `description`, `delai_declaration`, `total_reserve`, `statut_validation`, `date_validation`, `date_refus`, `date_cloture`, `commentaire_cnma`, `valide_par`, `id_expert`) VALUES
(4, 'DOS-ALG-2026-0001', '2026-03-30', 9, NULL, NULL, 'HH', 8, 1, 5, '2026-03-14', 'ALGER', 'Accident matériel', 4, 5350.00, 'valide', '2026-04-05', NULL, '2026-04-03', NULL, NULL, 2),
(5, 'DOS-ALG-2026-0002', '2026-03-30', 9, '2026-04-30', 9, 'HKJ', 5, 2, 5, '2026-03-14', 'ALGER', 'Accident matériel', 7, 5555.00, 'refuse', NULL, '2026-04-30', NULL, NULL, NULL, NULL),
(6, 'DOS-ALG-2026-0003', '2026-03-30', 9, '2026-04-19', 9, '', 2, 1, 7, '2026-03-06', 'ALGER', 'ACCIDE', 20, 700867.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(7, 'DOS-ALG-2026-0004', '2026-03-31', 9, '2026-04-30', 9, '', 6, 1, 5, '2026-03-12', 'ALGER', 'ACCCIDENT', 2, 5600290.00, 'non_soumis', '2026-04-02', NULL, NULL, NULL, 2, 1),
(8, 'DOS-ALG-2026-0005', '2026-03-31', 9, NULL, NULL, '', 8, 2, 5, '2026-03-03', 'ALGER', 'S', 16, 11991.00, 'valide', NULL, NULL, NULL, NULL, NULL, 2),
(9, 'DOS-ALG-2026-0006', '2026-04-02', 9, '2026-04-19', 9, '', 7, 1, 5, '2026-04-14', 'ALGER', 'ASCC', 2, 649155.00, 'valide', '2026-04-19', NULL, NULL, NULL, 2, 2),
(10, 'DOS-ALG-2026-0007', '2026-04-02', 9, NULL, NULL, '', 6, 2, 6, '2026-03-30', 'ALGER', 'JK', 2, 500100.00, 'non_soumis', '2026-04-04', NULL, NULL, NULL, NULL, 1),
(11, 'DOS-ALG-2026-0008', '2026-04-02', 9, NULL, NULL, '', 8, 1, 7, '2026-04-08', 'BIRTOTA', 'BR', 3, 10500.00, 'valide', '2026-04-24', NULL, NULL, NULL, NULL, 5),
(12, 'DOS-ORA-2026-0009', '2026-04-03', 10, NULL, NULL, '', 9, 1, 5, '2026-03-30', 'ALGER', 'HG', 2, 0.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 4),
(13, 'DOS-ALG-2026-0010', '2026-04-27', 9, NULL, NULL, 'S', 15, 1, 8, '2026-04-21', 'ALGER', 'ACCIDENT', 3, 2500.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 4),
(14, 'DOS-ALG-2026-0011', '2026-04-27', 9, NULL, NULL, 'BNB', 7, 2, 6, '2026-04-27', 'ALGER', 'ACCIENT', 2, 0.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 3),
(15, 'DOS-ALG-2026-0012', '2026-04-27', 9, NULL, NULL, '', 14, 1, 5, '2026-04-15', 'ALGER', 'ACCIDNT', 12, NULL, 'non_soumis', NULL, NULL, '2026-04-29', NULL, NULL, 2),
(17, 'DOS-ALG-2026-0014', '2026-04-27', 9, NULL, NULL, '', 6, 2, 5, '2026-04-23', 'ALGER', 'ACIDENT', 4, 599500.00, 'non_soumis', '2026-05-08', NULL, NULL, NULL, 2, 2),
(18, 'DOS-ORA-2026-0015', '2026-05-01', 10, NULL, NULL, 'ACCIDENTS', 7, 1, 5, '2026-04-22', 'ALGER', 'HVHN', 1, 100100.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(20, 'DOS-ORA-2026-0016', '2026-05-06', 10, NULL, NULL, 'acc', 21, 4, 8, '2026-04-24', 'ALGER', 'accident', 12, NULL, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(22, 'DOS-CST-2026-0001', '2026-05-07', 11, NULL, NULL, 'Constat amiable signé. Dégâts arrière cabine et pare-choc.', 8, 7, 7, '2026-05-05', 'constantine', 'Collision arrière avec un véhicule tiers lors d’un ralentissement sur autoroute. Le véhicule assuré était immobilisé au moment du choc.', 2, NULL, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 2),
(23, 'DOS-CST-2026-0002', '2026-05-07', 11, NULL, NULL, '', 21, 2, 8, '2026-04-27', 'constantine', 'accident a constantine', 10, NULL, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 2),
(24, 'DOS-CST-2026-0003', '2026-05-07', 11, NULL, NULL, '', 8, 6, 6, '2026-05-05', 'constantine', 'accident a constantine', 2, NULL, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(25, 'DOS-CST-2026-0004', '2026-05-08', 11, NULL, NULL, '', 8, 1, 7, '2026-05-05', 'CONSTATINE', 'ACCIDENT', 4, 31900.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(26, 'DOS-CST-2026-0005', '2026-05-08', 11, NULL, NULL, '', 8, 1, 6, '2026-05-05', 'CONST', 'CONST', 3, 11000.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(27, 'DOS-CST-2026-0006', '2026-05-08', 11, NULL, NULL, '', 8, 1, 7, '2026-05-05', 'const', 'const', 3, 100100.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(28, 'DOS-CST-2026-0007', '2026-05-08', 11, NULL, NULL, '', 15, 1, 7, '2026-05-04', 'CONST', 'CONST', 3, 20498.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 3),
(29, 'DOS-CST-2026-0008', '2026-05-08', 11, NULL, NULL, '', 8, 1, 8, '2026-05-05', 'const', 'const', 3, NULL, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1),
(30, 'DOS-CST-2026-0009', '2026-05-08', 11, NULL, NULL, '', 8, 3, 8, '2026-05-06', 'CONST', 'CONST', 2, 100500.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 2),
(31, 'DOS-CST-2026-0010', '2026-05-09', 11, NULL, NULL, '', 3, 1, 8, '2026-05-06', 'constantine', 'accident', 3, 550000.00, 'non_soumis', NULL, NULL, NULL, NULL, NULL, 1);

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
  `type` enum('recours','franchise','epave','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'recours',
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_encaissement`),
  KEY `id_dossier` (`id_dossier`),
  KEY `fk_encaissement_tiers` (`id_tiers`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `encaissement`
--

INSERT INTO `encaissement` (`id_encaissement`, `id_dossier`, `montant`, `date_encaissement`, `id_tiers`, `type`, `commentaire`) VALUES
(1, 11, 10.00, '2026-04-21', 7, 'recours', '');

-- --------------------------------------------------------

--
-- Structure de la table `etat_dossier`
--

DROP TABLE IF EXISTS `etat_dossier`;
CREATE TABLE IF NOT EXISTS `etat_dossier` (
  `id_etat` int NOT NULL AUTO_INCREMENT,
  `nom_etat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `motif_obligatoire` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etat_dossier`
--

INSERT INTO `etat_dossier` (`id_etat`, `nom_etat`, `motif_obligatoire`) VALUES
(1, 'Brouillon', 0),
(2, 'En cours CRMA', 0),
(3, 'Transmis CNMA', 0),
(4, 'Validé CNMA', 0),
(5, 'Refusé CNMA', 1),
(6, 'Complément demandé', 1),
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
(20, 'Gestion pour recours', 1),
(21, 'Refusé (déclaration hors délai)', 1);

-- --------------------------------------------------------

--
-- Structure de la table `expert`
--

DROP TABLE IF EXISTS `expert`;
CREATE TABLE IF NOT EXISTS `expert` (
  `id_expert` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activite` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_personne` int DEFAULT NULL,
  PRIMARY KEY (`id_expert`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_expert_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `expert`
--

INSERT INTO `expert` (`id_expert`, `nom`, `prenom`, `telephone`, `email`, `activite`, `id_personne`) VALUES
(1, 'Brahimi', 'Ahmed', '0550000001', 'expert1@mail.dz', 'Expert technique', 15),
(2, 'Benali', 'Karim', '0550000002', 'expert2@mail.dz', 'Expert incendie', NULL),
(3, 'Mansouri', 'Nadia', '0550000003', 'expert3@mail.dz', 'Expert automobile', NULL),
(4, 'Ferhat', 'Samir', '0550000004', 'expert4@mail.dz', 'Expert automobile', 17),
(5, 'Saadi', 'Lina', '0550000005', 'expert5@mail.dz', 'Expert automobile', 18),
(7, 'warda', 'gjhkj', '0541775494', 'warda.moufouki@esst-sup.com', 'Expert automobile', 29),
(8, 'aidni', 'aida', '0541375494', 'aida.moufouki@esst-sup.com', 'Expert automobile', 31);

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
  `rapport_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `montant_indemnite` decimal(12,2) DEFAULT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_expertise`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_expert` (`id_expert`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(20, 11, 5, '2026-04-02', 'BDAU9862.JPG', 10000.00, ''),
(21, 9, 2, '2026-04-16', 'DING2023.JPG', 100.00, ''),
(22, 9, 2, '2026-04-09', 'BDAU9862.JPG', 499000.00, ''),
(23, 10, 1, '2026-05-06', 'BDAU9862.JPG', 100.00, ''),
(24, 18, 1, '2026-05-06', 'DING2023.JPG', 100000.00, ''),
(25, 24, 1, '2026-05-07', 'BDAU9862.JPG', 10000.00, ''),
(26, 25, 1, '2026-05-08', 'BDAU9862.JPG', 1000.00, ''),
(27, 25, 1, '2026-05-07', 'BDAU9862.JPG', 10000.00, ''),
(28, 27, 1, '2026-05-07', '', 1000.00, '');

-- --------------------------------------------------------

--
-- Structure de la table `formule`
--

DROP TABLE IF EXISTS `formule`;
CREATE TABLE IF NOT EXISTS `formule` (
  `id_formule` int NOT NULL AUTO_INCREMENT,
  `nom_formule` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
-- Structure de la table `garantie`
--

DROP TABLE IF EXISTS `garantie`;
CREATE TABLE IF NOT EXISTS `garantie` (
  `id_garantie` int NOT NULL AUTO_INCREMENT,
  `nom_garantie` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `code_garantie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_garantie`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `garantie`
--

INSERT INTO `garantie` (`id_garantie`, `nom_garantie`, `description`, `code_garantie`, `prix`) VALUES
(1, 'Responsabilité civile', 'Couvre les dommages causés aux tiers', 'RC', 7000.00),
(2, 'Défense recours', 'Frais d avocat et recours', 'DR', 1000.00),
(3, 'Vol', 'Vol du véhicule', 'VOL', 3000.00),
(4, 'Incendie', 'Incendie du véhicule', 'INC', 2000.00),
(5, 'Bris de glace', 'Vitres et pare-brise', 'BG', 1500.00),
(7, 'Dommage collision', 'Dommages en cas de collision', 'DOM', 4000.00),
(8, 'Tierce', 'Garantie tierce', 'TIERCE', 5000.00),
(9, 'Assistance', 'Assistance en cas de panne', 'ASSIST', 1500.00),
(10, 'Personnes transportées', 'Protection des passagers', 'PERS', 2000.00),
(11, 'Dépannage automobile', 'Dépannage du véhicule', 'DEP', 1000.00);

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

DROP TABLE IF EXISTS `historique`;
CREATE TABLE IF NOT EXISTS `historique` (
  `id_historique` int NOT NULL AUTO_INCREMENT,
  `id_dossier` int DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_action` datetime DEFAULT NULL,
  `fait_par` int DEFAULT NULL,
  `ancien_etat` int DEFAULT NULL,
  `nouvel_etat` int DEFAULT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id_motif` int DEFAULT NULL,
  PRIMARY KEY (`id_historique`),
  KEY `id_dossier` (`id_dossier`),
  KEY `fait_par` (`fait_par`),
  KEY `fk_ancien_etat` (`ancien_etat`),
  KEY `fk_nouvel_etat` (`nouvel_etat`),
  KEY `fk_historique_motif` (`id_motif`)
) ENGINE=InnoDB AUTO_INCREMENT=314 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(88, 11, 'Règlement partiel', '2026-04-17 14:34:41', 9, 7, 7, NULL, NULL),
(89, 9, 'Classé en attente recours', '2026-04-17 18:16:29', 9, 2, 13, 'attente', NULL),
(90, 9, 'Repris', '2026-04-17 18:29:50', 9, 13, 15, '', 32),
(91, 9, 'Expertise validée CRMA', '2026-04-17 18:38:53', 9, 15, 2, NULL, NULL),
(92, 7, 'Règlement partiel', '2026-04-17 19:07:10', 9, 4, 7, NULL, NULL),
(93, 11, 'Gestion pour recours', '2026-04-17 19:40:12', 9, 7, 20, '', 34),
(94, 9, 'Expertise validée CRMA', '2026-04-19 10:34:26', 9, 2, 2, NULL, NULL),
(95, 9, 'Transmission CNMA - Dépassement seuil', '2026-04-19 10:34:55', 9, 2, 3, NULL, NULL),
(96, 9, 'Validation CNMA', '2026-04-19 10:35:17', 2, 3, 4, 'Dossier validé par la CNMA — règlement autorisé', NULL),
(97, 6, 'Transmission CNMA - Dépassement seuil', '2026-04-19 12:00:48', 9, 2, 3, NULL, NULL),
(98, 11, 'Encaissement enregistré — recours', '2026-04-21 10:48:17', 9, 20, 20, NULL, NULL),
(99, 11, 'Repris pour recours abouti', '2026-04-21 14:52:23', 9, 20, 18, '', NULL),
(100, 7, 'Règlement partiel', '2026-04-21 14:56:28', 9, 7, 7, NULL, NULL),
(101, 11, 'Règlement définitif amiable', '2026-04-24 10:22:01', 9, 18, 8, NULL, NULL),
(102, 7, 'Règlement partiel', '2026-04-24 10:50:32', 9, 7, 7, NULL, NULL),
(103, 7, 'Ajout réserve', '2026-04-26 20:07:15', 9, 7, 7, NULL, NULL),
(104, 7, 'Ajout réserve', '2026-04-26 20:13:53', 9, 7, 7, NULL, NULL),
(105, 7, 'Règlement partiel', '2026-04-26 20:14:50', 9, 7, 7, NULL, NULL),
(106, 7, 'Changement d\'état → Complément demandé', '2026-04-26 20:31:45', 9, 6, 6, '', NULL),
(107, 7, 'Changement d\'état → En cours d\'expertise', '2026-04-26 20:33:01', 9, 6, 9, '', NULL),
(108, 10, 'Changement d\'état → Règlement partiel', '2026-04-27 21:16:32', 9, 2, 7, '', NULL),
(109, 13, 'Création dossier', '2026-04-27 21:19:00', 9, NULL, 2, NULL, NULL),
(110, 13, 'Affectation expert', '2026-04-27 21:19:00', 9, 2, 9, NULL, NULL),
(111, 13, 'Ajout réserve', '2026-04-27 21:19:43', 9, 9, 9, NULL, NULL),
(112, 13, 'Règlement partiel', '2026-04-27 21:29:35', 9, 9, 7, NULL, NULL),
(113, 13, 'Règlement partiel', '2026-04-27 21:30:14', 9, 7, 7, NULL, NULL),
(114, 14, 'Création dossier', '2026-04-27 21:50:32', 9, NULL, 2, NULL, NULL),
(115, 14, 'Affectation expert', '2026-04-27 21:50:32', 9, 2, 9, NULL, NULL),
(116, 14, 'Règlement partiel', '2026-04-27 21:50:57', 9, 9, 7, NULL, NULL),
(117, 14, 'Règlement total', '2026-04-27 21:53:08', 9, 7, 8, NULL, NULL),
(118, 15, 'Création dossier', '2026-04-27 22:04:16', 9, NULL, 2, NULL, NULL),
(119, 15, 'Affectation expert', '2026-04-27 22:04:16', 9, 2, 9, NULL, NULL),
(120, 15, 'Règlement total', '2026-04-27 22:04:34', 9, 9, 8, NULL, NULL),
(124, 17, 'Création dossier', '2026-04-27 22:48:19', 9, NULL, 2, NULL, NULL),
(125, 17, 'Affectation expert', '2026-04-27 22:48:19', 9, 2, 9, NULL, NULL),
(126, 17, 'Règlement total', '2026-04-27 22:48:43', 9, 9, 8, NULL, NULL),
(127, 13, 'Changement d\'état → Classé sans suite', '2026-04-28 16:26:11', 9, 7, 11, '', 26),
(128, 6, 'Demande de complément CNMA', '2026-04-28 19:23:42', 2, 3, 2, 'Dossier renvoyé au CRMA pour complément de documents', NULL),
(129, 15, 'Changement d\'état → Clôturé', '2026-04-29 14:35:40', 9, 8, 14, '', NULL),
(130, 5, 'Changement d\'état → Transmis CNMA', '2026-04-30 15:38:04', 9, 7, 3, '', NULL),
(131, 5, 'Refus CNMA', '2026-04-30 15:38:54', 2, 3, 5, 'Motif CNMA : Sinistre non couvert par le contrat', 46),
(132, 7, 'Changement d\'état → Transmis CNMA', '2026-04-30 15:41:05', 9, 9, 3, '', NULL),
(133, 7, 'Demande de complement CNMA', '2026-04-30 15:41:36', 2, 3, 6, 'Complement demande par la CNMA. Motif : Montant à réévaluer', 41),
(134, 17, 'Changement d\'état → Classé sans suite', '2026-04-30 15:53:27', 9, 8, 11, '', 21),
(135, 17, 'Changement d\'état → Repris', '2026-04-30 15:54:41', 9, 11, 15, '', 28),
(136, 17, 'Règlement total', '2026-04-30 15:55:33', 9, 15, 8, NULL, NULL),
(137, 17, 'Changement d\'état → Repris', '2026-04-30 15:56:03', 9, 8, 15, '', 29),
(138, 17, 'Règlement total', '2026-04-30 15:56:20', 9, 15, 8, NULL, NULL),
(139, 14, 'Changement d\'état → Repris', '2026-04-30 15:57:17', 9, 8, 15, '', 29),
(140, 14, 'Ajout réserve', '2026-04-30 15:58:04', 9, 15, 15, NULL, NULL),
(141, 14, 'Ajout réserve', '2026-04-30 15:58:22', 9, 15, 15, NULL, NULL),
(147, 18, 'Création dossier', '2026-05-01 18:46:43', 10, NULL, 2, NULL, NULL),
(148, 18, 'Affectation expert', '2026-05-01 18:46:43', 10, 2, 9, NULL, NULL),
(149, 14, 'Règlement disponible', '2026-05-03 18:17:18', 9, 15, 15, NULL, NULL),
(150, 10, 'Ajout expertise', '2026-05-04 22:25:45', 9, 7, 7, NULL, NULL),
(151, 10, 'Transmission automatique CNMA (seuil dépassé)', '2026-05-04 22:26:11', 9, 7, 3, NULL, NULL),
(152, 10, 'Ajout réserve', '2026-05-04 22:26:11', 9, 7, 3, NULL, NULL),
(153, 14, 'Règlement disponible', '2026-05-05 18:18:29', 9, 15, 15, NULL, NULL),
(154, 14, 'Règlement remis', '2026-05-05 18:18:38', 9, 15, 15, NULL, NULL),
(160, 20, 'Création dossier', '2026-05-06 22:42:24', 10, NULL, 2, NULL, NULL),
(161, 20, 'Refus automatique : déclaration hors délai réglementaire', '2026-05-06 22:42:24', 10, NULL, 21, NULL, NULL),
(162, 18, 'Changement d\'état → En cours CRMA', '2026-05-06 22:53:01', 10, 9, 2, '', NULL),
(163, 18, 'Suppression document', '2026-05-06 22:57:17', 10, 2, 2, NULL, NULL),
(164, 18, 'Ajout expertise', '2026-05-06 22:58:13', 10, 2, 2, NULL, NULL),
(165, 18, 'Changement d\'état → En cours d\'expertise', '2026-05-06 23:00:38', 10, 2, 9, '', NULL),
(166, 18, 'Ajout réserve', '2026-05-06 23:01:19', 10, 9, 9, NULL, NULL),
(167, 18, 'Règlement partiel', '2026-05-06 23:15:12', 10, 9, 7, NULL, NULL),
(168, 22, 'Création dossier', '2026-05-07 12:27:18', 11, NULL, 2, NULL, NULL),
(169, 22, 'Affectation expert', '2026-05-07 12:27:18', 11, 2, 9, NULL, NULL),
(170, 23, 'Création dossier', '2026-05-07 12:46:57', 11, NULL, 2, NULL, NULL),
(171, 23, 'Refus automatique : déclaration hors délai réglementaire', '2026-05-07 12:46:57', 11, NULL, 21, NULL, NULL),
(172, 22, 'Changement d\'état → En cours CRMA', '2026-05-07 19:18:07', 11, 9, 2, '', NULL),
(173, 22, 'Règlement partiel', '2026-05-07 19:18:15', 11, 2, 7, NULL, NULL),
(174, 9, 'Règlement partiel', '2026-05-07 19:20:05', 9, 4, 7, NULL, NULL),
(175, 9, 'Règlement disponible', '2026-05-07 19:20:15', 9, 7, 7, NULL, NULL),
(176, 24, 'Création dossier', '2026-05-07 19:25:52', 11, NULL, 2, NULL, NULL),
(177, 24, 'Affectation expert', '2026-05-07 19:25:52', 11, 2, 9, NULL, NULL),
(178, 24, 'Ajout expertise', '2026-05-07 19:26:26', 11, 9, 9, NULL, NULL),
(179, 24, 'Règlement partiel', '2026-05-07 19:26:37', 11, 9, 7, NULL, NULL),
(180, 24, 'Règlement disponible', '2026-05-07 19:26:41', 11, 7, 7, NULL, NULL),
(181, 14, 'Règlement partiel', '2026-05-07 19:29:22', 9, 15, 7, NULL, NULL),
(182, 14, 'Règlement disponible', '2026-05-07 19:29:28', 9, 7, 7, NULL, NULL),
(183, 14, 'Règlement partiel', '2026-05-07 21:20:23', 9, 7, 7, NULL, NULL),
(184, 17, 'Changement d\'état → En cours CRMA', '2026-05-08 00:08:52', 9, 8, 2, '', NULL),
(185, 17, 'Ajout règlement: 300 DA', '2026-05-08 00:09:01', 9, 2, 2, NULL, NULL),
(186, 13, 'Changement d\'état → Repris', '2026-05-08 00:10:20', 9, 11, 15, '', 27),
(187, 13, 'Ajout règlement: 2 000 DA', '2026-05-08 00:10:34', 9, 15, 15, NULL, NULL),
(188, 13, 'Ajout réserve', '2026-05-08 00:11:11', 9, 15, 15, NULL, NULL),
(189, 13, 'Ajout règlement: 20 DA', '2026-05-08 00:16:22', 9, 15, 15, NULL, NULL),
(190, 13, 'Ajout réserve', '2026-05-08 00:16:32', 9, 15, 15, NULL, NULL),
(191, 13, 'Suppression réserve', '2026-05-08 00:17:34', 9, 15, 15, NULL, NULL),
(192, 13, 'Suppression règlement', '2026-05-08 00:17:49', 9, 15, 15, NULL, NULL),
(193, 13, 'Suppression règlement', '2026-05-08 00:17:54', 9, 15, 15, NULL, NULL),
(194, 13, 'Ajout règlement: 1 100 DA', '2026-05-08 00:18:15', 9, 15, 15, NULL, NULL),
(195, 14, 'Ajout règlement: 486 999 DA', '2026-05-08 00:29:38', 9, 7, 7, NULL, NULL),
(196, 14, 'Suppression règlement', '2026-05-08 00:30:25', 9, 7, 7, NULL, NULL),
(197, 17, 'Changement d\'état → Règlement définitif amiable', '2026-05-08 09:55:21', 9, 2, 8, '', NULL),
(198, 17, 'Changement d\'état → En cours CRMA', '2026-05-08 09:56:12', 9, 8, 2, '', NULL),
(199, 17, 'Transmission automatique CNMA (seuil dépassé)', '2026-05-08 09:56:21', 9, 2, 3, NULL, NULL),
(200, 17, 'Ajout réserve', '2026-05-08 09:56:21', 9, 2, 3, NULL, NULL),
(201, 17, 'Suppression règlement', '2026-05-08 09:56:36', 9, 3, 3, NULL, NULL),
(202, 17, 'Suppression réserve', '2026-05-08 09:57:21', 9, 3, 3, NULL, NULL),
(203, 17, 'Suppression réserve', '2026-05-08 09:57:30', 9, 3, 3, NULL, NULL),
(204, 17, 'Suppression réserve', '2026-05-08 09:57:41', 9, 3, 3, NULL, NULL),
(205, 17, 'Suppression règlement', '2026-05-08 09:57:57', 9, 3, 3, NULL, NULL),
(206, 17, 'Validation CNMA', '2026-05-08 09:58:56', 2, 3, 4, 'Dossier validé par la CNMA — règlement autorisé', NULL),
(207, 17, 'Ajout règlement: 500 000 DA', '2026-05-08 09:59:51', 9, 4, 4, NULL, NULL),
(208, 14, 'Ajout règlement: 486 901 DA', '2026-05-08 10:06:56', 9, 7, 7, NULL, NULL),
(209, 13, 'Ajout réserve', '2026-05-08 10:25:33', 9, 15, 15, NULL, NULL),
(210, 25, 'Création dossier', '2026-05-08 11:03:26', 11, NULL, 2, NULL, NULL),
(211, 25, 'Affectation expert', '2026-05-08 11:03:26', 11, 2, 9, NULL, NULL),
(212, 25, 'Règlement saisi', '2026-05-08 11:04:03', 11, 9, 9, NULL, NULL),
(213, 25, 'Règlement saisi', '2026-05-08 11:04:21', 11, 9, 9, NULL, NULL),
(214, 25, 'Règlement saisi', '2026-05-08 11:04:32', 11, 9, 9, NULL, NULL),
(215, 25, 'Règlement saisi', '2026-05-08 11:04:42', 11, 9, 9, NULL, NULL),
(216, 25, 'Règlement saisi', '2026-05-08 11:04:54', 11, 9, 9, NULL, NULL),
(217, 25, 'Ajout expertise', '2026-05-08 11:05:33', 11, 9, 9, NULL, NULL),
(218, 25, 'Règlement saisi', '2026-05-08 11:05:43', 11, 9, 9, NULL, NULL),
(219, 25, 'Règlement saisi', '2026-05-08 11:06:11', 11, 9, 9, NULL, NULL),
(220, 25, 'Règlement saisi', '2026-05-08 11:06:35', 11, 9, 9, NULL, NULL),
(221, 26, 'Création dossier', '2026-05-08 11:12:07', 11, NULL, 2, NULL, NULL),
(222, 26, 'Affectation expert', '2026-05-08 11:12:07', 11, 2, 9, NULL, NULL),
(223, 26, 'Ajout réserve', '2026-05-08 11:12:28', 11, 9, 9, NULL, NULL),
(224, 26, 'Règlement partiel', '2026-05-08 11:12:37', 11, 9, 7, NULL, NULL),
(225, 26, 'Règlement total', '2026-05-08 11:12:52', 11, 7, 8, NULL, NULL),
(226, 26, 'Règlement total', '2026-05-08 11:12:56', 11, 8, 8, NULL, NULL),
(227, 26, 'Règlement disponible', '2026-05-08 11:36:51', 11, 8, 8, NULL, NULL),
(228, 26, 'Règlement disponible', '2026-05-08 11:37:07', 11, 8, 8, NULL, NULL),
(229, 26, 'Règlement disponible', '2026-05-08 11:37:11', 11, 8, 8, NULL, NULL),
(230, 26, 'Règlement remis à l’assuré (quittance signée)', '2026-05-08 11:37:14', 11, 8, 8, NULL, NULL),
(231, 26, 'Règlement remis à l’assuré (quittance signée)', '2026-05-08 11:37:18', 11, 8, 8, NULL, NULL),
(232, 26, 'Règlement remis à l’assuré (quittance signée)', '2026-05-08 11:37:21', 11, 8, 8, NULL, NULL),
(233, 24, 'Règlement total', '2026-05-08 11:46:46', 11, 7, 8, NULL, NULL),
(234, 24, 'Règlement disponible', '2026-05-08 11:49:13', 11, 8, 8, NULL, NULL),
(235, 24, 'Remise totale des règlements à l’assuré', '2026-05-08 11:51:31', 11, NULL, NULL, NULL, NULL),
(236, 22, 'Règlement disponible', '2026-05-08 11:52:12', 11, 7, 7, NULL, NULL),
(237, 22, 'Règlement total', '2026-05-08 11:52:29', 11, 7, 8, NULL, NULL),
(238, 22, 'Règlement disponible', '2026-05-08 11:52:33', 11, 8, 8, NULL, NULL),
(239, 22, 'Remise totale des règlements à l’assuré', '2026-05-08 11:52:36', 11, NULL, NULL, NULL, NULL),
(240, 25, 'Ajout expertise', '2026-05-08 12:09:59', 11, 9, 9, NULL, NULL),
(241, 25, 'Changement d\'état → Règlement définitif amiable', '2026-05-08 12:10:15', 11, 9, 8, '', NULL),
(242, 25, 'Ajout réserve', '2026-05-08 12:10:38', 11, 8, 8, NULL, NULL),
(243, 25, 'Changement d\'état → En cours CRMA', '2026-05-08 12:10:50', 11, 8, 2, '', NULL),
(244, 25, 'Règlement total', '2026-05-08 12:11:06', 11, 2, 8, NULL, NULL),
(245, 25, 'Règlement total', '2026-05-08 12:11:08', 11, 8, 8, NULL, NULL),
(246, 25, 'Règlement disponible', '2026-05-08 12:11:12', 11, 8, 8, NULL, NULL),
(247, 25, 'Règlement disponible', '2026-05-08 12:11:16', 11, 8, 8, NULL, NULL),
(248, 25, 'Règlement disponible', '2026-05-08 12:11:20', 11, 8, 8, NULL, NULL),
(249, 25, 'Règlement disponible', '2026-05-08 12:11:25', 11, 8, 8, NULL, NULL),
(250, 25, 'Règlement disponible', '2026-05-08 12:11:28', 11, 8, 8, NULL, NULL),
(251, 25, 'Règlement disponible', '2026-05-08 12:11:32', 11, 8, 8, NULL, NULL),
(252, 25, 'Règlement disponible', '2026-05-08 12:11:36', 11, 8, 8, NULL, NULL),
(253, 25, 'Règlement disponible', '2026-05-08 12:11:39', 11, 8, 8, NULL, NULL),
(254, 25, 'Règlement disponible', '2026-05-08 12:11:43', 11, 8, 8, NULL, NULL),
(255, 25, 'Règlement disponible', '2026-05-08 12:11:46', 11, 8, 8, NULL, NULL),
(256, 25, 'Remise totale des règlements à l’assuré', '2026-05-08 12:11:49', 11, NULL, NULL, NULL, NULL),
(257, 25, 'Changement d\'état → En cours CRMA', '2026-05-08 12:26:39', 11, 8, 2, '', NULL),
(258, 25, 'Ajout réserve', '2026-05-08 12:26:46', 11, 2, 2, NULL, NULL),
(259, 25, 'Règlement total', '2026-05-08 12:26:56', 11, 2, 8, NULL, NULL),
(260, 25, 'Règlement disponible', '2026-05-08 12:27:01', 11, 8, 8, NULL, NULL),
(261, 27, 'Création dossier', '2026-05-08 12:28:13', 11, NULL, 2, NULL, NULL),
(262, 27, 'Affectation expert', '2026-05-08 12:28:13', 11, 2, 9, NULL, NULL),
(263, 27, 'Ajout expertise', '2026-05-08 12:28:36', 11, 9, 9, NULL, NULL),
(265, 27, 'Règlement total', '2026-05-08 12:31:26', 11, 9, 8, NULL, NULL),
(266, 27, 'Changement d\'état → En cours CRMA', '2026-05-08 12:39:13', 11, 8, 2, '', NULL),
(267, 27, 'Ajout réserve', '2026-05-08 12:39:21', 11, 2, 2, NULL, NULL),
(268, 27, 'Règlement total', '2026-05-08 12:39:33', 11, 2, 8, NULL, NULL),
(269, 27, 'Règlement disponible', '2026-05-08 12:39:49', 11, 8, 8, NULL, NULL),
(270, 27, 'Règlement disponible', '2026-05-08 12:39:52', 11, 8, 8, NULL, NULL),
(271, 27, 'Remise totale des règlements à l’assuré', '2026-05-08 12:39:56', 11, NULL, NULL, NULL, NULL),
(272, 28, 'Création dossier', '2026-05-08 12:40:55', 11, NULL, 2, NULL, NULL),
(273, 28, 'Affectation expert', '2026-05-08 12:40:55', 11, 2, 9, NULL, NULL),
(274, 28, 'Règlement total', '2026-05-08 12:41:09', 11, 9, 8, NULL, NULL),
(275, 28, 'Changement d\'état → Repris', '2026-05-08 12:54:24', 11, 8, 15, '', 29),
(276, 28, 'Ajout réserve', '2026-05-08 12:54:31', 11, 15, 15, NULL, NULL),
(277, 28, 'Règlement total', '2026-05-08 12:54:37', 11, 15, 8, NULL, NULL),
(278, 28, 'Changement d\'état → Repris', '2026-05-08 13:10:58', 11, 8, 15, '', 28),
(279, 28, 'Ajout réserve', '2026-05-08 13:11:08', 11, 15, 15, NULL, NULL),
(280, 28, 'Règlement total', '2026-05-08 13:12:38', 11, 15, 8, NULL, NULL),
(281, 28, 'Changement d\'état → Repris', '2026-05-08 13:20:01', 11, 8, 15, '', 32),
(282, 28, 'Ajout réserve', '2026-05-08 13:20:07', 11, 15, 15, NULL, NULL),
(283, 28, 'Règlement total', '2026-05-08 13:20:13', 11, 15, 8, NULL, NULL),
(284, 28, 'Changement d\'état → Repris', '2026-05-08 13:26:19', 11, 8, 15, '', 32),
(285, 28, 'Ajout réserve', '2026-05-08 13:26:26', 11, 15, 15, NULL, NULL),
(286, 28, 'Règlement total', '2026-05-08 13:26:34', 11, 15, 8, NULL, NULL),
(287, 28, 'Changement d\'état → Repris', '2026-05-08 13:33:28', 11, 8, 15, '', 32),
(288, 28, 'Ajout réserve', '2026-05-08 13:33:35', 11, 15, 15, NULL, NULL),
(289, 28, 'Règlement total', '2026-05-08 13:35:54', 11, 15, 8, NULL, NULL),
(290, 29, 'Création dossier', '2026-05-08 13:38:10', 11, NULL, 2, NULL, NULL),
(291, 29, 'Affectation expert', '2026-05-08 13:38:10', 11, 2, 9, NULL, NULL),
(292, 29, 'Règlement total', '2026-05-08 13:38:25', 11, 9, 8, NULL, NULL),
(293, 30, 'Création dossier', '2026-05-08 13:47:41', 11, NULL, 2, NULL, NULL),
(294, 30, 'Affectation expert', '2026-05-08 13:47:41', 11, 2, 9, NULL, NULL),
(295, 30, 'Règlement total', '2026-05-08 13:48:11', 11, 9, 8, NULL, NULL),
(296, 30, 'Changement d\'état → Repris', '2026-05-08 13:51:54', 11, 8, 15, '', 32),
(297, 30, 'Ajout réserve', '2026-05-08 13:52:01', 11, 15, 15, NULL, NULL),
(298, 30, 'Ajout réserve', '2026-05-08 13:52:16', 11, 15, 15, NULL, NULL),
(299, 30, 'Ajout réserve', '2026-05-08 13:52:24', 11, 15, 15, NULL, NULL),
(300, 30, 'Règlement total', '2026-05-08 13:52:33', 11, 15, 8, NULL, NULL),
(301, 30, 'Changement d\'état → Repris', '2026-05-08 13:54:09', 11, 8, 15, '', 32),
(302, 30, 'Ajout réserve', '2026-05-08 13:54:16', 11, 15, 15, NULL, NULL),
(303, 30, 'Règlement total', '2026-05-08 13:54:22', 11, 15, 8, NULL, NULL),
(304, 28, 'Changement d\'état → Repris', '2026-05-09 09:37:35', 11, 8, 15, '', 28),
(305, 10, 'Demande de complement CNMA', '2026-05-09 09:38:22', 2, 3, 6, 'Complement demande par la CNMA. Motif : Facture de réparation manquante', 36),
(306, 17, 'Changement d\'état → Repris', '2026-05-09 09:48:55', 9, 4, 15, '', 27),
(307, 17, 'Transmission automatique CNMA (seuil dépassé)', '2026-05-09 09:49:09', 9, 15, 3, NULL, NULL),
(308, 17, 'Ajout réserve', '2026-05-09 09:49:09', 9, 15, 3, NULL, NULL),
(309, 17, 'Demande de complement CNMA', '2026-05-09 09:50:25', 2, 3, 6, 'Complement demande par la CNMA. Motif : Montant à réévaluer', 41),
(310, 31, 'Création dossier', '2026-05-09 19:01:27', 11, NULL, 2, NULL, NULL),
(311, 31, 'Affectation expert', '2026-05-09 19:01:27', 11, 2, 9, NULL, NULL),
(312, 31, 'Transmission automatique CNMA (seuil dépassé)', '2026-05-09 19:01:55', 11, 9, 3, NULL, NULL),
(313, 31, 'Ajout réserve', '2026-05-09 19:01:55', 11, 9, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `motif`
--

DROP TABLE IF EXISTS `motif`;
CREATE TABLE IF NOT EXISTS `motif` (
  `id_motif` int NOT NULL AUTO_INCREMENT,
  `id_etat` int DEFAULT NULL,
  `nom_motif` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message_assure` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_motif`),
  KEY `id_etat` (`id_etat`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `motif`
--

INSERT INTO `motif` (`id_motif`, `id_etat`, `nom_motif`, `message_assure`) VALUES
(18, 11, 'Absence de garantie couvrant le sinistre', NULL),
(19, 11, 'Sinistre survenu hors période de couverture', NULL),
(20, 11, 'Exclusions de garanties mentionnées sur les conditions générales et particulières', NULL),
(21, 11, 'Prescription', NULL),
(22, 11, 'Absence de dégâts matériels engendrés par le sinistre', NULL),
(23, 11, 'Dégâts sous franchise', NULL),
(24, 11, 'Absence de PV d\'expertise ou de photos d\'expertise (matérialité non prouvée)', NULL),
(25, 11, 'Fausse déclaration (cas fraude à l\'assurance avéré)', NULL),
(26, 11, 'Absence de la réclamation du tiers dans le cas d\'un règlement au titre de la garantie RC', NULL),
(27, 15, 'Réception d\'une citation à comparaître', NULL),
(28, 15, 'Réclamation fondée de l\'assuré ou d\'une victime du sinistre', NULL),
(29, 15, 'Réception d\'un jugement par défaut', NULL),
(30, 15, 'Repris pour recours abouti', NULL),
(31, 15, 'Réouverture pour recours', NULL),
(32, 15, 'Réouverture pour erreur de classement', NULL),
(33, 19, 'Encaissement du recours', NULL),
(34, 20, 'Responsabilité de l\'assuré dégagée entièrement', NULL),
(35, 6, 'PV de police manquant', NULL),
(36, 6, 'Facture de réparation manquante', NULL),
(37, 6, 'Carte grise manquante', NULL),
(40, 6, 'Photos du sinistre insuffisantes', NULL),
(41, 6, 'Montant à réévaluer', NULL),
(43, 6, 'Informations du sinistre incomplètes', NULL),
(44, 6, 'Coordonnées tiers manquantes', NULL),
(45, 6, 'Rapport d’expertise manquant', NULL),
(46, 5, 'Sinistre non couvert par le contrat', 'Ce type d’incident n’est pas couvert par votre contrat d’assurance.'),
(50, 5, 'Montant du dommage inférieur à la franchise', 'Le montant des dommages est inférieur à la franchise.');

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
  `type` enum('validation','refus','complement','reglement','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email_to` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_body_html` mediumtext COLLATE utf8mb4_general_ci,
  `email_status` enum('sent','failed') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_attempts` int NOT NULL DEFAULT '0',
  `email_last_attempt_at` datetime DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `email_error` text COLLATE utf8mb4_general_ci,
  `date_notification` datetime DEFAULT CURRENT_TIMESTAMP,
  `lu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_notification`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_expediteur` (`id_expediteur`),
  KEY `id_destinataire` (`id_destinataire`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`id_notification`, `id_dossier`, `id_expediteur`, `id_destinataire`, `type`, `message`, `email_to`, `email_subject`, `email_body_html`, `email_status`, `email_attempts`, `email_last_attempt_at`, `email_sent_at`, `email_error`, `date_notification`, `lu`) VALUES
(1, 6, 2, 9, 'complement', 'Complément demandé pour le dossier DOS-2026-0003. Veuillez compléter les documents manquants et re-transmettre.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-03 00:40:45', 1),
(2, 4, 2, 9, 'cloture', 'Le dossier DOS-2026-0001 a été clôturé définitivement par la CNMA.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-03 00:50:06', 1),
(3, 10, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0007. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-04 14:15:18', 1),
(4, 4, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0001. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-05 20:19:54', 1),
(5, 4, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0001. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-05 20:19:59', 1),
(6, 8, 8, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0005. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-05 20:32:51', 1),
(7, 9, 9, 13, 'cloture', 'Votre dossier DOS-2026-0006 a changé d\'état : Classé en attente recours. Contactez votre agence pour plus d\'informations.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-17 18:16:29', 0),
(8, 9, 2, 9, 'validation', 'Le dossier DOS-2026-0006 a été VALIDÉ par la CNMA. Vous pouvez procéder au règlement.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-19 10:35:17', 0),
(9, 13, 9, 13, 'cloture', 'Votre dossier DOS-2026-0010 a changé d\'état : Classé sans suite. Contactez votre agence pour plus d\'informations.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-28 16:26:11', 0),
(10, 6, 2, 9, 'complement', 'Complément demandé pour le dossier DOS-2026-0003. Veuillez compléter les documents manquants et re-transmettre.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-28 19:23:42', 0),
(11, 15, 9, 13, 'cloture', 'Votre dossier DOS-2026-0012 a changé d\'état : Clôturé. Contactez votre agence pour plus d\'informations.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-29 14:35:40', 0),
(12, 5, 2, 9, 'refus', 'Le dossier DOS-2026-0002 a ete refuse par la CNMA. Motif : Sinistre non couvert par le contrat', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-30 15:38:54', 1),
(13, 5, 2, 8, 'refus', 'Ce type d’incident n’est pas couvert par votre contrat d’assurance.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-30 15:38:54', 1),
(14, 7, 2, 9, 'complement', 'Complement demande pour le dossier DOS-2026-0004. Motif : Montant à réévaluer', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-30 15:41:36', 0),
(15, 17, 9, 8, 'cloture', 'Votre dossier DOS-2026-0014 a changé d\'état : Classé sans suite. Contactez votre agence pour plus d\'informations.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-04-30 15:53:27', 1),
(17, 15, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0012. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-03 18:03:38', 1),
(18, 14, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0011. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-03 18:17:18', 0),
(19, 14, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0011. Veuillez vous présenter à votre agence CRMA pour le récupérer.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-05 18:18:29', 0),
(21, 9, 9, 13, 'reglement', 'Un chèque est disponible pour le dossier DOS-ALG-2026-0006. Veuillez vous présenter à votre agence CRMA pour le récupérer.', 'warda.moufouki@esst-sup.com', 'Votre règlement est disponible', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Votre règlement est disponible</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Règlement disponible pour DOS-ALG-2026-0006</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Votre règlement est disponible</p></div><div class=\"body\"><p class=\"muted\">Votre règlement est prêt. Vous pouvez récupérer votre chèque auprès de votre agence.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-ALG-2026-0006</div></div><div class=\"row\"><div class=\"label\">Montant</div><div class=\"value\">10 000,00 DZD</div></div><div class=\"row\"><div class=\"label\">Référence chèque</div><div class=\"value\">CHQ-0006-028</div></div><div class=\"row\"><div class=\"label\">Agence CRMA</div><div class=\"value\">CRMA Alger (Alger)</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de vous présenter à l’agence avec une pièce d’identité pour récupérer votre chèque.</p></div><div class=\"footer\">CRMA / CNMA – Gestion des sinistres automobile<br>Ce message est généré automatiquement, merci de ne pas répondre directement.</div></div></div></body></html>', 'sent', 1, '2026-05-07 19:20:19', '2026-05-07 18:20:19', NULL, '2026-05-07 19:20:15', 0),
(22, 24, 11, 14, 'reglement', 'Un chèque est disponible pour le dossier DOS-CST-2026-0003. Veuillez vous présenter à votre agence CRMA pour le récupérer.', 'kenza.meklati@esst-sup.com', 'Votre règlement est disponible', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Votre règlement est disponible</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Règlement disponible pour DOS-CST-2026-0003</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Votre règlement est disponible</p></div><div class=\"body\"><p class=\"muted\">Votre règlement est prêt. Vous pouvez récupérer votre chèque auprès de votre agence.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-CST-2026-0003</div></div><div class=\"row\"><div class=\"label\">Montant</div><div class=\"value\">1 000,00 DZD</div></div><div class=\"row\"><div class=\"label\">Référence chèque</div><div class=\"value\">CHQ-0003-029</div></div><div class=\"row\"><div class=\"label\">Agence CRMA</div><div class=\"value\">CRMA Constantine (Constantine)</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de vous présenter à l’agence avec une pièce d’identité pour récupérer votre chèque.</p></div><div class=\"footer\">CRMA / CNMA – Gestion des sinistres automobile<br>Ce message est généré automatiquement, merci de ne pas répondre directement.</div></div></div></body></html>', 'sent', 1, '2026-05-07 19:26:43', '2026-05-07 18:26:43', NULL, '2026-05-07 19:26:41', 0),
(23, 14, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-ALG-2026-0011. Veuillez vous présenter à votre agence CRMA pour le récupérer.', 'aida.moufouki@gmail.com', 'Votre règlement est disponible', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Votre règlement est disponible</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Règlement disponible pour DOS-ALG-2026-0011</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Votre règlement est disponible</p></div><div class=\"body\"><p class=\"muted\">Votre règlement est prêt. Vous pouvez récupérer votre chèque auprès de votre agence.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-ALG-2026-0011</div></div><div class=\"row\"><div class=\"label\">Montant</div><div class=\"value\">1 000,00 DZD</div></div><div class=\"row\"><div class=\"label\">Référence chèque</div><div class=\"value\">CHQ-0011-030</div></div><div class=\"row\"><div class=\"label\">Agence CRMA</div><div class=\"value\">CRMA Alger (Alger)</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de vous présenter à l’agence avec une pièce d’identité pour récupérer votre chèque.</p></div><div class=\"footer\">CRMA / CNMA – Gestion des sinistres automobile<br>Ce message est généré automatiquement, merci de ne pas répondre directement.</div></div></div></body></html>', 'sent', 1, '2026-05-07 19:29:31', '2026-05-07 18:29:31', NULL, '2026-05-07 19:29:28', 0),
(24, 17, 2, 9, 'validation', 'Le dossier DOS-ALG-2026-0014 a été VALIDÉ par la CNMA. Vous pouvez procéder au règlement.', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-08 09:58:56', 0),
(25, 26, 11, 13, 'reglement', 'Dossier réglé définitivement à l\'amiable.', 'warda.moufouki@esst-sup.com', 'Règlement définitif amiable — dossier DOS-CST-2026-0005', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Règlement définitif amiable — dossier DOS-CST-2026-0005</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Dossier DOS-CST-2026-0005 réglé définitivement</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Règlement définitif amiable — dossier DOS-CST-2026-0005</p></div><div class=\"body\"><p>Votre dossier est maintenant réglé définitivement à l’amiable.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-CST-2026-0005</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de vous rapprocher de votre agence CRMA pour le suivi final de votre dossier. Si vous avez reçu un chèque, veillez à le retirer rapidement.</p></div><div class=\"footer\">CRMA / CNMA – Gestion des sinistres automobile<br>Ce message est généré automatiquement, merci de ne pas répondre directement.</div></div></div></body></html>', 'sent', 1, '2026-05-08 11:12:56', '2026-05-08 10:12:56', NULL, '2026-05-08 11:12:52', 0),
(26, 25, 11, 13, 'reglement', 'Dossier réglé définitivement à l\'amiable.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0004', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0004</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Dossier DOS-CST-2026-0004 réglé définitivement</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0004</p></div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0004</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 12:11:08', '2026-05-08 11:11:08', NULL, '2026-05-08 12:11:06', 0),
(27, 27, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0006', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0006</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Paiement du dossier DOS-CST-2026-0006</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0006</p></div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0006</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">0,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 12:31:28', '2026-05-08 11:31:28', NULL, '2026-05-08 12:31:26', 0),
(28, 28, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0007', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0007</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Paiement du dossier DOS-CST-2026-0007</div><div class=\"container\"><div class=\"card\"><div class=\"header\"><p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0007</p></div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0007</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">9 999,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 12:41:12', '2026-05-08 11:41:12', NULL, '2026-05-08 12:41:09', 0),
(29, 28, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0007', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0007</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Paiement du dossier DOS-CST-2026-0007</div><div class=\"container\"><div class=\"card\"><div style=\"text-align:center;padding:22px 0 10px;\">\n    <img \n        src=\"http://localhost/PfeCnma/cnma/images/logo.webp\"\n        alt=\"CNMA\"\n        style=\"height:70px;object-fit:contain;\"\n    >\n</div><div class=\"header\">\n    <p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0007</p>\n</div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0007</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">20 099,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\n\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 13:12:40', '2026-05-08 12:12:40', NULL, '2026-05-08 13:12:38', 0),
(30, 28, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0007', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0007</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{display:flex;gap:10px;margin:0 0 10px;}.label{width:170px;min-width:170px;color:#64748b;font-size:13px;}.value{flex:1;font-size:14px;font-weight:600;color:#0f172a;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Paiement du dossier DOS-CST-2026-0007</div><div class=\"container\"><div class=\"card\"><div class=\"header\">\n    <p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0007</p>\n</div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0007</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">20 199,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\n\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 13:20:15', '2026-05-08 12:20:15', NULL, '2026-05-08 13:20:13', 0),
(31, 28, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0007', '<!doctype html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Paiement de votre dossier sinistre — DOS-CST-2026-0007</title><style>body{margin:0;padding:0;background:#f3f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}.container{max-width:640px;margin:0 auto;padding:24px;}.card{background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);}.header{padding:22px 24px;background:#0b8f3a;color:#ffffff;}.header-title{font-size:18px;line-height:1.3;font-weight:700;margin:0;}.body{padding:24px;background:#111827;color:#ffffff;}.footer{padding:18px 24px;background:#0b1220;color:#cbd5e1;font-size:12px;line-height:1.6;}.muted{color:#64748b;font-size:13px;line-height:1.6;margin:0 0 14px;}.divider{height:1px;background:#e5e7eb;margin:18px 0;}.row{margin:0 0 14px;}.label{color:#94a3b8;font-size:13px;margin-bottom:4px;}.value{font-size:16px;font-weight:700;color:#ffffff;}@media(max-width:520px){.container{padding:14px;}.body{padding:18px;}.row{display:block;}.label{width:auto;min-width:0;margin-bottom:4px;}.value{font-size:14px;}}</style></head><body><div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">Paiement du dossier DOS-CST-2026-0007</div><div class=\"container\"><div class=\"card\"><div class=\"header\">\n    <p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0007</p>\n</div><div class=\"body\"><p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0007</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">20 398,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p></div><div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile<br>\n\n</div></div></div></body></html>', 'sent', 1, '2026-05-08 13:26:37', '2026-05-08 12:26:37', NULL, '2026-05-08 13:26:34', 0),
(32, 28, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0007', '\n<!doctype html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Paiement de votre dossier sinistre — DOS-CST-2026-0007</title>\n\n<style>\nbody{\n    margin:0;\n    padding:0;\n    background:#f3f5f7;\n    font-family:Arial,Helvetica,sans-serif;\n    color:#1f2937;\n}\n\n.container{\n    max-width:640px;\n    margin:0 auto;\n    padding:24px;\n}\n\n.card{\n    background:#111827;\n    border-radius:14px;\n    overflow:hidden;\n}\n\n.header{\n    padding:22px 24px;\n    background:#0b8f3a;\n    color:#ffffff;\n}\n\n.header-title{\n    font-size:20px;\n    line-height:1.4;\n    font-weight:700;\n    margin:0;\n}\n\n.body{\n    padding:24px;\n    color:#ffffff;\n}\n\n.footer{\n    padding:18px 24px;\n    background:#e5e7eb;\n    color:#374151;\n    font-size:12px;\n    line-height:1.6;\n}\n\n.divider{\n    height:1px;\n    background:#374151;\n    margin:20px 0;\n}\n\n.row{\n    margin-bottom:16px;\n}\n\n.label{\n    color:#9ca3af;\n    font-size:13px;\n    margin-bottom:4px;\n}\n\n.value{\n    font-size:16px;\n    font-weight:700;\n    color:#ffffff;\n}\n\np{\n    color:#ffffff;\n}\n\n@media(max-width:520px){\n    .container{\n        padding:12px;\n    }\n\n    .body{\n        padding:18px;\n    }\n}\n</style>\n\n</head>\n\n<body>\n\n<div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">\nPaiement du dossier DOS-CST-2026-0007\n</div>\n\n<div class=\"container\">\n\n<div class=\"card\">\n\n<div class=\"header\">\n<p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0007</p>\n</div>\n\n<div class=\"body\">\n<p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0007</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">20 498,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p>\n</div>\n\n<div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile\n</div>\n\n</div>\n\n</div>\n\n</body>\n</html>\n', 'sent', 1, '2026-05-08 13:35:57', '2026-05-08 12:35:57', NULL, '2026-05-08 13:35:54', 0),
(33, 29, 11, 13, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'warda.moufouki@esst-sup.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0008', '\n<!doctype html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Paiement de votre dossier sinistre — DOS-CST-2026-0008</title>\n\n<style>\nbody{\n    margin:0;\n    padding:0;\n    background:#f3f5f7;\n    font-family:Arial,Helvetica,sans-serif;\n    color:#1f2937;\n}\n\n.container{\n    max-width:640px;\n    margin:0 auto;\n    padding:24px;\n}\n\n.card{\n    background:#111827;\n    border-radius:14px;\n    overflow:hidden;\n}\n\n.header{\n    padding:22px 24px;\n    background:#0b8f3a;\n    color:#ffffff;\n}\n\n.header-title{\n    font-size:20px;\n    line-height:1.4;\n    font-weight:700;\n    margin:0;\n}\n\n.body{\n    padding:24px;\n    color:#ffffff;\n}\n\n.footer{\n    padding:18px 24px;\n    background:#e5e7eb;\n    color:#374151;\n    font-size:12px;\n    line-height:1.6;\n}\n\n.divider{\n    height:1px;\n    background:#374151;\n    margin:20px 0;\n}\n\n.row{\n    margin-bottom:16px;\n}\n\n.label{\n    color:#9ca3af;\n    font-size:13px;\n    margin-bottom:4px;\n}\n\n.value{\n    font-size:16px;\n    font-weight:700;\n    color:#ffffff;\n}\n\np{\n    color:#ffffff;\n}\n\n@media(max-width:520px){\n    .container{\n        padding:12px;\n    }\n\n    .body{\n        padding:18px;\n    }\n}\n</style>\n\n</head>\n\n<body>\n\n<div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">\nPaiement du dossier DOS-CST-2026-0008\n</div>\n\n<div class=\"container\">\n\n<div class=\"card\">\n\n<div class=\"header\">\n<p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0008</p>\n</div>\n\n<div class=\"body\">\n<p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0008</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">10 000,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Alger</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p>\n</div>\n\n<div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile\n</div>\n\n</div>\n\n</div>\n\n</body>\n</html>\n', 'sent', 1, '2026-05-08 13:38:28', '2026-05-08 12:38:28', NULL, '2026-05-08 13:38:25', 0),
(34, 30, 11, 15, 'reglement', 'Le paiement de votre dossier sinistre est désormais finalisé.', 'moufouki.fadia.enssea@gmail.com', 'Paiement de votre dossier sinistre — DOS-CST-2026-0009', '\n<!doctype html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Paiement de votre dossier sinistre — DOS-CST-2026-0009</title>\n\n<style>\nbody{\n    margin:0;\n    padding:0;\n    background:#f3f5f7;\n    font-family:Arial,Helvetica,sans-serif;\n    color:#1f2937;\n}\n\n.container{\n    max-width:640px;\n    margin:0 auto;\n    padding:24px;\n}\n\n.card{\n    background:#111827;\n    border-radius:14px;\n    overflow:hidden;\n}\n\n.header{\n    padding:22px 24px;\n    background:#0b8f3a;\n    color:#ffffff;\n}\n\n.header-title{\n    font-size:20px;\n    line-height:1.4;\n    font-weight:700;\n    margin:0;\n}\n\n.body{\n    padding:24px;\n    color:#ffffff;\n}\n\n.footer{\n    padding:18px 24px;\n    background:#e5e7eb;\n    color:#374151;\n    font-size:12px;\n    line-height:1.6;\n}\n\n.divider{\n    height:1px;\n    background:#374151;\n    margin:20px 0;\n}\n\n.row{\n    margin-bottom:16px;\n}\n\n.label{\n    color:#9ca3af;\n    font-size:13px;\n    margin-bottom:4px;\n}\n\n.value{\n    font-size:16px;\n    font-weight:700;\n    color:#ffffff;\n}\n\np{\n    color:#ffffff;\n}\n\n@media(max-width:520px){\n    .container{\n        padding:12px;\n    }\n\n    .body{\n        padding:18px;\n    }\n}\n</style>\n\n</head>\n\n<body>\n\n<div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">\nPaiement du dossier DOS-CST-2026-0009\n</div>\n\n<div class=\"container\">\n\n<div class=\"card\">\n\n<div class=\"header\">\n<p class=\"header-title\">Paiement de votre dossier sinistre — DOS-CST-2026-0009</p>\n</div>\n\n<div class=\"body\">\n<p style=\"margin:0 0 14px;font-size:15px;line-height:1.7\">\r\nNous vous informons que le paiement de votre dossier sinistre est désormais finalisé.\r\n</p>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Numéro dossier</div>\r\n    <div class=\"value\">DOS-CST-2026-0009</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Montant total</div>\r\n    <div class=\"value\">100 500,00 DA</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Statut</div>\r\n    <div class=\"value\">Paiement effectué</div>\r\n</div>\r\n\r\n<div class=\"row\">\r\n    <div class=\"label\">Agence CRMA</div>\r\n    <div class=\"value\">CRMA Oran</div>\r\n</div>\r\n\r\n<div class=\"divider\"></div>\r\n\r\n<p style=\"margin:0;font-size:14px;line-height:1.7\">\r\nVotre dossier a été traité avec succès.\r\nPour toute information complémentaire, veuillez contacter votre agence CRMA.\r\n</p>\n</div>\n\n<div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile\n</div>\n\n</div>\n\n</div>\n\n</body>\n</html>\n', 'sent', 1, '2026-05-08 13:54:24', '2026-05-08 12:54:24', NULL, '2026-05-08 13:54:22', 0),
(35, 10, 2, 9, 'complement', 'Complement demande pour le dossier DOS-ALG-2026-0007. Motif : Facture de réparation manquante', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-09 09:38:22', 0),
(36, 10, 2, 8, 'complement', 'Complément demandé pour votre dossier DOS-ALG-2026-0007. Motif : Facture de réparation manquante. Veuillez transmettre les documents manquants à votre agence CRMA.', 'aida.moufouki@gmail.com', 'Complément demandé pour votre dossier', '\n<!doctype html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Complément demandé pour votre dossier</title>\n\n<style>\nbody{\n    margin:0;\n    padding:0;\n    background:#f3f5f7;\n    font-family:Arial,Helvetica,sans-serif;\n    color:#1f2937;\n}\n\n.container{\n    max-width:640px;\n    margin:0 auto;\n    padding:24px;\n}\n\n.card{\n    background:#111827;\n    border-radius:14px;\n    overflow:hidden;\n}\n\n.header{\n    padding:22px 24px;\n    background:#0b8f3a;\n    color:#ffffff;\n}\n\n.header-title{\n    font-size:20px;\n    line-height:1.4;\n    font-weight:700;\n    margin:0;\n}\n\n.body{\n    padding:24px;\n    color:#ffffff;\n}\n\n.footer{\n    padding:18px 24px;\n    background:#e5e7eb;\n    color:#374151;\n    font-size:12px;\n    line-height:1.6;\n}\n\n.divider{\n    height:1px;\n    background:#374151;\n    margin:20px 0;\n}\n\n.row{\n    margin-bottom:16px;\n}\n\n.label{\n    color:#9ca3af;\n    font-size:13px;\n    margin-bottom:4px;\n}\n\n.value{\n    font-size:16px;\n    font-weight:700;\n    color:#ffffff;\n}\n\np{\n    color:#ffffff;\n}\n\n@media(max-width:520px){\n    .container{\n        padding:12px;\n    }\n\n    .body{\n        padding:18px;\n    }\n}\n</style>\n\n</head>\n\n<body>\n\n<div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">\nComplément demandé pour DOS-ALG-2026-0007\n</div>\n\n<div class=\"container\">\n\n<div class=\"card\">\n\n<div class=\"header\">\n<p class=\"header-title\">Complément demandé pour votre dossier</p>\n</div>\n\n<div class=\"body\">\n<p class=\"muted\">Des informations ou documents supplémentaires sont nécessaires pour traiter votre dossier.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-ALG-2026-0007</div></div><div class=\"row\"><div class=\"label\">Motif du complément</div><div class=\"value\">Facture de réparation manquante</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de transmettre les documents manquants à votre agence CRMA afin d’accélérer le traitement.</p>\n</div>\n\n<div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile\n</div>\n\n</div>\n\n</div>\n\n</body>\n</html>\n', 'failed', 1, '2026-05-09 09:38:22', NULL, 'SMTP Error: Could not connect to SMTP host. Failed to connect to server', '2026-05-09 09:38:22', 0),
(37, 17, 2, 9, 'complement', 'Complement demande pour le dossier DOS-ALG-2026-0014. Motif : Montant à réévaluer', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-09 09:50:25', 0),
(38, 17, 2, 8, 'complement', 'Complément demandé pour votre dossier DOS-ALG-2026-0014. Motif : Montant à réévaluer. Veuillez transmettre les documents manquants à votre agence CRMA.', 'aida.moufouki@gmail.com', 'Complément demandé pour votre dossier', '\n<!doctype html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>Complément demandé pour votre dossier</title>\n\n<style>\nbody{\n    margin:0;\n    padding:0;\n    background:#f3f5f7;\n    font-family:Arial,Helvetica,sans-serif;\n    color:#1f2937;\n}\n\n.container{\n    max-width:640px;\n    margin:0 auto;\n    padding:24px;\n}\n\n.card{\n    background:#111827;\n    border-radius:14px;\n    overflow:hidden;\n}\n\n.header{\n    padding:22px 24px;\n    background:#0b8f3a;\n    color:#ffffff;\n}\n\n.header-title{\n    font-size:20px;\n    line-height:1.4;\n    font-weight:700;\n    margin:0;\n}\n\n.body{\n    padding:24px;\n    color:#ffffff;\n}\n\n.footer{\n    padding:18px 24px;\n    background:#e5e7eb;\n    color:#374151;\n    font-size:12px;\n    line-height:1.6;\n}\n\n.divider{\n    height:1px;\n    background:#374151;\n    margin:20px 0;\n}\n\n.row{\n    margin-bottom:16px;\n}\n\n.label{\n    color:#9ca3af;\n    font-size:13px;\n    margin-bottom:4px;\n}\n\n.value{\n    font-size:16px;\n    font-weight:700;\n    color:#ffffff;\n}\n\np{\n    color:#ffffff;\n}\n\n@media(max-width:520px){\n    .container{\n        padding:12px;\n    }\n\n    .body{\n        padding:18px;\n    }\n}\n</style>\n\n</head>\n\n<body>\n\n<div style=\"display:none;max-height:0;overflow:hidden;opacity:0;color:transparent\">\nComplément demandé pour DOS-ALG-2026-0014\n</div>\n\n<div class=\"container\">\n\n<div class=\"card\">\n\n<div class=\"header\">\n<p class=\"header-title\">Complément demandé pour votre dossier</p>\n</div>\n\n<div class=\"body\">\n<p class=\"muted\">Des informations ou documents supplémentaires sont nécessaires pour traiter votre dossier.</p><div class=\"divider\"></div><div class=\"row\"><div class=\"label\">Numéro dossier</div><div class=\"value\">DOS-ALG-2026-0014</div></div><div class=\"row\"><div class=\"label\">Motif du complément</div><div class=\"value\">Montant à réévaluer</div></div><div class=\"divider\"></div><p style=\"margin:0;font-size:14px;line-height:1.7\">Merci de transmettre les documents manquants à votre agence CRMA afin d’accélérer le traitement.</p>\n</div>\n\n<div class=\"footer\">\nCRMA / CNMA – Gestion des sinistres automobile\n</div>\n\n</div>\n\n</div>\n\n</body>\n</html>\n', 'sent', 1, '2026-05-09 09:50:28', '2026-05-09 08:50:28', NULL, '2026-05-09 09:50:25', 0);

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

DROP TABLE IF EXISTS `parametre`;
CREATE TABLE IF NOT EXISTS `parametre` (
  `id_parametre` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `valeur` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `type_personne` enum('physique','morale') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `raison_sociale` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `num_identite` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nif` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `num_id_fiscal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activite` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `statut_personne` enum('assure','expert','adversaire') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_personne`),
  UNIQUE KEY `num_identite` (`num_identite`),
  UNIQUE KEY `num_identite_2` (`num_identite`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `personne`
--

INSERT INTO `personne` (`id_personne`, `type_personne`, `nom`, `prenom`, `raison_sociale`, `num_identite`, `telephone`, `adresse`, `email`, `date_naissance`, `lieu_naissance`, `nin`, `nif`, `num_id_fiscal`, `activite`, `statut_personne`) VALUES
(2, 'physique', 'warda', 'mf', '', '026730618', '0541775494', 'alger', 'warda.moufouki@esst-sup.com', NULL, NULL, NULL, NULL, NULL, NULL, 'assure'),
(7, 'physique', 'Moufouki', 'Aida', '', '026737644', '0541775499', 'alger', 'aida.moufouki@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'assure'),
(9, 'physique', 'Ali', 'Karim', NULL, '026737693', '0551111111', 'Alger', 'ali@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(10, 'physique', 'Ben', 'Salah', NULL, '026737690', '0552222222', 'Blida', 'ben@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(11, 'physique', 'Kaci', 'Nadia', NULL, '449693688', '0553333333', 'Oran', 'kaci@mail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(15, 'physique', 'Brahimi', 'Ahmed', NULL, NULL, '0550000001', 'Alger', 'expert1@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(17, 'physique', 'Ferhat', 'Samir', NULL, NULL, '0550000004', 'Alger', 'expert4@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(18, 'physique', 'Saadi', 'Lina', NULL, '026737600', '0550000005', 'Alger', 'expert5@mail.dz', NULL, NULL, NULL, NULL, NULL, NULL, 'expert'),
(19, 'physique', 'mfk', 'fadia', '', '937693688', '0541775498', 'alger', 'moufouki.fadia.enssea@gmail.com', '2004-03-08', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(26, 'physique', 'warda', 'faten', '', '026736614', '0541775499', 'alger', 'faten.moufouki@esst-sup.com', '2004-03-10', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(27, 'physique', 'SALM', 'SLMNI', NULL, '097693620', '0541775494', 'ALGER', 'SALIM.moufouki@esst-sup.com', NULL, NULL, NULL, NULL, NULL, NULL, 'adversaire'),
(29, 'physique', 'warda', 'gjhkj', '', '026737698', '0541775494', 'ALGER', 'warda.moufouki@esst-sup.com', '2004-03-10', 'H', NULL, NULL, NULL, NULL, 'expert'),
(30, 'physique', 'kalem', 'zohra', '', '737693617', '0541775494', 'ALGER', 'kalemzohra70@gmail.com', '2000-04-16', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(31, 'physique', 'aidni', 'aida', NULL, '026733618', '0541375494', 'Butte des deux bassins - Résidence Sahraoui, El Achour 16104', 'aida.moufouki@esst-sup.com', '2000-03-31', 'biar', NULL, NULL, NULL, NULL, 'expert'),
(32, 'physique', 'kalem', 'zohra', NULL, '456789654', '0541776694', 'khraicia', 'kalemzoh70@gmail.com', '1970-05-12', 'biar', NULL, NULL, NULL, NULL, 'assure'),
(34, 'physique', 'meklati', 'kenza', NULL, '063741794', '0541775881', 'kouba', 'kenza.meklati@esst-sup.com', '2003-05-07', 'ALGER', NULL, NULL, NULL, NULL, 'assure'),
(35, 'physique', 'metlab', 'kenza', NULL, '527368909', '0541775899', 'kouba', 'metlab.kenza@p.com', '1999-05-07', 'ALGER', NULL, NULL, NULL, NULL, 'adversaire'),
(36, 'morale', NULL, NULL, 'SARL TRANS LOGISTIQUE', NULL, '0541555494', 'Zone industrielle Rouiba Alger', 'contact@translog.dz', NULL, NULL, NULL, '1547689249', NULL, NULL, 'assure'),
(37, 'physique', 'mbarek', 'amel', NULL, '782829903', '0541-77-54-94', 'kouba', 'amel70@gmail.com', '2000-05-06', 'kouba', NULL, NULL, NULL, NULL, 'assure');

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
  `mode_paiement` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `saisi_par` int DEFAULT NULL,
  `statut` enum('en_attente','disponible','remis') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `reference_paiement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_reglement`),
  UNIQUE KEY `reference_paiement` (`reference_paiement`),
  UNIQUE KEY `reference_paiement_2` (`reference_paiement`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_garantie` (`id_garantie`),
  KEY `saisi_par` (`saisi_par`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reglement`
--

INSERT INTO `reglement` (`id_reglement`, `id_dossier`, `id_garantie`, `montant`, `date_reglement`, `mode_paiement`, `saisi_par`, `statut`, `reference_paiement`, `commentaire`) VALUES
(1, 5, 1, 555.00, '2026-03-31', 'Chèque', 9, 'en_attente', 'CHQ-0002-001', ''),
(2, 8, NULL, 2000.00, '2026-03-31', 'Chèque', 9, 'en_attente', 'CHQ-0005-002', ''),
(3, 8, NULL, 6.00, '2026-03-31', 'Chèque', 9, 'en_attente', 'CHQ-0005-003', ''),
(4, 8, NULL, 1000.00, '2026-04-01', 'Chèque', 9, 'en_attente', 'CHQ-0005-004', ''),
(5, 8, NULL, 8985.00, '2026-04-01', 'Chèque', 9, 'disponible', 'CHQ-0005-005', ''),
(6, 4, NULL, 5050.00, '2026-04-01', 'Chèque', 9, 'disponible', 'CHQ-0001-006', ''),
(7, 4, NULL, 300.00, '2026-04-01', 'Chèque', 9, 'disponible', 'CHQ-0001-007', ''),
(11, 11, NULL, 10.00, '2026-04-17', 'Chèque', 9, 'en_attente', 'CHQ-0008-011', ''),
(12, 7, NULL, 300.00, '2026-04-17', 'Chèque', 9, 'en_attente', 'CHQ-0004-012', ''),
(13, 7, NULL, 600000.00, '2026-04-21', 'Chèque', 9, 'en_attente', 'CHQ-0004-013', ''),
(14, 11, NULL, 10500.00, '2026-04-24', 'Chèque', 9, 'en_attente', 'CHQ-0008-014', ''),
(15, 7, NULL, 5000000.00, '2026-04-24', 'Chèque', 9, 'en_attente', 'CHQ-0004-015', ''),
(16, 7, NULL, 10020.00, '2026-04-26', 'Chèque', 9, 'en_attente', 'CHQ-0004-016', ''),
(17, 13, NULL, 1000.00, '2026-04-27', 'Chèque', 9, 'en_attente', 'CHQ-0010-017', ''),
(18, 13, NULL, 300.00, '2026-04-27', 'Chèque', 9, 'en_attente', 'CHQ-0010-018', ''),
(19, 14, NULL, 600.00, '2026-04-27', 'Chèque', 9, 'remis', 'CHQ-0011-019', ''),
(20, 14, NULL, 1500.00, '2026-04-27', 'Chèque', 9, 'disponible', 'CHQ-0011-020', ''),
(21, 15, NULL, 1500.00, '2026-04-27', 'Chèque', 9, 'remis', 'CHQ-0012-021', ''),
(23, 17, NULL, 1500.00, '2026-04-27', 'Chèque', 9, 'en_attente', 'CHQ-0014-023', ''),
(25, 17, NULL, 1.00, '2026-04-30', 'Chèque', 9, 'en_attente', 'CHQ-0014-025', ''),
(26, 18, NULL, 10000.00, '2026-05-06', 'Chèque', 10, 'en_attente', 'CHQ-0015-026', ''),
(27, 22, NULL, 1000.00, '2026-05-07', 'Chèque', 11, 'remis', 'CHQ-0001-027', ''),
(28, 9, NULL, 10000.00, '2026-05-07', 'Chèque', 9, 'disponible', 'CHQ-0006-028', ''),
(29, 24, NULL, 1000.00, '2026-05-07', 'Chèque', 11, 'remis', 'CHQ-0003-029', ''),
(30, 14, NULL, 1000.00, '2026-05-07', 'Chèque', 9, 'disponible', 'CHQ-0011-030', ''),
(31, 14, NULL, 10000.00, '2026-05-07', 'Chèque', 9, 'en_attente', 'CHQ-0011-031', ''),
(35, 13, NULL, 1100.00, '2026-05-08', 'Chèque', 9, 'en_attente', 'CHQ-0010-035', ''),
(37, 17, NULL, 500000.00, '2026-05-08', 'Chèque', 9, 'en_attente', 'CHQ-0014-037', ''),
(38, 14, NULL, 486901.00, '2026-05-08', 'Chèque', 9, 'en_attente', 'CHQ-0011-038', ''),
(39, 25, NULL, 1789.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-039', ''),
(40, 25, NULL, 400.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-040', ''),
(41, 25, NULL, 500.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-041', ''),
(42, 25, NULL, 1000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-042', ''),
(43, 25, NULL, 4000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-043', ''),
(44, 25, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-044', ''),
(45, 25, NULL, 200.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-045', ''),
(46, 25, NULL, 2011.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-046', ''),
(47, 26, NULL, 2000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0005-047', ''),
(48, 26, NULL, 9100.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0005-048', ''),
(49, 26, NULL, 9100.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0005-049', ''),
(50, 24, NULL, 10000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0003-050', ''),
(51, 22, NULL, 149000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0001-051', ''),
(52, 25, NULL, 10000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-052', ''),
(53, 25, NULL, 10000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0004-053', ''),
(54, 25, NULL, 1900.00, '2026-05-08', 'Chèque', 11, 'disponible', 'CHQ-0004-054', ''),
(56, 27, NULL, 100000.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0006-056', ''),
(57, 27, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'remis', 'CHQ-0006-057', ''),
(58, 28, NULL, 9999.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-058', ''),
(59, 28, NULL, 10000.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-059', ''),
(60, 28, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-060', ''),
(61, 28, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-061', ''),
(62, 28, NULL, 199.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-062', ''),
(63, 28, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0007-063', ''),
(64, 29, NULL, 10000.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0008-064', ''),
(65, 30, NULL, 100000.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0009-065', ''),
(66, 30, NULL, 400.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0009-066', ''),
(67, 30, NULL, 100.00, '2026-05-08', 'Chèque', 11, 'en_attente', 'CHQ-0009-067', '');

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
  `type_reserve` enum('initiale','expertise','ajustement','complementaire') COLLATE utf8mb4_general_ci NOT NULL,
  `cree_par` int DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `statut` enum('actif','annule') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'actif',
  PRIMARY KEY (`id_reserve`),
  KEY `id_dossier` (`id_dossier`),
  KEY `id_garantie` (`id_garantie`),
  KEY `cree_par` (`cree_par`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(47, 6, 1, 90.00, '2026-04-02', 'ajustement', 2, '2026-04-02', 'UNPEU', 'actif'),
(48, 9, 1, 100.00, '2026-04-17', 'expertise', 9, '2026-04-17', 'Réserve après expertise', 'actif'),
(49, 9, 1, 499000.00, '2026-04-19', 'expertise', 9, '2026-04-19', 'Réserve après expertise', 'actif'),
(50, 9, 1, 150000.00, '2026-04-19', 'ajustement', 9, '2026-04-19', '', 'actif'),
(51, 6, 1, 100000.00, '2026-04-19', 'ajustement', 9, '2026-04-19', '', 'actif'),
(52, 11, 1, 90.00, '2026-04-24', 'ajustement', 9, '2026-04-24', 'Réserve complémentaire auto (règlement > réserve)', 'actif'),
(53, 7, 1, 555300.00, '2026-04-26', 'ajustement', 9, '2026-04-26', 'AJUSTEMENT', 'actif'),
(54, 7, 1, 5010.00, '2026-04-26', 'ajustement', 9, '2026-04-26', '', 'actif'),
(55, 13, 1, 200.00, '2026-04-27', 'initiale', NULL, NULL, NULL, 'actif'),
(56, 13, 1, 2000.00, '2026-04-27', 'ajustement', 9, '2026-04-27', '', 'actif'),
(57, 14, 1, 1000.00, '2026-04-27', 'initiale', NULL, NULL, NULL, 'actif'),
(58, 14, 1, 600.00, '2026-04-27', 'ajustement', 9, '2026-04-27', 'Réserve complémentaire auto (dépassement règlement)', 'actif'),
(59, 14, 1, 500.00, '2026-04-27', 'ajustement', 9, '2026-04-27', 'Réserve complémentaire auto (dépassement règlement)', 'actif'),
(60, 15, 1, 1000.00, '2026-04-27', 'initiale', NULL, NULL, NULL, 'actif'),
(61, 15, 1, 500.00, '2026-04-27', 'ajustement', 9, '2026-04-27', 'Ajustement estimation après dépassement paiement', 'actif'),
(64, 17, 1, 1000.00, '2026-04-27', 'initiale', NULL, NULL, NULL, 'actif'),
(66, 17, 1, 498500.00, '2026-04-30', '', 9, '2026-04-30', 'Réserve complémentaire après dépassement règlement', 'actif'),
(68, 14, 1, 497900.00, '2026-04-30', 'ajustement', 9, '2026-04-30', '', 'actif'),
(69, 14, 1, 1.00, '2026-04-30', 'ajustement', 9, '2026-04-30', '', 'actif'),
(72, 18, 1, 100.00, '2026-05-01', 'initiale', NULL, NULL, NULL, 'actif'),
(73, 10, 1, 500100.00, '2026-05-04', 'ajustement', 9, '2026-05-04', '', 'actif'),
(76, 20, 1, 190000.00, '2026-05-06', 'initiale', NULL, NULL, NULL, 'actif'),
(77, 18, 1, 100000.00, '2026-05-06', 'ajustement', 10, '2026-05-06', 'EXPERTISE', 'actif'),
(78, 22, 1, 150000.00, '2026-05-07', 'initiale', NULL, NULL, NULL, 'actif'),
(79, 24, 1, 10000.00, '2026-05-07', 'initiale', NULL, NULL, NULL, 'actif'),
(80, 24, 4, 1000.00, '2026-05-07', 'initiale', NULL, NULL, NULL, 'actif'),
(81, 13, 1, 100.00, '2026-05-08', 'ajustement', 9, '2026-05-08', '', 'actif'),
(84, 13, 1, 200.00, '2026-05-08', 'ajustement', 9, '2026-05-08', '', 'actif'),
(85, 25, 1, 10000.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(86, 26, 1, 1000.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(87, 26, 1, 10000.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(88, 26, 1, 100.00, '2026-05-08', 'complementaire', 11, '2026-05-08', 'Réserve complémentaire après dépassement règlement', 'actif'),
(89, 26, 1, 9100.00, '2026-05-08', 'complementaire', 11, '2026-05-08', 'Réserve complémentaire après dépassement règlement', 'actif'),
(90, 25, 1, 10000.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(91, 25, 1, 10000.00, '2026-05-08', 'complementaire', 11, '2026-05-08', 'Réserve complémentaire après dépassement règlement', 'actif'),
(92, 25, 1, 1900.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(93, 27, 1, 100000.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(94, 27, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(95, 28, 1, 9999.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(96, 28, 1, 1000.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(97, 28, 1, 9000.00, '2026-05-08', 'complementaire', 11, '2026-05-08', 'Réserve complémentaire après dépassement règlement', 'actif'),
(98, 28, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(99, 28, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(100, 28, 1, 199.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(101, 28, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(102, 29, 1, 10000.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(103, 30, 1, 100000.00, '2026-05-08', 'initiale', NULL, NULL, NULL, 'actif'),
(104, 30, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(105, 30, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(106, 30, 1, 200.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(107, 30, 1, 100.00, '2026-05-08', 'ajustement', 11, '2026-05-08', '', 'actif'),
(108, 17, 1, 100000.00, '2026-05-09', 'ajustement', 9, '2026-05-09', '', 'actif'),
(109, 31, 1, 100000.00, '2026-05-09', 'initiale', NULL, NULL, NULL, 'actif'),
(110, 31, 1, 450000.00, '2026-05-09', 'ajustement', 11, '2026-05-09', '', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `seuil_validation`
--

DROP TABLE IF EXISTS `seuil_validation`;
CREATE TABLE IF NOT EXISTS `seuil_validation` (
  `id_seuil` int NOT NULL AUTO_INCREMENT,
  `montant_min` decimal(12,2) DEFAULT NULL,
  `montant_max` decimal(12,2) DEFAULT NULL,
  `niveau_validation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `compagnie_assurance` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_police` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable` enum('oui','non','partiel') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_tiers`),
  UNIQUE KEY `numero_police` (`numero_police`),
  KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tiers`
--

INSERT INTO `tiers` (`id_tiers`, `id_personne`, `compagnie_assurance`, `numero_police`, `responsable`) VALUES
(5, 9, 'SAA', 'SAA123456', 'oui'),
(6, 10, 'CAAR', 'CAAR654321', 'oui'),
(7, 11, 'GAM', 'GAM456123', 'non'),
(8, 27, 'SAA', 'saa23E', 'non'),
(9, 35, 'SAA', 'SAA789090', 'oui');

-- --------------------------------------------------------

--
-- Structure de la table `type_document`
--

DROP TABLE IF EXISTS `type_document`;
CREATE TABLE IF NOT EXISTS `type_document` (
  `id_type_document` int NOT NULL AUTO_INCREMENT,
  `nom_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mot_de_passe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('CNMA','CRMA','ASSURE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_agence` int DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `id_personne` int DEFAULT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`),
  KEY `id_agence` (`id_agence`),
  KEY `id_personne` (`id_personne`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `id_agence`, `actif`, `id_personne`, `telephone`) VALUES
(2, 'Admin CNMA', NULL, 'admin@cnma.dz', '$2y$10$SGV3kl4Q1PAdY6lKs3XTRefMCmq.IYZ2OcmGijgxQ2DhEHnNajrou', 'CNMA', NULL, 1, NULL, '021 74 50 21'),
(8, NULL, NULL, 'aida.moufouki@gmail.com', '$2y$10$0s91L7PPUpFwEdwuMKLXS.a2q8UFt5gDmaqRB4ib3FGPfsQBj6Ccq', 'ASSURE', NULL, 1, 7, NULL),
(9, 'Agent Alger', NULL, 'alger@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 1, 1, NULL, '023321597'),
(10, 'Agent Oran', NULL, 'oran@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 2, 1, NULL, NULL),
(11, 'Agent Constantine', NULL, 'constantine@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 3, 1, NULL, NULL),
(12, 'Agent Ouargla', NULL, 'ouargla@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 5, 1, NULL, NULL),
(13, NULL, NULL, 'warda.moufouki@esst-sup.com', '$2y$10$a/lCJPeGNRJS07fCPJA4cOPn5sZtS9i93kyaEZM1qChFjsTWeEJ7C', 'ASSURE', NULL, 1, 2, NULL),
(14, NULL, NULL, 'kenza.meklati@esst-sup.com', '$2y$10$1jGi4ncSzMA/5foN2avohOG7idTOdkXkp5WgtEsqUX/.1q/YNgYYq', 'ASSURE', NULL, 1, 34, NULL),
(15, NULL, NULL, 'moufouki.fadia.enssea@gmail.com', '$2y$10$mzYnSGISGS3u/H9M0qkc..A7pM3AGVJKw68SlRjD6e6VzkBrv0hbK', 'ASSURE', NULL, 1, 19, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

DROP TABLE IF EXISTS `vehicule`;
CREATE TABLE IF NOT EXISTS `vehicule` (
  `id_vehicule` int NOT NULL AUTO_INCREMENT,
  `marque` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modele` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `couleur` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre_places` int DEFAULT NULL,
  `matricule` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_chassis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `annee` int DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `carrosserie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_vehicule`),
  UNIQUE KEY `matricule` (`matricule`),
  UNIQUE KEY `numero_chassis` (`numero_chassis`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicule`
--

INSERT INTO `vehicule` (`id_vehicule`, `marque`, `modele`, `couleur`, `nombre_places`, `matricule`, `numero_chassis`, `numero_serie`, `annee`, `type`, `carrosserie`) VALUES
(1, 'renault', 'clio', 'blanche', 5, '12345-114-15', 'CVC426VVNBS648076', 'SN_5516747897', 2014, 'Tourisme', 'Berline'),
(2, 'peugot', '308', 'blanche', 5, '12994-122-16', 'VF1ABC12345678901', 'SER123456987', 2022, 'Tourisme', 'Berline'),
(3, 'kia', 'karens', 'BLANC', 7, '5434-115-16', 'CH123454789', 'SER997654321', 2015, 'Tourisme', 'Berline'),
(6, 'toyota', 'yaris', 'blanc', 5, '00345-116-16', 'VN4653782932', 'SN-4536178913', 2016, 'Tourisme', 'Berline'),
(7, 'renault', 'premium', 'blanc', 3, '12345-117-02', 'VF633GPA000123456', 'RNL-2026-001', 2009, 'Camion', 'Camion'),
(8, 'kia', 'picanto', 'blanc', 4, '45526-112-16', 'GH34251678999021', 'SDF1267899991', 2012, 'Tourisme', 'Berline');

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
