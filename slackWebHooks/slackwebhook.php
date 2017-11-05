<?php

define('NO_DEFAULT_HEADERS', true);
include 'settings.php';
//initialize request  to create order with wehook
//define the link for the slack (out Gaza or in Gaza)

$outGazaCarrier = 'CarrierMohammed';
date_default_timezone_set("Asia/Gaza");
$todayDate = date("Y/m/d h:i");
$orderStatus = '';
$orderNoteForShortLink = 'shortAdminLink';
$orderNoteShortLinkValue = '';
//////////////////slack

$channel = 'C6LB5HXD0';
$text = '';
$fullfilledStatusReaction = ':racing_motorcycle:';
$paidStatusReaction = ':100:';
$cancelStatusReaction = ':x:';
$slackMSGTS = 'slack_message_id';
$token = base64_decode("eG94cC0xOTU3OTAyMDY4MjEtMTk1NzkwMjA2OTQ5LTI2ODAxODUxNTEyNi04NzQyOTBhZTc5MzQ2M2UyM2JlN2Q1NjhiN2Y5YWJhNg==");
$tsMSG_ID = '';
$postMessageEndPoint = "https://slack.com/api/chat.postMessage";
$updatePostMessageEndPoint = "https://slack.com/api/chat.update";

//////////////////slack
//define the client source if possible 
//https://github.com/firebase/quickstart-js/tree/master/messaging

$data = json_decode($data, true);



/// Get all the order information 
$request = new Request($mainURLEndPoint . '/orders.json?ids=' . $data['id'] . '&status=any');
$response = $request->execute();
$order = $response['orders'][0];

//////////////////
$pushNotificationToken = '';
$pushNotificationTokenAttribute = 'PushNotificationToken';
$fcmEndPoint = "https://fcm.googleapis.com/fcm/send";
$webAPIKey = base64_decode('QUFBQVoyUFNSRTQ6QVBBOTFiSHU4dmJrMDZGYXhwYktLZWsyYnFnLThkeV9UdU4yeUQ1QktKUWhEU0FZMFJrc1Z6RURFUHppaWhrcjg5VmVDM21OR0luTlRtcHRWVUxETG9zMjVFZ0lRcEo1MUJLd251alRfdS1aNENxZjZiaHNQbkRxTUlCem9fZjFodVB0dVNNY1RrdWo=');
$fullFillmentPushMSGTitle = ' تم خروج طلب رقم';
$fullFillmentPushMSGTitle .= $order['name'];
$fullFillmentPushMSGBody .= 'شكرا لتعاملك معنا';

$paidStatusPushMSGTitle = ' تم تأكيد إستلام طلب رقم';
$paidStatusPushMSGTitle .= $order['name'];
$paidStatusPushMSGBody = 'شكرا لتعاملك معنا';


$cancelPushMSGTitle = ' تم إلغاء طلب رقم';
$cancelPushMSGTitle .= $order['name'];
$cancelPushMSGBody .= 'شكرا لتعاملك معنا';
//////////////////
//
// check the order params
$notesArray = array();
foreach ($order['note_attributes'] as $note) {
    //push notification token 
    $notesArray[$note['name']] = $note['value'];

    if ($note['name'] == $pushNotificationTokenAttribute) {
        $pushNotificationToken = $note['value'];
    }
    if ($note['name'] == $slackMSGTS) {
        $tsMSG_ID = $note['value'];
    }
    if($note['name'] == $orderNoteForShortLink){
        $orderNoteShortLinkValue = $note['value'];
    }
}



$order = $response['orders'][0];        
// google short link
if($orderNoteShortLinkValue == ''){
    $request = new Request($googleShortURL);
    $request->setMethod('POST');
    $request->setData(json_encode(array('longUrl' => $shopifyOrderLink.$order['id'])));
    $response = $request->execute();
    $orderNoteShortLinkValue = $response['id'];
    $notesArray[$orderNoteForShortLink] = $response['id'];
}


$slackMSG = $order['name'];
//define the action the we want to do (Reply, Post Message ..etc)
//if fullfilled 
if (strpos(strtolower($order['tags']), strtolower($outGazaCarrier)) !== FALSE) {
    //out side gaza channel
    $channel = 'C6KU0SLSV';
}

