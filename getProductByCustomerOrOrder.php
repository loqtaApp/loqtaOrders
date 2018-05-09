<?php

include 'slackWebHooks/settings.php';
$data = json_decode($data, true);

$ordersURL = "{$mainURLEndPoint}/orders.json";
$customersURL = "{$mainURLEndPoint}/customers/search.json?query=";

$palpayOauthKey = '83c9c662d05a81a4b58d009fe792e7e953e26a6b';

$dateToVeirifyToken = date("d/m/y H");

$tokenToVeirify = md5($palpayOauthKey . $dateToVeirifyToken);
//die(sha1(uniqid("palpay_operations", true)));

$token = $_GET['tt'];
$retriveKeyValue = ($_GET['idd']);
$actionKey = ($_GET['pay']);
$point_of_sale = ($_GET['pos']);
$payment_amount = ($_GET['amount']);

$payment_currency = ($_GET['currency']) ? $_GET['currency'] :  'ILS';


define('PAY_ACTION', 1);
define('CANCEL_ACTION', 2);

define('PALPAY_TAG', 'PAID_PAL_PAY, '.strtoupper($payment_currency));

define('PALPAY_ORDER_NOTE', ' تم دفع هذا الطلب بواسطة بال بي');
define('PALPAY_ORDER_CANCEL_NOTE', ' تم إلغاء دفع هذا الطلب بواسطة بال بي');



$ordersFilter = "financial_status=pending&fulfillment_status=unshipped&name=";

$note_palpay_elements[] = array('name' => 'palpay_paid_date', 'value' => date("Y/m/d h:i"));
$note_palpay_elements[] = array('name' => 'palpay_pos', 'value' => $point_of_sale);
$note_palpay_elements[] = array('name' => 'payment_amount', 'value' => $payment_amount);
$note_palpay_elements[] = array('name' => 'payment_currency', 'value' => $payment_currency);


//operations
define('LOOK_FOR_CUSTOMER_VIA_CUSTOMER_ID', 1);
define('LOOK_FOR_ORDER_VIA_CUSTOMER_ID', 2);
define('LOOK_FOR_ORDER_VIA_ORDER_ID', 3);
define('PAY_ORDER_VIA_ORDER_ID', 4);
define('CANCEL_ORDER_VIA_ORDER_ID', 5);



$resultMessages = array();
$resultOperations = array();
$resultOrders = array();
$resultStatus = true;
$customerInformation = array();

if ($tokenToVeirify != $token) {
    $resultStatus = false;
    $resultMessages[] = 'Invalid Token, Or Expired Token';
}

