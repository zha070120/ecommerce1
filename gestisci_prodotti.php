<?php
/**
 * Pagina di Gestione Prodotti per l'Area Venditori
 * 
 * Questa pagina permette ai venditori autenticati di:
 * - Visualizzare tutti i propri prodotti
 * - Aggiungere nuovi prodotti al catalogo
 * - Modificare i dettagli dei prodotti esistenti
 * - Eliminare prodotti dal catalogo
 * - Caricare e gestire le immagini dei prodotti
 * 
 * @author Sviluppatore E-commerce
 * @version 1.0
 * @package AreaVenditori
 */

// Includi il file di configurazione del database e le funzioni generali
include 'config.php';

/**
 * Verifica che l'utente sia autenticato come venditore
 * Se non è autenticato, reindirizza alla pagina di login
 */
richiedi_login_venditore();

// Ottieni la Partita IVA del venditore dalla sessione
$p_iva_venditore = $_SESSION['venditore_piva'];

// Variabili per i messaggi di feedback all'utente
$messaggio = '';  // Messaggi di successo
$errore = '';     // Messaggi di errore

/**
 * Configurazione unificata per l'upload delle immagini dei prodotti
 * Centralizza tutte le impostazioni per facilitare la manutenzione
 */
$upload_config = [
    'dir' => 'img/prodotti/',          // Directory dove salvare le immagini
    'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif'], // Estensioni file consentite
    'max_size' => 2 * 1024 * 1024,     // Dimensione massima file: 2MB (in byte)
    'prefix' => 'prod_'                // Prefisso per i nomi dei file generati
];

// ==============================================================
// 1. FUNZIONE: ELIMINAZIONE PRODOTTO
// ==============================================================
/**
 * Gestisce la richiesta di eliminazione di un prodotto
 * Elimina sia il record dal database che l'immagine associata dal server
 */
if (isset($_POST['elimina_prodotto'])) {
    // Converti l'ID prodotto in intero per sicurezza (previene SQL injection)
    $id_prodotto = intval($_POST['id_prodotto']);
    
    // Prepara la query per verificare che il prodotto appartenga al venditore corrente
    // Questo è fondamentale per la sicurezza: impedisce a venditori di eliminare prodotti altrui
    $stmt_check = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_check->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    // Se il prodotto esiste e appartiene al venditore
    if ($result->num_rows === 1) {
        // Ottieni i dati del prodotto (in particolare il percorso dell'immagine)
        $prodotto = $result->fetch_assoc();
        
        // Prepara la query di eliminazione dal database
        $stmt_elimina = $conn->prepare("DELETE FROM prodotto WHERE id_prodotto = ?");
        $stmt_elimina->bind_param("i", $id_prodotto);
        
        // Esegui l'eliminazione
        if ($stmt_elimina->execute()) {
            // Se l'eliminazione dal database è andata a buon fine, elimina anche l'immagine dal server
            // Verifica che il percorso dell'immagine non sia vuoto e che il file esista
            if (!empty($prodotto['indirizzo_img']) && file_exists($prodotto['indirizzo_img'])) {
                unlink($prodotto['indirizzo_img']); // Elimina il file fisico
            }
            $messaggio = "Prodotto eliminato con successo!";
        } else {
            // Errore durante l'eliminazione (probabilmente il prodotto è presente in ordini attivi)
            $errore = "Errore: non puoi eliminare un prodotto presente in ordini attivi.";
        }
        $stmt_elimina->close(); // Chiudi lo statement per liberare risorse
    } else {
        // Il prodotto non esiste o non appartiene al venditore corrente
        $errore = "Non sei autorizzato a eliminare questo prodotto.";
    }
    $stmt_check->close(); // Chiudi lo statement di verifica
}

// ==============================================================
// 2. FUNZIONE: AGGIUNTA NUOVO PRODOTTO
// ==============================================================
/**
 * Gestisce la richiesta di aggiunta di un nuovo prodotto
 * Gestisce anche l'upload dell'immagine associata e il rollback in caso di errore
 */
