<?php

// Подключение к базе данных
function connectToDatabase(): PDO
{
    $options = [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $dsn = 'mysql:host=localhost;dbname=database_name;charset=utf8mb4';
    $pdo = new PDO($dsn, 'username', 'password', $options);
    return $pdo;
}

// Функция для вставки данных из CSV-файла в базу данных
/**
 * @throws Exception
 */
function insertUsersFromCsv($filePath)
{
    $pdo = connectToDatabase();
    $query = "INSERT INTO users (name, number) VALUES (?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $number);

    // Чтение данных из CSV-файла и вставка их в базу данных
    if (($handle = fopen($filePath, 'r')) !== false) {
        $pdo->beginTransaction();

        while (($data = fgetcsv($handle, 0, ",")) !== false) {
            $name = $data[1] ?? '';
            $number = $data[0] ?? '';

            if (empty($name) || empty($number)) {
                continue;
            }

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                $pdo->rollBack();
                throw new Exception("Error inserting data: " . $e->getMessage());
            }
        }

        $pdo->commit();

        fclose($handle);
    } else {
        throw new Exception("Failed to open file $filePath");
    }
}

// Определение маршрута API для вставки данных из CSV-файла в базу данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_path'])) {
    $filePath = $_POST['file_path'];
    try {
        insertUsersFromCsv($filePath);
        echo 'Data inserted successfully';
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo $e->getMessage();
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid request';
}
