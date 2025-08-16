<?php

include "controllo_login.php";
accesso_riservato('azienda');  // Solo aziende possono entrare
include "menu.php";

// Controllo accesso: solo utenti autenticati e azienda (artigiano = false)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['artigiano'] == true) {
    echo "<p>Accesso negato. Questa pagina è riservata alle aziende registrate.</p>";
    echo '<p><a href="login.php">Vai al login</a></p>';
    exit;
}

// Connessione DB con utente 'modificatore'
$conn = mysqli_connect('localhost', 'marco123', 'mk@84L$GG!', 'eco_scambio');
if (!$conn) {
    die("Errore di connessione al DB.");
}

$userid = $_SESSION['id'];
$errors = [];
$success_message = "";

// Funzione validazione nome
function valida_nome($nome) {
    if (strlen($nome) < 10 || strlen($nome) > 40) return false;
    if (!preg_match('/^[a-zA-Z0-9 ]+$/', $nome)) return false;
    return true;
}

// Funzione validazione costo (multiplo di 0.05)
function valida_costo($costo) {
    return (fmod($costo * 100, 5) == 0);
}

// Inserimento nuovo materiale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inserisci'])) {
    $nome = trim($_POST['nome']);
    $descrizione = trim($_POST['descrizione']);
    $data = trim($_POST['data']);
    $quantita = trim($_POST['quantita']);
    $costo = trim($_POST['costo']);

    // Validazioni
    if (!valida_nome($nome)) {
        $errors[] = "Nome non valido: deve essere tra 10 e 40 caratteri, solo lettere, numeri e spazi.";
    }
    if (strlen($descrizione) > 250) {
        $errors[] = "Descrizione troppo lunga (max 250 caratteri).";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        $errors[] = "Data non valida (formato aaaa-mm-gg).";
    }
    if (!ctype_digit($quantita) || intval($quantita) < 0) {
        $errors[] = "Quantità deve essere un numero intero positivo.";
    }
    if (!is_numeric($costo) || !valida_costo(floatval($costo))) {
        $errors[] = "Costo non valido: deve essere multiplo di 0.05 euro.";
    }

    if (empty($errors)) {
        $nome_esc = mysqli_real_escape_string($conn, $nome);
        $descrizione_esc = mysqli_real_escape_string($conn, $descrizione);
        $data_esc = mysqli_real_escape_string($conn, $data);
        $quantita_int = intval($quantita);
        $costo_float = floatval($costo);

        $sql = "INSERT INTO MATERIALI (NOME, DESCRIZIONE, DATA, QUANTITA, COSTO, ID_UTENTE)
                VALUES ('$nome_esc', '$descrizione_esc', '$data_esc', $quantita_int, $costo_float, $userid)";
        if (mysqli_query($conn, $sql)) {
            $success_message = "Materiale inserito correttamente.";
        } else {
            $errors[] = "Errore nell'inserimento del materiale.";
        }
    }
}

// Modifica materiale esistente (descrizione, quantità, costo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifica'])) {
    $id_materiale = intval($_POST['id_materiale']);
    $descrizione = trim($_POST['descrizione_mod']);
    $quantita = trim($_POST['quantita_mod']);
    $costo = trim($_POST['costo_mod']);
    $errors_mod = [];

    if (strlen($descrizione) > 250) {
        $errors_mod[] = "Descrizione troppo lunga (max 250 caratteri).";
    }
    if (!ctype_digit($quantita) || intval($quantita) < 0) {
        $errors_mod[] = "Quantità deve essere un numero intero positivo.";
    }
    if (!is_numeric($costo) || !valida_costo(floatval($costo))) {
        $errors_mod[] = "Costo non valido: deve essere multiplo di 0.05 euro.";
    }

    if (empty($errors_mod)) {
        $descrizione_esc = mysqli_real_escape_string($conn, $descrizione);
        $quantita_int = intval($quantita);
        $costo_float = floatval($costo);

        // Controllo che materiale appartenga a utente
        $check_sql = "SELECT ID FROM MATERIALI WHERE ID = $id_materiale AND ID_UTENTE = $userid";
        $check_res = mysqli_query($conn, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $update_sql = "UPDATE MATERIALI SET DESCRIZIONE='$descrizione_esc', QUANTITA=$quantita_int, COSTO=$costo_float WHERE ID=$id_materiale";
            if (mysqli_query($conn, $update_sql)) {
                $success_message = "Materiale aggiornato correttamente.";
            } else {
                $errors_mod[] = "Errore durante l'aggiornamento.";
            }
        } else {
            $errors_mod[] = "Materiale non trovato o non autorizzato.";
        }
    }

    if (!empty($errors_mod)) {
        $errors = array_merge($errors, $errors_mod);
    }
}

