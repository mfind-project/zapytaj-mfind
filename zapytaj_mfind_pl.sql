-- phpMyAdmin SQL Dump
-- version 4.1.9
-- http://www.phpmyadmin.net
--
-- Host: walkingptest.mysql.db
-- Generation Time: 31 Maj 2015, 03:25
-- Server version: 5.5.43-0+deb7u1-log
-- PHP Version: 5.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `walkingptest`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `emails`
--

CREATE TABLE IF NOT EXISTS `emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

--
-- Zrzut danych tabeli `emails`
--

INSERT INTO `emails` (`id`, `email`) VALUES
(1, 'grzegorz.ptasinski@mfind.pl'),
(2, 'grzegorz.ptasinski@mfind.pl'),
(3, 'grzegorz.ptasinski@mfind.pl'),
(4, 'g.m.ptasinski@gmail.com'),
(5, 'g.m.ptasinski@gmail.com'),
(6, 'wojciech.martynski@mfind.pl'),
(7, 'grzegorz.ptasinski@mfind.pl'),
(8, 'grzegorz.ptasinski@mfind.pl'),
(9, 'wojciech.martynski@mfind.pl'),
(10, 'wojciech.martynski@mfind.pl'),
(11, 'testzapytaj@zapytaj.pl'),
(12, 'hgcugd@wp.pl'),
(13, 'bartek.roszkowski@gmail.com'),
(14, 'wojciech.martynski@mfind.pl'),
(15, 'grzegorz.ptasinski@mfind.pl'),
(16, 'grzegorz.ptasinski@mfind.pl'),
(17, 'grzegorz.ptasinski@mfind.pl'),
(18, 'grzegorz.ptasinski@mfind.pl'),
(19, 'grzegorz.ptasinski@mfind.pl'),
(20, 'grzegorz.ptasinski@mfind.pl'),
(21, 'grzegorz.ptasinski@mfind.pl'),
(22, 'grzegorz.ptasinski@mfind.pl'),
(23, 'wojciech.martynski@mfind.pl'),
(24, 'szefwoj7@wp.pl');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
