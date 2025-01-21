<?php
if (isset($_POST['download'])) {
    $directory = 'log-files/';
    $csvFile = 'merged_logs.csv';

    $output = fopen($csvFile, 'w');

    // Write CSV header
    fputcsv($output, ['timestamp', 'nome_zona', 'elettrodomestico', 'potenza_istantanea', 'probability_percentages', 'state', 'role']);

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && strpos($file->getFilename(), 'part-') === 0 && $file->getExtension() === 'json') {
            $filePath = $file->getPathname();
            $jsonContent = file_get_contents($filePath);
            if ($jsonContent === false) {
                continue; // Skip if file cannot be read
            }
            $logEntries = explode("\n", $jsonContent);

            foreach ($logEntries as $entry) {
                if (!empty($entry)) {
                    $logData = json_decode($entry, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        fputcsv($output, $logData);
                    }
                }
            }
        }
    }

    fclose($output);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $csvFile . '"');
    readfile($csvFile);

    // Delete the temporary CSV file after download
    unlink($csvFile);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Logs</title>
    <style>
        button {
            background-color: #28a745;
            color: #fff;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body>
    <form method="post">
        <button type="submit" name="download">Download Logs as CSV</button>
    </form>
</body>
</html>