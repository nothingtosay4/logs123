<?php
// ===================== НАСТРОЙКИ =====================
$LOG_FILE = 'visits.log';  // Файл для хранения логов

// ===================== ТРЕКИНГ =====================
// Если перешли по ссылке с параметром ref
if (isset($_GET['ref'])) {
    // 1. Собираем данные
    $data = [
        'time' => date('H:i:s d.m.Y'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'referer' => $_SERVER['HTTP_REFER'] ?? 'прямой заход',
        'ref' => $_GET['ref']
    ];
    
    // 2. Определяем VPN/Прокси (простая проверка)
    $data['vpn'] = false;
    if (checkVPN($data['ip'])) {
        $data['vpn'] = true;
    }
    
    // 3. Сохраняем в файл
    saveLog($data);
    
    // 4. Показываем уведомление и остаемся на странице
    $message = "✅ Записан переход от " . $data['ip'];
}

// ===================== ФУНКЦИИ =====================
function saveLog($data) {
    global $LOG_FILE;
    $line = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

function checkVPN($ip) {
    // Простая проверка: если IP из известных датацентров или VPN
    $hostname = @gethostbyaddr($ip);
    if (!$hostname) return false;
    
    $vpn_keywords = ['vpn', 'proxy', 'hosting', 'datacenter', 'cloud'];
    foreach ($vpn_keywords as $keyword) {
        if (stripos($hostname, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function getLogs() {
    global $LOG_FILE;
    $logs = [];
    if (file_exists($LOG_FILE)) {
        $lines = array_reverse(file($LOG_FILE, FILE_IGNORE_NEW_LINES));
        foreach ($lines as $line) {
            if ($log = json_decode($line, true)) {
                $logs[] = $log;
            }
        }
    }
    return $logs;
}

// ===================== ИНТЕРФЕЙС =====================
$logs = getLogs();
$site_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Трекер переходов</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        h2 { color: #555; margin: 15px 0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #4CAF50; color: white; padding: 15px; border-radius: 8px; flex: 1; text-align: center; }
        .stat-box h3 { font-size: 24px; margin-bottom: 5px; }
        .log-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .ip { font-family: monospace; background: #f0f0f0; padding: 3px 6px; border-radius: 4px; }
        .vpn-badge { background: #ff4444; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .link-generator { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
        input, button { padding: 10px; font-size: 16px; }
        input { width: 200px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1976D2; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .notification { background: #4CAF50; color: white; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- ШАПКА -->
        <div class="card">
            <h1>🔍 VPN/IP ТРЕКЕР</h1>
            <p>Следите за переходами по вашим ссылкам с любого устройства</p>
        </div>

        <!-- УВЕДОМЛЕНИЕ -->
        <?php if (!empty($message)): ?>
        <div class="notification"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- ГЕНЕРАТОР ССЫЛОК -->
        <div class="card link-generator">
            <h2>📝 Создать ссылку для отслеживания</h2>
            <div style="margin: 15px 0;">
                <input type="text" id="refInput" placeholder="Название теста" value="test">
                <button onclick="generateLink()">Создать ссылку</button>
            </div>
            <div>
                <input type="text" id="linkOutput" style="width: 80%;" readonly>
                <button onclick="copyLink()">Копировать</button>
                <button onclick="testLink()" style="background: #4CAF50;">Перейти</button>
            </div>
            <p style="margin-top: 10px; color: #666;">
                🔗 <strong>Как использовать:</strong> Создайте ссылку → Перейдите по ней → Данные появятся ниже
            </p>
        </div>

        <!-- СТАТИСТИКА -->
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo count($logs); ?></h3>
                <p>Всего переходов</p>
            </div>
            <div class="stat-box" style="background: #FF9800;">
                <h3><?php
                    $vpn_count = 0;
                    foreach ($logs as $log) {
                        if ($log['vpn']) $vpn_count++;
                    }
                    echo $vpn_count;
                ?></h3>
                <p>Через VPN/Прокси</p>
            </div>
            <div class="stat-box" style="background: #9C27B0;">
                <h3><?php
                    $ips = [];
                    foreach ($logs as $log) $ips[] = $log['ip'];
                    echo count(array_unique($ips));
                ?></h3>
                <p>Уникальных IP</p>
            </div>
        </div>

        <!-- ИСТОРИЯ ПЕРЕХОДОВ -->
        <div class="card">
            <h2>📊 История переходов</h2>
            
            <?php if (empty($logs)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <p style="font-size: 18px;">📭 Нет данных</p>
                    <p>Создайте ссылку выше и перейдите по ней</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Время</th>
                            <th>IP адрес</th>
                            <th>Устройство</th>
                            <th>Источник</th>
                            <th>Метка</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['time']; ?></td>
                            <td><span class="ip"><?php echo $log['ip']; ?></span></td>
                            <td>
                                <?php
                                $ua = $log['user_agent'];
                                if (strlen($ua) > 30) echo substr($ua, 0, 30) . '...';
                                else echo $ua;
                                ?>
                            </td>
                            <td><?php echo $log['referer']; ?></td>
                            <td><strong><?php echo $log['ref']; ?></strong></td>
                            <td>
                                <?php if ($log['vpn']): ?>
                                    <span class="vpn-badge">VPN/Прокси</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50;">Обычный</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ИНФОРМАЦИЯ -->
        <div class="card">
            <h2>ℹ️ Как это работает</h2>
            <ol style="margin-left: 20px; line-height: 1.6;">
                <li><strong>Создайте ссылку</strong> с уникальной меткой (например, "my_vpn_test")</li>
                <li><strong>Перейдите по этой ссылке</strong> с другого компьютера/телефона/VPN</li>
                <li><strong>Данные автоматически сохранятся</strong> (IP, время, устройство)</li>
                <li><strong>Вернитесь на эту страницу</strong> - увидите все переходы в таблице</li>
                <li><strong>Система определит VPN</strong> по IP адресу (не всегда точно)</li>
            </ol>
        </div>
    </div>

    <script>
        // Генерация ссылки
        function generateLink() {
            const ref = document.getElementById('refInput').value || 'test';
            const link = '<?php echo $site_url; ?>?ref=' + encodeURIComponent(ref);
            document.getElementById('linkOutput').value = link;
        }
        
        // Копирование ссылки
        function copyLink() {
            const link = document.getElementById('linkOutput');
            link.select();
            navigator.clipboard.writeText(link.value);
            alert('✅ Ссылка скопирована!\n\n' + link.value);
        }
        
        // Тестирование ссылки (открытие в новой вкладке)
        function testLink() {
            const link = document.getElementById('linkOutput').value;
            window.open(link, '_blank');
        }
        
        // Автогенерация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            generateLink();
            document.getElementById('refInput').addEventListener('input', generateLink);
            
            // Автообновление каждые 10 секунд
            setInterval(() => {
                location.reload();
            }, 10000);
        });
    </script>
</body>
</html>
