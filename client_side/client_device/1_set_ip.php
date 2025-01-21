<!DOCTYPE html>
<html>
<head>
    <title>Inserisci Indirizzo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<a href="index.php" class="top-left-button"><button>HOME</button></a>
    <div class="container">
        <h1>Inserimento indirizzo per l'invio dei file di Mapping</h1>
        <?php
        session_start();
        $file = './indirizzo_ip.txt';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['reset'])) {
                if (file_exists($file)) {
                    unlink($file);
                    $_SESSION['message'] = 'File eliminato con successo!';
                }
            } else {
                $ip = $_POST['ip'];
                file_put_contents($file, $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
                $_SESSION['message'] = 'Indirizzo salvato con successo!';
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (isset($_SESSION['message'])) {
            echo "<script type='text/javascript'>alert('" . $_SESSION['message'] . "');</script>";
            unset($_SESSION['message']);
        }

        if (file_exists($file)) {
            ?>
            <p>Indirizzo IP salvato: <?php echo file_get_contents($file); ?></p>
            <form method="post" action="">
                <input type="submit" name="reset" value="Modifica Indirizzo IP">
            </form>
            </form>
            <form action="2_upload_mapping.php" method="get">
                <button type="submit">Avanti</button>
            </form>
            <?php
        } else {
            echo '<form method="post" action="">
                    <label for="ip">Indirizzo:</label>
                    <input type="text" id="ip" name="ip" required>
                    <input type="submit" value="Invia">
                  </form>';
        }
        ?>
    </div>
</body>
</html>