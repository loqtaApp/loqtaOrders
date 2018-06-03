<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
//die(sha1(uniqid("loqta_sendContactEmail", true)));
//f1e1f4421d57660a7fa3bafe7c569eb41899314c

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
    $resultMessages[] = 'Invalid Token, Or Expired Token';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'mail.rozn.org';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'loqta_contact@rozn.org';                 // SMTP username
    $mail->Password = '%KwK.ju3VQ3[';                           // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 465;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('loqta_contact@rozn.org', 'Loqta contact');
    $mail->addAddress('loqta_contact@rozn.org', 'Loqta contact');     // Add a recipient
    //$mail->addAddress('ellen@example.com');               // Name is optional
    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //Attachments
    // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subjectToSend;
    $mail->Body    = $formatedMessageToSend;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}