// Recupero materiali dell'utente azienda
$sql_materiali = "SELECT * FROM MATERIALI WHERE ID_UTENTE = $userid ORDER BY DATA DESC";
$res_materiali = mysqli_query($conn, $sql_materiali);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Offerta - Eco Scambio</title>
</head>
<body>

<table style="width:100%;"><tr><td style="text-align:right;">
<?php 
echo isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true 
    ? "Utente: " . htmlspecialchars($_SESSION['nick']) . " | Saldo: 0,00 €" 
    : "Non loggato | Saldo: 0,00 €"; 
?>
</td></tr></table>

<h1>Offerta: inserimento e modifica materiali</h1>

<?php
if (!empty($errors)) {
    echo "<ul style='color:red;'>";
    foreach ($errors as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo "</ul>";
}
if ($success_message !== "") {
    echo "<p style='color:green;'>" . htmlspecialchars($success_message) . "</p>";
}
?>

<h2>Elenco materiali della tua azienda</h2>
<?php if ($res_materiali && mysqli_num_rows($res_materiali) > 0) { ?>
<table border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Nome</th><th>Descrizione</th><th>Data</th><th>Quantità</th><th>Costo (€)</th><th>Modifica</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = mysqli_fetch_assoc($res_materiali)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['NOME']); ?></td>
            <td>
                <form method="post" action="offerta.php">
                    <input type="hidden" name="id_materiale" value="<?php echo $row['ID']; ?>" />
                    <input type="text" name="descrizione_mod" maxlength="250" value="<?php echo htmlspecialchars($row['DESCRIZIONE']); ?>" />
            </td>
            <td><?php echo htmlspecialchars($row['DATA']); ?></td>
            <td>
                    <input type="text" name="quantita_mod" size="5" value="<?php echo htmlspecialchars($row['QUANTITA']); ?>" />
            </td>
            <td>
                    <input type="text" name="costo_mod" size="6" value="<?php echo htmlspecialchars(number_format($row['COSTO'],2,'.','')); ?>" />
            </td>
            <td>
                    <input type="submit" name="modifica" value="Modifica" />
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } else {
    echo "<p>Nessun materiale inserito finora.</p>";
} ?>

<h2>Inserisci nuovo materiale</h2>
<form method="post" action="offerta.php">
    <p>
        <label for="nome">Nome (10-40 caratteri, lettere/numeri/spazi):</label><br/>
        <input type="text" id="nome" name="nome" maxlength="40" required />
    </p>
    <p>
        <label for="descrizione">Descrizione (max 250 caratteri):</label><br/>
        <textarea id="descrizione" name="descrizione" maxlength="250"></textarea>
    </p>
    <p>
        <label for="data">Data inserimento (aaaa-mm-gg):</label><br/>
        <input type="date" id="data" name="data" required />
    </p>
    <p>
        <label for="quantita">Quantità (numero intero):</label><br/>
        <input type="number" id="quantita" name="quantita" min="0" required />
    </p>
    <p>
        <label for="costo">Costo unitario (€), multipli di 0.05:</label><br/>
        <input type="text" id="costo" name="costo" required />
    </p>
    <p><input type="submit" name="inserisci" value="Inserisci nuovo materiale" /></p>
</form>

<p><a href="home.php">Torna alla home</a></p>
<footer>
        © 2025 Eco Scambio - Tutti i diritti riservati
    </footer>

</body>
</html>

<?php
mysqli_close($conn);
?>
