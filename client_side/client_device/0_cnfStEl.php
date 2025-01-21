<?php
// Funzione per leggere un file CSV e restituirlo come array associativo
function readCsv($filename) {
    $rows = [];
    if (($handle = fopen($filename, "r")) !== false) {
        $headers = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $data);
        }
        fclose($handle);
    }
    return $rows;
}

// Funzione per scrivere un array associativo in un file CSV
function writeCsv($filename, $data) {
    if (!empty($data)) {
        $handle = fopen($filename, "w");
        if ($handle !== false) {
            fputcsv($handle, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }
    } else {
        // Se l'array è vuoto, crea un file CSV vuoto con solo l'intestazione
        $handle = fopen($filename, "w");
        if ($handle !== false) {
            fclose($handle);
        }
    }
}

// Percorsi ai file
$outputDir = "./uploads/";
$zoneFile = $outputDir . "zone_room.csv";
$productFile = $outputDir . "mapping_elettr_conn.csv";

if (!is_dir($outputDir)) {
    mkdir($outputDir);
}

$zones = file_exists($zoneFile) ? readCsv($zoneFile) : [];
$products = file_exists($productFile) ? readCsv($productFile) : [];

// Funzione per ottenere il prossimo ID incrementale per le zone
function getNextZoneId($zones) {
    $ids = array_map(function($zone) {
        return (int)$zone['zona'];
    }, $zones);
    return empty($ids) ? 1 : max($ids) + 1;
}

// Funzione per ottenere il prossimo ID incrementale per i prodotti in base alla zona
function getNextProductId($products, $zoneId) {
    $ids = array_map(function($product) use ($zoneId) {
        return $product['zona'] == $zoneId ? (int)$product['prodotto_id'] : 0;
    }, $products);
    return empty($ids) ? 1 : max($ids) + 1;
}

// Gestione richieste POST per aggiungere, modificare o eliminare dati
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_zone'])) {
        $zones[] = ["zona" => getNextZoneId($zones), "nome_zona" => $_POST['room_name']];
        writeCsv($zoneFile, $zones);
    } elseif (isset($_POST['add_product'])) {
        $zoneId = $_POST['zone_name'];
        $products[] = [
            "zona" => $zoneId,
            "prodotto_id" => getNextProductId($products, $zoneId),
            "elettrodomestico" => $_POST['product_name'],
            "role" => $_POST['role'],
            "connection" => $_POST['connection'],
            "device_ip" => $_POST['device_ip'],
            "device_nm" => $_POST['device_nm']
        ];
        writeCsv($productFile, $products);
    } elseif (isset($_POST['delete_zone'])) {
        if (isset($_POST['zone_id'])) {
            $zones = array_filter($zones, function($zone) {
                return $zone['zona'] != $_POST['zone_id'];
            });
            $zones = array_values($zones); // Reindicizza l'array
            $products = array_filter($products, function($product) {
                return $product['zona'] != $_POST['zone_id'];
            });
            $products = array_values($products); // Reindicizza l'array
            writeCsv($zoneFile, $zones);
            writeCsv($productFile, $products);
        }
    } elseif (isset($_POST['delete_product'])) {
        if (isset($_POST['zone_id']) && isset($_POST['product_id'])) {
            $products = array_filter($products, function($product) {
                return !($product['zona'] == $_POST['zone_id'] && $product['prodotto_id'] == $_POST['product_id']);
            });
            $products = array_values($products); // Reindicizza l'array
            writeCsv($productFile, $products);
        }
    } elseif (isset($_POST['download_zone_file'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="zone_room.csv"');
        readfile($zoneFile);
        exit;
    } elseif (isset($_POST['download_product_file'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mapping_elettr_conn.csv"');
        readfile($productFile);
        exit;
    } elseif (isset($_POST['delete_zone_file'])) {
        if (file_exists($zoneFile)) unlink($zoneFile);
        $zones = [];
    } elseif (isset($_POST['delete_product_file'])) {
        if (file_exists($productFile)) unlink($productFile);
        $products = [];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

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

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Stanze e Zone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        form {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: left;
            align-items: center;
            overflow: hidden;
        }

        form > * {
            flex-shrink: 1;
            margin-right: 10px;
            min-width: 0;
        }
        
    </style>
    <script>
        function handleConnectionChange() {
            const connection = document.getElementById('connection').value;
            const deviceName = document.getElementById('device_nm');
            const submitButton = document.getElementById('submit_button');
            const passFileExists = {
                tapo: <?= file_exists(__DIR__ . "/connections/tapo-connect/pass.csv") ? 'true' : 'false' ?>,
                meross: <?= file_exists(__DIR__ . "/connections/meross-connect/pass.csv") ? 'true' : 'false' ?>
            };

            if (connection === 'simulation' || connection === 'tapo') {
                deviceName.value = '-';
                deviceName.disabled = true;
            } else {
                deviceName.value = '';
                deviceName.disabled = false;
            }

            if ((connection === 'tapo' && !passFileExists.tapo) || (connection === 'meross' && !passFileExists.meross)) {
                submitButton.disabled = true;
                submitButton.textContent = 'Prima eseguire il login';
            } else {
                submitButton.disabled = false;
                submitButton.textContent = 'Aggiungi Elettrodomestico';
            }
        }

        function checkProductName() {
            const productName = document.getElementById('product_name').value.toLowerCase();
            const zoneId = document.getElementById('zone_name').value;
            const existingProducts = <?= json_encode($products) ?>;
            const submitButton = document.getElementById('submit_button');

            const productsInZone = existingProducts
                .filter(product => String(product.zona) === String(zoneId))
                .map(product => product.elettrodomestico.toLowerCase());

            if (productsInZone.includes(productName)) {
                submitButton.disabled = true;
                submitButton.textContent = 'Elemento già inserito';
            } else {
                submitButton.disabled = false;
                submitButton.textContent = 'Aggiungi Elettrodomestico';
            }
        }

        function checkZoneName() {
            const zoneName = document.getElementById('room_name').value.toLowerCase();
            const existingZones = <?= json_encode(array_map('strtolower', array_column($zones, 'nome_zona'))) ?>;
            const submitButton = document.getElementById('add_zone_button');

            if (existingZones.includes(zoneName)) {
                submitButton.disabled = true;
                submitButton.textContent = 'Elemento già inserito';
            } else {
                submitButton.disabled = false;
                submitButton.textContent = 'Aggiungi Zona';
            }
        }
    </script>
</head>
<body>
    <a href="0_login.php" class="top-right-button"><button>Login integrazioni</button></a>
    <a href="index.php" class="top-left-button"><button>HOME</button></a>
    

    <h1>Gestione Stanze e Elettrodomestico</h1>

    <!-- Form per aggiungere una zona/stanza -->
    <h2>Aggiungi Zona</h2>
    <form method="post">
        <label for="room_name">Nome Zona:</label>
        <input type="text" id="room_name" name="room_name" required oninput="checkZoneName()">
        <button type="submit" id="add_zone_button" name="add_zone">Aggiungi Zona</button>
    </form>

    <!-- Form per aggiungere un elettrodomestico -->
    <h2>Aggiungi Elettrodomestico</h2>
    <form method="post">
        <label for="zone_name">ID Zona:</label>
        <select id="zone_name" name="zone_name" required>
            <option value="" disabled selected>Seleziona una zona</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?= htmlspecialchars($zone['zona']) ?>">
                    <?= htmlspecialchars($zone['zona']) ?> - <?= htmlspecialchars($zone['nome_zona']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="product_name">Elettrodomestico:</label>
        <input type="text" id="product_name" name="product_name" required oninput="checkProductName()">
        <label for="role">Ruolo:</label>
        <select id="role" name="role" required>
            <option value="" disabled selected>Seleziona un ruolo</option>
            <option value="essential">Essential</option>
            <option value="utility">Utility</option>
            <option value="svago">Svago</option>
            <option value="other">Other</option>
        </select>
        <label for="connection">Connection:</label>
        <select id="connection" name="connection" required onchange="handleConnectionChange()">
            <option value="" disabled selected>Seleziona una connessione</option>
            <option value="simulation">Simulation</option>
            <option value="tapo">Tapo - p110</option>
            <option value="meross">Meross</option>
            <!-- Aggiungere nuova option in caso di implementazioni di nuovi device-->
            <!-- N.B. il valore value deve essere corrispondente a ciò che sta prima del trattino 
             nel nome della cartella della nuova implementazione
             ES: per value = "tapo" -> cartella: "tapo-connect"
             -->
        </select>
        <label for="device_ip">Device IP:</label>
        <input type="text" id="device_ip" name="device_ip" required>
        <label for="device_nm">Device Name:</label>
        <input type="text" id="device_nm" name="device_nm">
        <button type="submit" id="submit_button" name="add_product">Aggiungi Elettrodomestico</button>
    </form>

    <!-- Tabella Zone e Stanze -->
    <h2>Zone</h2>
    <table>
        <thead>
            <tr>
                <th>ID Zona</th>
                <th>Nome Zona</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zones as $zone): ?>
                <tr>
                    <td><?= htmlspecialchars($zone['zona']) ?></td>
                    <td><?= htmlspecialchars($zone['nome_zona']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="zone_id" value="<?= htmlspecialchars($zone['zona']) ?>">
                            <button type="submit" name="delete_zone">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Tabella Elettrodomestici -->
    <h2>Elettrodomestici</h2>
    <table>
        <thead>
            <tr>
                <th>ID Zona</th>
                <th>ID Prodotto</th>
                <th>Elettrodomestico</th>
                <th>Ruolo</th>
                <th>Connection</th>
                <th>Device IP</th>
                <th>Device Name</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['zona']) ?></td>
                    <td><?= htmlspecialchars($product['prodotto_id']) ?></td>
                    <td><?= htmlspecialchars($product['elettrodomestico']) ?></td>
                    <td><?= htmlspecialchars($product['role']) ?></td>
                    <td><?= htmlspecialchars($product['connection']) ?></td>
                    <td><?= htmlspecialchars($product['device_ip']) ?></td>
                    <td><?= htmlspecialchars($product['device_nm']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="zone_id" value="<?= htmlspecialchars($product['zona']) ?>">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['prodotto_id']) ?>">
                            <button type="submit" name="delete_product">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Prosegui Configurazione</h2>
    <form action="1_set_ip.php" method="get">
        <button type="submit">Avanti</button>
    </form>
    <!-- Scaricare i file -->
    <h2>Gestione File</h2>
    <form method="post" style="display:inline;">
        <button type="submit" name="download_zone_file">Scarica File Zone</button>
    </form>
    <form method="post" style="display:inline;">
        <button type="submit" name="download_product_file">Scarica File Elettrodomestici</button>
    </form>
</body>
</html>