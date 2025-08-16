<?php
session_start();
include('connessione.php'); 

// Mostra errori per debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = "";
$user = "";
$pwd = "";
$ricordami = false;

// Precompila dal cookie "ricordami"
if (isset($_COOKIE['eco_user']) && isset($_COOKIE['eco_pwd'])) {
    $user = $_COOKIE['eco_user'];
    $pwd  = $_COOKIE['eco_pwd'];
    $ricordami = true;
}

// Se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = isset($_POST['user']) ? trim($_POST['user']) : "";
    $pwd  = isset($_POST['pwd'])  ? trim($_POST['pwd'])  : "";
    $ricordami = isset($_POST['ricordami']);

    // Prepara query sicura
    $stmt = mysqli_prepare($conn, "SELECT id, password, artigiano FROM utenti WHERE nick = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_utente, $password_db, $artigiano);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $found = false;
    }

    if (!$found || $pwd !== $password_db) {
        $error_message = "Utente o password errati, riprova.";
    } else {
        // Login riuscito
        session_regenerate_id(true);
        $_SESSION['loggedin']  = true;
        $_SESSION['nick']      = $user;
        $_SESSION['id']        = $id_utente;
        $_SESSION['artigiano'] = ((int)$artigiano === 1);

        // Saldo: se artigiano leggo la tabella dati_artigiani
        if ($_SESSION['artigiano']) {
            $saldo = 0;
            $stmt2 = mysqli_prepare($conn, "SELECT credit FROM dati_artigiani WHERE id_utente = ? LIMIT 1");
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "i", $id_utente);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_bind_result($stmt2, $saldo_utente);
                if (mysqli_stmt_fetch($stmt2)) {
                    $saldo = (float)$saldo_utente;
                }
                mysqli_stmt_close($stmt2);
            }
            $_SESSION['saldo'] = $saldo;
        } else {
            $_SESSION['saldo'] = 0;
        }

        // Cookie ricordami
        if ($ricordami) {
            setcookie("eco_user", $user, time() + 72*3600, "/");
            setcookie("eco_pwd",  $pwd,  time() + 72*3600, "/");
        } else {
            setcookie("eco_user", "", time() - 3600, "/");
            setcookie("eco_pwd",  "", time() - 3600, "/");
        }

        // Redirect in base al tipo utente
        if ($_SESSION['artigiano']) {
            header("Location: domanda.php");
        } else {
            header("Location: offerta.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Eco Scambio</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <?php include("menu.php"); ?>

    <h1>Login</h1>
    <?php if ($error_message !== ""): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>Username: <input type="text" name="user" value="<?php echo htmlspecialchars($user); ?>" required></label><br>
        <label>Password: <input type="password" name="pwd" value="<?php echo htmlspecialchars($pwd); ?>" required></label><br>
        <label><input type="checkbox" name="ricordami" <?php echo $ricordami ? "checked" : ""; ?>> Ricordami</label><br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
