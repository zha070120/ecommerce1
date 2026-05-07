-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 06, 2026 alle 13:05
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_italia`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `carello`
--

CREATE TABLE `carello` (
  `id_carello` int(11) NOT NULL COMMENT 'ID univoco carrello',
  `email` varchar(100) NOT NULL COMMENT 'Email cliente (chiave esterna)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella carrelli clienti';

--
-- Dump dei dati per la tabella `carello`
--

INSERT INTO `carello` (`id_carello`, `email`) VALUES
(1, 'aaa@gmail.com');

-- --------------------------------------------------------

--
-- Struttura della tabella `cliente`
--

CREATE TABLE `cliente` (
  `email` varchar(100) NOT NULL COMMENT 'Email cliente, chiave primaria',
  `nome` varchar(50) NOT NULL COMMENT 'Nome del cliente',
  `cognome` varchar(50) NOT NULL COMMENT 'Cognome del cliente',
  `indirizzo` varchar(50) NOT NULL COMMENT 'Indirizzo di residenza',
  `cap` varchar(8) NOT NULL COMMENT 'Codice di avviamento postale',
  `password` varchar(256) NOT NULL COMMENT 'Password hashata (SHA-256)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella anagrafica clienti';

--
-- Dump dei dati per la tabella `cliente`
--

INSERT INTO `cliente` (`email`, `nome`, `cognome`, `indirizzo`, `cap`, `password`) VALUES
('aaa@gmail.com', 'aa', 'bb', 'saasl', '30125', '$2y$10$QjPTudHpA3ZaJVEX.1t1dOjVmRZvtUbbImAQstknE6k1R6/duDVNK');

-- --------------------------------------------------------

--
-- Struttura della tabella `ordine`
--

CREATE TABLE `ordine` (
  `id_ordine` int(11) NOT NULL COMMENT 'ID univoco ordine',
  `email` varchar(100) NOT NULL COMMENT 'Email cliente (chiave esterna)',
  `data_ordine` date NOT NULL COMMENT 'Data di creazione ordine',
  `stato_ordine` varchar(10) NOT NULL COMMENT 'Stato ordine (es: attivo, consegnato, annullato)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella testata ordini';

--
-- Dump dei dati per la tabella `ordine`
--

INSERT INTO `ordine` (`id_ordine`, `email`, `data_ordine`, `stato_ordine`) VALUES
(1, 'aaa@gmail.com', '2026-05-06', 'attivo');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotto`
--

