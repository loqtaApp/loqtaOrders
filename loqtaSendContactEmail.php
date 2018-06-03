<?php
header('Content-Type: application/json');

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
//die(sha1(uniqid("loqta_sendContactEmail", true)));
//f1e1f4421d57660a7fa3bafe7c569eb41899314c
include 'lib/phpmailer/PHPMailerAutoload.php';
error_reporting(0);

$loqtaContactKey = "f1e1f4421d57660a7fa3bafe7c569eb41899314c";
$dateToVeirifyToken = date("d/m/y H");
 

$tokenToVeirify = base64_encode($loqtaContactKey . $dateToVeirifyToken);

$token = $_GET['tt'];
$name = ($_POST['name']) ? $_POST['name'] : '';
$region = ($_POST['region']) ? $_POST['region'] : '';
$mobileNumber = ($_POST['mobileNumber']) ? $_POST['mobileNumber'] : '';
$email = ($_POST['email']) ? $_POST['email'] : '';
$reason = ($_POST['reason']) ? $_POST['reason'] : '';
$subject = ($_POST['subject']) ? $_POST['subject'] : '';
$message = ($_POST['message']) ? $_POST['message'] : '';
$subjectToSend = "Loqta Subject: ".$subject;
$formatedMessageToSend = "Name:".$name." <br><br> Region:".$region." <br><br> Mobile Number:".$mobileNumber." <br><br> Email:".$email."<br><br> Reason:".$reason."<br><br> Message:".$message." ";

if ($tokenToVeirify != $token) {
    $resultStatus = false;
    $resultMessages['status'] = false;
    $resultMessages['message'] = 'Invalid Token, Or Expired Token';
    echo json_encode($resultMessages);
    
}else{





//Load Composer's autoloader

$mail = new PHPMailer();                              // Passing `true` enables exceptions
try {


       // $mail->IsSMTP(); // telling the class to use SMTP
        $mail->SMTPDebug = 0;                     // enables SMTP debug information (for testing)
        // 1 = errors and messages
        // 2 = messages only
        $mail->SMTPAuth = true;                  // enable SMTP authentication
        $mail->SMTPSecure = "tls";
        $mail->Host = "smtp.gmail.com";      // SMTP server
        $mail->Port = 587;                   // SMTP port
        $mail->Username = "loqta.ps2016@gmail.com";  // username
        $passord = "\$uperStar18@gm";
        $mail->Password = $passord;            // password
        
        $mail->SetFrom('loqta.ps2016@gmail.com', 'Loqta');
        $mail->Subject = $subjectToSend;
        $mail->MsgHTML($formatedMessageToSend);
      //Recipients
       $mail->AddAddress('mona.subaih@gmail.com');     // Add a recipient
        $mail->AddCC('eng.hkurd@gmail.com');

      $mail->isHTML(true);                                  // Set email format to HTML
      $mail->Body    = $formatedMessageToSend;
    

    if($mail->send()){
      $resultMessages['status'] = true;
    $resultMessages['message'] = 'send!!';
    echo json_encode($resultMessages);
    }else{
       $resultMessages['status'] = false;
      
    $resultMessages['message'] = 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
    echo json_encode($resultMessages);
    }
} catch (Exception $e) {
      $resultMessages['status'] = false;
      
    $resultMessages['message'] = 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
    echo json_encode($resultMessages);
    
}
}