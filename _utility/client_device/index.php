<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Benvenuto</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Benvenuto</h1>
        <p>inserire didascalia</p>
        
        <?php
        $dockerComposePath = './connections/docker-compose.yml';
        if (file_exists($dockerComposePath)) {
            ?>
            <form action="3_start_connections.php" method="get">
                    <button type="submit">Vai al Monitoring</button>
                  </form>
            <form action="" method="post">
                <button type="submit" name="delete_files">Elimina File</button>
            </form>    
            <?php
        }else {

        ?>
        
        <form action="1_set_ip.php" method="get">
            <button type="submit">Continua con un file di configurazione</button>
        </form>
        
        <form action="0_cnfStEl.php" method="get">
            <button type="submit">Continua senza un file di configurazione</button>
        </form>

        <?php
            }
        if (isset($_POST['delete_files'])) {
            $filesToDelete = [
                './uploads/mapping_elettr_conn.csv',
                './uploads/zone_room.csv',
                './connections/docker-compose.yml',
                './indirizzo_ip.txt'
            ];

            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            header('Location: index.php');
            exit();
        }
        ?>
    </div>
</body>
</html>