<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

require '../vendor/autoload.php';
class EmailController extends Controller
{
    // mover esto a Mailers
    public function sendVerifyEmail($validationCode,$userMail,$userName)
    {

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("admin@medik.mx", "Rafael Payns");
        $email->setSubject("Verifica tu correo");
        $email->addTo($userMail, $userName);
        $email->addContent("text/plain", "Codigo de verificacion");
        $email->addContent(
            "text/html",
            "<p> tu codigo de verificacion es : <strong>".$validationCode."</strong>"
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            print $response->statusCode() . "\n";
            print_r($response->headers());
            print $response->body() . "\n";
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }
}