<?php 
error_reporting(0);
include 'request.php';

$mainURLEndPoint = 'https://f3aa0d6659405ab34f9c0af85d0f2ef9:590b142f0e9922bd187703cd6729bae8@loqta-ps.myshopify.com/admin';
$shopifyOrderLink = 'https://loqta-ps.myshopify.com/admin/orders/';
$googleKey = 'AIzaSyDWg8Hx5yxkqj2KPevTGyEiXpzKAsgtMts';
$googleShortURL = "https://www.googleapis.com/urlshortener/v1/url?key=${googleKey}";
$data = file_get_contents('php://input');
$postOrderInfo = array();

if(!defined('NO_DEFAULT_HEADERS')){
    header('Content-Type: application/json');
    header('Accept: application/json');
}
date_default_timezone_set ( 'Asia/Gaza' );
