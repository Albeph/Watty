<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['server'])) {
    $server = $_POST['server'];
    echo "Server: $server<br>";

    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "Directory 'uploads' created.<br>";
    }

    if (isset($_FILES['mapping_file']) && $_FILES['mapping_file']['name'] !== '') {
        $file = $_FILES['mapping_file']['tmp_name'];
        $file_name = $_FILES['mapping_file']['name'];
        echo "Uploaded file name: $file_name<br>";

        if ($file_name !== 'mapping_elettr_conn.csv') {
            echo "Error: The uploaded file must be named 'mapping_elettr_conn.csv'.";
            exit;
        }

        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($file, 'uploads/mapping_elettr_conn.csv')) {
            echo "File uploaded successfully.<br>";
        } else {
            echo "Error: Failed to move uploaded file.<br>";
            exit;
        }
        $file = 'uploads/mapping_elettr_conn.csv';
    } else {
        $file = 'uploads/mapping_elettr_conn.csv';
        echo "Using default file: $file<br>";
    }

    if (!file_exists($file)) {
        echo "Error: File not found.";
        exit;
    }

    $mapping = array_map('str_getcsv', file($file));
    $header = array_shift($mapping);

    $services = [];

    foreach ($mapping as $row) {
        $data = array_combine($header, $row);
        $zone_id = $data['zona'];
        $product_id = $data['prodotto_id'];
        $connection = $data['connection'];
        $device_ip = $data['device_ip'];
        $device_nm = $data['device_nm'];

        $service_name = "{$connection}_zone{$zone_id}_product{$product_id}";
        $dockerfile_path = "{$connection}-connect/Dockerfile";

        $services[$service_name] = [
            'build' => [
                'context' => "./{$connection}-connect/",
                'dockerfile' => "Dockerfile"
            ],
            'environment' => [
                "zone_id={$zone_id}",
                "product_id={$product_id}",
                "device_ip={$device_ip}",
                "device_nm={$device_nm}",
                "server={$server}",
                "PYTHONUNBUFFERED=1"
            ],
            'restart' => "always"
        ];
    }

    $docker_compose = [
        'version' => '3',
        'services' => $services
    ];

    file_put_contents('./connections/docker-compose.yml', yaml_emit($docker_compose));
    echo "docker-compose.yml generated successfully.";
}

if (isset($_POST['run_docker_compose'])) {
    shell_exec('docker compose -f ./connections/docker-compose.yml -p connections up --build -d 2>&1');
}

if (isset($_POST['stop_docker_compose'])) {
    shell_exec('docker compose -f ./connections/docker-compose.yml -p connections down 2>&1');
}

if (isset($_POST['reset_docker_compose'])) {
    if (file_exists('./connections/docker-compose.yml')) {
        unlink('./connections/docker-compose.yml');
        echo "docker-compose.yml deleted successfully.";
    } else {
        echo "docker-compose.yml does not exist.";
    }
}

if (isset($_GET['get_logs'])) {
    echo shell_exec('docker compose -f ./connections/docker-compose.yml logs 2>&1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Docker Compose</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function updateLogOutput() {
            fetch('?get_logs=1')
                .then(response => response.text())
                .then(data => {
                    const logOutput = document.getElementById('log_output');
                    logOutput.innerText = data;
                    logOutput.scrollTop = logOutput.scrollHeight; // Scroll to the bottom
                });
        }

        setInterval(updateLogOutput, 2000);
    </script>
</head>
<body>
<a href="index.php" class="top-left-button"><button>HOME</button></a>
    <div class="container">
        <h1>Carica il file di Mapping corrispondente </h1>
        <?php if (!file_exists('./connections/docker-compose.yml')): ?>
            <form action="#" method="post" enctype="multipart/form-data">
                <label for="server">Server Logstash:</label>
                <input type="text" id="server" name="server" required>
                <br><br>
                <?php if(!file_exists('uploads/mapping_elettr_conn.csv')) { ?>
                    <label for="mapping_file">Upload mapping_elettr_conn.csv:</label>
                    <input type="file" id="mapping_file" name="mapping_file" accept=".csv" required>
                <?php } ?>
                <br><br>
                <input type="submit" value="Generate Docker Compose">
            </form>
        <?php else: ?>
            <form action="#" method="post">
                <input type="hidden" name="run_docker_compose" value="1">
                <input type="submit" value="Start Docker Compose">
            </form>
            <form action="#" method="post">
                <input type="hidden" name="stop_docker_compose" value="1">
                <input type="submit" value="Stop Docker Compose">
            </form>
            <form action="#" method="post">
                <input type="hidden" name="reset_docker_compose" value="1">
                <input type="submit" value="Reset Docker Compose">
            </form>
        <?php endif; ?>
        <br>
        <pre id="log_output" style="solid #ccc; width:99%; height:200px;"></pre>
    </div>
</body>
</html>