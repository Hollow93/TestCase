<?php

declare(strict_types=1);

// Подключение к базе данных
function connectToDatabase(): PDO
{
    $options = [
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $dsn     = 'mysql:host=localhost;dbname=test;charset=utf8mb4';
    return new PDO($dsn, 'hollow', '', $options);
}

// Функция для вставки данных из CSV-файла в базу данных
/**
 * @throws Exception
 */
function insertUsersFromCsv(string $filePath): void
{
    $pdo   = connectToDatabase();
    $query = "INSERT INTO users (name, number) VALUES (?, ?)";
    $stmt  = $pdo->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $number);

// Открытие лог-файла
    $logFile   = 'errors.log';
    $logHandle = fopen($logFile, 'a');
    if (!$logHandle) {
        throw new Exception("Failed to open log file $logFile");
    }

// Чтение данных из CSV-файла и вставка их в базу данных
    if (($handle = fopen($filePath, 'r')) !== false) {
        $pdo->beginTransaction();
        while (($data = fgetcsv($handle, 0)) !== false) {
            $name   = $data[1] ?? '';
            $number = $data[0] ?? '';
            if (empty($name) || empty($number)) {
                continue;
            }

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errorMessage = "Error inserting data: ".$e->getMessage();
                error_log($errorMessage, 3, $logFile);
                throw new Exception($errorMessage);
            }
        }

        $pdo->commit();

        fclose($handle);
        fclose($logHandle);
    } else {
        $errorMessage = "Failed to open file $filePath";
        error_log($errorMessage, 3, $logFile);
        throw new Exception($errorMessage);
    }
}

// Определение маршрута API для вставки данных из CSV-файла в базу данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_path'])) {
    $filePath = $_POST['file_path'];
    try {
        insertUsersFromCsv((string)$filePath);
        echo 'Data inserted successfully';
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo $e->getMessage();
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid request';
}
