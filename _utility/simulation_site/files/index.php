<?php

// Funzione per leggere i dati dai file CSV
function read_csv($file_path) {
    $rows = array_map('str_getcsv', file($file_path));
    $header = array_shift($rows);
    $csv = [];
    foreach ($rows as $row) {
        $csv[] = array_combine($header, $row);
    }
    return $csv;
}

// Funzione per salvare il valore in un file di testo specifico per ogni combinazione di zone_id e product_id
function send_value($zone_id, $product_id, $value) {
    $directory = 'outputs';
    $file_path = "{$directory}/{$zone_id}{$product_id}.txt";
    
    // Scrivi solo il valore nel file di testo
    file_put_contents($file_path, $value);
}

//elimina contenuto cartella output
function delete_outputs() {
    $directory = 'outputs';
    $files = glob("{$directory}/*"); // get all file names
    foreach($files as $file) { // iterate files
        if(is_file($file)) {
            unlink($file); // delete file
        }
    }
}

// Funzione per eliminare i file mapping_elettr_conn.csv e zone_room.csv
function delete_mapping_files() {
    $mapping_file = './_mapping/mapping_elettr_conn.csv';
    $zone_file = './_mapping/zone_room.csv';
    if (file_exists($mapping_file)) {
        unlink($mapping_file);
    }
    if (file_exists($zone_file)) {
        unlink($zone_file);
    }
}

// Se viene richiesto di eliminare il contenuto della cartella outputs
if (isset($_POST['delete_outputs'])) {
    delete_outputs();
    header("Location: /"); // Redirect to the main page    
    exit;
}

// Se viene richiesto di eliminare i file mapping_elettr_conn.csv e zone_room.csv
if (isset($_POST['delete_mapping_files'])) {
    delete_mapping_files();
    header("Location: /"); // Redirect to the main page    
    exit;
}

// Se ci sono parametri GET, invia i dati
if (isset($_GET['zone_id']) && isset($_GET['product_id']) && isset($_GET['value'])) {
    $zone_id = $_GET['zone_id'];
    $product_id = $_GET['product_id'];
    $value = $_GET['value'];
    echo send_value($zone_id, $product_id, $value);
    exit;
}

// Se viene richiesto un file specifico nella cartella outputs, restituisci il contenuto del file
if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '/' && !isset($_GET['zone_id']) && !isset($_GET['product_id']) && !isset($_GET['value']) && !isset($_GET['action'])) {
    $requested_file = basename($_SERVER['REQUEST_URI']);
    $file_path = "outputs/{$requested_file}.txt";
    
    if (file_exists($file_path)) {
        echo file_get_contents($file_path);
    } else {
        http_response_code(404);
        echo "-1.";
    }
    exit;
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mappingFile']) && isset($_FILES['zoneFile'])) {
    $upload_dir = './_mapping/';
    $mapping_file = $upload_dir . 'mapping_elettr_conn.csv';
    $zone_file = $upload_dir . 'zone_room.csv';

    // Check if the upload directory exists, if not create it
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle the mapping file upload
    if (isset($_FILES['mappingFile']) && $_FILES['mappingFile']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['mappingFile']['tmp_name'], $mapping_file);
    }

    // Handle the zone file upload
    if (isset($_FILES['zoneFile']) && $_FILES['zoneFile']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['zoneFile']['tmp_name'], $zone_file);
    }

    // Redirect back to the main page
    header("Location: #");
    exit;
}

// Check if the required files exist
$mapping_file = './_mapping/mapping_elettr_conn.csv';
$zone_file = './_mapping/zone_room.csv';

