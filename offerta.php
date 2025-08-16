<?php
include "controllo_login.php";
include('connessione.php'); 
accesso_riservato('azienda');  // Solo aziende possono entrare
include "menu.php";

// Controllo accesso: solo utenti autenticati e azienda (artigiano = false)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['artigiano'] == true) {
    echo "<p>Accesso negato. Questa pagina è riservata alle aziende registrate.</p>";
    echo '<p><a href="login.php">Vai al login</a></p>';
    exit;
}

$userid = $_SESSION['id'];

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
    $errors = [];

    // Validazioni
    if (!valida_nome($nome)) $errors[] = "Nome non valido: deve essere tra 10 e 40 caratteri, solo lettere, numeri e spazi.";
    if (strlen($descrizione) > 250) $errors[] = "Descrizione troppo lunga (max 250 caratteri).";
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $errors[] = "Data non valida (formato aaaa-mm-gg).";
    if (!ctype_digit($quantita) || intval($quantita) < 0) $errors[] = "Quantità deve essere un numero intero positivo.";
    if (!is_numeric($costo) || !valida_costo(floatval($costo))) $errors[] = "Costo non valido: deve essere multiplo di 0.05 euro.";

    if (empty($errors)) {
        $nome_esc = mysqli_real_escape_string($conn, $nome);
        $descrizione_esc = mysqli_real_escape_string($conn, $descrizione);
        $data_esc = mysqli_real_escape_string($conn, $data);
        $quantita_int = intval($quantita);
        $costo_float = floatval($costo);

        // Inserimento materiale con campo attivo=1
        $sql = "INSERT INTO MATERIALI (NOME, DESCRIZIONE, DATA, QUANTITA, COSTO, ID_UTENTE, ATTIVO)
                VALUES ('$nome_esc', '$descrizione_esc', '$data_esc', $quantita_int, $costo_float, $userid, 1)";
        if (mysqli_query($conn, $sql)) {
            header("Location: lista.php");
            exit;
        } else {
            $errors[] = "Errore nell'inserimento del materiale.";
        }
    }
}

// Modifica materiale esistente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifica'])) {
    $id_materiale = intval($_POST['id_materiale']);
    $descrizione = trim($_POST['descrizione_mod']);
    $quantita = trim($_POST['quantita_mod']);
    $costo = trim($_POST['costo_mod']);
    $errors_mod = [];

    if (strlen($descrizione) > 250) $errors_mod[] = "Descrizione troppo lunga (max 250 caratteri).";
    if (!ctype_digit($quantita) || intval($quantita) < 0) $errors_mod[] = "Quantità deve essere un numero intero positivo.";
    if (!is_numeric($costo) || !valida_costo(floatval($costo))) $errors_mod[] = "Costo non valido: deve essere multiplo di 0.05 euro.";

    if (empty($errors_mod)) {
        $descrizione_esc = mysqli_real_escape_string($conn, $descrizione);
        $quantita_int = intval($quantita);
        $costo_float = floatval($costo);

        $check_sql = "SELECT ID FROM MATERIALI WHERE ID = $id_materiale AND ID_UTENTE = $userid";
        $check_res = mysqli_query($conn, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $update_sql = "UPDATE MATERIALI 
                           SET DESCRIZIONE='$descrizione_esc', QUANTITA=$quantita_int, COSTO=$costo_float, ATTIVO=1 
                           WHERE ID=$id_materiale";
            if (mysqli_query($conn, $update_sql)) {
                header("Location: lista.php");
                exit;
            } else {
                $errors_mod[] = "Errore durante l'aggiornamento.";
            }
        } else {
            $errors_mod[] = "Materiale non trovato o non autorizzato.";
        }
    }

    if (!empty($errors_mod)) $errors = array_merge($errors, $errors_mod);
}

// Recupero materiali dell'utente per visualizzazione nella tabella
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

<main class="contenuto-principale">

  <div class="barra-utente">
    <?php 
      echo isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true 
        ? "Utente: " . htmlspecialchars($_SESSION['nick']) . " | Saldo: 0,00 €" 
        : "Non loggato | Saldo: 0,00 €"; 
    ?>
  </div>

  <div class="contenuto-secondario">
    <h1 class="domanda-titolo">Offerta: inserimento e modifica materiali</h1>

    <?php if (!empty($errors)) { ?>
      <div class="messaggio-errore">
        <ul>
          <?php foreach ($errors as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?>
        </ul>
      </div>
    <?php } ?>

    <!-- Tabella materiali -->
    <?php if ($res_materiali && mysqli_num_rows($res_materiali) > 0) { ?>
      <table class="lista-tabella">
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
                  <textarea name="descrizione_mod" class="descrizione-mod" maxlength="250"><?php echo htmlspecialchars($row['DESCRIZIONE']); ?></textarea>
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
    <?php } else { ?>
      <p class="nessun-materiale">Nessun materiale inserito finora.</p>
    <?php } ?>

    <!-- Form inserimento -->
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
        <label for="quantita">Quantità:</label><br/>
        <input type="number" id="quantita" name="quantita" min="0" required />
      </p>
      <p>
        <label for="costo">Costo (€) multiplo di 0.05:</label><br/>
        <input type="text" id="costo" name="costo" required />
      </p>
      <p>
        <input type="submit" name="inserisci" value="Inserisci materiale" />
      </p>
    </form>

  </div>
</main>

</body>
</html>
