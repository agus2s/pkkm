-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.5.28-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for pkkm
CREATE DATABASE IF NOT EXISTS `pkkm` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci */;
USE `pkkm`;

-- Dumping structure for table pkkm.indikator_kerja
CREATE TABLE IF NOT EXISTS `indikator_kerja` (
  `kode` varchar(10) NOT NULL,
  `unsur_tugas_utama` varchar(10) NOT NULL,
  `judul` varchar(50) NOT NULL,
  `data_kinerja` varchar(50) NOT NULL,
  `hasil_kinerja` int(11) NOT NULL DEFAULT 0,
  `bukti_otentik` text NOT NULL,
  PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table pkkm.indikator_kerja: ~0 rows (approximately)

-- Dumping structure for table pkkm.tugas_utama
CREATE TABLE IF NOT EXISTS `tugas_utama` (
  `kode` varchar(10) NOT NULL,
  `judul` varchar(50) NOT NULL,
  PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table pkkm.tugas_utama: ~0 rows (approximately)

-- Dumping structure for table pkkm.unsur_tugas_utama
CREATE TABLE IF NOT EXISTS `unsur_tugas_utama` (
  `kode` varchar(10) NOT NULL,
  `tugas_utama` varchar(10) NOT NULL,
  `judul` varchar(50) NOT NULL,
  PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table pkkm.unsur_tugas_utama: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
