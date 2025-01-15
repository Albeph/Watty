<!DOCTYPE html>
<html>
<head>
    <title>Upload file mapping</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<a href="index.php" class="top-left-button"><button>HOME</button></a>
    <div class="container">
<?php
$curl = curl_init();

$file1Exists = file_exists('uploads/mapping_elettr_conn.csv');
$file2Exists = file_exists('uploads/zone_room.csv');

$ip_file_path = './indirizzo_ip.txt';

if (!file_exists($ip_file_path)) {
    echo "Non c'è un indirizzo IP impostato. <a href='1_set_ip.php'>Imposta un indirizzo IP</a>";
    ?>
    <form action="3_start_connections.php" method="get">
                <button type="submit">Avanti</button>
            </form>
    <?php
    exit;
}

$ip_address = trim(file_get_contents($ip_file_path));

curl_setopt_array($curl, [
    CURLOPT_URL => "http://$ip_address",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
]);

$response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);
curl_close($curl);

if (strpos($curl_error, 'Received HTTP/0.9 when not allowed') === false) {
    echo "<h1>Carica i tuoi file di mapping</h1>";
    echo "<div>Operazione già completata o server temporaneamente non raggiungibile</div>";
    ?>
    
    <form action="#" method="POST">
        <button type="submit">Ricarica</button>
    </form>
    <form action="3_start_connections.php" method="get">
        <button type="submit">Avanti</button>
    </form>

    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadDir = 'uploads/';
    $errors = [];

    // Check if the upload directory exists, if not, create it
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle the first file upload
    if (isset($_FILES['file1'])) {
        $file1 = $_FILES['file1'];
        $file1Path = $uploadDir . basename($file1['name']);

        if ($file1['name'] === 'mapping_elettr_conn.csv') {
            if (move_uploaded_file($file1['tmp_name'], $file1Path)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                echo "File 1 uploaded successfully.<br>";
            } else {
                $errors[] = "Failed to upload File 1.";
            }
        } else {
            $errors[] = "File 1 must be named mapping_elettr_conn.csv.";
        }
    } elseif (!$file1Exists) {
        $errors[] = "File 1 is not set.";
    }

    // Handle the second file upload
    if (isset($_FILES['file2'])) {
        $file2 = $_FILES['file2'];
        $file2Path = $uploadDir . basename($file2['name']);
        
        if ($file2['name'] === 'zone_room.csv') {
            if (move_uploaded_file($file2['tmp_name'], $file2Path)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                echo "File 2 uploaded successfully.<br>";
            } else {
                $errors[] = "Failed to upload File 2.";
            }
        } else {
            $errors[] = "File 2 must be named zone_room.csv.";
        }
    } elseif (!$file2Exists) {
        $errors[] = "File 2 is not set.";
    }

    // Display errors if any
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo $error . "<br>";
        }
    }

    // Execute the bash command if the button is clicked
    if (isset($_POST['execute_command'])) {
        $ip_address = trim(file_get_contents($ip_file_path));
        list($ip, $port) = explode(':', $ip_address);
        $command = 'sshpass -p "password" scp -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -P ' . $port . ' uploads/* sftpuser@' . $ip . ':/home/sftpuser/upload';
        $output = shell_exec($command); //TO DO Aggiungi rilevamento errore nell'invio
        //shell_exec("rm uploads/*");
        header("Location: " . '3_start_connections.php');
        echo "<pre>$output</pre>";
        exit;
    }

    // Execute the bash command if the button is clicked
    if (isset($_POST['reset_file'])) {
        shell_exec("rm uploads/*");
        header("Location: " . $_SERVER['PHP_SELF']);
        echo "<pre>$output</pre>";
        exit;
    }
}
?>

        <h1>Carica i tuoi file di mapping</h1>
        <h3>Ricorda di fare il login alle intergrazioni che si vuole utilizzare</h3>
        
        <div style="text-align: center;">
            <a href="0_login.php"><button>Login integrazioni</button></a>
        </div>
        <?php if (!$file1Exists): ?>
            <form action="#" method="post" enctype="multipart/form-data">
                <label for="file1">Choose File 1 (mapping_elettr_conn.csv):</label>
                <input type="file" name="file1" id="file1" required><br><br>
                <input type="submit" value="Upload File 1">
            </form>
        <?php endif; ?>

        <?php if (!$file2Exists): ?>
            <form action="#" method="post" enctype="multipart/form-data">
                <label for="file2">Choose File 2 (zone_room.csv):</label>
                <input type="file" name="file2" id="file2" required><br><br>
                <input type="submit" value="Upload File 2">
            </form>
        <?php endif; ?>

        <?php if ($file1Exists && $file2Exists): ?>
            <form action="0_cnfStEl.php" method="get">
                <input type="submit" value="Visualizza/modifica file caricati">
            </form>
            <form action="#" method="post">
                <input type="hidden" name="reset_file" value="1">
                <input type="submit" value="Reset File">
            </form>
            <form action="#" method="post">
                <input type="hidden" name="execute_command" value="1">
                <input type="submit" value="Invia Mapping">
            </form>
            
        <?php endif; ?>
    </div>
</body>
</html>