<?php

declare(strict_types=1);

// Подключение к базе данных
$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', 'hollow', '', [
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function getPeople(PDO $dbh): array
{
    $stmt = $dbh->query('SELECT id, number FROM people');
    return $stmt->fetchAll();
}

function getSentPeople(PDO $dbh, int $idMailing): array
{
    $stmt = $dbh->prepare('SELECT person_id FROM mail_sent WHERE id_mailing = ?');
    $stmt->execute([$idMailing]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function addSentPerson(PDO $dbh, int $personId, int $idMailing): void
{
    $stmt = $dbh->prepare('INSERT INTO mail_sent (person_id, id_mailing) VALUES (?, ?)');
    $stmt->execute([$personId, $idMailing]);
}

function getSentMessage(PDO $dbh, int $idMailing): array
{
    $stmt = $dbh->prepare('SELECT topic, message FROM mailing_list WHERE id_mailing = ?');
    $stmt->execute([$idMailing]);
    return $stmt->fetch();
}

function sendMessage(string $number, array $messageBody): void
{
// Реализация метода отправки сообщения
}

/**
 * @throws Exception
 */
function addPeopleToQueue(PDO $dbh, int $idMailing): void
{
    try {
        $people      = getPeople($dbh);
        $messageBody = getSentMessage($dbh, $idMailing);
        $sentPeople  = getSentPeople($dbh, $idMailing);
        $newPeople   = array_diff(array_column($people, 'id'), $sentPeople);
        foreach ($newPeople as $personId) {
            sendMessage($people[$personId]['number'], $messageBody);
            addSentPerson($dbh, $personId, $idMailing);
        }
        if (empty($newPeople)) {
            throw new Exception('No new people to add to queue');
        }
    } catch (Exception $e) {
        error_log('Error adding people to queue for mailing ID '.$idMailing.': '.$e->getMessage(), 3);
        throw new Exception('Error adding people to queue: '.$e->getMessage());
    }
}

// Определение маршрута API для отправки сообщений
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mailing'])) {
    $idMailing = (int)$_POST['id_mailing'];
    try {
        addPeopleToQueue($pdo, $idMailing);
        echo 'Message sent successfully';
    } catch (Exception $e) {
        error_log('Error sending message for mailing ID '.$idMailing.': '.$e->getMessage(), 3);
        http_response_code(500);
        echo $e->getMessage();
    }
} else {
    error_log('Invalid request', 3);
    http_response_code(400);
    echo 'Invalid request';
}