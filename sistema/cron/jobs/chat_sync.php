<?php
/**
 * Job: chat_sync
 * - Actualiza estado de conexión de cada instancia
 */
$instancias = $chat->getInstanciasActivas();
$updated = 0;

foreach ($instancias as $inst) {
    try {
        $evo = new ChatEvolution($inst['base_url'], $inst['api_key']);
        $resp = $evo->connectionState($inst['instance_name']);

        $state = 'unknown';
        if (is_array($resp['data'])) {
            $state = ChatModulo::deepGet($resp['data'], ['instance.state', 'state', 'data.state', 'connectionState'], 'unknown');
        }

        $stmt = $db->prepare("UPDATE chat_instancia SET estado=?, ultimo_check=? WHERE id=?");
        $now = date('Y-m-d H:i:s');
        $state = (string)$state;
        $idInst = (int)$inst['id'];
        $stmt->bind_param("ssi", $state, $now, $idInst);
        $stmt->execute();

        $updated++;
    } catch (Exception $e) {
        // no corta el job
        continue;
    }
}

return ['ok' => true, 'updated' => $updated];
