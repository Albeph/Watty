<!-- filepath: /home/tap/OneDrive/Uni/tap/tests/_utility/rasp_file/configurazione/test.php -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php" class="top-left-button"><button>HOME</button></a>
    <div class="container">
        <h2>Aggiungi Login</h2>
        <form id="loginForm" method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <label for="service">Scegli un servizio:</label>
            <select id="service" name="service" required>
                <option value="" disabled selected>Seleziona un servizio</option>
                <?php
                $services = ['tapo', 'meross'];
                foreach ($services as $service) {
                    $filePath = __DIR__ . "/connections/{$service}-connect/pass.csv";
                    if (!file_exists($filePath)) {
                        echo "<option value=\"$service\">" . ucfirst($service) . "</option>";
                    }
                }
                ?>
            </select><br><br>
            <button type="submit">Login</button>
        </form>
        <h2>Elimina Login</h2>
        <form id="deleteForm" method="POST" action="">
            <label for="deleteService">Scegli un servizio:</label>
            <select id="deleteService" name="deleteService" required>
                <option value="" disabled selected>Seleziona un servizio</option>
                <?php
                $services = ['tapo', 'meross'];
                foreach ($services as $service) {
                    $filePath = __DIR__ . "/connections/{$service}-connect/pass.csv";
                    if (file_exists($filePath)) {
                        echo "<option value=\"$service\">" . ucfirst($service) . "</option>";
                    }
                }
                ?>
            </select><br><br>
            <button type="submit" name="delete">Elimina</button>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const services = ['tapo', 'meross'];
            services.forEach(service => {
                fetch(`./connections/${service}-connect/pass.csv`)
                    .then(response => {
                        if (response.ok) {
                            const option = document.querySelector(`option[value="${service}"]`);
                            if (option) {
                                option.classList.add('hidden');
                            }
                        }
                    });
            });
        });
    </script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['service'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $service = $_POST['service'];

        $csvContent = "email,pass\n$email,$password\n";
        $dirPath = __DIR__ . "/connections/{$service}-connect";
        $filePath = $dirPath . "/pass.csv";

        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        if (file_put_contents($filePath, $csvContent) !== false) {
            echo "<script>alert('File salvato con successo!');</script>";
        } else {
            echo "<script>alert('Errore nel salvataggio del file.');</script>";
        }
    }

    if (isset($_POST['deleteService'])) {
        $service = $_POST['deleteService'];
        $filePath = __DIR__ . "/connections/{$service}-connect/pass.csv";

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                echo "<script>alert('File eliminato con successo!');</script>";
            } else {
                echo "<script>alert('Errore nell'eliminazione del file.');</script>";
            }
        } else {
            echo "<script>alert('File non trovato.');</script>";
        }
    }
}
?>