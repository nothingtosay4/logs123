<?php
// Создаем папку для логов
if (!is_dir('logs')) mkdir('logs', 0777, true);

// Собираем данные
$data = [
    'time' => date('H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'ua' => $_SERVER['HTTP_USER_AGENT'],
    'ref' => $_GET['ref'] ?? 'direct'
];

// Простая геолокация
$geo = @json_decode(file_get_contents("http://ip-api.com/json/{$data['ip']}?fields=country,city,proxy"), true);
if ($geo) $data['geo'] = $geo;

// Пишем в лог
file_put_contents('logs/visits.log', json_encode($data) . "\n", FILE_APPEND);

// Редирект на главную
header('Location: /?ref=' . $data['ref']);
exit;
?>