if (isset($_POST['aggiungi_prodotto'])) {
    // Recupera e sanitizza i dati dal form
    // Nota: Non usiamo real_escape_string perché usiamo prepared statements
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']); // Converti in float per il prezzo
    $quantita = intval($_POST['quantita_disponibile']); // Converti in intero per la quantità
    $indirizzo_img = ''; // Inizializza il percorso dell'immagine come vuoto

    // Gestisci l'upload dell'immagine se è stato selezionato un file
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name']; // Percorso temporaneo del file sul server
        // Ottieni l'estensione del file in minuscolo per uniformità
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        // Validazione del file: verifica estensione consentita
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito. Solo JPG, JPEG, PNG e GIF sono accettati.';
        } 
        // Validazione del file: verifica dimensione massima
        elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande. Massimo 2MB.';
        }

        // Se non ci sono errori nella validazione, procedi con l'upload
        if (empty($errore)) {
            // Crea la directory di destinazione se non esiste
            // Il parametro true permette la creazione ricorsiva delle directory
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            
            // Genera un nome file univoco per evitare sovrascritture
            // Combina prefisso, uniqid() e timestamp per garantire l'unicità
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name; // Percorso completo del file

            // Sposta il file dalla directory temporanea alla directory definitiva
            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine. Verifica i permessi della cartella.';
            }
        }
    }

    // Se non ci sono errori, inserisci il prodotto nel database
    if (empty($errore)) {
        // Prepara la query di inserimento con prepared statement (sicura contro SQL injection)
        $stmt_aggiungi = $conn->prepare("INSERT INTO prodotto (nome, prezzo, p_iva, quantita_disponibile, indirizzo_img) VALUES (?, ?, ?, ?, ?)");
        // Associa i parametri: s=stringa, d=decimale, i=intero
        $stmt_aggiungi->bind_param("sdsis", $nome, $prezzo, $p_iva_venditore, $quantita, $indirizzo_img);
        
        // Esegui l'inserimento
        if ($stmt_aggiungi->execute()) {
            $messaggio = "Prodotto aggiunto con successo!";
        } else {
            // Errore durante l'inserimento nel database
            $errore = "Errore nell'aggiunta del prodotto: " . $conn->error;
            
            // Rollback: elimina l'immagine che era stata caricata
            // Questo evita che rimangano file orfani sul server
            if (!empty($indirizzo_img) && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_aggiungi->close(); // Chiudi lo statement
    }
}

// ==============================================================
// 3. FUNZIONE: RECUPERA DATI PRODOTTO PER MODIFICA
// ==============================================================
/**
 * Recupera i dati di un prodotto specifico per la modifica
 * Viene attivato quando l'utente clicca sul pulsante "Modifica"
 */
$prodotto_da_modificare = null; // Inizializza la variabile come null
if (isset($_GET['modifica'])) {
    // Converti l'ID prodotto in intero per sicurezza
    $id_prodotto = intval($_GET['modifica']);
    
    // Prepara la query per recuperare i dati del prodotto
    // Verifica anche che il prodotto appartenga al venditore corrente
    $stmt_modifica = $conn->prepare("SELECT * FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_modifica->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_modifica->execute();
    
    // Ottieni i dati del prodotto e memorizzali nella variabile
    $prodotto_da_modificare = $stmt_modifica->get_result()->fetch_assoc();
    $stmt_modifica->close(); // Chiudi lo statement
}

// ==============================================================
// 4. FUNZIONE: SALVA MODIFICHE PRODOTTO
// ==============================================================
/**
 * Gestisce il salvataggio delle modifiche a un prodotto esistente
 * Gestisce anche l'aggiornamento dell'immagine e l'eliminazione della vecchia
 */
if (isset($_POST['salva_modifiche'])) {
    // Recupera e sanitizza i dati dal form
    $id_prodotto = intval($_POST['id_prodotto']);
    $nome = $_POST['nome'];
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita_disponibile']);
    $indirizzo_img = null; // Inizializza come null (nessuna modifica immagine)
    $vecchia_immagine = ''; // Percorso della vecchia immagine

    // Recupera il percorso della vecchia immagine dal database
    $stmt_old_img = $conn->prepare("SELECT indirizzo_img FROM prodotto WHERE id_prodotto = ? AND p_iva = ?");
    $stmt_old_img->bind_param("is", $id_prodotto, $p_iva_venditore);
    $stmt_old_img->execute();
    $old_result = $stmt_old_img->get_result();
    
    // Se il prodotto esiste, ottieni il percorso della vecchia immagine
    if ($old_result->num_rows === 1) {
        $vecchia_immagine = $old_result->fetch_assoc()['indirizzo_img'];
    }
    $stmt_old_img->close(); // Chiudi lo statement

    // Gestisci l'upload di una nuova immagine se è stato selezionato un file
    if (!empty($_FILES['indirizzo_img']['name'])) {
        $file_tmp = $_FILES['indirizzo_img']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['indirizzo_img']['name'], PATHINFO_EXTENSION));

        // Validazione del file
        if (!in_array($file_ext, $upload_config['allowed_ext'])) {
            $errore = 'Formato immagine non consentito.';
        } elseif ($_FILES['indirizzo_img']['size'] > $upload_config['max_size']) {
            $errore = 'Dimensione immagine troppo grande.';
        }

        // Se non ci sono errori, procedi con l'upload della nuova immagine
        if (empty($errore)) {
            if (!file_exists($upload_config['dir'])) {
                mkdir($upload_config['dir'], 0755, true);
            }
            
            // Genera un nome file univoco per la nuova immagine
            $new_file_name = $upload_config['prefix'] . uniqid() . '_' . time() . '.' . $file_ext;
            $indirizzo_img = $upload_config['dir'] . $new_file_name;

            // Sposta il file nella directory definitiva
            if (!move_uploaded_file($file_tmp, $indirizzo_img)) {
                $errore = 'Impossibile caricare l\'immagine.';
            }
        }
    }

    // Se non ci sono errori, aggiorna il prodotto nel database
    if (empty($errore)) {
        // Prepara la query di aggiornamento in base a se è stata caricata una nuova immagine
        if ($indirizzo_img !== null) {
            // Aggiorna anche il campo indirizzo_img
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ?, indirizzo_img = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdissi", $nome, $prezzo, $quantita, $indirizzo_img, $id_prodotto, $p_iva_venditore);
        } else {
            // Non aggiorna il campo indirizzo_img (mantieni la vecchia)
            $stmt_salva = $conn->prepare("UPDATE prodotto SET nome = ?, prezzo = ?, quantita_disponibile = ? WHERE id_prodotto = ? AND p_iva = ?");
            $stmt_salva->bind_param("sdisi", $nome, $prezzo, $quantita, $id_prodotto, $p_iva_venditore);
        }

        // Esegui l'aggiornamento
        if ($stmt_salva->execute()) {
            $messaggio = "Prodotto modificato con successo!";
            
            // Se è stata caricata una nuova immagine, elimina la vecchia dal server
            if ($indirizzo_img !== null && !empty($vecchia_immagine) && file_exists($vecchia_immagine)) {
                unlink($vecchia_immagine);
            }
            
            $prodotto_da_modificare = null; // Resetta la variabile per tornare alla modalità aggiunta
        } else {
            // Errore durante l'aggiornamento nel database
            $errore = "Errore nella modifica: " . $conn->error;
            
            // Rollback: elimina la nuova immagine che era stata caricata
            if ($indirizzo_img !== null && file_exists($indirizzo_img)) {
                unlink($indirizzo_img);
            }
        }
        $stmt_salva->close(); // Chiudi lo statement
    }
}

