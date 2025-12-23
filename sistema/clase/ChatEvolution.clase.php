<?php

class ChatEvolution
{
    private $baseUrl;
    private $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;
    }

public function request($method, $path, $payload = null) {
    $url = rtrim($this->base_url, '/') . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'apikey: ' . $this->api_key,  // si tu Evolution usa header apikey
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) {
        return ['ok'=>false, 'code'=>$code, 'error'=>$err ?: 'curl_error', 'data'=>null];
    }

    $data = json_decode($res, true);
    if (!is_array($data)) $data = $res;

    return ['ok'=>($code>=200 && $code<300), 'code'=>$code, 'error'=>null, 'data'=>$data];
}

public function getQrCode($instanceName) {
    /**
     * NOTA: El endpoint exacto varía según versión de Evolution.
     * Muchos montajes exponen algo como:
     * - GET /instance/connect/{instanceName}
     * - GET /instance/qrcode/{instanceName}
     * - GET /instance/qr/{instanceName}
     *
     * Ajusta el PATH si tu Evolution usa otro.
     */
    $r = $this->request('GET', '/instance/qrcode/' . urlencode($instanceName));
    if (!$r['ok']) {
        // devuelve mensaje claro
        throw new Exception('Evolution QR error: HTTP '.$r['code']);
    }

    // Intenta encontrar base64 o data-url en diferentes formas
    $d = $r['data'];
    if (is_array($d)) {
        if (isset($d['base64'])) return 'data:image/png;base64,' . $d['base64'];
        if (isset($d['qrcode'])) return $d['qrcode'];
        if (isset($d['qr'])) return $d['qr'];
    }
    if (is_string($d)) return $d;

    throw new Exception('No se pudo interpretar el QR retornado por Evolution.');
}

public function logout($instanceName) {
    $r = $this->request('DELETE', '/instance/logout/' . urlencode($instanceName));
    if (!$r['ok']) {
        throw new Exception('Evolution logout error: HTTP '.$r['code']);
    }
    return $r['data'];
}


    public function connectionState($instance)
    {
        // GET /instance/connectionState/{instance}
        return $this->request('GET', '/instance/connectionState/' . urlencode($instance));
    }

    public function sendText($instance, $numberE164, $text)
    {
        // POST /message/sendText/{instance}
        return $this->request('POST', '/message/sendText/' . urlencode($instance), [
            'number' => $numberE164,
            'text'   => $text,
        ]);
    }

    public function setWebhookInstance($url, $events, $byEvents = false, $base64 = false)
    {
        // POST /webhook/instance
        return $this->request('POST', '/webhook/instance', [
            'url'              => $url,
            'webhook_by_events'=> (bool)$byEvents,
            'webhook_base64'   => (bool)$base64,
            'events'           => array_values($events),
        ]);
    }
}