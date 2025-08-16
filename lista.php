<?php
session_start();
include "connessione.php"; // usa la connessione già funzionante ($conn)
include "menu.php";

$conn = getConnessione();

// Recupero nick utente
$nick = isset($_SESSION['nick']) ? $_SESSION['nick'] : "non loggato";
$saldo = 0;
$is_artigiano = false;

// Se utente loggato, prendo saldo aggiornato dal DB
if ($nick !== "non loggato") {
    $query_id = "SELECT ID, ARTIGIANO FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($conn, $nick) . "'";
    $res_id = mysqli_query($conn, $query_id);

    if ($res_id && mysqli_num_rows($res_id) > 0) {
        $row_id = mysqli_fetch_assoc($res_id);
        $id_utente = $row_id['ID'];
        $is_artigiano = (bool)$row_id['ARTIGIANO'];

        // Prendo saldo
        $query_saldo = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
        $res_saldo = mysqli_query($conn, $query_saldo);
        if ($res_saldo && mysqli_num_rows($res_saldo) > 0) {
            $row_saldo = mysqli_fetch_assoc($res_saldo);
            $saldo = $row_saldo['CREDIT'];
        }
    }
}

// Filtro per data
$filtro_data = null;
if (isset($_SESSION['filtro_data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_SESSION['filtro_data'])) {
    $filtro_data = $_SESSION['filtro_data'];
}

// Query materiali
$query = "SELECT NOME, DESCRIZIONE, QUANTITA, COSTO, DATA FROM MATERIALI";
if ($filtro_data !== null) {
    $data_esc = mysqli_real_escape_string($conn, $filtro_data);
    $query .= " WHERE DATA >= '$data_esc'";
}
$query .= " ORDER BY DATA DESC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Errore nel recupero dei materiali: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Eco Scambio - Materiali</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body class="lista">

    <?php include "menu.php"; ?>

    <main class="contenuto-lista">

        <!-- login stato in alto a destra -->
        <p class="login-stato">
            Utente: <?php echo htmlspecialchars($nick); ?> | Saldo: €<?php echo number_format($saldo, 2); ?>
            <?php if ($nick != "non loggato") { ?>
                | <a href="logout.php">Logout</a>
            <?php } else { ?>
                | <a href="login.php">Login</a>
            <?php } ?>
        </p>

        <!-- contenuto principale tabella -->
        <div class="lista-contenitore">

            <h1 class="lista-titolo">Materiali disponibili</h1>

            <?php if ($filtro_data !== null) { ?>
                <p class="filtro-attivo">Filtro attivo: solo materiali inseriti dal <?php echo htmlspecialchars($filtro_data); ?> in poi.</p>
            <?php } ?>

            <?php if (mysqli_num_rows($result) > 0) { ?>
                <table class="lista-tabella">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <?php if ($is_artigiano) { ?>
                                <th>Quantità</th>
                                <th>Costo (€)</th>
                            <?php } ?>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['NOME']); ?></td>
                                <td><?php echo htmlspecialchars($row['DESCRIZIONE']); ?></td>
                                <?php if ($is_artigiano) { ?>
                                    <td><?php echo htmlspecialchars($row['QUANTITA']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($row['COSTO'], 2, ',', '')); ?> €</td>
                                <?php } ?>
                                <td><?php echo htmlspecialchars($row['DATA']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p class="nessun-materiale">Nessun materiale disponibile.</p>
            <?php } ?>

            <p><a class="link-home" href="home.php">Torna alla home</a></p>

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
