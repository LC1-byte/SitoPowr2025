<link rel="stylesheet" href="stile.css">
<nav class="menu">
<ul class="menu">
    <li><a href="home.php">Home</a></li>
    <li><a href="lista.php">Lista</a></li>
    <li><a href="domanda.php">Domanda</a></li>
    <li><a href="registrazione.php">Registra</a></li>
    <li><a href="conferma.php">Conferma</a></li>
     <li>
        <?php
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            echo '<a href="login.php">Login</a>';
        } else {
            echo '<a href="#" class="non-attivo">Login</a>';
        }
        ?>
    </li>
    <li>
        <?php
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['artigiano'] == false) {
            echo '<a href="offerta.php">Offerta</a>';
        } else {
            echo '<a href="#" class="non-attivo">Offerta</a>';
        }
        ?>
    </li>
    <li>
        <?php
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            echo '<a href="logout.php">Logout</a>';
        } else {
            echo '<a href="#" class="non-attivo">Logout</a>';
        }
        ?>
    </li>
   
</ul>
</nav>