// ==============================================================
// 5. FUNZIONE: RECUPERA TUTTI I PRODOTTI DEL VENDITORE
// ==============================================================
/**
 * Recupera tutti i prodotti appartenenti al venditore corrente
 * Ordinati per ID decrescente (i più recenti prima)
 */
$stmt_prodotti = $conn->prepare("SELECT * FROM prodotto WHERE p_iva = ? ORDER BY id_prodotto DESC");
$stmt_prodotti->bind_param("s", $p_iva_venditore);
$stmt_prodotti->execute();
$prodotti = $stmt_prodotti->get_result(); // Ottieni il risultato della query
$stmt_prodotti->close(); // Chiudi lo statement
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Prodotti - Area Venditori</title>
    <!-- Includi Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Includi il foglio di stile personalizzato -->
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <!-- Intestazione della pagina con menu di navigazione -->
    <header>
        <div class="container">
            <h1><i class="fas fa-store"></i> Area Venditori</h1>
            <nav>
                <!-- Messaggio di benvenuto con il nome della ragione sociale -->
                <span><i class="fas fa-user"></i> Ciao, <?= $_SESSION['venditore_ragione_sociale'] ?></span>
                <!-- Link alle varie sezioni dell'area venditori -->
                <a href="dashboard_venditore.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="gestisci_prodotti.php" style="border-bottom: 2px solid #fef3c7;"><i class="fas fa-box"></i> Prodotti</a>
                <a href="ordini_venditore.php"><i class="fas fa-file-invoice"></i> Ordini</a>
                <a href="profilo_venditore.php"><i class="fas fa-user-cog"></i> Profilo</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Titolo dinamico: cambia tra "Aggiungi" e "Modifica" prodotto -->
        <h2><i class="fas fa-<?= $prodotto_da_modificare ? 'edit' : 'plus-circle' ?>"></i> <?= $prodotto_da_modificare ? 'Modifica Prodotto' : 'Aggiungi Nuovo Prodotto' ?></h2>
        
        <!-- Visualizza messaggi di successo -->
        <?php if ($messaggio): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>
        
        <!-- Visualizza messaggi di errore -->
        <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errore ?>
            </div>
        <?php endif; ?>

        <!-- Form per aggiungere/modificare un prodotto -->
        <!-- enctype="multipart/form-data" è obbligatorio per l'upload di file -->
        <form method="POST" enctype="multipart/form-data" class="form-card">
            <!-- Campo nascosto con l'ID prodotto (solo in modalità modifica) -->
            <?php if ($prodotto_da_modificare): ?>
                <input type="hidden" name="id_prodotto" value="<?= $prodotto_da_modificare['id_prodotto'] ?>">
            <?php endif; ?>

            <!-- Campo Nome Prodotto -->
            <div class="form-group">
                <label for="nome"><i class="fas fa-tag"></i> Nome Prodotto</label>
                <input type="text" id="nome" name="nome" required value="<?= $prodotto_da_modificare['nome'] ?? '' ?>" placeholder="Inserisci il nome del prodotto">
            </div>
            
            <!-- Campo Prezzo (con step 0.01 per i centesimi) -->
            <div class="form-group">
                <label for="prezzo"><i class="fas fa-euro-sign"></i> Prezzo (€)</label>
                <input type="number" step="0.01" id="prezzo" name="prezzo" required value="<?= $prodotto_da_modificare['prezzo'] ?? '' ?>" placeholder="Inserisci il prezzo">
            </div>
            
            <!-- Campo Quantità Disponibile -->
            <div class="form-group">
                <label for="qty"><i class="fas fa-boxes"></i> Quantità Disponibile</label>
                <input type="number" id="qty" name="quantita_disponibile" required value="<?= $prodotto_da_modificare['quantita_disponibile'] ?? '' ?>" placeholder="Inserisci la quantità disponibile">
            </div>

            <!-- Campo Upload Immagine Prodotto -->
            <div class="form-group">
                <label for="img"><i class="fas fa-image"></i> Immagine Prodotto</label>
                <input type="file" id="img" name="indirizzo_img" accept="image/jpg, image/jpeg, image/png, image/gif">
                <small>Formati consentiti: JPG, JPEG, PNG, GIF | Dimensione massima: 2MB</small>
                
                <!-- In modalità modifica: mostra l'immagine attuale -->
                <?php if ($prodotto_da_modificare && !empty($prodotto_da_modificare['indirizzo_img'])): ?>
                    <br>
                    <small>Immagine attuale:</small>
                    <br>
                    <img src="<?= $prodotto_da_modificare['indirizzo_img'] ?>" class="product-img-preview" alt="Immagine prodotto">
                    <br>
                    <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                <?php endif; ?>
            </div>

            <!-- Pulsanti di azione dinamici -->
            <div class="form-actions">
                <?php if ($prodotto_da_modificare): ?>
                    <!-- Pulsanti per la modalità modifica -->
                    <button type="submit" name="salva_modifiche" class="btn btn-success">
                        <i class="fas fa-save"></i> Salva Modifiche
                    </button>
                    <a href="gestisci_prodotti.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                <?php else: ?>
                    <!-- Pulsante per la modalità aggiunta -->
                    <button type="submit" name="aggiungi_prodotto" class="btn btn-success">
                        <i class="fas fa-plus"></i> Aggiungi Prodotto
                    </button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Sezione con l'elenco di tutti i prodotti del venditore -->
        <h2><i class="fas fa-boxes"></i> I Tuoi Prodotti</h2>
        
        <?php if ($prodotti->num_rows > 0): ?>
            <!-- Tabella dei prodotti (se ci sono prodotti) -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Immagine</th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Prezzo</th>
                        <th>Disponibilità</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Ciclo attraverso tutti i prodotti e crea una riga per ognuno -->
                    <?php while ($prodotto = $prodotti->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <!-- Mostra l'immagine del prodotto se esiste, altrimenti un messaggio -->
                                <?php if (!empty($prodotto['indirizzo_img'])): ?>
                                    <img src="<?= $prodotto['indirizzo_img'] ?>" class="product-img-table" alt="<?= $prodotto['nome'] ?>">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> Nessuna</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prodotto['id_prodotto'] ?></td>
                            <td><?= $prodotto['nome'] ?></td>
                            <!-- Formatta il prezzo con 2 decimali, virgola come separatore decimale e punto come separatore migliaia -->
                            <td class="price">€ <?= number_format($prodotto['prezzo'], 2, ',', '.') ?></td>
                            <td class="stock"><?= $prodotto['quantita_disponibile'] ?> pezzi</td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Pulsante Modifica: reindirizza alla stessa pagina con parametro modifica -->
                                    <a href="gestisci_prodotti.php?modifica=<?= $prodotto['id_prodotto'] ?>" class="btn">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <!-- Form per l'eliminazione (con conferma JavaScript) -->
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="id_prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                        <button type="submit" name="elimina_prodotto" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questo prodotto?')">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Stato vuoto: se il venditore non ha ancora aggiunto prodotti -->
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Non hai ancora aggiunto prodotti al catalogo.</p>
                <a href="#aggiungi" class="btn">
                    <i class="fas fa-plus"></i> Aggiungi il tuo primo prodotto
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>