<?php
include('controllo_login.php');
include('connessione.php'); // connessione al database

$saldo = 0;
$nick = "non loggato";

if (isset($_SESSION['nick'])) {
    $nick = $_SESSION['nick'];

    // Recupero ID utente
    $query = "SELECT ID FROM UTENTI WHERE NICK = '" . mysqli_real_escape_string($conn, $nick) . "'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id_utente = $row['ID'];

        // Recupero saldo aggiornato dal database
        $query_saldo = "SELECT CREDIT FROM DATI_ARTIGIANI WHERE ID_UTENTE = " . intval($id_utente);
        $result_saldo = mysqli_query($conn, $query_saldo);

        if ($result_saldo && mysqli_num_rows($result_saldo) > 0) {
            $row_saldo = mysqli_fetch_assoc($result_saldo);
            $saldo = $row_saldo['CREDIT'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Eco Scambio - Home</title>
    <link rel="stylesheet" href="stile.css" />
</head>
<body>

    <?php include("menu.php"); ?>

    <main class="contenuto">

        <!-- Login stato in alto a destra -->
        <p class="login-stato">
            Utente: <?php echo htmlspecialchars($nick); ?> | Saldo: €<?php echo number_format($saldo, 2); ?>
            <?php if ($nick != "non loggato") { ?>
                | <a href="logout.php">Logout</a>
            <?php } else { ?>
                | <a href="login.php">Login</a>
            <?php } ?>
        </p>

        <!-- Contenuto principale -->
        <div class="home-contenitore">
            <h1 class="home-titolo">Eco</h1>
            <h1 class="home-titolo">Scambio</h1>
            <p>Promuoviamo il riuso di materiali tra aziende e artigiani</p> 
            <p>per un'economia più sostenibile.</p>
        </div>

        <div class="home-img">
            <img src="immagineHome.jpeg" alt="immagine sostenibilità" />
        </div>

    </main>

    <footer>
        © 2025 Eco Scambio - Tutti i diritti riservati

        On branch develop
    </footer>

</body>
</html>
