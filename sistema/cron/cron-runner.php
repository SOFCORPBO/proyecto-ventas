<?php
/**
 * Cron runner único para el sistema.
 * Configura un cron del SO para ejecutar este archivo cada 1 minuto:
 *
 * Linux (crontab):
 *   * * * * * /usr/bin/php -d detect_unicode=0 /var/www/html/sistema/cron/cron-runner.php >/dev/null 2>&1
 *
 * Windows (Task Scheduler):
 *   php C:\xampp\htdocs\tu_sistema\sistema\cron\cron-runner.php
 *
 * Jobs actuales (tabla sys_cron_jobs):
 *   - chat_outbox
 *   - chat_sync
 */
require_once __DIR__ . '/../configuracion.php';
require_once __DIR__ . '/../clase/ChatModulo.clase.php';
require_once __DIR__ . '/../clase/ChatEvolution.clase.php';

$chat = new ChatModulo();
$db = $chat->db();

$nowTs = time();
$now   = date('Y-m-d H:i:s', $nowTs);

$jobs = $db->query("SELECT * FROM sys_cron_jobs WHERE habilitado=1 ORDER BY id ASC");

while ($job = $jobs->fetch_assoc()) {
    $interval = max(60, (int)$job['intervalo_seg']);
    $lastRun  = $job['ultimo_run'] ? strtotime($job['ultimo_run']) : 0;

    if ($lastRun > 0 && ($lastRun + $interval) > $nowTs) {
        continue; // no due
    }

    $jobId = (int)$job['id'];
    $cmd   = (string)$job['comando'];

    $jobFile = __DIR__ . '/jobs/' . $cmd . '.php';

    $ok = 0;
    $estado = 'ok';
    $error = null;

    try {
        if (!file_exists($jobFile)) {
            throw new Exception("Job file missing: " . $jobFile);
        }

        // Variables disponibles en jobs: $chat, $db, $now, $nowTs
        $result = include $jobFile;

        $ok = 1;
        if (is_array($result) && isset($result['ok']) && !$result['ok']) {
            $ok = 0;
            $estado = 'error';
            $error = $result['error'] ?? 'unknown';
        }
    } catch (Exception $e) {
        $ok = 0;
        $estado = 'error';
        $error = $e->getMessage();
    }

    // persist run info
    $stmt = $db->prepare("UPDATE sys_cron_jobs SET ultimo_run=?, ultimo_estado=?, ultimo_error=? WHERE id=?");
    $errText = $error ? (string)$error : null;
    $stmt->bind_param("sssi", $now, $estado, $errText, $jobId);
    $stmt->execute();
}

echo "OK $now\n";
