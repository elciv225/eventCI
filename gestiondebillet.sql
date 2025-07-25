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
    `Salle`                 varchar(100)                            DEFAULT NULL,
    `DateDebut`             datetime                                DEFAULT NULL,
    `DateFin`               datetime                                DEFAULT NULL,
    `Id_CategorieEvenement` int NOT NULL,
    `statut_approbation`    enum ('en_attente','approuve','rejete') DEFAULT 'en_attente',
    PRIMARY KEY (`Id_Evenement`),
    KEY `Id_CategorieEvenement` (`Id_CategorieEvenement`),
    KEY `Id_Evenement` (`Id_Evenement`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

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

-- --------------------------------------------------------

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

-- --------------------------------------------------------

--
-- Déchargement des données de la table `ville`
--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
