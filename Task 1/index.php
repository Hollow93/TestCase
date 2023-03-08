<?php

$path = __DIR__ . '/../../data/users.csv';

// Проверяем, существует ли файл и доступен ли он для чтения
if (!is_readable($path)) {
    die("File $path does not exist or is not readable");
}

// Устанавливаем параметры соединения с базой данных
$options = [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Подключаемся к базе данных с помощью объекта PDO
$dsn = 'mysql:host=localhost;dbname=database_name;charset=utf8mb4';
$pdo = new PDO($dsn, 'username', 'password', $options);

// Запрос на вставку данных в базу данных
$query = "INSERT INTO users (name, number) VALUES (?, ?)";
$stmt = $pdo->prepare($query);

// Читаем данные из CSV-файла и вставляем их в базу данных
if (($handle = fopen($path, 'r')) !== false) {
    // Начинаем транзакцию для оптимизации вставки данных
    $pdo->beginTransaction();

    while (($data = fgetcsv($handle, 1000)) !== false) {
        $name = $data[1] ?? '';
        $number = $data[0] ?? '';

        // Пропускаем пустые значения в CSV-файле
        if (empty($name) || empty($number)) {
            continue;
        }

        // Добавляем данные в буфер подготовленного выражения
        $stmt->execute([$name, $number]);
    }

    // Завершаем транзакцию, чтобы все изменения были сохранены
    $pdo->commit();

    fclose($handle);

} else {
    die("Failed to open file $path");
}

// Оптимизация: закрываем соединение с базой данных
$pdo = null;