CREATE TABLE `prodotto` (
  `id_prodotto` int(11) NOT NULL COMMENT 'ID univoco prodotto',
  `nome` varchar(100) NOT NULL COMMENT 'Nome del prodotto',
  `prezzo` decimal(10,2) NOT NULL COMMENT 'Prezzo unitario',
  `p_iva` varchar(13) NOT NULL COMMENT 'Partita IVA venditore (chiave esterna)',
  `quantita_disponibile` int(11) NOT NULL COMMENT 'Quantità disponibile in magazzino',
  `indirizzo_img` varchar(100) NOT NULL COMMENT 'Percorso/URL immagine prodotto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella catalogo prodotti';

--
-- Dump dei dati per la tabella `prodotto`
--

INSERT INTO `prodotto` (`id_prodotto`, `nome`, `prezzo`, `p_iva`, `quantita_disponibile`, `indirizzo_img`) VALUES
(1, 'Smartphone X1', 299.99, '0123456789012', 50, 'https://picsum.photos/300/300?random=1'),
(2, 'Laptop Pro 15', 899.90, '0123456789012', 19, 'https://picsum.photos/300/300?random=2'),
(3, 'Maglia Cotone Uomo', 29.90, '0123456789012', 100, 'https://picsum.photos/300/300?random=3'),
(4, 'Jeans Slim Donna', 49.99, '0123456789012', 75, 'https://picsum.photos/300/300?random=4');

-- --------------------------------------------------------

--
-- Struttura della tabella `p_c`
--

CREATE TABLE `p_c` (
  `id_prodotto` int(11) NOT NULL COMMENT 'ID prodotto',
  `id_carello` int(11) NOT NULL COMMENT 'ID carrello',
  `pezzi` int(11) NOT NULL COMMENT 'Numero di pezzi nel carrello'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dettaglio righe carrello';

--
-- Dump dei dati per la tabella `p_c`
--

INSERT INTO `p_c` (`id_prodotto`, `id_carello`, `pezzi`) VALUES
(3, 1, 5);

-- --------------------------------------------------------

--
-- Struttura della tabella `p_o`
--

CREATE TABLE `p_o` (
  `id_prodotto` int(11) NOT NULL COMMENT 'ID prodotto',
  `id_ordine` int(11) NOT NULL COMMENT 'ID ordine',
  `pezzi` int(11) NOT NULL COMMENT 'Numero di pezzi ordinati',
  `prezzo_singolo` decimal(10,2) NOT NULL COMMENT 'Prezzo unitario al momento dell''ordine',
  `prezzo_tot` decimal(10,2) NOT NULL COMMENT 'Prezzo totale riga (pezzi * prezzo_singolo)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dettaglio righe ordine';

--
-- Dump dei dati per la tabella `p_o`
--

INSERT INTO `p_o` (`id_prodotto`, `id_ordine`, `pezzi`, `prezzo_singolo`, `prezzo_tot`) VALUES
(2, 1, 1, 899.90, 899.90);

-- --------------------------------------------------------

--
-- Struttura della tabella `venditore`
--

CREATE TABLE `venditore` (
  `p_iva` varchar(13) NOT NULL COMMENT 'Partita IVA venditore, chiave primaria',
  `ragione_sociale` varchar(100) NOT NULL COMMENT 'Ragione sociale dell''azienda',
  `indirizzo` varchar(100) NOT NULL COMMENT 'Indirizzo della sede',
  `cap` varchar(8) NOT NULL COMMENT 'Codice di avviamento postale',
  `password` varchar(256) NOT NULL COMMENT 'Password hashata (SHA-256)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella anagrafica venditori';

--
-- Dump dei dati per la tabella `venditore`
--

INSERT INTO `venditore` (`p_iva`, `ragione_sociale`, `indirizzo`, `cap`, `password`) VALUES
('0123456789012', 'Elettronica SRL', 'Via Roma 123, Milano', '20100', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `carello`
--
ALTER TABLE `carello`
  ADD PRIMARY KEY (`id_carello`),
  ADD KEY `email` (`email`);

--
-- Indici per le tabelle `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`email`);

--
-- Indici per le tabelle `ordine`
--
ALTER TABLE `ordine`
  ADD PRIMARY KEY (`id_ordine`),
  ADD KEY `email` (`email`);

--
-- Indici per le tabelle `prodotto`
--
ALTER TABLE `prodotto`
  ADD PRIMARY KEY (`id_prodotto`),
  ADD KEY `p_iva` (`p_iva`);

--
-- Indici per le tabelle `p_c`
--
ALTER TABLE `p_c`
  ADD PRIMARY KEY (`id_prodotto`,`id_carello`) COMMENT 'Chiave primaria composta',
  ADD KEY `id_carello` (`id_carello`);

--
-- Indici per le tabelle `p_o`
--
ALTER TABLE `p_o`
  ADD PRIMARY KEY (`id_prodotto`,`id_ordine`) COMMENT 'Chiave primaria composta',
  ADD KEY `id_ordine` (`id_ordine`);

--
-- Indici per le tabelle `venditore`
--
ALTER TABLE `venditore`
  ADD PRIMARY KEY (`p_iva`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `carello`
--
ALTER TABLE `carello`
  MODIFY `id_carello` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco carrello', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `ordine`
--
ALTER TABLE `ordine`
  MODIFY `id_ordine` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco ordine', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `id_prodotto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco prodotto', AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `carello`
--
ALTER TABLE `carello`
  ADD CONSTRAINT `carello_ibfk_1` FOREIGN KEY (`email`) REFERENCES `cliente` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `ordine`
--
ALTER TABLE `ordine`
  ADD CONSTRAINT `ordine_ibfk_1` FOREIGN KEY (`email`) REFERENCES `cliente` (`email`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  ADD CONSTRAINT `prodotto_ibfk_1` FOREIGN KEY (`p_iva`) REFERENCES `venditore` (`p_iva`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `p_c`
--
ALTER TABLE `p_c`
  ADD CONSTRAINT `p_c_ibfk_1` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotto` (`id_prodotto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `p_c_ibfk_2` FOREIGN KEY (`id_carello`) REFERENCES `carello` (`id_carello`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `p_o`
--
ALTER TABLE `p_o`
  ADD CONSTRAINT `p_o_ibfk_1` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotto` (`id_prodotto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `p_o_ibfk_2` FOREIGN KEY (`id_ordine`) REFERENCES `ordine` (`id_ordine`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
