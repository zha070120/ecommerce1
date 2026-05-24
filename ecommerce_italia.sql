-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-05-24 23:34:16
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
(4, 'aaa@gmail.com');

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
('aaa@gmail.com', 'shabi', 'nishi', 'via laoshishizhu', '123456', '$2y$10$/SDdwmZWv3I4ACwmufMGneSTZ1ORkjL3GFTvlmm2JMGl2h25H2tdO');

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
(19, 'aaa@gmail.com', '2026-05-24', 'consegnato'),
(20, 'aaa@gmail.com', '2026-05-24', 'attivo');

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
(10, 'telefono', 888.00, '999999999', 12, 'img/prodotti/prod_6a12e9c7bb61d_1779624391.jpg'),
(11, 'computer', 1234.00, '88888888', 30, 'img/prodotti/prod_6a12eaa2c8410_1779624610.jpg'),
(12, '*****', 9.99, '88888888', 100000, 'img/prodotti/prod_6a13150e31bdf_1779635470.png'),
(13, 'maiale', 911.00, '88888888', 2, 'img/prodotti/prod_6a131623352e1_1779635747.gif'),
(14, 'Raccoglitrice di cotone', 91919.00, '88888888', 32, 'img/prodotti/prod_6a1319253d305_1779636517.jpeg'),
(15, 'Matita', 0.50, '999999999', 231, 'img/prodotti/prod_6a136d46a4820_1779658054.jpg'),
(16, 'Penna', 1.00, '999999999', 35, 'img/prodotti/prod_6a136d54aeaad_1779658068.jpg'),
(17, 'Quaderno', 3.00, '999999999', 90, 'img/prodotti/prod_6a136d62eb747_1779658082.jpg'),
(18, 'Righello', 5.00, '999999999', 876, 'img/prodotti/prod_6a136dc714ad8_1779658183.jpg'),
(19, 'Forbici', 6.00, '999999999', 8750, 'img/prodotti/prod_6a136e0c070a0_1779658252.jpg');

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
(10, 19, 1, 888.00, 888.00),
(11, 19, 1, 1234.00, 1234.00),
(16, 20, 10, 1.00, 10.00),
(19, 20, 6, 6.00, 36.00);

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
('88888888', 'caonima', 'San polo', '30125', '$2y$10$si9wSWasiZ5VnogE1mi15eq7xQXx2f61j8Q9x0cSolEmS.CWmiAe2'),
('999999999', 'nishizhuma', '', '99999999', '$2y$10$uWTdG3V3JVagJ2m8CMXRQus3QenKuNq6VDmMSPD37O.hujJBKw5qG');

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
  MODIFY `id_carello` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco carrello', AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `ordine`
--
ALTER TABLE `ordine`
  MODIFY `id_ordine` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco ordine', AUTO_INCREMENT=21;

--
-- 使用表AUTO_INCREMENT `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `id_prodotto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco prodotto', AUTO_INCREMENT=20;

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
