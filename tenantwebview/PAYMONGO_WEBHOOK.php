<?php
// Raw POST body from PayMongo
$raw_payload = file_get_contents("php://input");
$event = json_decode($raw_payload, true);

// For logging (optional but recommended during testing)
file_put_contents("webhook_log.txt", $raw_payload . PHP_EOL, FILE_APPEND);

// Example: handle payment.paid event
if (isset($event['data']['attributes']['status']) && $event['data']['attributes']['status'] === 'paid') {
    $payment_id = $event['data']['id'];
    $amount = $event['data']['attributes']['amount'];
    $source_id = $event['data']['attributes']['source']['id'];
    $description = $event['data']['attributes']['description'];

    // TODO: Match this source_id or description with your DB entry
    // and mark the payment as "confirmed" or "successful"
}
?>
