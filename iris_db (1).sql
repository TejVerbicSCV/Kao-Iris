-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gostitelj: 127.0.0.1
-- Čas nastanka: 22. maj 2025 ob 14.20
-- Različica strežnika: 10.4.32-MariaDB
-- Različica PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Zbirka podatkov: `iris_db`
--

-- --------------------------------------------------------

--
-- Struktura tabele `bolniske`
--

CREATE TABLE `bolniske` (
  `id` int(11) NOT NULL,
  `uporabnik_id` int(11) NOT NULL,
  `zdravnik_id` int(11) NOT NULL,
  `razlog` text NOT NULL,
  `datum_zacetka` date NOT NULL,
  `datum_konca` date NOT NULL,
  `opombe` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `bolniske`
--

INSERT INTO `bolniske` (`id`, `uporabnik_id`, `zdravnik_id`, `razlog`, `datum_zacetka`, `datum_konca`, `opombe`, `created_at`) VALUES
(1, 1, 3, 'Zlomljena roka', '2025-05-21', '2025-05-23', NULL, '2025-05-22 11:02:25');

-- --------------------------------------------------------

--
-- Struktura tabele `napotnice`
--

CREATE TABLE `napotnice` (
  `id` int(11) NOT NULL,
  `uporabnik_id` int(11) NOT NULL,
  `zdravnik_id` int(11) NOT NULL,
  `specializacija` varchar(100) NOT NULL,
  `ustanova` varchar(100) NOT NULL,
  `zadeva` varchar(200) NOT NULL,
  `razlog` text NOT NULL,
  `nujnost` enum('nujno','obstojno','planirano') NOT NULL,
  `datum_izdaje` date NOT NULL,
  `datum_pregleda` date NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `opombe` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `napotnice`
--

INSERT INTO `napotnice` (`id`, `uporabnik_id`, `zdravnik_id`, `specializacija`, `ustanova`, `zadeva`, `razlog`, `nujnost`, `datum_izdaje`, `datum_pregleda`, `status`, `opombe`, `created_at`) VALUES
(1, 1, 3, 'je ja', 'zd vel', 'zadeva', 'eveve', 'nujno', '2025-05-09', '2025-05-09', '', NULL, '2025-05-09 08:53:21');

-- --------------------------------------------------------

--
-- Struktura tabele `pogovori`
--

CREATE TABLE `pogovori` (
  `id` int(11) NOT NULL,
  `posiljatelj_id` int(11) DEFAULT NULL,
  `uporabnik_id` int(11) NOT NULL,
  `zdravnik_id` int(11) NOT NULL,
  `zadeva` varchar(200) NOT NULL,
  `sporocilo` text NOT NULL,
  `datum_poslano` datetime NOT NULL,
  `prebrano` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `pogovori`
--

INSERT INTO `pogovori` (`id`, `posiljatelj_id`, `uporabnik_id`, `zdravnik_id`, `zadeva`, `sporocilo`, `datum_poslano`, `prebrano`, `created_at`) VALUES
(6, NULL, 1, 3, 'c', 'c<xv<v<<', '2025-05-22 13:38:19', 1, '2025-05-22 11:38:19'),
(7, NULL, 1, 3, 'c', 'hej', '2025-05-22 13:52:04', 1, '2025-05-22 11:52:04');

-- --------------------------------------------------------

--
-- Struktura tabele `recepti`
--

CREATE TABLE `recepti` (
  `id` int(11) NOT NULL,
  `uporabnik_id` int(11) NOT NULL,
  `zdravnik_id` int(11) NOT NULL,
  `zdravilo` varchar(100) NOT NULL,
  `doza` varchar(50) NOT NULL,
  `navodila` text DEFAULT NULL,
  `datum_izdaje` date NOT NULL,
  `datum_poteka` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `recepti`
--

INSERT INTO `recepti` (`id`, `uporabnik_id`, `zdravnik_id`, `zdravilo`, `doza`, `navodila`, `datum_izdaje`, `datum_poteka`, `created_at`) VALUES
(1, 1, 3, 'Neki', '1ml', 'vzami', '2025-05-22', '2025-05-23', '2025-05-22 11:03:02');

-- --------------------------------------------------------

--
-- Struktura tabele `uporabniki`
--

CREATE TABLE `uporabniki` (
  `id` int(11) NOT NULL,
  `ime` varchar(50) NOT NULL,
  `priimek` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `naslov` text DEFAULT NULL,
  `geslo` varchar(255) NOT NULL,
  `zdravnik_id` int(11) DEFAULT NULL,
  `vloga_id` int(11) NOT NULL,
  `specializacija` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `uporabniki`
--

INSERT INTO `uporabniki` (`id`, `ime`, `priimek`, `email`, `telefon`, `naslov`, `geslo`, `zdravnik_id`, `vloga_id`, `specializacija`, `created_at`) VALUES
(1, 'Tej', 'Verbič', 'tej.verbic@gmail.com', '1234567889', 'Šercerjeva cesta 20', '$2y$10$kCGz3iS9S20yVrT4HEDQneSBmL3kTJZlShm1w9p0u6noGKD97bwP6', 3, 3, NULL, '2025-05-09 08:50:50'),
(2, 'ad', ' ', 'admin@iris.si', '', '', '$2y$10$HZ8UTJQvgbjfaP/kgzzry.y28uRzbuk00LnI2S7DkJrsX9jLIhUXi', NULL, 1, NULL, '2025-05-09 08:51:05'),
(3, 'zdravnik', ' ', 'zdravnik@iris.si', '', '', '$2y$10$MXzeNW/UECk12Shy1pbetegNsSp6gme7IMSKZ7GF7YL2mtQUfacce', NULL, 2, NULL, '2025-05-09 08:51:18');

-- --------------------------------------------------------

--
-- Struktura tabele `vloge`
--

CREATE TABLE `vloge` (
  `id` int(11) NOT NULL,
  `naziv` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Odloži podatke za tabelo `vloge`
--

INSERT INTO `vloge` (`id`, `naziv`, `created_at`) VALUES
(1, 'admin', '2025-05-09 08:50:34'),
(2, 'zdravnik', '2025-05-09 08:50:34'),
(3, 'pacient', '2025-05-09 08:50:34');

--
-- Indeksi zavrženih tabel
--

--
-- Indeksi tabele `bolniske`
--
ALTER TABLE `bolniske`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uporabnik_id` (`uporabnik_id`),
  ADD KEY `zdravnik_id` (`zdravnik_id`);

--
-- Indeksi tabele `napotnice`
--
ALTER TABLE `napotnice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uporabnik_id` (`uporabnik_id`),
  ADD KEY `zdravnik_id` (`zdravnik_id`);

--
-- Indeksi tabele `pogovori`
--
ALTER TABLE `pogovori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uporabnik_id` (`uporabnik_id`),
  ADD KEY `zdravnik_id` (`zdravnik_id`),
  ADD KEY `posiljatelj_id` (`posiljatelj_id`);

--
-- Indeksi tabele `recepti`
--
ALTER TABLE `recepti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uporabnik_id` (`uporabnik_id`),
  ADD KEY `zdravnik_id` (`zdravnik_id`);

--
-- Indeksi tabele `uporabniki`
--
ALTER TABLE `uporabniki`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `vloga_id` (`vloga_id`),
  ADD KEY `zdravnik_id` (`zdravnik_id`);

--
-- Indeksi tabele `vloge`
--
ALTER TABLE `vloge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `naziv` (`naziv`);

--
-- AUTO_INCREMENT zavrženih tabel
--

--
-- AUTO_INCREMENT tabele `bolniske`
--
ALTER TABLE `bolniske`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT tabele `napotnice`
--
ALTER TABLE `napotnice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT tabele `pogovori`
--
ALTER TABLE `pogovori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT tabele `recepti`
--
ALTER TABLE `recepti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT tabele `uporabniki`
--
ALTER TABLE `uporabniki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT tabele `vloge`
--
ALTER TABLE `vloge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Omejitve tabel za povzetek stanja
--

--
-- Omejitve za tabelo `bolniske`
--
ALTER TABLE `bolniske`
  ADD CONSTRAINT `bolniske_ibfk_1` FOREIGN KEY (`uporabnik_id`) REFERENCES `uporabniki` (`id`),
  ADD CONSTRAINT `bolniske_ibfk_2` FOREIGN KEY (`zdravnik_id`) REFERENCES `uporabniki` (`id`);

--
-- Omejitve za tabelo `napotnice`
--
ALTER TABLE `napotnice`
  ADD CONSTRAINT `napotnice_ibfk_1` FOREIGN KEY (`uporabnik_id`) REFERENCES `uporabniki` (`id`),
  ADD CONSTRAINT `napotnice_ibfk_2` FOREIGN KEY (`zdravnik_id`) REFERENCES `uporabniki` (`id`);

--
-- Omejitve za tabelo `pogovori`
--
ALTER TABLE `pogovori`
  ADD CONSTRAINT `pogovori_ibfk_1` FOREIGN KEY (`uporabnik_id`) REFERENCES `uporabniki` (`id`),
  ADD CONSTRAINT `pogovori_ibfk_2` FOREIGN KEY (`zdravnik_id`) REFERENCES `uporabniki` (`id`),
  ADD CONSTRAINT `pogovori_ibfk_3` FOREIGN KEY (`posiljatelj_id`) REFERENCES `uporabniki` (`id`);

--
-- Omejitve za tabelo `recepti`
--
ALTER TABLE `recepti`
  ADD CONSTRAINT `recepti_ibfk_1` FOREIGN KEY (`uporabnik_id`) REFERENCES `uporabniki` (`id`),
  ADD CONSTRAINT `recepti_ibfk_2` FOREIGN KEY (`zdravnik_id`) REFERENCES `uporabniki` (`id`);

--
-- Omejitve za tabelo `uporabniki`
--
ALTER TABLE `uporabniki`
  ADD CONSTRAINT `uporabniki_ibfk_1` FOREIGN KEY (`vloga_id`) REFERENCES `vloge` (`id`),
  ADD CONSTRAINT `uporabniki_ibfk_2` FOREIGN KEY (`zdravnik_id`) REFERENCES `uporabniki` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
