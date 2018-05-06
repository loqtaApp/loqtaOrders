<?php

include 'settings.php';
$data = json_decode($data, true);

$ordersURL = "{$mainLink}/orders.json";
$customersURL = "{$mainLink}/customers/search.json?query=";

$retriveKeyValue = intval($_GET['idd']);
$numberOfDigits = strlen((string) $retriveKeyValue);
if ($numberOfDigits < 8) {
    //get orders path
    $params = "?status=any&name=";
    $restRequest->setUrl($mainURL . $params . $ordersNum);
    $request = new Request($ordersURL . $params . $ordersNum);
    $response = $request->execute();

    if (array_key_exists('orders', $response) && sizeof($response["orders"]) > 0) {
        $orders = $response['orders'];
        var_dump($orders);
    } else {
        //no orders found
        echo 'No orders found for ' . $retriveKeyValue;
    }
} else {
    //get customer path
    $customersURL = $customersURL . $retriveKeyValue;
    $request = new Request($customersURL);
    $response = $request->execute();
    if (array_key_exists('customers', $response) && sizeof($response["customers"]) > 0) {
        $customer = $response["customers"][0];
        $customerID = $customer["id"];
        $request = new Request($ordersURL . "?customer_id=" . $customerID);
        $response = $request->execute();
        if (array_key_exists('orders', $response) && sizeof($response["orders"]) > 0) {
            $orders = $response['orders'];
            var_dump($orders);

        } else {
            //no orders found
            echo 'No orders found for ' . $customer['first_name'];
        }
    } else {
        //no customer found
        echo 'No customer name found for id = ' . $retriveKeyValue;
    }
}


//$mainURL = $mainURLEndPoint . "/orders/" . $data['id'] . "/metafields.json";
//
//if ($data['cancelled_at'] != '') {
//    $key = 'cancel_date';
//} 
//elseif ($data['financial_status'] == 'paid') {
//    $key = 'paid_date';
//}
//elseif ($data['fulfillment_status'] == 'fulfilled') {
//    $key = 'fulfill_date';
//}
//
//$metaFieldData = array(
//    'metafield' => array(
//        'namespace' => 'operations',
//        'key' => $key,
//        'value' => date('d/m/Y h:i:s A'),
//        "value_type" => "string"
//    )
//);
//$headers = array(
//    'Content-Type:application/json'
//);
//$request = new Request($mainURL, 'POST', json_encode($metaFieldData));
//$request->setHeaders($headers);
//$result = $request->execute(false);


