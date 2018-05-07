<?php

include 'slackWebHooks/settings.php';
$data = json_decode($data, true);

$ordersURL = "{$mainURLEndPoint}/orders.json";
$customersURL = "{$mainURLEndPoint}/customers/search.json?query=";

define('PAY_ACTION', 1);
define('CANCEL_ACTION', 2);

$retriveKeyValue = ($_GET['idd']);
$actionKey = ($_GET['pay']);

$ordersFilter = "financial_status=pending&fulfillment_status=unshipped&name=";

function getOrdersByLink($link) {
    $request = new Request($link);
    $response = $request->execute();
    if (array_key_exists('orders', $response) && sizeof($response["orders"]) > 0) {
        return $response['orders'];
    } else {
        //no orders found
        return false;
    }
}

function getPreparedOrderInformation($order) {
    $preparedOrder['order_number'] = $order['order_number'];
    $preparedOrder['customer']["first_name"] = $order['customer']["first_name"];
    $preparedOrder['customer']["last_name"] = $order['customer']["last_name"];
    $preparedOrder['customer']["phone"] = ($order['customer']["phone"] == null) ? $order['customer']["phone"] : $order['customer']["default_address"]["phone"];
    $preparedOrder["subtotal_price"] = $order["subtotal_price"];
    $itemTitles = '';
    $itemsSize = sizeof($order["line_items"]);
    $i = 0;
    foreach ($order["line_items"] as $lineItem) {
        $itemTitles .= $lineItem['title'] . ' ' . '(' . $lineItem['price'] . ') ' . ($itemsSize == ($i - 1)) ? '' : ',';
        $i++;
    }
    $preparedOrder["line_items"] = $itemTitles;

    return $preparedOrder;
}

$numberOfDigits = strlen((string) $retriveKeyValue);
if ($numberOfDigits < 8) {
    //get orders path
    $params = "?" . $ordersFilter . $retriveKeyValue;
    $requestedOrder = '';

    $orders = getOrdersByLink($ordersURL . $params . $ordersNum);
    foreach ($orders as $order) {
        if ($order['order_number'] == $ordersNum) {
            $requestedOrder = $ordersNum;
        }
    }
    if (!$orders || $requestedOrder == '') {
        echo 'No orders found for ' . $retriveKeyValue;
    }
    else{
        return getPreparedOrderInformation([$requestedOrder]);
    }
} else {
    $email = $retriveKeyValue . '@loqta.ps';
    $requestedCustomer = '';

    //get customer path
    $customersURL = $customersURL . 'email:' . $email . ';fields=id,email,phone';
    $request = new Request($customersURL);
    $response = $request->execute();
    if (array_key_exists('customers', $response) && sizeof($response["customers"]) > 0) {
        $customers = $response["customers"];
        foreach ($customers as $customer) {
            if ($customer['email'] == $email) {
                $requestedCustomer = $customer;
            }
        }
        $customerID = $requestedCustomer["id"];
        $orders = getOrdersByLink($ordersURL . "?customer_id=" . $customerID . '&' . $ordersFilter);
        if (!$orders || $requestedCustomer == '') {
            echo 'No orders found for ' . $customer['first_name'];
        }
    } else {
        //no customer found
        echo 'No customer name found for id = ' . $retriveKeyValue;
    }
}