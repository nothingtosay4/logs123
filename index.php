<?php
// Читаем логи
$logs = [];
if (file_exists('logs/visits.log')) {
    $lines = array_reverse(file('logs/visits.log', FILE_IGNORE_NEW_LINES));
    foreach ($lines as $line) {
        if ($log = json_decode($line, true)) $logs[] = $log;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Трекер</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .log { border: 1px solid #ccc; padding: 10px; margin: 5px; }
        .ip { font-family: monospace; color: blue; }
    </style>
</head>
<body>
    <h1>📊 Трекер переходов</h1>
    
    <!-- Генератор ссылки -->
    <div style="background:#f5f5f5; padding:15px; margin:20px 0;">
        <input id="refInput" value="test" placeholder="метка">
        <button onclick="genLink()">Создать ссылку</button>
        <input id="linkOutput" style="width:300px;" readonly>
        <button onclick="copyLink()">Копировать</button>
    </div>
    
    <!-- Логи -->
    <h3>Последние переходы (<?php echo count($logs); ?>):</h3>
    <?php if (empty($logs)): ?>
        <p>Нет данных. Создайте ссылку и перейдите по ней.</p>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <div class="log">
            <strong><?php echo $log['time']; ?></strong> | 
            <span class="ip"><?php echo $log['ip']; ?></span> | 
            <?php echo $log['geo']['country'] ?? 'N/A'; ?> | 
            <strong>Метка:</strong> <?php echo $log['ref']; ?>
            <?php if ($log['geo']['proxy'] ?? false): ?>
                <span style="background:red; color:white; padding:2px 5px;">VPN</span>
            <?php endif; ?>
            <br>
            <small><?php echo substr($log['ua'], 0, 50); ?>...</small>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        function genLink() {
            const ref = document.getElementById('refInput').value || 'test';
            const link = window.location.origin + '/track?ref=' + ref;
            document.getElementById('linkOutput').value = link;
        }
        
        function copyLink() {
            const link = document.getElementById('linkOutput');
            link.select();
            navigator.clipboard.writeText(link.value);
            alert('Скопировано!');
        }
        
        genLink(); // Автогенерация при загрузке
    </script>
</body>
</html>