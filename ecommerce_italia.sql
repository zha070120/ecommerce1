-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-05-09 22:49:32
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `ecommerce_italia`
--

-- --------------------------------------------------------

--
-- 表的结构 `carello`
--

CREATE TABLE `carello` (
  `id_carello` int(11) NOT NULL COMMENT 'ID univoco carrello',
  `email` varchar(100) NOT NULL COMMENT 'Email cliente (chiave esterna)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella carrelli clienti';

--
-- 转存表中的数据 `carello`
--

INSERT INTO `carello` (`id_carello`, `email`) VALUES
(1, 'aaa@gmail.com');

-- --------------------------------------------------------

--
-- 表的结构 `cliente`
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
-- 转存表中的数据 `cliente`
--

INSERT INTO `cliente` (`email`, `nome`, `cognome`, `indirizzo`, `cap`, `password`) VALUES
('aaa@gmail.com', 'aa', 'bb', 'saasl', '30125', '$2y$10$QjPTudHpA3ZaJVEX.1t1dOjVmRZvtUbbImAQstknE6k1R6/duDVNK');

-- --------------------------------------------------------

--
-- 表的结构 `ordine`
--

CREATE TABLE `ordine` (
  `id_ordine` int(11) NOT NULL COMMENT 'ID univoco ordine',
  `email` varchar(100) NOT NULL COMMENT 'Email cliente (chiave esterna)',
  `data_ordine` date NOT NULL COMMENT 'Data di creazione ordine',
  `stato_ordine` enum('attivo','spedito','consegnato','annullato') NOT NULL DEFAULT 'attivo' COMMENT 'Stato ordine (es: attivo, consegnato, annullato)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella testata ordini';

--
-- 转存表中的数据 `ordine`
--

INSERT INTO `ordine` (`id_ordine`, `email`, `data_ordine`, `stato_ordine`) VALUES
(1, 'aaa@gmail.com', '2026-05-06', 'attivo'),
(2, 'aaa@gmail.com', '2026-05-07', 'annullato'),
(3, 'aaa@gmail.com', '2026-05-07', 'attivo'),
(4, 'aaa@gmail.com', '2026-05-07', 'consegnato');

-- --------------------------------------------------------

--
-- 表的结构 `prodotto`
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
-- 转存表中的数据 `prodotto`
--

INSERT INTO `prodotto` (`id_prodotto`, `nome`, `prezzo`, `p_iva`, `quantita_disponibile`, `indirizzo_img`) VALUES
(1, 'Smartphone X1', 299.99, '0123456789012', 50, 'https://picsum.photos/300/300?random=1'),
(2, 'Laptop Pro 15', 899.90, '0123456789012', 14, 'https://picsum.photos/300/300?random=2'),
(3, 'Maglia Cotone Uomo', 29.90, '0123456789012', 100, 'https://picsum.photos/300/300?random=3'),
(4, 'Jeans Slim Donna', 49.99, '0123456789012', 75, 'https://picsum.photos/300/300?random=4'),
(5, 'matita', 2.00, '88888888', 995, 'https://picsum.photos/300/300?random=5');

-- --------------------------------------------------------

--
-- 表的结构 `p_c`
--

CREATE TABLE `p_c` (
  `id_prodotto` int(11) NOT NULL COMMENT 'ID prodotto',
  `id_carello` int(11) NOT NULL COMMENT 'ID carrello',
  `pezzi` int(11) NOT NULL COMMENT 'Numero di pezzi nel carrello'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dettaglio righe carrello';

-- --------------------------------------------------------

--
-- 表的结构 `p_o`
--

CREATE TABLE `p_o` (
  `id_prodotto` int(11) NOT NULL COMMENT 'ID prodotto',
  `id_ordine` int(11) NOT NULL COMMENT 'ID ordine',
  `pezzi` int(11) NOT NULL COMMENT 'Numero di pezzi ordinati',
  `prezzo_singolo` decimal(10,2) NOT NULL COMMENT 'Prezzo unitario al momento dell''ordine',
  `prezzo_tot` decimal(10,2) NOT NULL COMMENT 'Prezzo totale riga (pezzi * prezzo_singolo)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dettaglio righe ordine';

--
-- 转存表中的数据 `p_o`
--

INSERT INTO `p_o` (`id_prodotto`, `id_ordine`, `pezzi`, `prezzo_singolo`, `prezzo_tot`) VALUES
(2, 1, 1, 899.90, 899.90),
(2, 3, 5, 899.90, 4499.50),
(3, 2, 5, 29.90, 149.50),
(5, 4, 5, 2.00, 10.00);

-- --------------------------------------------------------

--
-- 表的结构 `venditore`
--

CREATE TABLE `venditore` (
  `p_iva` varchar(13) NOT NULL COMMENT 'Partita IVA venditore, chiave primaria',
  `ragione_sociale` varchar(100) NOT NULL COMMENT 'Ragione sociale dell''azienda',
  `indirizzo` varchar(100) NOT NULL COMMENT 'Indirizzo della sede',
  `cap` varchar(8) NOT NULL COMMENT 'Codice di avviamento postale',
  `password` varchar(256) NOT NULL COMMENT 'Password hashata (SHA-256)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella anagrafica venditori';

--
-- 转存表中的数据 `venditore`
--

INSERT INTO `venditore` (`p_iva`, `ragione_sociale`, `indirizzo`, `cap`, `password`) VALUES
('0123456789012', 'Elettronica SRL', 'Via Roma 123, Milano', '20100', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('88888888', 'shabi', 'via aaaaaaaaaaaaaaaaa', '33333', '$2y$10$dPr4Lfgrf2BKtD8we/JhdelYGd/6U45fQgU1dVmAdCPPIFRzBx/pO');

--
-- 转储表的索引
--

--
-- 表的索引 `carello`
--
ALTER TABLE `carello`
  ADD PRIMARY KEY (`id_carello`),
  ADD KEY `email` (`email`);

--
-- 表的索引 `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`email`);

--
-- 表的索引 `ordine`
--
ALTER TABLE `ordine`
  ADD PRIMARY KEY (`id_ordine`),
  ADD KEY `email` (`email`);

--
-- 表的索引 `prodotto`
--
ALTER TABLE `prodotto`
  ADD PRIMARY KEY (`id_prodotto`),
  ADD KEY `p_iva` (`p_iva`);

--
-- 表的索引 `p_c`
--
ALTER TABLE `p_c`
  ADD PRIMARY KEY (`id_prodotto`,`id_carello`) COMMENT 'Chiave primaria composta',
  ADD KEY `id_carello` (`id_carello`);

--
-- 表的索引 `p_o`
--
ALTER TABLE `p_o`
  ADD PRIMARY KEY (`id_prodotto`,`id_ordine`) COMMENT 'Chiave primaria composta',
  ADD KEY `id_ordine` (`id_ordine`);

--
-- 表的索引 `venditore`
--
ALTER TABLE `venditore`
  ADD PRIMARY KEY (`p_iva`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `carello`
--
ALTER TABLE `carello`
  MODIFY `id_carello` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco carrello', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `ordine`
--
ALTER TABLE `ordine`
  MODIFY `id_ordine` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco ordine', AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `id_prodotto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco prodotto', AUTO_INCREMENT=6;

--
-- 限制导出的表
--

--
-- 限制表 `carello`
--
ALTER TABLE `carello`
  ADD CONSTRAINT `carello_ibfk_1` FOREIGN KEY (`email`) REFERENCES `cliente` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ordine`
--
ALTER TABLE `ordine`
  ADD CONSTRAINT `ordine_ibfk_1` FOREIGN KEY (`email`) REFERENCES `cliente` (`email`) ON UPDATE CASCADE;

--
-- 限制表 `prodotto`
--
ALTER TABLE `prodotto`
  ADD CONSTRAINT `prodotto_ibfk_1` FOREIGN KEY (`p_iva`) REFERENCES `venditore` (`p_iva`) ON UPDATE CASCADE;

--
-- 限制表 `p_c`
--
ALTER TABLE `p_c`
  ADD CONSTRAINT `p_c_ibfk_1` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotto` (`id_prodotto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `p_c_ibfk_2` FOREIGN KEY (`id_carello`) REFERENCES `carello` (`id_carello`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `p_o`
--
ALTER TABLE `p_o`
  ADD CONSTRAINT `p_o_ibfk_1` FOREIGN KEY (`id_prodotto`) REFERENCES `prodotto` (`id_prodotto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `p_o_ibfk_2` FOREIGN KEY (`id_ordine`) REFERENCES `ordine` (`id_ordine`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
