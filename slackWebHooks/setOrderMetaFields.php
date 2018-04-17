<?php

include 'settings.php';
$data = json_decode($data, true);

$mainURL = $mainURLEndPoint . "/orders/" . $data['id'] . "/metafields.json";

if ($order['fulfillment_status'] == 'fulfilled') {
    $key = 'fulfill_date';
} elseif ($data['cancelled_at'] != '') {
    $key = 'cancel_date';
} elseif ($data['financial_status'] == 'paid') {
    $key = 'paid_date';
}

$metaFieldData = array(
    'metafield' => array(
        'namespace' => 'operations',
        'key' => $key,
        'value' => date('d/m/Y h:i:s A'),
        "value_type" => "string"
    )
);
$headers = array(
    'Content-Type:application/json'
);
$request = new Request($mainURL, 'POST', json_encode($metaFieldData));
$request->setHeaders($headers);
$result = $request->execute(false);


echo $result;
