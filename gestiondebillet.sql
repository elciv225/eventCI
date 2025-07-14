-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 12 juil. 2025 à 21:27
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestiondebillet`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `AjouterEvenement`$$
CREATE
    DEFINER = `root`@`localhost` PROCEDURE `AjouterEvenement`(IN `p_Titre` VARCHAR(100), IN `p_Description` TEXT,
                                                              IN `p_Adresse` VARCHAR(100), IN `p_DateDebut` DATETIME,
                                                              IN `p_DateFin` DATETIME, IN `p_Id_Ville` VARCHAR(50),
                                                              IN `p_Id_Categorie` INT, IN `p_Id_Createur` INT)
BEGIN
    INSERT INTO Evenement (Titre, Description, Adresse, DateDebut, DateFin, Id_Ville, Id_CategorieEvenement)
    VALUES (p_Titre, p_Description, p_Adresse, p_DateDebut, p_DateFin, p_Id_Ville, p_Id_Categorie);

    SET @last_id = LAST_INSERT_ID();

    INSERT INTO Creer (Id_Utilisateur, Id_Evenement, DateCreation)
    VALUES (p_Id_Createur, @last_id, NOW());
END$$

DROP PROCEDURE IF EXISTS `ModifierEvenement`$$
CREATE
    DEFINER = `root`@`localhost` PROCEDURE `ModifierEvenement`(IN `p_Id_Evenement` INT, IN `p_Titre` VARCHAR(100),
                                                               IN `p_Description` TEXT, IN `p_Adresse` VARCHAR(100),
                                                               IN `p_DateDebut` DATETIME, IN `p_DateFin` DATETIME,
                                                               IN `p_Id_Ville` VARCHAR(50), IN `p_Id_Categorie` INT)
BEGIN
    UPDATE Evenement
    SET Titre                 = p_Titre,
        Description           = p_Description,
        Adresse               = p_Adresse,
        DateDebut             = p_DateDebut,
        DateFin               = p_DateFin,
        Id_Ville              = p_Id_Ville,
        Id_CategorieEvenement = p_Id_Categorie
    WHERE Id_Evenement = p_Id_Evenement;
END$$

DROP PROCEDURE IF EXISTS `SupprimerEvenement`$$
CREATE
    DEFINER = `root`@`localhost` PROCEDURE `SupprimerEvenement`(IN `p_Id_Evenement` INT)
BEGIN
    DELETE
    FROM Achat
    WHERE Id_TicketEvenement IN (SELECT Id_TicketEvenement FROM TicketEvenement WHERE Id_Evenement = p_Id_Evenement);
    DELETE FROM NoteEvenement WHERE Id_Evenement = p_Id_Evenement;
    DELETE FROM CommentaireEvenement WHERE Id_Evenement = p_Id_Evenement;
    DELETE FROM ImageEvenement WHERE Id_Evenement = p_Id_Evenement;
    DELETE FROM TicketEvenement WHERE Id_Evenement = p_Id_Evenement;
    DELETE FROM Creer WHERE Id_Evenement = p_Id_Evenement;
    DELETE FROM Evenement WHERE Id_Evenement = p_Id_Evenement;
END$$

--
-- Fonctions
--
DROP FUNCTION IF EXISTS `EstCreateur`$$
CREATE
    DEFINER = `root`@`localhost` FUNCTION `EstCreateur`(`p_Id_Utilisateur` INT, `p_Id_Evenement` INT) RETURNS TINYINT(1)
    DETERMINISTIC
BEGIN
    DECLARE exist BOOLEAN;
    SELECT COUNT(*) > 0
    INTO exist
    FROM Creer
    WHERE Id_Utilisateur = p_Id_Utilisateur
      AND Id_Evenement = p_Id_Evenement;
    RETURN exist;
END$$

DROP FUNCTION IF EXISTS `UtilisateurExiste`$$
CREATE
    DEFINER = `root`@`localhost` FUNCTION `UtilisateurExiste`(`p_Id` INT) RETURNS TINYINT(1) DETERMINISTIC
BEGIN
    DECLARE exist BOOLEAN;
    SELECT COUNT(*) > 0 INTO exist FROM Utilisateur WHERE Id_Utilisateur = p_Id;
    RETURN exist;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `achat`
--

DROP TABLE IF EXISTS `achat`;
CREATE TABLE IF NOT EXISTS `achat`
(
    `Id_Achat`           int NOT NULL AUTO_INCREMENT,
    `Id_Utilisateur`     int NOT NULL,
    `Id_TicketEvenement` int NOT NULL,
    `DateAchat`          datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id_Achat`),
    KEY `Id_Utilisateur` (`Id_Utilisateur`),
    KEY `Id_TicketEvenement` (`Id_TicketEvenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

DROP TABLE IF EXISTS `administrateur`;
CREATE TABLE IF NOT EXISTS `administrateur`
(
    `Email_admin`  varchar(100) NOT NULL,
    `MotDePasse`   varchar(255) NOT NULL,
    `DateCreation` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Email_admin`),
    UNIQUE KEY `Email_admin` (`Email_admin`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorieevenement`
--

DROP TABLE IF EXISTS `categorieevenement`;
CREATE TABLE IF NOT EXISTS `categorieevenement`
(
    `Id_CategorieEvenement` int NOT NULL AUTO_INCREMENT,
    `Libelle`               varchar(50) DEFAULT NULL,
    PRIMARY KEY (`Id_CategorieEvenement`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 11
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `categorieevenement`
--

INSERT INTO `categorieevenement` (`Id_CategorieEvenement`, `Libelle`)
VALUES (1, 'Concert-spectacle'),
       (2, 'Sport'),
       (3, 'Dîner gala'),
       (4, 'Soirée party'),
       (5, 'Tourisme'),
       (6, 'Formation'),
       (7, 'Festival'),
       (8, 'Rencontre-privée'),
       (9, 'Rencontre groupée'),
       (10, 'Autre');

-- --------------------------------------------------------

--
-- Structure de la table `commentaireevenement`
--

DROP TABLE IF EXISTS `commentaireevenement`;
CREATE TABLE IF NOT EXISTS `commentaireevenement`
(
    `Id_Commentaire`  int NOT NULL AUTO_INCREMENT,
    `Id_Utilisateur`  int NOT NULL,
    `Id_Evenement`    int NOT NULL,
    `Contenu`         text,
    `DateCommentaire` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id_Commentaire`),
    KEY `Id_Utilisateur` (`Id_Utilisateur`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `creer`
--

DROP TABLE IF EXISTS `creer`;
CREATE TABLE IF NOT EXISTS `creer`
(
    `Id_Utilisateur` int NOT NULL,
    `Id_Evenement`   int NOT NULL,
    `DateCreation`   datetime DEFAULT NULL,
    PRIMARY KEY (`Id_Utilisateur`, `Id_Evenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `creer`
--

INSERT INTO `creer` (`Id_Utilisateur`, `Id_Evenement`, `DateCreation`)
VALUES (4, 14, '2025-07-10 21:32:59'),
       (4, 15, '2025-07-10 21:50:38'),
       (5, 16, '2025-07-10 22:46:47'),
       (7, 17, '2025-07-11 17:01:42'),
       (7, 18, '2025-07-11 22:14:07'),
       (7, 19, '2025-07-11 22:14:52'),
       (7, 20, '2025-07-11 22:20:19'),
       (7, 21, '2025-07-11 22:29:40'),
       (7, 22, '2025-07-12 20:14:27');

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

DROP TABLE IF EXISTS `evenement`;
CREATE TABLE IF NOT EXISTS `evenement`
(
    `Id_Evenement`          int NOT NULL AUTO_INCREMENT,
    `Description`           text,
    `Titre`                 varchar(100)                            DEFAULT NULL,
    `Adresse`               varchar(100)                            DEFAULT NULL,
    `DateDebut`             datetime                                DEFAULT NULL,
    `DateFin`               datetime                                DEFAULT NULL,
    `Id_Ville`              int NOT NULL,
    `Id_CategorieEvenement` int NOT NULL,
    `statut_approbation`    enum ('en_attente','approuve','rejete') DEFAULT 'en_attente',
    PRIMARY KEY (`Id_Evenement`),
    KEY `Id_Ville` (`Id_Ville`),
    KEY `Id_CategorieEvenement` (`Id_CategorieEvenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`Id_Evenement`, `Description`, `Titre`, `Adresse`, `DateDebut`, `DateFin`, `Id_Ville`,
                         `Id_CategorieEvenement`, `statut_approbation`)
VALUES (17, 'bbbb', 'bbb', 'bbbb', '2025-07-25 17:00:00', '2025-07-31 17:00:00', 1, 10, 'approuve'),
       (16, 'GGGG', 'FYYY', 'GGGG', '2025-07-10 04:52:00', '2025-07-31 22:46:00', 75, 6, 'approuve'),
       (15, 'fdd', 'MAro', 'fffff', '2025-07-10 21:50:00', '2025-07-23 21:50:00', 87, 7, 'rejete'),
       (13, 'ddsser', 'AJ', 'jjueeie', '2025-07-10 20:20:00', '2025-07-16 14:14:00', 28, 8, 'rejete'),
       (12, 'HUIIFFF', 'QSSDDFF', 'EDMOND', '2025-07-07 23:30:00', '2025-07-31 04:27:00', 78, 1, 'rejete'),
       (14, '22222', '1234455', 'dddd', '2025-07-10 21:26:00', '2025-07-31 21:26:00', 63, 3, 'rejete'),
       (18, 'dddd', 'ddd', 'dddd', '2025-07-24 22:13:00', '2025-07-30 22:13:00', 69, 7, 'rejete'),
       (19, 'ddddddddd', 'ddd', 'dddddddd', '2025-07-24 22:13:00', '2025-07-30 22:13:00', 69, 4, 'rejete'),
       (20, 'rrrr', 'eeee', 'rrr', '2025-07-11 22:19:00', '2025-07-28 22:20:00', 77, 7, 'rejete'),
       (21, 'ccc', 'ccc', 'cccc', '2025-07-24 22:33:00', '2025-07-30 03:29:00', 33, 8, 'rejete'),
       (22, 'ffff', 'ddddd', 'dddd', '2025-07-20 23:13:00', '2025-07-24 20:17:00', 82, 7, 'en_attente');

-- --------------------------------------------------------

--
-- Structure de la table `imageevenement`
--

DROP TABLE IF EXISTS `imageevenement`;
CREATE TABLE IF NOT EXISTS `imageevenement`
(
    `Id_ImageEvenement` int NOT NULL AUTO_INCREMENT,
    `Titre`             varchar(50)  DEFAULT NULL,
    `Description`       varchar(255) DEFAULT NULL,
    `Lien`              text,
    `Id_Evenement`      int NOT NULL,
    PRIMARY KEY (`Id_ImageEvenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 28
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `imageevenement`
--

INSERT INTO `imageevenement` (`Id_ImageEvenement`, `Titre`, `Description`, `Lien`, `Id_Evenement`)
VALUES (16, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF', 'image/686c58047ae20_kkkkcccc.png', 12),
       (17, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF', 'image/686c58047b625_tu vois.png', 12),
       (15, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF', 'image/686c58047a81d_erreur.png', 12),
       (14, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF',
        'image/686c58047a22e_evenement avec succes.png', 12),
       (13, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF',
        'image/686c580479bab_tickets evenement .png', 12),
       (18, 'Image pour AJ', 'Image associée à l\'événement : AJ', 'image/686fcb007a5ed_evenement avec succes.png', 13),
       (12, 'Image pour QSSDDFF', 'Image associée à l\'événement : QSSDDFF', 'image/686c58047828c_kjggff.png', 12),
       (19, 'Image pour 1234455', 'Image associée à l\'événement : 1234455',
        'image/6870318b17c4b_Capture_ecran_2025-07-09_222943.png', 14),
       (20, 'Image pour MAro', 'Image associée à l\'événement : MAro',
        'image/687035aeb9772_Capture_ecran_2025-07-09_224802.png', 15),
       (21, 'Image pour FYYY', 'Image associée à l\'événement : FYYY', 'image/687042d783a14_la table.png', 16),
       (22, 'Image pour bbb', 'Image associée à l\'événement : bbb',
        'image/6871437673ad6_base.php499944664_1256749356461317_5345604814470262606_n.jpg', 17),
       (23, 'Image pour ddd', 'Image associée à l\'événement : ddd',
        'image/68718caf25f45_base.php499944664_1256749356461317_5345604814470262606_n.jpg', 18),
       (24, 'Image pour ddd', 'Image associée à l\'événement : ddd',
        'image/68718cdcc4e0a_base.php499944664_1256749356461317_5345604814470262606_n.jpg', 19),
       (25, 'Image pour eeee', 'Illustration de l\'événement : eeee',
        'image/68718e22e9a4a_base.php488186791_1630423617901880_8641409876945198478_n.jpg', 20),
       (26, 'Image pour ccc', 'Illustration de l\'événement : ccc',
        'image/68719054746d6_base.php488186791_1630423617901880_8641409876945198478_n.jpg', 21),
       (27, 'Image pour ddddd', 'Illustration de l\'événement : ddddd',
        'image/6872c22349ebb_base.php488186791_1630423617901880_8641409876945198478_n.jpg', 22);

-- --------------------------------------------------------

--
-- Structure de la table `noteevenement`
--

DROP TABLE IF EXISTS `noteevenement`;
CREATE TABLE IF NOT EXISTS `noteevenement`
(
    `Id_Note`        int NOT NULL AUTO_INCREMENT,
    `Id_Utilisateur` int NOT NULL,
    `Id_Evenement`   int NOT NULL,
    `Note`           int      DEFAULT NULL,
    `DateNote`       datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id_Note`),
    UNIQUE KEY `Id_Utilisateur` (`Id_Utilisateur`, `Id_Evenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticketevenement`
--

DROP TABLE IF EXISTS `ticketevenement`;
CREATE TABLE IF NOT EXISTS `ticketevenement`
(
    `Id_TicketEvenement` int NOT NULL AUTO_INCREMENT,
    `Titre`              varchar(50)    DEFAULT NULL,
    `Description`        varchar(255)   DEFAULT NULL,
    `Prix`               decimal(10, 2) DEFAULT NULL,
    `NombreDisponible`   int            DEFAULT '0',
    `Id_Evenement`       int NOT NULL,
    PRIMARY KEY (`Id_TicketEvenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `ticketevenement`
--

INSERT INTO `ticketevenement` (`Id_TicketEvenement`, `Titre`, `Description`, `Prix`, `NombreDisponible`, `Id_Evenement`)
VALUES (3, 'AJ', 'DDD', 1233.00, 444, 10),
       (4, '1234455', 'dddd', 123444.00, 9, 11),
       (5, 'concert de dena', 'hhhh', 100000.00, 199, 12),
       (6, 'maloi', 'ggg', 233344.00, 45577888, 15),
       (7, '1234455', 'GGG', 2233344.00, 5555, 16),
       (8, 'GUUUII', '2333R', 3000.00, 10000, 17);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur`
(
    `Id_Utilisateur`   int                     NOT NULL AUTO_INCREMENT,
    `Nom`              varchar(50)                      DEFAULT NULL,
    `Prenom`           varchar(50)                      DEFAULT NULL,
    `DateNaissance`    date                             DEFAULT NULL,
    `Photo`            text,
    `Telephone`        varchar(20)                      DEFAULT NULL,
    `Email`            varchar(100)                     DEFAULT NULL,
    `MotDePasse`       varchar(255)                     DEFAULT NULL,
    `Type_utilisateur` enum ('client','admin') NOT NULL DEFAULT 'client',
    PRIMARY KEY (`Id_Utilisateur`),
    UNIQUE KEY `Email` (`Email`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 10
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`Id_Utilisateur`, `Nom`, `Prenom`, `DateNaissance`, `Photo`, `Telephone`, `Email`,
                           `MotDePasse`, `Type_utilisateur`)
VALUES (9, 'LOIJ', 'HHH', '2025-07-16', 'uploads/4759e9631b18bc83b2c6dcfbac9ca694.jpg', '2334445', 'lundi@gmail.com',
        '$2y$10$uL6aMs0QW/O07q8nnTPl2eBYcxvbq5hMOn0ilUCp47lpKM5CTxsb.', 'client'),
       (7, 'admin', 'istrateur', '2025-07-11', 'uploads/photos_profil/photo_68711ffaa9ac57.88819822.png', '0564719487',
        'administrateur@gmail.com', '123456789', 'client'),
       (8, 'Nguessan', 'Edmond', '2025-07-30', 'uploads/fd91862b56ef6b77b24789f5cd69f9a9.jpg', '0564719487',
        'edmonnguessan01@gmail.comddd', '$2y$10$tShrfjazh/YwHjV4qP93Tuml/YaFbAUs0OzdTRxZb1TP..4YCsBfy', 'client');

-- --------------------------------------------------------

--
-- Structure de la table `ville`
--

DROP TABLE IF EXISTS `ville`;
CREATE TABLE IF NOT EXISTS `ville`
(
    `Id_Ville` int NOT NULL AUTO_INCREMENT,
    `Libelle`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
    PRIMARY KEY (`Id_Ville`)
) ENGINE = MyISAM
  AUTO_INCREMENT = 89
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `ville`
--

INSERT INTO `ville` (`Id_Ville`, `Libelle`)
VALUES (1, 'Abidjan'),
       (5, 'Bouaké'),
       (8, 'Korhogo'),
       (9, 'Daloa'),
       (11, 'San-Pédro'),
       (14, 'Yamoussoukro'),
       (16, 'Divo'),
       (17, 'Gagnoa'),
       (18, 'Soubré'),
       (19, 'Man'),
       (20, 'Duékoué'),
       (22, 'Bouaflé'),
       (24, 'Guiglo'),
       (25, 'Lakota'),
       (26, 'Abengourou'),
       (27, 'Ferkéssédougou'),
       (28, 'Adzopé'),
       (29, 'Méagui'),
       (30, 'Bondoukou'),
       (31, 'Dabou'),
       (32, 'Sinfra'),
       (33, 'Agboville'),
       (34, 'Vavoua'),
       (35, 'Danané'),
       (36, 'Grand-Bassam'),
       (37, 'Oumé'),
       (38, 'Issia'),
       (39, 'Bonoua'),
       (40, 'Bonon'),
       (41, 'Séguéla'),
       (42, 'Daoukro'),
       (43, 'Aboisso'),
       (44, 'Buyo'),
       (45, 'Saïoua'),
       (46, 'Agnibilékrou'),
       (47, 'NDouci'),
       (48, 'Doba'),
       (49, 'Bouna'),
       (50, 'Boundiali'),
       (51, 'Tengréla'),
       (52, 'Katiola'),
       (53, 'Songon'),
       (54, 'Toumodi'),
       (55, 'Sassandra'),
       (56, 'Odienné'),
       (57, 'Ouangolodougou'),
       (58, 'Tiassalé'),
       (59, 'Grand-Lahou'),
       (60, 'Mankono'),
       (61, 'Dimbokro'),
       (62, 'Tabou'),
       (63, 'Bocanda'),
       (64, 'Taabo'),
       (65, 'Kouto'),
       (66, 'Kani'),
       (67, 'Jacqueville'),
       (68, 'Zuénoula'),
       (69, 'Akoupé'),
       (70, 'Doropo'),
       (71, 'Fresco'),
       (72, 'Dahiri'),
       (73, 'Lolobo'),
       (74, 'Kong'),
       (75, 'Bongouanou'),
       (76, 'Touba'),
       (77, 'Béoumi'),
       (78, 'Biankouma'),
       (79, 'Toulépleu'),
       (80, 'Bangolo'),
       (81, 'Tiébissou'),
       (82, 'Adiaké'),
       (83, 'Sakassou'),
       (84, 'Botro'),
       (85, 'Dabakala'),
       (86, 'Tafire'),
       (87, 'Attiegouakro'),
       (88, 'Minignan');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
