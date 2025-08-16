<?php
include('controllo_login.php');

if (isset($_SESSION['nick'])) {
    $nick = $_SESSION['nick'];
    $saldo = isset($_SESSION['saldo']) ? $_SESSION['saldo'] : 0;
} else {
    $nick = "non loggato";
    $saldo = 0;
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

        <!-- PRIMA RIGA: login stato in alto a destra -->
        <p class="login-stato">
            Utente: <?php echo htmlspecialchars($nick); ?> | Saldo: €<?php echo number_format($saldo, 2); ?>
            <?php if ($nick != "non loggato") { ?>
                | <a href="logout.php">Logout</a>
            <?php } else { ?>
                | <a href="login.php">Login</a>
            <?php } ?>
        </p>

        <!-- SECONDA RIGA: testo a sinistra -->
        <div class="home-contenitore">
            <h1 class="home-titolo">Eco</h1>
            <h1 class="home-titolo">Scambio</h1>
            <p>Promuoviamo il riuso di materiali tra aziende e artigiani</p> 
            <p>per un'economia più sostenibile.</p>
        </div>

        <!-- SECONDA RIGA: immagine a destra -->
        <div class="home-img">
            <img src="immagineHome.jpeg" alt="immagine sostenibilità" />
        </div>

    </main>

    <footer>
        © 2025 Eco Scambio - Tutti i diritti riservati
    </footer>

</body>
</html>
