<?php

include "controllo_login.php";
include "menu.php";
require_once "connessione.php";  // 👈 connessione centralizzata

$conn = getConnessione(); // Ottieni la connessione al database


accesso_riservato('artigiano');  // Solo artigiani possono entrare

// Recupero nick utente
$nick = isset($_SESSION['nick']) ? $_SESSION['nick'] : "non loggato";
$denaro_disponibile = 0;
$is_artigiano = false;
$userid = null;

// Prendo ID utente e stato artigiano dal DB
if ($nick !== "non loggato") {
    $query_utente = "SELECT ID, ARTIGIANO FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($conn, $nick) . "'";
    $res_utente = mysqli_query($conn, $query_utente);

    if ($res_utente && mysqli_num_rows($res_utente) > 0) {
        $row_utente = mysqli_fetch_assoc($res_utente);
        $userid = $row_utente['ID'];
        $is_artigiano = (bool)$row_utente['ARTIGIANO'];

        // Prendo saldo
        $query_saldo = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($userid);
        $res_saldo = mysqli_query($conn, $query_saldo);
        if ($res_saldo && mysqli_num_rows($res_saldo) > 0) {
            $row_saldo = mysqli_fetch_assoc($res_saldo);
            $denaro_disponibile = $row_saldo['CREDIT'];
        }
    }
}

// Se utente non artigiano o non loggato
if (!$is_artigiano || $userid === null) {
    echo "<p>Attenzione! Questa pagina è riservata agli artigiani registrati. Inserisci le credenziali prima di procedere all’acquisto.</p>";
    echo '<p><a href="login.php">Vai al login</a></p>';
    exit;
}

// $conn = mysqli_connect('localhost', 'lettore', 'P@ssw0rd!', 'eco_scambio');
// if (!$conn) {
//     die("Errore di connessione al DB.");
// }

$conn = getConnessione(); // Ottieni la connessione

$userid = $_SESSION['id'];
$denaro_disponibile = isset($_SESSION['saldo']) ? $_SESSION['saldo'] : 0;
$filtra_data = "";
$messaggio = "";
$selezionati = [];

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['filtra'])) {
        $filtra_data = trim($_POST['filtra_data']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtra_data)) {
            $filtra_data = "";
            $messaggio = "Data filtro non valida, nessun filtro applicato.";
        }
    }
    if (isset($_POST['annulla'])) {
        $selezionati = [];
    }
    if (isset($_POST['acquista'])) {
        $_SESSION['acquisti'] = [];
        if (isset($_POST['quantita']) && is_array($_POST['quantita'])) {
            foreach ($_POST['quantita'] as $id_mat => $qta) {
                $qta_int = intval($qta);
                if ($qta_int > 0) {
                    $_SESSION['acquisti'][$id_mat] = $qta_int;
                }
            }
        }
        header("Location: conferma.php");
        exit;
    }
}

// Query materiali
if ($filtra_data != "") {
    $filtra_data_esc = mysqli_real_escape_string($conn, $filtra_data);
    $sql = "SELECT M.ID, M.NOME, M.DESCRIZIONE, M.DATA, M.QUANTITA, M.COSTO, U.NICK
            FROM MATERIALI M JOIN UTENTI U ON M.ID_UTENTE = U.ID
            WHERE U.ARTIGIANO = 0 AND M.DATA > '$filtra_data_esc'
            ORDER BY M.DATA DESC";
} else {
    $sql = "SELECT M.ID, M.NOME, M.DESCRIZIONE, M.DATA, M.QUANTITA, M.COSTO, U.NICK
            FROM MATERIALI M JOIN UTENTI U ON M.ID_UTENTE = U.ID
            WHERE U.ARTIGIANO = 0
            ORDER BY M.DATA DESC";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Domanda - Eco Scambio</title>
</head>
<body class="pagina-domanda">

<main class="contenuto-principale">
    <div class="barra-utente">
        <p>
        <?php 
        echo "Utente: " . htmlspecialchars($nick) . " | Saldo: " . number_format($denaro_disponibile,2,",",".") . " €"; 
        ?>
        </p>
    </div>

    <div class="contenuto-secondario">
        <h1 class="domanda-titolo">Domanda: acquisto materiali</h1>

        <?php if ($messaggio != "") { echo "<p class='messaggio-errore'>" . htmlspecialchars($messaggio) . "</p>"; } ?>

        <form method="post" action="domanda.php" class="form-domanda">
            <div class="filtro-data">
                <label for="filtra_data">Filtro data inserimento (mostra solo materiali dopo):</label><br/>
                <input type="date" id="filtra_data" name="filtra_data" value="<?php echo htmlspecialchars($filtra_data); ?>" />
                <input type="submit" name="filtra" value="Applica filtro" />
            </div>

            <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <div class="tabella-materiali">
                <table class="tabella-domanda">
                    <thead>
                        <tr>
                            <th>Nome</th><th>Descrizione</th><th>Data</th><th>Quantità disponibile</th><th>Costo (€)</th><th>Acquista</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['NOME']); ?></td>
                            <td><?php echo htmlspecialchars($row['DESCRIZIONE']); ?></td>
                            <td><?php echo htmlspecialchars($row['DATA']); ?></td>
                            <td><?php echo htmlspecialchars($row['QUANTITA']); ?></td>
                            <td><?php echo number_format($row['COSTO'], 2, ',', ''); ?></td>
                            <td>
                                <input class="input-quantita" type="number" name="quantita[<?php echo $row['ID']; ?>]" min="0" max="<?php echo $row['QUANTITA']; ?>" value="0" />
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="bottoni-domanda">
                <input type="submit" name="annulla" value="Annulla" />
                <input type="submit" name="acquista" value="Acquista" />
            </div>
            <?php } else { ?>
                <p class="nessun-materiale">Nessun materiale disponibile.</p>
            <?php } ?>
        </form>

        <p class="link-home"><a href="home.php">Torna alla home</a></p>
    </div>
</main>

<footer>
    © 2025 Eco Scambio - Tutti i diritti riservati
</footer>

</body>
</html>

<?php
mysqli_close($conn);
?>
