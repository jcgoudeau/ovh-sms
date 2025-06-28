<?php
// === Paramètres OVH ===
$applicationKey    = "fcc15c3fa2457f17";
$applicationSecret = "12cca57df3c39124ef8a9fe9885be9f4";
$consumerKey       = "4d5e38cfa13f2511f747f3c20fce2051";
$endpoint          = "ovh-eu";
$serviceName       = "smpp-pl830700-1";  // ton identifiant de service SMS OVH

// === Paramètres depuis l’URL GET ===
$receiver = $_GET['to'] ?? '';
$message = $_GET['message'] ?? '';

if (!$receiver || !$message) {
  echo "Paramètres manquants";
  exit;
}

// === Corps de la requête ===
$data = [
  'charset'     => 'UTF-8',
  'class'       => 'phoneDisplay',
  'coding'      => '7bit',
  'message'     => 'Alerte',
  'noStopClause'=> true,
  'priority'    => 'high',
  'receivers'   => ['0033789598938'],
  'sender'      => 'GOUDEAU' // max 11 caractères
];

// === Timestamp OVH ===
$time = file_get_contents("https://$endpoint.api.ovh.com/1.0/auth/time");

// === Signature ===
$body = json_encode($data);
$method = "POST";
$url = "/1.0/sms/$serviceName/jobs";
$signature = "$applicationSecret+$consumerKey+$method+https://$endpoint.api.ovh.com$url+$body+$time";
$sig = '$1$' . sha1($signature);

// === Envoi de la requête ===
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$endpoint.api.ovh.com$url");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "X-Ovh-Application: $applicationKey",
  "X-Ovh-Consumer: $consumerKey",
  "X-Ovh-Signature: $sig",
  "X-Ovh-Timestamp: $time"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// === Gestion des erreurs
if ($response === false) {
    $error = curl_error($ch);
    echo "Erreur cURL : " . $error;
} else {
    echo "HTTP OVH : $httpCode\n";
    echo "Réponse OVH : $response";
}

curl_close($ch);
?>
