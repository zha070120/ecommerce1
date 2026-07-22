-- =====================================================
-- Database Upgrade Script - E-commerce Italia v2.0
-- Descrizione: Aggiornamento schema database per nuove funzionalità
-- Esecuzione: Importa questo file in phpMyAdmin o esegui via MySQL CLI
-- =====================================================

-- 1. Estende enum degli stati ordine + imposta default a 'in_attesa'
ALTER TABLE `ordine`
    MODIFY `stato_ordine` ENUM('in_attesa', 'attivo', 'spedito', 'consegnato', 'annullato')
    NOT NULL DEFAULT 'in_attesa'
    COMMENT 'Stato ordine';

-- 2. Aggiunge campi indirizzo di spedizione alla tabella ordine
ALTER TABLE `ordine`
    ADD COLUMN `indirizzo_spedizione` VARCHAR(200) DEFAULT NULL COMMENT 'Indirizzo di spedizione' AFTER `stato_ordine`,
    ADD COLUMN `citta` VARCHAR(100) DEFAULT NULL COMMENT 'Città di spedizione' AFTER `indirizzo_spedizione`,
    ADD COLUMN `cap` VARCHAR(10) DEFAULT NULL COMMENT 'CAP di spedizione' AFTER `citta`,
    ADD COLUMN `provincia` VARCHAR(50) DEFAULT NULL COMMENT 'Provincia di spedizione' AFTER `cap`,
    ADD COLUMN `telefono` VARCHAR(20) DEFAULT NULL COMMENT 'Numero di telefono' AFTER `provincia`,
    ADD COLUMN `note` TEXT DEFAULT NULL COMMENT 'Note aggiuntive ordine' AFTER `telefono`;

-- 3. Aggiunge campo descrizione alla tabella prodotto
ALTER TABLE `prodotto`
    ADD COLUMN `descrizione` TEXT DEFAULT NULL COMMENT 'Descrizione prodotto' AFTER `nome`;

-- =====================================================
-- FINE SCRIPT DI AGGIORNAMENTO
-- =====================================================