if ($resultStatus) {
    function estimateOrderPriceInUSD($order){
        $exRate = $order['total_price']/$order['total_price_usd'];
        return $order["subtotal_price"]/$exRate;
    }
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
        $preparedOrder["subtotal_price"] = $order["subtotal_price"];
        $preparedOrder["subtotal_price_usd"] = estimateOrderPriceInUSD($order);

        $itemTitles = '';
        $itemsSize = sizeof($order["line_items"]);
        $i = 0;
        foreach ($order["line_items"] as $lineItem) {
            $itemTitles .= $lineItem['title'] . ' - ' . $lineItem['price'] . ((($itemsSize - 1) == $i) ? '' : ',');
            $i++;
        }
        $preparedOrder["line_items"] = $itemTitles;
        $preparedOrder["address"] = ($order['shipping_address']["address1"] != null) ? $order['shipping_address']["address1"] : $order['customer']["default_address"]["address1"];

        return $preparedOrder;
    }

    function updateOrderPalPay($requestedOrder, $postNoteData) {
        global $mainURLEndPoint;
        $shopifyParamsURL = $mainURLEndPoint . '/orders/' . $requestedOrder['id'] . ".json";
        $request = new Request($shopifyParamsURL);
        $request->setURL($shopifyParamsURL);
        $request->setHeaders(array(
            'Content-Type:application/json'
        ));
        $request->setData(json_encode($postNoteData));
        $request->setMethod('PUT');

        return $request->execute();
    }

    $numberOfDigits = strlen((string) $retriveKeyValue);
    if ($numberOfDigits < 8) {
        //get orders path
        $params = "?" . $ordersFilter . $retriveKeyValue;
        $requestedOrder = '';


        $orders = getOrdersByLink($ordersURL . $params);
        foreach ($orders as $order) {

            if ($order['order_number'] == $retriveKeyValue) {
                $requestedOrder = $order;
            }
        }
        if (!$orders || $requestedOrder == '') {
            $resultOperations[] = array(LOOK_FOR_ORDER_VIA_ORDER_ID => false);
            $resultMessages[] = 'No orders found for "' . $retriveKeyValue . '"';
            $resultStatus = false;
        } else {

            $resultOperations[] = array(LOOK_FOR_ORDER_VIA_ORDER_ID => true);
            $resultMessages[] = 'Order found with number "' . $retriveKeyValue . '"';
            $customerInformation["first_name"] = $orders[0]['customer']["first_name"];
            $customerInformation["last_name"] = $orders[0]['customer']["last_name"];
            $customerInformation["phone"] = ($orders[0]['customer']["phone"] != null) ? $orders[0]['customer']["phone"] : $orders[0]['customer']["default_address"]["phone"];

            $preparedOrder = getPreparedOrderInformation($requestedOrder);
            $resultOrders = [$preparedOrder];

            if ($actionKey == PAY_ACTION) {

                $postNoteData['order']['id'] = $requestedOrder['id'];
                $postNoteData['order']['tags'] = (array_key_exists('tags', $requestedOrder) && $requestedOrder['tags'] != '') ? $requestedOrder['tags'] . ', ' . PALPAY_TAG : PALPAY_TAG;
                if (array_key_exists('note_attributes', $requestedOrder) && is_array($requestedOrder['note_attributes'])) {
                    $note_attributes = $requestedOrder['note_attributes'];
                    foreach ($note_palpay_elements as $note_palpay_element)
                        array_push($note_attributes, $note_palpay_element);
                } else {
                    $note_attributes = $note_palpay_elements;
                }
                $postNoteData ['order']['note_attributes'] = $note_attributes;
                $postNoteData ['order']['note'] = (array_key_exists('note', $requestedOrder) && $requestedOrder['note'] != '') ? $requestedOrder['note'] . ' ' . PALPAY_ORDER_NOTE : PALPAY_ORDER_NOTE;
                $shopifyResponse = updateOrderPalPay($requestedOrder, $postNoteData);

                if (array_key_exists('order', $shopifyResponse) && $shopifyResponse['order']['id']) {
                    $resultMessages[] = 'Order "' . $retriveKeyValue . '" was paid Successfully';
                    $resultOperations[] = array(PAY_ORDER_VIA_ORDER_ID => true);
                } else {
                    $resultMessages[] = 'Error in pay order "' . $retriveKeyValue . '"';
                    $resultOperations[] = array(PAY_ORDER_VIA_ORDER_ID => false);
                    $resultStatus = false;
                }
            }
            if ($actionKey == CANCEL_ACTION) {
                $tagsStr = str_replace(', ' . PALPAY_TAG, "", $requestedOrder['tags']);
                $tagsStr = str_replace(PALPAY_TAG, "", $tagsStr);


                $postNoteData['order']['tags'] = $tagsStr;
                $postNoteData['order']['note'] = $requestedOrder['note'] . ' ' . PALPAY_ORDER_CANCEL_NOTE;

                $note_attributes = $requestedOrder['note_attributes'];
                array_push($note_attributes, array('name' => 'palpay_cancel_date', 'value' => date("Y/m/d h:i")));
                $postNoteData['order']['note_attributes'] = $note_attributes;

                $shopifyResponse = updateOrderPalPay($requestedOrder, $postNoteData);
                if (array_key_exists('order', $shopifyResponse) && $shopifyResponse['order']['id']) {
                    $resultMessages[] = 'Order "' . $retriveKeyValue . '" was canceled Successfully';
                    $resultOperations[] = array(CANCEL_ORDER_VIA_ORDER_ID => true);
                } else {
                    $resultMessages[] = 'Error in cancel order "' . $retriveKeyValue . '"';
                    $resultOperations[] = array(CANCEL_ORDER_VIA_ORDER_ID => false);
                    $resultStatus = false;
                }
            }
        }
    } else {
        $email = $retriveKeyValue . '@loqta.ps';
        $requestedCustomer = '';

        //get customer path
        $customersURL = $customersURL . 'email:' . $email . ';fields=id,email,phone';
        $request = new Request($customersURL);
        $response = $request->execute();
        if (array_key_exists('customers', $response) && sizeof($response["customers"]) > 0) {
            $resultOperations[] = array(LOOK_FOR_CUSTOMER_VIA_CUSTOMER_ID => true);
            $customers = $response["customers"];
            foreach ($customers as $customer) {
                if ($customer['email'] == $email) {
                    $requestedCustomer = $customer;
                }
            }
            $customerID = $requestedCustomer["id"];
            $orders = getOrdersByLink($ordersURL . "?customer_id=" . $customerID . '&' . $ordersFilter);

            if (!$orders || $requestedCustomer == '') {
                $resultOperations[] = array(LOOK_FOR_ORDER_VIA_CUSTOMER_ID => false);

                $resultMessages[] = 'No orders found for customer "' . $customer['first_name'] . '"';
            } else {
                $resultOperations[] = array(LOOK_FOR_ORDER_VIA_CUSTOMER_ID => true);
                $preparedOrders = [];
                foreach ($orders as $order) {
                    $preparedOrders[] = getPreparedOrderInformation($order);
                }

                $customerInformation["first_name"] = $orders[0]['customer']["first_name"];
                $customerInformation["last_name"] = $orders[0]['customer']["last_name"];
                $customerInformation["phone"] = ($orders[0]['customer']["phone"] != null) ? $orders[0]['customer']["phone"] : $orders[0]['customer']["default_address"]["phone"];
                
                $resultOrders = ($preparedOrders);
            }
        } else {
            $resultOperations[] = array(LOOK_FOR_CUSTOMER_VIA_CUSTOMER_ID => false);
            //no customer found
            $resultMessages[] = 'No customer name found for id "' . $retriveKeyValue . '"';
        }
    }
}
$result_of_operations = array(
    'orders' => $resultOrders,
    'customer' => $customerInformation,
    'operations' => $resultOperations,
    'status' => $resultStatus,
    'messages' => $resultMessages
);

die(json_encode($result_of_operations));
