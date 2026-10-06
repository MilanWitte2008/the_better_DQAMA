-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3310
-- Gegenereerd op: 06 okt 2026 om 09:42
-- Serverversie: 10.4.32-MariaDB
-- PHP-versie: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `leerlingbegeleiding`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `begeleidingsnotitie`
--

CREATE TABLE `begeleidingsnotitie` (
  `id_notitie` int(11) NOT NULL,
  `inhoud` text DEFAULT NULL,
  `datum` datetime DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `idcoach` int(11) DEFAULT NULL,
  `idstudent` int(11) DEFAULT NULL,
  `student_leeruitkomsten_id_leeruitkomsten` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `begeleidingsnotitie`
--

INSERT INTO `begeleidingsnotitie` (`id_notitie`, `inhoud`, `datum`, `type`, `idcoach`, `idstudent`, `student_leeruitkomsten_id_leeruitkomsten`) VALUES
(101, 'Kennismaking en leerdoelen besproken', '2026-10-02 11:43:06', 'gesprek', 101, 101, 101),
(102, 'Voortgang programmeeropdracht besproken', '2026-10-02 11:43:06', 'gesprek', 101, 101, 102),
(103, 'Vooruitgang bij samenwerken besproken', '2026-10-02 11:43:06', 'gesprek', 101, 102, 103);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `bericht`
--

CREATE TABLE `bericht` (
  `idbericht` int(11) NOT NULL,
  `naam_verzender` varchar(255) DEFAULT NULL,
  `inhoud` text DEFAULT NULL,
  `datum` datetime DEFAULT NULL,
  `gelezen` tinyint(4) DEFAULT NULL,
  `student_idstudent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `bericht`
--

INSERT INTO `bericht` (`idbericht`, `naam_verzender`, `inhoud`, `datum`, `gelezen`, `student_idstudent`) VALUES
(1, 'Emma Jansen', 'Vergeet niet je reflectie bij te werken.', '2026-10-02 11:46:19', 0, 101),
(2, 'Liam de Vries', 'Je leeruitkomst samenwerken is bijgewerkt.', '2026-10-02 11:46:19', 1, 102),
(3, 'Noah Bakker', 'Plan een moment in om je voortgang te bespreken.', '2026-10-02 11:46:19', 0, 103);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `coach`
--

CREATE TABLE `coach` (
  `idcoach` int(11) NOT NULL,
  `functie` varchar(50) DEFAULT NULL,
  `telefoonnummer` varchar(30) DEFAULT NULL,
  `lid_idlid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `coach`
--

INSERT INTO `coach` (`idcoach`, `functie`, `telefoonnummer`, `lid_idlid`) VALUES
(101, 'Studieloopbaancoach', NULL, 104);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `fase`
--

CREATE TABLE `fase` (
  `idfase` int(11) NOT NULL,
  `naam` varchar(50) DEFAULT NULL,
  `beschrijving` text DEFAULT NULL,
  `volgorde` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `fase`
--

INSERT INTO `fase` (`idfase`, `naam`, `beschrijving`, `volgorde`) VALUES
(1, 'Startfase', 'Begin van het leertraject', 1),
(2, 'Ontwikkelfase', 'Werken aan leerdoelen', 2),
(3, 'Eindfase', 'Evaluatie van het leertraject', 3),
(101, 'Beginfase', 'Kennismaking en introductie', 1),
(102, 'Ontwikkelfase', 'Werken aan leerdoelen', 2),
(103, 'Eindfase', 'Afronding en evaluatie', 3);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `gesprekken`
--

CREATE TABLE `gesprekken` (
  `idgesprek` int(11) NOT NULL,
  `datum` datetime DEFAULT NULL,
  `onderwerp` varchar(255) DEFAULT NULL,
  `inhoud` text DEFAULT NULL,
  `afspraken` text DEFAULT NULL,
  `student_idstudent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `leerdoelen`
--

CREATE TABLE `leerdoelen` (
  `idleerdoel` int(11) NOT NULL,
  `titel` varchar(255) DEFAULT NULL,
  `omschrijving` text DEFAULT NULL,
  `status` enum('Te doen','Bezig','Behaald') DEFAULT 'Te doen',
  `startdatum` date DEFAULT NULL,
  `einddatum` date DEFAULT NULL,
  `student_idstudent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `leeruitkomsten`
--

CREATE TABLE `leeruitkomsten` (
  `leeruitkomsten_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `titel` varchar(255) DEFAULT NULL,
  `omschrijving` text DEFAULT NULL,
  `categorie` varchar(50) DEFAULT NULL,
  `fase_idfase` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `leeruitkomsten`
--

INSERT INTO `leeruitkomsten` (`leeruitkomsten_id`, `code`, `titel`, `omschrijving`, `categorie`, `fase_idfase`) VALUES
(101, 'LO101', 'Samenwerken', 'Kan goed samenwerken in een team', 'vaardigheid', 101),
(102, 'LO102', 'Programmeren', 'Kan eenvoudige programmaonderdelen ontwikkelen', 'vaardigheid', 102),
(103, 'LO103', 'Reflecteren', 'Kan reflecteren op de eigen ontwikkeling', 'houding', 102);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `lid`
--

CREATE TABLE `lid` (
  `idlid` int(11) NOT NULL,
  `voornaam` varchar(50) DEFAULT NULL,
  `achternaam` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `wachtwoord` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `aangemaakt_datum` datetime DEFAULT NULL,
  `rol_idrol` int(11) DEFAULT NULL,
  `school_idschool` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `lid`
--

INSERT INTO `lid` (`idlid`, `voornaam`, `achternaam`, `email`, `wachtwoord`, `status`, `aangemaakt_datum`, `rol_idrol`, `school_idschool`) VALUES
(101, 'Emma', 'Jansen', 'emma@example.com', 'test123', 'actief', '2026-10-02 11:43:06', 101, 101),
(102, 'Liam', 'de Vries', 'liam@example.com', 'test123', 'actief', '2026-10-02 11:43:06', 101, 101),
(103, 'Noah', 'Bakker', 'noah@example.com', 'test123', 'actief', '2026-10-02 11:43:06', 101, 102),
(104, 'Sophie', 'Visser', 'sophie@example.com', 'test123', 'actief', '2026-10-02 11:43:06', 102, 101);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `reflectie`
--

CREATE TABLE `reflectie` (
  `idreflectie` int(11) NOT NULL,
  `inhoud` text DEFAULT NULL,
  `datum` date DEFAULT NULL,
  `student_leeruitkomsten_id_leeruitkomsten` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `reflectie`
--

INSERT INTO `reflectie` (`idreflectie`, `inhoud`, `datum`, `student_leeruitkomsten_id_leeruitkomsten`) VALUES
(101, 'Ik heb geleerd beter samen te werken.', '2026-10-02', 101),
(102, 'Ik wil mijn programmeervaardigheden verbeteren.', '2026-10-02', 102),
(103, 'Ik kan mijn voortgang beter beoordelen.', '2026-10-02', 104);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `rol`
--

CREATE TABLE `rol` (
  `idrol` int(11) NOT NULL,
  `naam` varchar(50) DEFAULT NULL,
  `beschrijving` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `rol`
--

INSERT INTO `rol` (`idrol`, `naam`, `beschrijving`) VALUES
(1, 'Admin', 'Beheerder van de website'),
(2, 'Student', 'Leerling'),
(3, 'Coach', 'Begeleider'),
(101, 'Student', 'Volgt een opleiding'),
(102, 'Coach', 'Begeleidt studenten'),
(103, 'Admin', 'Beheert de applicatie');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `school`
--

CREATE TABLE `school` (
  `idschool` int(11) NOT NULL,
  `naam` varchar(45) DEFAULT NULL,
  `adres` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `school`
--

INSERT INTO `school` (`idschool`, `naam`, `adres`, `email`) VALUES
(1, 'Testschool', 'Schoolstraat 10', 'school@test.nl'),
(101, 'Campus Noord', 'Stationsstraat 10', 'campusnoord@example.com'),
(102, 'College Zuid', 'Schoollaan 25', 'collegezuid@example.com');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `student`
--

CREATE TABLE `student` (
  `idstudent` int(11) NOT NULL,
  `studentnummer` varchar(20) DEFAULT NULL,
  `klas` varchar(20) DEFAULT NULL,
  `opleiding` varchar(50) DEFAULT NULL,
  `startdatum` date DEFAULT NULL,
  `einddatum` date DEFAULT NULL,
  `lid_idlid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `student`
--

INSERT INTO `student` (`idstudent`, `studentnummer`, `klas`, `opleiding`, `startdatum`, `einddatum`, `lid_idlid`) VALUES
(101, 'STU2026101', 'ICT2A', 'Software Development', '2026-09-01', '2028-07-01', 101),
(102, 'STU2026102', 'ICT2A', 'Software Development', '2026-09-01', '2028-07-01', 102),
(103, 'STU2026103', 'ICT2B', 'ICT Beheer', '2026-09-01', '2028-07-01', 103);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `student_coach`
--

CREATE TABLE `student_coach` (
  `id` int(11) NOT NULL,
  `startdatum` date DEFAULT NULL,
  `einddatum` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `student_idstudent` int(11) DEFAULT NULL,
  `coach_idcoach` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `student_coach`
--

INSERT INTO `student_coach` (`id`, `startdatum`, `einddatum`, `status`, `student_idstudent`, `coach_idcoach`) VALUES
(101, '2026-09-01', NULL, 'actief', 101, 101),
(102, '2026-09-01', NULL, 'actief', 102, 101),
(103, '2026-09-01', NULL, 'actief', 103, 101);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `student_leeruitkomsten`
--

CREATE TABLE `student_leeruitkomsten` (
  `id_leeruitkomsten` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `vooruitgang` int(11) DEFAULT NULL,
  `laatste_update` datetime DEFAULT NULL,
  `opmerking` text DEFAULT NULL,
  `student_idstudent` int(11) DEFAULT NULL,
  `leeruitkomsten_leeruitkomsten_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Gegevens worden geëxporteerd voor tabel `student_leeruitkomsten`
--

INSERT INTO `student_leeruitkomsten` (`id_leeruitkomsten`, `status`, `vooruitgang`, `laatste_update`, `opmerking`, `student_idstudent`, `leeruitkomsten_leeruitkomsten_id`) VALUES
(101, 'bezig', NULL, '2026-10-02 11:43:06', NULL, 101, 101),
(102, 'bezig', NULL, '2026-10-02 11:43:06', NULL, 101, 102),
(103, 'behaald', NULL, '2026-10-02 11:43:06', NULL, 102, 101),
(104, 'bezig', NULL, '2026-10-02 11:43:06', NULL, 103, 103);

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `begeleidingsnotitie`
--
ALTER TABLE `begeleidingsnotitie`
  ADD PRIMARY KEY (`id_notitie`),
  ADD KEY `idcoach` (`idcoach`),
  ADD KEY `idstudent` (`idstudent`),
  ADD KEY `student_leeruitkomsten_id_leeruitkomsten` (`student_leeruitkomsten_id_leeruitkomsten`);

--
-- Indexen voor tabel `bericht`
--
ALTER TABLE `bericht`
  ADD PRIMARY KEY (`idbericht`),
  ADD KEY `student_idstudent` (`student_idstudent`);

--
-- Indexen voor tabel `coach`
--
ALTER TABLE `coach`
  ADD PRIMARY KEY (`idcoach`),
  ADD UNIQUE KEY `lid_idlid_2` (`lid_idlid`),
  ADD KEY `lid_idlid` (`lid_idlid`);

--
-- Indexen voor tabel `fase`
--
ALTER TABLE `fase`
  ADD PRIMARY KEY (`idfase`);

--
-- Indexen voor tabel `gesprekken`
--
ALTER TABLE `gesprekken`
  ADD PRIMARY KEY (`idgesprek`),
  ADD KEY `student_idstudent` (`student_idstudent`);

--
-- Indexen voor tabel `leerdoelen`
--
ALTER TABLE `leerdoelen`
  ADD PRIMARY KEY (`idleerdoel`),
  ADD KEY `student_idstudent` (`student_idstudent`);

--
-- Indexen voor tabel `leeruitkomsten`
--
ALTER TABLE `leeruitkomsten`
  ADD PRIMARY KEY (`leeruitkomsten_id`),
  ADD KEY `fase_idfase` (`fase_idfase`);

--
-- Indexen voor tabel `lid`
--
ALTER TABLE `lid`
  ADD PRIMARY KEY (`idlid`),
  ADD KEY `rol_idrol` (`rol_idrol`),
  ADD KEY `school_idschool` (`school_idschool`);

--
-- Indexen voor tabel `reflectie`
--
ALTER TABLE `reflectie`
  ADD PRIMARY KEY (`idreflectie`),
  ADD KEY `student_leeruitkomsten_id_leeruitkomsten` (`student_leeruitkomsten_id_leeruitkomsten`);

--
-- Indexen voor tabel `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`idrol`);

--
-- Indexen voor tabel `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`idschool`);

--
-- Indexen voor tabel `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`idstudent`),
  ADD UNIQUE KEY `studentnummer` (`studentnummer`),
  ADD UNIQUE KEY `uniek_student_lid` (`lid_idlid`),
  ADD KEY `lid_idlid` (`lid_idlid`);

--
-- Indexen voor tabel `student_coach`
--
ALTER TABLE `student_coach`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_idstudent` (`student_idstudent`),
  ADD KEY `coach_idcoach` (`coach_idcoach`);

--
-- Indexen voor tabel `student_leeruitkomsten`
--
ALTER TABLE `student_leeruitkomsten`
  ADD PRIMARY KEY (`id_leeruitkomsten`),
  ADD KEY `student_idstudent` (`student_idstudent`),
  ADD KEY `leeruitkomsten_leeruitkomsten_id` (`leeruitkomsten_leeruitkomsten_id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `begeleidingsnotitie`
--
ALTER TABLE `begeleidingsnotitie`
  MODIFY `id_notitie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `bericht`
--
ALTER TABLE `bericht`
  MODIFY `idbericht` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT voor een tabel `coach`
--
ALTER TABLE `coach`
  MODIFY `idcoach` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT voor een tabel `fase`
--
ALTER TABLE `fase`
  MODIFY `idfase` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `gesprekken`
--
ALTER TABLE `gesprekken`
  MODIFY `idgesprek` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `leerdoelen`
--
ALTER TABLE `leerdoelen`
  MODIFY `idleerdoel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `leeruitkomsten`
--
ALTER TABLE `leeruitkomsten`
  MODIFY `leeruitkomsten_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `lid`
--
ALTER TABLE `lid`
  MODIFY `idlid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT voor een tabel `reflectie`
--
ALTER TABLE `reflectie`
  MODIFY `idreflectie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `school`
--
ALTER TABLE `school`
  MODIFY `idschool` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT voor een tabel `student`
--
ALTER TABLE `student`
  MODIFY `idstudent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `student_coach`
--
ALTER TABLE `student_coach`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT voor een tabel `student_leeruitkomsten`
--
ALTER TABLE `student_leeruitkomsten`
  MODIFY `id_leeruitkomsten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `begeleidingsnotitie`
--
ALTER TABLE `begeleidingsnotitie`
  ADD CONSTRAINT `begeleidingsnotitie_ibfk_1` FOREIGN KEY (`idcoach`) REFERENCES `coach` (`idcoach`),
  ADD CONSTRAINT `begeleidingsnotitie_ibfk_2` FOREIGN KEY (`idstudent`) REFERENCES `student` (`idstudent`),
  ADD CONSTRAINT `begeleidingsnotitie_ibfk_3` FOREIGN KEY (`student_leeruitkomsten_id_leeruitkomsten`) REFERENCES `student_leeruitkomsten` (`id_leeruitkomsten`);

--
-- Beperkingen voor tabel `bericht`
--
ALTER TABLE `bericht`
  ADD CONSTRAINT `bericht_ibfk_1` FOREIGN KEY (`student_idstudent`) REFERENCES `student` (`idstudent`);

--
-- Beperkingen voor tabel `coach`
--
ALTER TABLE `coach`
  ADD CONSTRAINT `coach_ibfk_1` FOREIGN KEY (`lid_idlid`) REFERENCES `lid` (`idlid`);

--
-- Beperkingen voor tabel `gesprekken`
--
ALTER TABLE `gesprekken`
  ADD CONSTRAINT `gesprekken_ibfk_1` FOREIGN KEY (`student_idstudent`) REFERENCES `student` (`idstudent`);

--
-- Beperkingen voor tabel `leerdoelen`
--
ALTER TABLE `leerdoelen`
  ADD CONSTRAINT `leerdoelen_ibfk_1` FOREIGN KEY (`student_idstudent`) REFERENCES `student` (`idstudent`);

--
-- Beperkingen voor tabel `leeruitkomsten`
--
ALTER TABLE `leeruitkomsten`
  ADD CONSTRAINT `leeruitkomsten_ibfk_1` FOREIGN KEY (`fase_idfase`) REFERENCES `fase` (`idfase`);

--
-- Beperkingen voor tabel `lid`
--
ALTER TABLE `lid`
  ADD CONSTRAINT `lid_ibfk_1` FOREIGN KEY (`rol_idrol`) REFERENCES `rol` (`idrol`),
  ADD CONSTRAINT `lid_ibfk_2` FOREIGN KEY (`school_idschool`) REFERENCES `school` (`idschool`);

--
-- Beperkingen voor tabel `reflectie`
--
ALTER TABLE `reflectie`
  ADD CONSTRAINT `reflectie_ibfk_1` FOREIGN KEY (`student_leeruitkomsten_id_leeruitkomsten`) REFERENCES `student_leeruitkomsten` (`id_leeruitkomsten`);

--
-- Beperkingen voor tabel `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`lid_idlid`) REFERENCES `lid` (`idlid`);

--
-- Beperkingen voor tabel `student_coach`
--
ALTER TABLE `student_coach`
  ADD CONSTRAINT `student_coach_ibfk_1` FOREIGN KEY (`student_idstudent`) REFERENCES `student` (`idstudent`),
  ADD CONSTRAINT `student_coach_ibfk_2` FOREIGN KEY (`coach_idcoach`) REFERENCES `coach` (`idcoach`);

--
-- Beperkingen voor tabel `student_leeruitkomsten`
--
ALTER TABLE `student_leeruitkomsten`
  ADD CONSTRAINT `student_leeruitkomsten_ibfk_1` FOREIGN KEY (`student_idstudent`) REFERENCES `student` (`idstudent`),
  ADD CONSTRAINT `student_leeruitkomsten_ibfk_2` FOREIGN KEY (`leeruitkomsten_leeruitkomsten_id`) REFERENCES `leeruitkomsten` (`leeruitkomsten_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
