<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,OPTIONS');
header('Cache-Control: no-cache');
header('Pragma: no-cache');
//(0);
session_start();
require_once __DIR__ . '/lib/google/vendor/autoload.php';
define('OAUTH2_CLIENT_ID', '1089990018340-frdjldsicdgrbn7r637b63brstqj0fie.apps.googleusercontent.com');
define('OAUTH2_CLIENT_SECRET', 'QY1xdsM49JzQ-AmujkyzSl6b');
$key = file_get_contents('token.txt');
$data = json_decode(file_get_contents('php://input'), true);
// Client init
$client = new Google_Client();
$client->setClientId(OAUTH2_CLIENT_ID);
$client->setAccessType('offline');
$client->setApprovalPrompt('force');
$client->setAccessToken($key);
$client->setClientSecret(OAUTH2_CLIENT_SECRET);
/**
 * Check to see if our access token has expired. If so, get a new one and save it to file for future use.
 */
if ($client->isAccessTokenExpired()) {
    $newToken = json_encode($client->getAccessToken());
    $client->refreshToken($newToken->refresh_token);
    file_put_contents('token.txt', json_encode($client->getAccessToken()));
}
$client->setScopes('https://www.googleapis.com/auth/spreadsheets');
// Define an object that will be used to make all API requests.
// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_SESSION[$tokenSessionKey])) {
    $client->setAccessToken($_SESSION[$tokenSessionKey]);
}
if ($client->getAccessToken()) {
    $spreadsheetId = '1v0gHqEXScAqnBg9hudpGfINGyKVQUnS--Co0UVgfBkc';
    $rowData = array();
    /// read from Excel ordersbeforePaid sheet to get the custom data  
    $service = new Google_Service_Sheets($client);
    $orderfoundFlag = false;
    //check if in orders sheet 
    if (!$orderfoundFlag) {
        $range = 'fullfilled!A:E';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $count = count($values) - 1;
        $i = '';
        if (count($values) == 0) {
            
        } else {
            for ($i = $count; $i >= 0; $i --) {
                // Print columns A and E, which correspond to indices 0 and 4.
                if ($values[$i][1] == $data['name']) {
                    $rowData = $values[$i];
                    break;
                }
            }
        }
        if (sizeof($rowData) > 0) {
            ///Delete the order from Orders Sheet and store it in Fullfilled sheet
            $orderfoundFlag = true;
            $requests[] = new Google_Service_Sheets_Request(array(
                'deleteDimension' => array('range' => array(
                        'sheetId' => 497374135,
                        'dimension' => "ROWS",
                        'startIndex' => $i,
                        'endIndex' => ($i + 1),
                    )
            )));
// Add additional requests (operations) ...

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                'requests' => $requests
            ));

            $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }
    } elseif (!$orderfoundFlag) {

        //check if in paid sheet 
        $range = 'paid!A:E';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $count = count($values) - 1;
        $i = '';
        if (count($values) == 0) {
            
        } else {
            for ($i = $count; $i >= 0; $i --) {
                // Print columns A and E, which correspond to indices 0 and 4.
                if ($values[$i][1] == $data['name']) {
                    $rowData = $values[$i];
                    break;
                }
            }
        }
        if (sizeof($rowData) > 0) {
            ///Delete the order from Orders Sheet and store it in Fullfilled sheet
            $orderfoundFlag = true;

            $requests[] = new Google_Service_Sheets_Request(array(
                'deleteDimension' => array('range' => array(
                        'sheetId' => 297238844,
                        'dimension' => "ROWS",
                        'startIndex' => $i,
                        'endIndex' => ($i + 1),
                    )
            )));
// Add additional requests (operations) ...

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                'requests' => $requests
            ));

            $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }
    } elseif (!$orderfoundFlag) {
        //check if in orders sheet 
        $range = 'orders!A:E';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $count = count($values) - 1;
        $i = '';
        if (count($values) == 0) {
            
        } else {
            for ($i = $count; $i >= 0; $i --) {
                // Print columns A and E, which correspond to indices 0 and 4.
                if ($values[$i][1] == $data['name']) {
                    $rowData = $values[$i];
                    break;
                }
            }
        }
        if (sizeof($rowData) > 0) {
            ///Delete the order from Orders Sheet and store it in Fullfilled sheet
            $orderfoundFlag = true;

            $requests[] = new Google_Service_Sheets_Request(array(
                'deleteDimension' => array('range' => array(
                        'sheetId' => 0,
                        'dimension' => "ROWS",
                        'startIndex' => $i,
                        'endIndex' => ($i + 1),
                    )
            )));
// Add additional requests (operations) ...

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                'requests' => $requests
            ));

            $response = $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }
    }

    /////write on excel 
    $values = array(
        array(
            $data['id'],
            $data['name'],
            $data['customer']['first_name'] . ' ' . $data['customer']['last_name'],
            $data['created_at'],
            $data['total_price']
        // Cell values ...
        ),
            // Additional rows ...
    );
    $range = 'cancelled!A2:E';
    $body = new Google_Service_Sheets_ValueRange(array(
        'values' => $values
    ));
    $params = array(
        'valueInputOption' => "RAW"
    );
    $result = $service->spreadsheets_values->append('1v0gHqEXScAqnBg9hudpGfINGyKVQUnS--Co0UVgfBkc', $range, $body, $params);
} elseif (OAUTH2_CLIENT_ID == 'REPLACE_ME') {
    $OAUTH2_CLIENT_ID = OAUTH2_CLIENT_ID;
    $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
    // If the user hasn't authorized the app, initiate the OAuth flow
    $state = mt_rand();
    $client->setState($state);
    $_SESSION['state'] = $state;

    $authUrl = $client->createAuthUrl();
    $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>
