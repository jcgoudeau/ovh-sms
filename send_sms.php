<?php

// Récupération des paramètres GET
$to = $_GET['to'] ?? '';
$message = $_GET['message'] ?? '';

if (!$to || !$message) {
    http_response_code(400);
    echo "Paramètres manquants";
    exit;
}

// Clés stockées dans les variables d'environnement Render
$appKey      = getenv('OVH_APP_KEY');
$appSecret   = getenv('OVH_APP_SECRET');
$consumerKey = getenv('OVH_CONSUMER_KEY');
$serviceName = getenv('OVH_SERVICE_NAME');  // Ex: "sms-xxxxxx"

$endpoint = "ovh-eu";  // Garder ce endpoint pour la France

// Étape 1 : Obtenir l'heure OVH
$ch = curl_init("https://$endpoint.api.ovh.com/1.0/auth/time");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$serverTime = curl_exec($ch);
curl_close($ch);

if (!$serverTime) {
    echo "Erreur : impossible d'obtenir l'heure OVH";
    exit;
}

// Étape 2 : Préparation de la requête POST
$url = "https://$endpoint.api.ovh.com/1.0/sms/$serviceName/jobs";
$postData = json_encode([
    "sender" => "ESP32",
    "recipient" => [$to],
    "message" => $message,
    "priority" => "high",
    "charset" => "UTF-8",
    "coding" => "7bit"
]);

$timestamp = time() + ($serverTime - time());
$toSign = "POST" . "+" . $url . "+" . $postData . "+" . $consumerKey . "+" . $appSecret . "+" . $timestamp;
$signature = "$1$" . hash('sha1', $toSign);

$headers = [
    "Content-Type: application/json",
    "X-Ovh-Application: $appKey",
    "X-Ovh-Timestamp: $timestamp",
    "X-Ovh-Signature: $signature",
    "X-Ovh-Consumer: $consumerKey"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch);
} else {
    echo "Réponse OVH : " . $response;
}

curl_close($ch);
