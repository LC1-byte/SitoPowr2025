<?php
include "controllo_login.php";
require_once('connessione.php'); // Include il file di connessione
include "menu.php";

$conn = getConnessione(); // Ottieni la connessione al database

accesso_riservato("artigiano");

if (!isset($_SESSION['acquisti']) || !is_array($_SESSION['acquisti']) || count($_SESSION['acquisti']) === 0) {
    echo "<p>Nessun materiale selezionato per l'acquisto.</p>";
    echo '<p><a href="domanda.php">Torna a domanda</a></p>';
    exit;
}

$utente = $_SESSION['nick'];
$acquisti = $_SESSION['acquisti'];

// Recupero ID utente
$query_utente = "SELECT ID FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($conn, $utente) . "'";
$ris_utente = mysqli_query($conn, $query_utente);
if (!$ris_utente || mysqli_num_rows($ris_utente) == 0) {
    die("<p>Utente non trovato.</p>");
}
$riga_utente = mysqli_fetch_assoc($ris_utente);
$id_utente = $riga_utente['ID'];

// Recupero credito artigiano
$query_credito = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
$ris_credito = mysqli_query($conn, $query_credito);
if (!$ris_credito || mysqli_num_rows($ris_credito) == 0) {
    die("<p>Credito artigiano non trovato.</p>");
}
$riga_credito = mysqli_fetch_assoc($ris_credito);
$credito = $riga_credito['CREDIT'];

// Recupero dati materiali
$ids = array_keys($acquisti);
$ids_esc = array_map('intval', $ids); // sicuro: solo interi
$ids_list = implode(",", $ids_esc);

$query_materiali = "SELECT * FROM MATERIALI WHERE ID IN ($ids_list)";
$ris_materiali = mysqli_query($conn, $query_materiali);
if (!$ris_materiali) {
    die("<p>Errore nel recupero materiali.</p>");
}

$materiali = [];
while ($row = mysqli_fetch_assoc($ris_materiali)) {
    $materiali[$row['ID']] = $row;
}

// Calcolo totale e controllo quantità e credito
$errore = "";
$totale_ordine = 0.0;

foreach ($acquisti as $id_mat => $qta) {
    if (!isset($materiali[$id_mat])) {
        $errore = "Materiale con ID $id_mat non trovato.";
        break;
    }
    $mat = $materiali[$id_mat];
    if ($qta > $mat['QUANTITA']) {
        $errore = "Quantità richiesta ($qta) per materiale '{$mat['NOME']}' superiore alla disponibilità ({$mat['QUANTITA']}).";
        break;
    }
    $totale_ordine += $qta * $mat['COSTO'];
}

if ($errore === "" && $totale_ordine > $credito) {
    $errore = "Credito insufficiente per completare l'acquisto. Totale: €" . number_format($totale_ordine, 2) . ", credito disponibile: €" . number_format($credito, 2);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Acquisto Multiplo</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body class="pagina-conferma">

<main class="contenuto-conferma">
    <p class="login-stato">Utente loggato: <?php echo htmlspecialchars($utente); ?></p>

    <div class="conferma-contenitore">
        <h1 class="conferma-titolo">Conferma dell'acquisto</h1>

        <?php if ($errore !== ""): ?>
            <p class="messaggio-errore"><?php echo htmlspecialchars($errore); ?></p>
            <form action="domanda.php" method="post" class="modulo-conferma">
                <input type="submit" value="Indietro" class="bottone-conferma" />
            </form>
        <?php else: ?>
            <div class="tabella-contenitore">
                <table class="tabella-conferma">
                    <thead>
                        <tr>
                            <th>Materiale</th>
                            <th>Descrizione</th>
                            <th>Data</th>
                            <th>Quantità</th>
                            <th>Prezzo unitario (€)</th>
                            <th>Subtotale (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($acquisti as $id_mat => $qta): 
                        $mat = $materiali[$id_mat];
                        $subtotale = $qta * $mat['COSTO'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mat['NOME']); ?></td>
                            <td><?php echo htmlspecialchars($mat['DESCRIZIONE']); ?></td>
                            <td><?php echo htmlspecialchars($mat['DATA']); ?></td>
                            <td><?php echo intval($qta); ?></td>
                            <td><?php echo number_format($mat['COSTO'], 2); ?></td>
                            <td><?php echo number_format($subtotale, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="testo-conferma">Credito disponibile: € <?php echo number_format($credito, 2); ?></p>
            <p class="testo-conferma">Totale ordine: € <?php echo number_format($totale_ordine, 2); ?></p>

            <div class="contenitore-pulsanti">
                <form action="fine.php" method="post" class="modulo-conferma">
                    <?php foreach ($acquisti as $id_mat => $qta): ?>
                        <input type="hidden" name="materiale_id[]" value="<?php echo intval($id_mat); ?>" />
                        <input type="hidden" name="quantita[]" value="<?php echo intval($qta); ?>" />
                    <?php endforeach; ?>
                    <input type="submit" value="Conferma acquisto" class="bottone-conferma" />
                </form>

                <form action="domanda.php" method="post" class="modulo-reset">
                    <input type="submit" name="reset" value="Reset" class="bottone-reset" />
                </form>
            </div>

            <p class="link-indietro">
                Vuoi aggiungere altri Articoli? 
                <a href="domanda.php" class="link-bottone">Torna alla pagina domanda</a>
            </p>
        <?php endif; ?>
    </div>
</main>

<footer>
    © 2025 Eco Scambio - Tutti i diritti riservati
</footer>

</body>
</html>

<?php
mysqli_close($connessione);
?>
