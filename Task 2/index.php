<?php

// Подключение к базе данных
function connectToDatabase(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $dsn     = 'mysql:host=localhost;dbname=database_name;charset=utf8mb4';
        $pdo     = new PDO($dsn, 'username', 'password', $options);
    }

    return $pdo;
}

// Получение всех людей из БД
function getPeople()
{
    $dbh  = connectToDatabase();
    $stmt = $dbh->query('SELECT id, number FROM people');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получение списка уже отправленных рассылок
function getSentPeople()
{
    $dbh    = connectToDatabase();
    $stmt   = $dbh->query('SELECT person_id FROM mail_sent');
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result ? array_column($result, 'person_id') : [];
}

// Добавление записи в список отправленных рассылок
function addSentPerson($person_id, $idMailing)
{
    $dbh  = connectToDatabase();
    $stmt = $dbh->prepare('INSERT INTO mail_sent ($person_id, $id_mailing) VALUES (?, ?)');
    $stmt->bindParam(1, $person_id);
    $stmt->bindParam(2, $idMailing);
    $stmt->execute();
}

// Отправка Message
function sendMessage($number, $subject, $message)
{
    // Фиктивный метод отправки Message
}

// Получение всех людей, которых еще не было в списке отправленных, и добавление их в очередь рассылки
function addPeopleToQueue($idMailing)
{
    $people      = getPeople();
    $sentPeople  = getSentPeople($idMailing);
    foreach ($people as $person) {
        if (!in_array($person['id'], $sentPeople)) {
            // Добавление в очередь рассылки
            sendMessage($person['number'], 'Название рассылки', 'Текст рассылки');
            // Запись в список отправленных
            addSentPerson($person['id'], $idMailing);
        }
    }
}

// Определение маршрута API для отправки писем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mailing'])) {
    $idMailing = (int)$_POST['id_mailing'];
    try {
        addPeopleToQueue($idMailing);
        echo 'Message sent successfully';
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo $e->getMessage();
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid request';
}