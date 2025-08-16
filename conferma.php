<?php
include "controllo_login.php";

accesso_riservato("artigiano");

if (!isset($_SESSION['acquisti']) || !is_array($_SESSION['acquisti']) || count($_SESSION['acquisti']) === 0) {
    echo "<p>Nessun materiale selezionato per l'acquisto.</p>";
    echo '<p><a href="domanda.php">Torna a domanda</a></p>';
    exit;
}

$utente = $_SESSION['nick'];
$acquisti = $_SESSION['acquisti'];

$connessione = mysqli_connect("localhost", "lettore", "P@ssw0rd!", "eco_scambio");
if (!$connessione) {
    echo "<p>Errore di connessione al database.</p>";
    exit;
}

// Prendo ID utente da nick
$query_utente = "SELECT ID FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($connessione, $utente) . "'";
$ris_utente = mysqli_query($connessione, $query_utente);
if (!$ris_utente || mysqli_num_rows($ris_utente) == 0) {
    echo "<p>Utente non trovato.</p>";
    exit;
}
$riga_utente = mysqli_fetch_assoc($ris_utente);
$id_utente = $riga_utente['ID'];

// Prendo credito artigiano
$query_credito = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
$ris_credito = mysqli_query($connessione, $query_credito);
if (!$ris_credito || mysqli_num_rows($ris_credito) == 0) {
    echo "<p>Credito artigiano non trovato.</p>";
    exit;
}
$riga_credito = mysqli_fetch_assoc($ris_credito);
$credito = $riga_credito['CREDIT'];

// Recupero dati materiali per gli ID selezionati
$ids = array_keys($acquisti);
$ids_esc = array_map(function($id) use ($connessione) {
    return intval($id);
}, $ids);

$ids_list = implode(",", $ids_esc);

$query_materiali = "SELECT * FROM MATERIALI WHERE ID IN ($ids_list)";
$ris_materiali = mysqli_query($connessione, $query_materiali);

if (!$ris_materiali) {
    echo "<p>Errore nel recupero materiali.</p>";
    exit;
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

<?php

include "menu.php";


if (!isset($_SESSION['acquisti']) || !is_array($_SESSION['acquisti']) || count($_SESSION['acquisti']) === 0) {
    echo "<p>Nessun materiale selezionato per l'acquisto.</p>";
    echo '<p><a href="domanda.php">Torna a domanda</a></p>';
    exit;
}

$utente = $_SESSION['nick'];
$acquisti = $_SESSION['acquisti'];

$connessione = mysqli_connect("localhost", "lettore", "P@ssw0rd!", "eco_scambio");
if (!$connessione) {
    echo "<p>Errore di connessione al database.</p>";
    exit;
}

$query_utente = "SELECT ID FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($connessione, $utente) . "'";
$ris_utente = mysqli_query($connessione, $query_utente);
if (!$ris_utente || mysqli_num_rows($ris_utente) == 0) {
    echo "<p>Utente non trovato.</p>";
    exit;
}
$riga_utente = mysqli_fetch_assoc($ris_utente);
$id_utente = $riga_utente['ID'];

$query_credito = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
$ris_credito = mysqli_query($connessione, $query_credito);
if (!$ris_credito || mysqli_num_rows($ris_credito) == 0) {
    echo "<p>Credito artigiano non trovato.</p>";
    exit;
}
$riga_credito = mysqli_fetch_assoc($ris_credito);
$credito = $riga_credito['CREDIT'];

$ids = array_keys($acquisti);
$ids_esc = array_map(function($id) {
    return intval($id);
}, $ids);
$ids_list = implode(",", $ids_esc);

$query_materiali = "SELECT * FROM MATERIALI WHERE ID IN ($ids_list)";
$ris_materiali = mysqli_query($connessione, $query_materiali);

if (!$ris_materiali) {
    echo "<p>Errore nel recupero materiali.</p>";
    exit;
}

$materiali = [];
while ($row = mysqli_fetch_assoc($ris_materiali)) {
    $materiali[$row['ID']] = $row;
}

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
        <?php endif; ?>

        <form action="domanda.php" method="post" class="modulo-reset">
            <input type="submit" name="reset" value="Reset" class="bottone-reset" />
        </form>
</div>
<p class="link-indietro">
  Vuoi aggiungere altri Articoli? 
  <a href="domanda.php" class="link-bottone">Torna alla pagina domanda</a>
</p>    </div>

</main>

<footer>
    © 2025 Eco Scambio - Tutti i diritti riservati
</footer>

</body>
</html>

<?php
mysqli_close($connessione);
?>
