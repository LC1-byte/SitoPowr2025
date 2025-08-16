<?php
include "controllo_login.php";
include('connessione.php'); 
accesso_riservato("artigiano");

if (!isset($_POST['materiale_id']) || !isset($_POST['quantita'])) {
    echo "<p class='messaggio-errore'>Errore: dati mancanti per completare l'acquisto.</p>";
    echo '<p><a href="domanda.php" class="link-ritorno">Torna a domanda</a></p>';
    exit;
}

$materiale_id = $_POST['materiale_id'];
$quantita = $_POST['quantita'];

if (!is_array($materiale_id) || !is_array($quantita) || count($materiale_id) !== count($quantita)) {
    echo "<p class='messaggio-errore'>Errore: dati non validi.</p>";
    echo '<p><a href="domanda.php" class="link-ritorno">Torna a domanda</a></p>';
    exit;
}

$utente = $_SESSION['nick'];

$conn = mysqli_connect("localhost", "modificatore", "Str0ng#Admin9", "eco_scambio");
if (!$conn) {
    echo "<p class='messaggio-errore'>Errore di connessione al database.</p>";
    exit;
}

$query_utente = "SELECT ID FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($conn, $utente) . "'";
$ris_utente = mysqli_query($conn, $query_utente);
if (!$ris_utente || mysqli_num_rows($ris_utente) == 0) {
    echo "<p class='messaggio-errore'>Utente non trovato.</p>";
    exit;
}
$riga_utente = mysqli_fetch_assoc($ris_utente);
$id_utente = $riga_utente['ID'];

$query_credito = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
$ris_credito = mysqli_query($conn, $query_credito);
if (!$ris_credito || mysqli_num_rows($ris_credito) == 0) {
    echo "<p class='messaggio-errore'>Credito artigiano non trovato.</p>";
    exit;
}
$riga_credito = mysqli_fetch_assoc($ris_credito);
$credito = $riga_credito['CREDIT'];

$ids_esc = array_map('intval', $materiale_id);
$ids_list = implode(",", $ids_esc);

$query_materiali = "SELECT * FROM MATERIALI WHERE ID IN ($ids_list)";
$ris_materiali = mysqli_query($conn, $query_materiali);

if (!$ris_materiali) {
    echo "<p class='messaggio-errore'>Errore nel recupero materiali.</p>";
    exit;
}

$materiali = [];
while ($row = mysqli_fetch_assoc($ris_materiali)) {
    $materiali[$row['ID']] = $row;
}

$totale_ordine = 0.0;
for ($i = 0; $i < count($materiale_id); $i++) {
    $id_mat = intval($materiale_id[$i]);
    $qta = intval($quantita[$i]);

    if (!isset($materiali[$id_mat])) {
        echo "<p class='messaggio-errore'>Errore: materiale con ID $id_mat non trovato.</p>";
        exit;
    }

    if ($qta > $materiali[$id_mat]['QUANTITA']) {
        echo "<p class='messaggio-errore'>Errore: quantità richiesta ($qta) per materiale '" . 
             htmlspecialchars($materiali[$id_mat]['NOME']) . "' superiore alla disponibilità ({$materiali[$id_mat]['QUANTITA']}).</p>";
        exit;
    }

    $totale_ordine += $qta * $materiali[$id_mat]['COSTO'];
}

if ($totale_ordine > $credito) {
    echo "<p class='messaggio-errore'>Errore: credito insufficiente per completare l'acquisto.</p>";
    exit;
}

mysqli_begin_transaction($conn);

try {
    for ($i = 0; $i < count($materiale_id); $i++) {
        $id_mat = intval($materiale_id[$i]);
        $qta = intval($quantita[$i]);

        $query_update_mat = "UPDATE MATERIALI SET QUANTITA = QUANTITA - $qta WHERE ID = $id_mat AND QUANTITA >= $qta";
        $res_update = mysqli_query($conn, $query_update_mat);

        if (!$res_update || mysqli_affected_rows($conn) === 0) {
            throw new Exception("Errore aggiornamento quantità per materiale ID $id_mat");
        }
    }

    $query_update_credito = "UPDATE DATI_ARTIGIANI SET CREDIT = CREDIT - $totale_ordine WHERE ID_UTENTE = $id_utente AND CREDIT >= $totale_ordine";
    $res_credito = mysqli_query($conn, $query_update_credito);

    if (!$res_credito || mysqli_affected_rows($conn) === 0) {
        throw new Exception("Errore aggiornamento credito artigiano");
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<p class='messaggio-errore'>Errore durante l'acquisto: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<p><a href="domanda.php" class="link-ritorno">Torna a domanda</a></p>';
    mysqli_close($conn);
    exit;
}

$query_materiali_agg = "SELECT ID, NOME, QUANTITA FROM MATERIALI WHERE ID IN ($ids_list)";
$ris_materiali_agg = mysqli_query($conn, $query_materiali_agg);
if (!$ris_materiali_agg) {
    echo "<p class='messaggio-errore'>Errore nel recupero materiali aggiornati.</p>";
    mysqli_close($conn);
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Acquisto completato</title>
<link rel="stylesheet" href="stile.css">
</head>
<body class="body-acquisto">

<div class="contenuto-acquisto">
    <h1 class="titolo-principale">Acquisto effettuato correttamente</h1>

    <p class="testo-base">Il tuo acquisto è stato registrato con successo.</p>

    <h2 class="sottotitolo">Quantità disponibili aggiornate dopo l'acquisto:</h2>

    <table class="tabella-materiali">
        <thead>
            <tr><th>Materiale</th><th>Quantità disponibile</th></tr>
        </thead>
        <tbody>
            <?php while ($mat = mysqli_fetch_assoc($ris_materiali_agg)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mat['NOME']); ?></td>
                    <td><?php echo intval($mat['QUANTITA']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p><a class="link-home" href="home.php">Torna alla home</a></p>
</div>
<footer>
        © 2025 Eco Scambio - Tutti i diritti riservati
    </footer>

</body>
</html>