if (!file_exists($mapping_file) || !file_exists($zone_file)) {
    // Display upload form if files are missing
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upload Files</title>
    </head>
    <body>
        <h1>Upload Required Files</h1>
        <form action="#" method="post" enctype="multipart/form-data">
            <label for="mappingFile">Upload mapping_elettr_conn.csv:</label>
            <input type="file" name="mappingFile" id="mappingFile" required>
            <br><br>
            <label for="zoneFile">Upload zone_room.csv:</label>
            <input type="file" name="zoneFile" id="zoneFile" required>
            <br><br>
            <button type="submit">Upload</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$room_product_appliance = read_csv($mapping_file);
$training_data = read_csv('./_mapping/training_data.csv');
$zone_room = read_csv($zone_file);

// Crea un dizionario per i valori minimi e massimi degli elettrodomestici
$device_ranges = [];
foreach ($training_data as $row) {
    $device = $row['Device'];
    $state = $row['State'];
    $min_value = $row['pr-min (W)'];
    $max_value = $row['pr-max (W)'];
    if (!isset($device_ranges[$device])) {
        $device_ranges[$device] = [];
    }
    $device_ranges[$device][$state] = [$min_value, $max_value];
}

// Crea un dizionario per i nomi delle zone
$zone_names = [];
foreach ($zone_room as $row) {
    $zone_names[$row['zona']] = $row['nome_zona'];
}

// Raggruppa gli elettrodomestici per zona
$zones = [];
foreach ($room_product_appliance as $row) {
    if ($row['connection'] !== 'simulation') {
        continue;
    }
    $zona = $row['zona'];
    if (!isset($zones[$zona])) {
        $zones[$zona] = [];
    }
    $zones[$zona][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Zone ed Elettrodomestici</h1>
    <label for="generalState">Abilita invio messaggi:</label>
    <select id="generalState">
        <option value="enabled">Enabled</option>
        <option value="disabled" selected>Disabled</option>
    </select>
    <br><br>
    <form method="post">
        <button type="submit" name="delete_outputs">Reset all</button>
    </form>
    <br><br>
    <form method="post">
        <button type="submit" name="delete_mapping_files">Delete mapping files</button>
    </form>
    <h2>Somma totale dei valori inviati: <span id="totalSum">0</span> W</h2>
    <div id="zonesContainer">
        <?php foreach ($zones as $zona => $appliances): ?>
            <div>
                <h2>Zona <?php echo $zona . ' - ' . $zone_names[$zona]; ?></h2>
                <table>
                    <tr>
                        <th>Nome</th>
                        <th>Intervallo Active</th>
                        <th>Intervallo Idle</th>
                        <th>Stato</th>
                        <th>Valore Inviato</th>
                    </tr>
                    <?php foreach ($appliances as $appliance): ?>
                        <?php
                        $device = $appliance['elettrodomestico'];
                        $active_range = $device_ranges[$device]['Active'][0] . ' - ' . $device_ranges[$device]['Active'][1];
                        $idle_range = isset($device_ranges[$device]['Idle']) ? $device_ranges[$device]['Idle'][0] . ' - ' . $device_ranges[$device]['Idle'][1] : '-';
                        ?>
                        <tr>
                            <td><?php echo $device; ?></td>
                            <td><?php echo $active_range; ?></td>
                            <td><?php echo $idle_range; ?></td>
                            <td>
                                <select class="stateSelect">
                                    <option value="none" selected>None</option>
                                    <option value="active">Active</option>
                                    <option value="off">Off</option>
                                    <?php if (isset($device_ranges[$device]['Idle'])): ?>
                                        <option value="idle">Idle</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td class="sentValue">0</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        let intervalId;

        function sendRandomValues() {
            const generalState = document.getElementById("generalState").value;
            if (generalState === "disabled") return;

            const zonesContainer = document.getElementById("zonesContainer");
            const zones = zonesContainer.querySelectorAll("table");
            let totalSum = 0;

            zones.forEach((zone, zoneIndex) => {
                const appliances = zone.querySelectorAll("tr");
                appliances.forEach((appliance, applianceIndex) => {
                    if (applianceIndex === 0) return; // Skip header row
                    const stateSelect = appliance.querySelector(".stateSelect");
                    const state = stateSelect.value;
                    let randomValue = 0;

                    if (state === "active") {
                        const minValue = parseInt(appliance.querySelector("td:nth-child(2)").innerText.split(' - ')[0]);
                        const maxValue = parseInt(appliance.querySelector("td:nth-child(2)").innerText.split(' - ')[1]);
                        randomValue = Math.floor(Math.random() * (maxValue - minValue + 1)) + minValue;
                    } else if (state === "idle") {
                        const minValue = parseInt(appliance.querySelector("td:nth-child(3)").innerText.split(' - ')[0]);
                        const maxValue = parseInt(appliance.querySelector("td:nth-child(3)").innerText.split(' - ')[1]);
                        randomValue = Math.floor(Math.random() * (maxValue - minValue + 1)) + minValue;
                    } else if (state === "off") {
                        randomValue = 0;
                    } else {
                        return; // Non inviare nessun dato
                    }

                    appliance.querySelector(".sentValue").innerText = randomValue;
                    totalSum += randomValue;

                    const zoneId = zoneIndex + 1;
                    const productId = applianceIndex;
                    const value = randomValue;

                    fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?zone_id=${zoneId}&product_id=${productId}&value=${value}`)
                        .then(response => response.text())
                        .then(data => console.log(data));
                });
            });

            document.getElementById("totalSum").innerText = totalSum;
        }

        function startSendingValues() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(sendRandomValues, 10000);
        }

        function resetValues() {
            const stateSelects = document.querySelectorAll(".stateSelect");
            stateSelects.forEach(select => {
                select.value = "none";
            });
            const sentValues = document.querySelectorAll(".sentValue");
            sentValues.forEach(value => {
                value.innerText = "0";
            });
            document.getElementById("totalSum").innerText = "0";
            clearInterval(intervalId);
        }

        document.getElementById("generalState").addEventListener("change", function() {
            if (this.value === "enabled") {
                startSendingValues();
            } else {
                clearInterval(intervalId);
            }
        });

        // Start with the initial state
        if (document.getElementById("generalState").value === "enabled") {
            startSendingValues();
        }
    </script>
</body>
</html>