if ($order['cancelled_at'] != '') { //update existing 
    $slackMSG = $slackMSG . ' ' . $cancelStatusReaction;
    if ($pushNotificationToken != '') {
        //send Push Notification
        $notificationArr = Array(
            'notification' => array(
                'title' => $cancelPushMSGTitle,
                'body' => $cancelPushMSGBody,
            ),
            'to' => $pushNotificationToken,
        );
    }
    $orderStatus = 'ملغي';
} elseif ($order['financial_status'] == 'paid') { //update existing 
    $slackMSG = $slackMSG . ' ' . $paidStatusReaction;
    if ($pushNotificationToken != '') {
        //send Push Notification
        $notificationArr = Array(
            'notification' => array(
                'title' => $paidStatusPushMSGTitle,
                'body' => $paidStatusPushMSGBody
            ),
            'to' => $pushNotificationToken,
        );
    }
    $orderStatus = 'مدفوع';
} elseif ($order['fulfillment_status'] == 'fulfilled') {
    //get the carriar information 
    $orderStatus = 'شحن';
    $slackMSG = $slackMSG . ' ' . $fullfilledStatusReaction;
    //in Gaza channel
    //get the total information
    if ($pushNotificationToken != '') {
        //send Push Notification
        $notificationArr = Array(
            'notification' => array(
                'title' => $fullFillmentPushMSGTitle,
                'body' => $fullFillmentPushMSGBody,
            ),
            'to' => $pushNotificationToken,
        );
    }
}
$slackMSG.= ' ('.$order['customer']['first_name']. ' ' .$order['customer']['last_name'].')';

$slackMSG.= ' - ('.$order['subtotal_price'].')';
if($order['note'] != ''){
    $slackMSG.= ' -- '.$order['note'];
}
if($orderNoteShortLinkValue != ''){
    $slackMSG.= ' - '.$orderNoteShortLinkValue;
}
/**/
$slackMSGRequestArray = array();
$slackMSGRequestArray['channel'] = $channel;
$slackMSGRequestArray['text'] = $slackMSG;

$request->setHeaders(array(
    'Content-Type:application/json',
    'Authorization:Bearer ' . $token
));
$request->setMethod('POST');

if ($tsMSG_ID != '') { //slack message before
    $slackMSGRequestArray['ts'] = $tsMSG_ID;
    $request->setURL($updatePostMessageEndPoint);
    $request->setData(json_encode($slackMSGRequestArray));
    $slackResponse = $request->execute();
    if(!array_key_exists('ts', $slackResponse)){
        $tsMSG_ID = '';
    }
    
} 
if($tsMSG_ID == ''){//new slack message
    $request->setURL($postMessageEndPoint);
    $request->setData(json_encode($slackMSGRequestArray));
    $slackResponse = $request->execute();
    $tsMSG_ID = $slackResponse['ts'];
    if($tsMSG_ID != ''){
        $notesArray[$slackMSGTS] = $tsMSG_ID;
        //we must update the order with the new message ID
        $postNoteData ['order']['id'] = $order['id'];
        $postNoteData ['order']['note_attributes'] = $notesArray;

        $shopifyParamsURL = $mainURLEndPoint . '/orders/' . $order['id'] . ".json";
        $request->setURL($shopifyParamsURL);
        $request->setHeaders(array(
            'Content-Type:application/json'
            ));    
        $request->setData(json_encode($postNoteData));
        $request->setMethod('PUT');
        $shopifyResponse = $request->execute();
    }
}
//second message for the order status and date change
if($tsMSG_ID != ''){
    $slackMSGRequestArray = array();
    $slackMSGRequestArray['channel'] = $channel;
    $slackMSGRequestArray['text'] = $orderStatus . ' - ' . $todayDate;
    $slackMSGRequestArray['thread_ts'] = $tsMSG_ID;
    $request->setHeaders(array(
        'Content-Type:application/json',
        'Authorization:Bearer ' . $token
    ));    
    $request->setMethod('POST');    
    $request->setURL($postMessageEndPoint);
    $request->setData(json_encode($slackMSGRequestArray));
    $request->execute();
}

// push notification to the client
if ($pushNotificationToken != '' && sizeof($notificationArr) > 0) {

    //send the push notification
    $request->setURL($fcmEndPoint);
    $request->setHeaders(array(
        'Content-Type:application/json',
        'Authorization:key= ' . $webAPIKey,
        'Host:fcm.googleapis.com'
    ));
    $request->setData(json_encode($notificationArr));
    $request->setMethod('POST');
    $response = $request->execute();
}
    //Post the message order with motocycle reaction 
    //if the order contain token for FCM 
        //Post fCM message about fullfillmenet status 
      
//Post the message order 
//Get channel, Messaage Ts 
//Save it in the same order info 
/*
$postNoteData ['order']['id'] = $postOrderInfo['Order_ID'];
$postNoteData ['order']['note_attributes']['slack_message_id'] = $rowData[0];
$shopifyParamsURL = $mainURLEndPoint.$orderDataSet['id'] . ".json";

*/
/***thread_ts 
 * initliaze request

$headers = array(
	'Content-Type:application/json',
        'Authorization:Bearer '.$token
);
$request = new Request($postMessageEndPoint, 'POST', $data);
$request->setHeaders($headers);
$result = $request->execute(false);

echo $result;

/**
 * thread_ts
 * {"channel":"C5R3RPLQZ","text":"I hope the tour went well, Mr. Wonka.","attachments":[{"text":"Who wins the lifetime supply of chocolate?","fallback":"You could be telling the computer exactly what it can do with a lifetime supply of chocolate.","color":"#3AA3E3","attachment_type":"default","callback_id":"select_simple_1234","actions":[{"name":"winners_list","text":"Who should win?","type":"select","data_source":"users"}]}]}

 */