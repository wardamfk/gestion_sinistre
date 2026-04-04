-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 04 avr. 2026 à 15:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

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

CREATE TABLE `agence` (
  `id_agence` int(11) NOT NULL,
  `nom_agence` varchar(100) NOT NULL,
  `type_agence` enum('CRMA','CNMA') NOT NULL,
  `wilaya` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `assure` (
  `id_assure` int(11) NOT NULL,
  `id_personne` int(11) DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `num_permis` varchar(50) DEFAULT NULL,
  `date_delivrance_permis` date DEFAULT NULL,
  `lieu_delivrance_permis` varchar(100) DEFAULT NULL,
  `type_permis` varchar(50) DEFAULT NULL,
  `piece_identite` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `assure`
--

INSERT INTO `assure` (`id_assure`, `id_personne`, `date_creation`, `actif`, `num_permis`, `date_delivrance_permis`, `lieu_delivrance_permis`, `type_permis`, `piece_identite`) VALUES
(2, 7, '2026-03-24', 0, NULL, NULL, NULL, NULL, NULL),
(3, 2, '2026-03-24', 1, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `contrat`
--

CREATE TABLE `contrat` (
  `id_contrat` int(11) NOT NULL,
  `numero_police` varchar(100) DEFAULT NULL,
  `id_assure` int(11) DEFAULT NULL,
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
  `statut` enum('actif','expire','suspendu') DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `id_vehicule` int(11) DEFAULT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `id_formule` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `contrat_garantie` (
  `id_contrat` int(11) NOT NULL,
  `id_garantie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contrat_garantie`
--

INSERT INTO `contrat_garantie` (`id_contrat`, `id_garantie`) VALUES
(1, 1),
(1, 2),
(2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id_document` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `nom_fichier` varchar(255) DEFAULT NULL,
  `date_upload` date DEFAULT NULL,
  `upload_par` int(11) DEFAULT NULL,
  `id_type_document` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `dossier` (
  `id_dossier` int(11) NOT NULL,
  `numero_dossier` varchar(100) DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `cree_par` int(11) DEFAULT NULL,
  `date_transmission` date DEFAULT NULL,
  `transmis_par` int(11) DEFAULT NULL,
  `info_complementaire` text DEFAULT NULL,
  `id_etat` int(11) DEFAULT NULL,
  `id_contrat` int(11) DEFAULT NULL,
  `id_tiers` int(11) DEFAULT NULL,
  `date_sinistre` date DEFAULT NULL,
  `lieu_sinistre` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `delai_declaration` int(11) DEFAULT NULL,
  `total_reserve` decimal(10,2) DEFAULT NULL,
  `statut_validation` enum('non_soumis','en_attente','valide','refuse') DEFAULT 'non_soumis',
  `date_validation` date DEFAULT NULL,
  `date_refus` date DEFAULT NULL,
  `date_cloture` date DEFAULT NULL,
  `commentaire_cnma` text DEFAULT NULL,
  `valide_par` int(11) DEFAULT NULL,
  `id_expert` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossier`
--

INSERT INTO `dossier` (`id_dossier`, `numero_dossier`, `date_creation`, `cree_par`, `date_transmission`, `transmis_par`, `info_complementaire`, `id_etat`, `id_contrat`, `id_tiers`, `date_sinistre`, `lieu_sinistre`, `description`, `delai_declaration`, `total_reserve`, `statut_validation`, `date_validation`, `date_refus`, `date_cloture`, `commentaire_cnma`, `valide_par`, `id_expert`) VALUES
(4, 'DOS-2026-0001', '2026-03-30', 9, NULL, NULL, 'HH', 14, 1, 5, '2026-03-14', 'ALGER', 'Accident matériel', 4, 5350.00, 'valide', '2026-04-01', NULL, '2026-04-03', NULL, NULL, 2),
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

CREATE TABLE `encaissement` (
  `id_encaissement` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_encaissement` date DEFAULT NULL,
  `id_tiers` int(11) DEFAULT NULL,
  `type` enum('recours','franchise','epave','autre') DEFAULT 'recours',
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `etat_dossier`
--

CREATE TABLE `etat_dossier` (
  `id_etat` int(11) NOT NULL,
  `nom_etat` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etat_dossier`
--

INSERT INTO `etat_dossier` (`id_etat`, `nom_etat`) VALUES
(1, 'Brouillon'),
(2, 'En cours CRMA'),
(3, 'Transmis CNMA'),
(4, 'Valide CNMA'),
(5, 'Refuse CNMA'),
(6, 'Complément demandé'),
(7, 'reglement partiel'),
(8, 'reglement total'),
(9, 'en cours dexpertise'),
(10, 'reglement partiel'),
(11, 'clasee sans suite'),
(12, 'clasee apres rejet'),
(13, 'clasee en attente recours'),
(14, 'Clôturé');

-- --------------------------------------------------------

--
-- Structure de la table `expert`
--

CREATE TABLE `expert` (
  `id_expert` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `activite` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `expert`
--

INSERT INTO `expert` (`id_expert`, `nom`, `prenom`, `telephone`, `email`, `activite`) VALUES
(1, 'Brahimi', 'Ahmed', '0550000001', 'expert1@mail.dz', 'Expert automobile'),
(2, 'Benali', 'Karim', '0550000002', 'expert2@mail.dz', 'Expert automobile'),
(3, 'Mansouri', 'Nadia', '0550000003', 'expert3@mail.dz', 'Expert automobile'),
(4, 'Ferhat', 'Samir', '0550000004', 'expert4@mail.dz', 'Expert automobile'),
(5, 'Saadi', 'Lina', '0550000005', 'expert5@mail.dz', 'Expert automobile');

-- --------------------------------------------------------

--
-- Structure de la table `expertise`
--

CREATE TABLE `expertise` (
  `id_expertise` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `id_expert` int(11) DEFAULT NULL,
  `date_expertise` date DEFAULT NULL,
  `rapport_pdf` varchar(255) DEFAULT NULL,
  `montant_indemnite` decimal(12,2) DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `formule` (
  `id_formule` int(11) NOT NULL,
  `nom_formule` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `formule_garantie` (
  `id_formule` int(11) NOT NULL,
  `id_garantie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `formule_garantie`
--

INSERT INTO `formule_garantie` (`id_formule`, `id_garantie`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 1),
(3, 3),
(3, 4),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6);

-- --------------------------------------------------------

--
-- Structure de la table `garantie`
--

CREATE TABLE `garantie` (
  `id_garantie` int(11) NOT NULL,
  `nom_garantie` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `code_garantie` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `historique` (
  `id_historique` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `date_action` datetime DEFAULT NULL,
  `fait_par` int(11) DEFAULT NULL,
  `ancien_etat` int(11) DEFAULT NULL,
  `nouvel_etat` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `id_motif` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(83, 10, 'Règlement total', '2026-04-04 14:10:51', 9, 2, 8, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `motif`
--

CREATE TABLE `motif` (
  `id_motif` int(11) NOT NULL,
  `id_etat` int(11) DEFAULT NULL,
  `nom_motif` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id_notification` int(11) NOT NULL,
  `id_dossier` int(11) NOT NULL,
  `id_expediteur` int(11) NOT NULL,
  `id_destinataire` int(11) NOT NULL,
  `type` enum('validation','refus','complement','reglement','cloture') NOT NULL,
  `message` text NOT NULL,
  `date_notification` datetime DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`id_notification`, `id_dossier`, `id_expediteur`, `id_destinataire`, `type`, `message`, `date_notification`, `lu`) VALUES
(1, 6, 2, 9, 'complement', 'Complément demandé pour le dossier DOS-2026-0003. Veuillez compléter les documents manquants et re-transmettre.', '2026-04-03 00:40:45', 1),
(2, 4, 2, 9, 'cloture', 'Le dossier DOS-2026-0001 a été clôturé définitivement par la CNMA.', '2026-04-03 00:50:06', 1),
(3, 10, 9, 8, 'reglement', 'Un chèque est disponible pour le dossier DOS-2026-0007. Veuillez vous présenter à votre agence CRMA pour le récupérer.', '2026-04-04 14:15:18', 1);

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

CREATE TABLE `parametre` (
  `id_parametre` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `valeur` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `personne` (
  `id_personne` int(11) NOT NULL,
  `type_personne` enum('physique','morale') DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `raison_sociale` varchar(150) DEFAULT NULL,
  `num_identite` varchar(50) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `nin` varchar(50) DEFAULT NULL,
  `nif` varchar(50) DEFAULT NULL,
  `num_id_fiscal` varchar(50) DEFAULT NULL,
  `activite` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `personne`
--

INSERT INTO `personne` (`id_personne`, `type_personne`, `nom`, `prenom`, `raison_sociale`, `num_identite`, `telephone`, `adresse`, `email`, `date_naissance`, `lieu_naissance`, `nin`, `nif`, `num_id_fiscal`, `activite`) VALUES
(2, 'physique', 'warda', 'mf', '', '026737693618', '0541775494', 'alger', 'warda.moufouki@esst-sup.com', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'physique', 'Moufouki', 'Aida', '', '026737693619', '0541775499', 'alger', 'medecin@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'physique', 'FH', 'KILO', '', '026737693617', '0541775499', 'alger', 'warda.moufouki@esst-s.com', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'physique', 'Ali', 'Karim', NULL, NULL, '0551111111', 'Alger', 'ali@mail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'physique', 'Ben', 'Salah', NULL, NULL, '0552222222', 'Blida', 'ben@mail.com', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'physique', 'Kaci', 'Nadia', NULL, NULL, '0553333333', 'Oran', 'kaci@mail.com', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reglement`
--

CREATE TABLE `reglement` (
  `id_reglement` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `id_garantie` int(11) DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_reglement` date DEFAULT NULL,
  `mode_paiement` varchar(50) DEFAULT NULL,
  `saisi_par` int(11) DEFAULT NULL,
  `statut` enum('en_attente','disponible','remis') DEFAULT 'en_attente',
  `reference_paiement` varchar(100) DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reglement`
--

INSERT INTO `reglement` (`id_reglement`, `id_dossier`, `id_garantie`, `montant`, `date_reglement`, `mode_paiement`, `saisi_par`, `statut`, `reference_paiement`, `commentaire`) VALUES
(1, 5, 1, 555.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(2, 8, NULL, 2000.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(3, 8, NULL, 6.00, '2026-03-31', 'Chèque', 9, 'en_attente', NULL, ''),
(4, 8, NULL, 1000.00, '2026-04-01', 'Chèque', 9, 'en_attente', NULL, ''),
(5, 8, NULL, 8985.00, '2026-04-01', 'Chèque', 9, 'en_attente', NULL, ''),
(6, 4, NULL, 5050.00, '2026-04-01', 'Chèque', 9, 'en_attente', NULL, ''),
(7, 4, NULL, 300.00, '2026-04-01', 'Chèque', 9, 'en_attente', NULL, ''),
(8, 11, NULL, 200.00, '2026-04-02', 'Chèque', 9, 'en_attente', NULL, ''),
(9, 10, NULL, 2000.00, '2026-04-04', 'Chèque', 9, 'disponible', NULL, '');

-- --------------------------------------------------------

--
-- Structure de la table `reserve`
--

CREATE TABLE `reserve` (
  `id_reserve` int(11) NOT NULL,
  `id_dossier` int(11) DEFAULT NULL,
  `id_garantie` int(11) DEFAULT NULL,
  `montant` decimal(12,2) DEFAULT NULL,
  `date_reserve` date DEFAULT NULL,
  `type_reserve` enum('initiale','expertise','ajustement') NOT NULL,
  `cree_par` int(11) DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `statut` enum('actif','annule') DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `seuil_validation` (
  `id_seuil` int(11) NOT NULL,
  `montant_min` decimal(12,2) DEFAULT NULL,
  `montant_max` decimal(12,2) DEFAULT NULL,
  `niveau_validation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `tiers` (
  `id_tiers` int(11) NOT NULL,
  `id_personne` int(11) DEFAULT NULL,
  `compagnie_assurance` varchar(150) DEFAULT NULL,
  `numero_police` varchar(100) DEFAULT NULL,
  `responsable` enum('oui','non','partiel') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tiers`
--

INSERT INTO `tiers` (`id_tiers`, `id_personne`, `compagnie_assurance`, `numero_police`, `responsable`) VALUES
(5, 9, 'SAA', 'SAA123456', 'oui'),
(6, 10, 'CAAR', 'CAAR654321', 'oui'),
(7, 11, 'GAM', 'GAM456123', 'oui');

-- --------------------------------------------------------

--
-- Structure de la table `type_document`
--

CREATE TABLE `type_document` (
  `id_type_document` int(11) NOT NULL,
  `nom_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `utilisateur` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` enum('CNMA','CRMA','ASSURE') DEFAULT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `id_personne` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `id_agence`, `actif`, `id_personne`) VALUES
(2, 'Admin CNMA', NULL, 'admin@cnma.dz', '$2y$10$SGV3kl4Q1PAdY6lKs3XTRefMCmq.IYZ2OcmGijgxQ2DhEHnNajrou', 'CNMA', NULL, 1, NULL),
(8, NULL, NULL, 'medecin@gmail.com', '$2y$10$0s91L7PPUpFwEdwuMKLXS.a2q8UFt5gDmaqRB4ib3FGPfsQBj6Ccq', 'ASSURE', NULL, 1, 7),
(9, 'Agent Alger', NULL, 'alger@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 1, 1, NULL),
(10, 'Agent Oran', NULL, 'oran@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 2, 1, NULL),
(11, 'Agent Constantine', NULL, 'constantine@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 3, 1, NULL),
(12, 'Agent Ouargla', NULL, 'ouargla@crma.dz', '$2y$10$FAEnhpk92fXUAlWWFD9PqOJqlFMmheWHkpaEoLQsMS4IVAeORR.IS', 'CRMA', 5, 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

CREATE TABLE `vehicule` (
  `id_vehicule` int(11) NOT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `couleur` varchar(50) DEFAULT NULL,
  `nombre_places` int(11) DEFAULT NULL,
  `matricule` varchar(50) DEFAULT NULL,
  `numero_chassis` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `annee` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `carrosserie` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicule`
--

INSERT INTO `vehicule` (`id_vehicule`, `marque`, `modele`, `couleur`, `nombre_places`, `matricule`, `numero_chassis`, `numero_serie`, `annee`, `type`, `carrosserie`) VALUES
(1, 'KL', 'KL', 'KL', 4, '567899', '89', '8', 2005, 'Tourisme', 'Berline');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `agence`
--
ALTER TABLE `agence`
  ADD PRIMARY KEY (`id_agence`);

--
-- Index pour la table `assure`
--
ALTER TABLE `assure`
  ADD PRIMARY KEY (`id_assure`),
  ADD UNIQUE KEY `id_personne` (`id_personne`);

--
-- Index pour la table `contrat`
--
ALTER TABLE `contrat`
  ADD PRIMARY KEY (`id_contrat`),
  ADD UNIQUE KEY `numero_police` (`numero_police`),
  ADD KEY `id_assure` (`id_assure`),
  ADD KEY `fk_contrat_vehicule` (`id_vehicule`),
  ADD KEY `fk_contrat_agence` (`id_agence`),
  ADD KEY `id_formule` (`id_formule`);

--
-- Index pour la table `contrat_garantie`
--
ALTER TABLE `contrat_garantie`
  ADD PRIMARY KEY (`id_contrat`,`id_garantie`),
  ADD KEY `id_garantie` (`id_garantie`);

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id_document`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `upload_par` (`upload_par`),
  ADD KEY `fk_type_document` (`id_type_document`);

--
-- Index pour la table `dossier`
--
ALTER TABLE `dossier`
  ADD PRIMARY KEY (`id_dossier`),
  ADD UNIQUE KEY `numero_dossier` (`numero_dossier`),
  ADD KEY `cree_par` (`cree_par`),
  ADD KEY `transmis_par` (`transmis_par`),
  ADD KEY `fk_etat_dossier` (`id_etat`);

--
-- Index pour la table `encaissement`
--
ALTER TABLE `encaissement`
  ADD PRIMARY KEY (`id_encaissement`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `fk_encaissement_tiers` (`id_tiers`);

--
-- Index pour la table `etat_dossier`
--
ALTER TABLE `etat_dossier`
  ADD PRIMARY KEY (`id_etat`);

--
-- Index pour la table `expert`
--
ALTER TABLE `expert`
  ADD PRIMARY KEY (`id_expert`);

--
-- Index pour la table `expertise`
--
ALTER TABLE `expertise`
  ADD PRIMARY KEY (`id_expertise`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_expert` (`id_expert`);

--
-- Index pour la table `formule`
--
ALTER TABLE `formule`
  ADD PRIMARY KEY (`id_formule`);

--
-- Index pour la table `formule_garantie`
--
ALTER TABLE `formule_garantie`
  ADD PRIMARY KEY (`id_formule`,`id_garantie`),
  ADD KEY `id_garantie` (`id_garantie`);

--
-- Index pour la table `garantie`
--
ALTER TABLE `garantie`
  ADD PRIMARY KEY (`id_garantie`);

--
-- Index pour la table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `fait_par` (`fait_par`),
  ADD KEY `fk_ancien_etat` (`ancien_etat`),
  ADD KEY `fk_nouvel_etat` (`nouvel_etat`),
  ADD KEY `fk_historique_motif` (`id_motif`);

--
-- Index pour la table `motif`
--
ALTER TABLE `motif`
  ADD PRIMARY KEY (`id_motif`),
  ADD KEY `id_etat` (`id_etat`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_expediteur` (`id_expediteur`),
  ADD KEY `id_destinataire` (`id_destinataire`);

--
-- Index pour la table `parametre`
--
ALTER TABLE `parametre`
  ADD PRIMARY KEY (`id_parametre`);

--
-- Index pour la table `personne`
--
ALTER TABLE `personne`
  ADD PRIMARY KEY (`id_personne`),
  ADD UNIQUE KEY `num_identite` (`num_identite`);

--
-- Index pour la table `reglement`
--
ALTER TABLE `reglement`
  ADD PRIMARY KEY (`id_reglement`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_garantie` (`id_garantie`),
  ADD KEY `saisi_par` (`saisi_par`);

--
-- Index pour la table `reserve`
--
ALTER TABLE `reserve`
  ADD PRIMARY KEY (`id_reserve`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_garantie` (`id_garantie`),
  ADD KEY `cree_par` (`cree_par`);

--
-- Index pour la table `seuil_validation`
--
ALTER TABLE `seuil_validation`
  ADD PRIMARY KEY (`id_seuil`);

--
-- Index pour la table `tiers`
--
ALTER TABLE `tiers`
  ADD PRIMARY KEY (`id_tiers`),
  ADD KEY `id_personne` (`id_personne`);

--
-- Index pour la table `type_document`
--
ALTER TABLE `type_document`
  ADD PRIMARY KEY (`id_type_document`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_agence` (`id_agence`),
  ADD KEY `id_personne` (`id_personne`);

--
-- Index pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD PRIMARY KEY (`id_vehicule`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `agence`
--
ALTER TABLE `agence`
  MODIFY `id_agence` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `assure`
--
ALTER TABLE `assure`
  MODIFY `id_assure` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `contrat`
--
ALTER TABLE `contrat`
  MODIFY `id_contrat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `document`
--
ALTER TABLE `document`
  MODIFY `id_document` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `dossier`
--
ALTER TABLE `dossier`
  MODIFY `id_dossier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `encaissement`
--
ALTER TABLE `encaissement`
  MODIFY `id_encaissement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `etat_dossier`
--
ALTER TABLE `etat_dossier`
  MODIFY `id_etat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `expert`
--
ALTER TABLE `expert`
  MODIFY `id_expert` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `expertise`
--
ALTER TABLE `expertise`
  MODIFY `id_expertise` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `formule`
--
ALTER TABLE `formule`
  MODIFY `id_formule` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `garantie`
--
ALTER TABLE `garantie`
  MODIFY `id_garantie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `historique`
--
ALTER TABLE `historique`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT pour la table `motif`
--
ALTER TABLE `motif`
  MODIFY `id_motif` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `parametre`
--
ALTER TABLE `parametre`
  MODIFY `id_parametre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `personne`
--
ALTER TABLE `personne`
  MODIFY `id_personne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `reglement`
--
ALTER TABLE `reglement`
  MODIFY `id_reglement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `reserve`
--
ALTER TABLE `reserve`
  MODIFY `id_reserve` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `seuil_validation`
--
ALTER TABLE `seuil_validation`
  MODIFY `id_seuil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `tiers`
--
ALTER TABLE `tiers`
  MODIFY `id_tiers` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `type_document`
--
ALTER TABLE `type_document`
  MODIFY `id_type_document` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `vehicule`
--
ALTER TABLE `vehicule`
  MODIFY `id_vehicule` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
