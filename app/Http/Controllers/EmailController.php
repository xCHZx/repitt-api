<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

require '../vendor/autoload.php';
class EmailController extends Controller
{
    public function sendVerifyEmail($userId,$email)
    {

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("admin@medik.mx", "Rafael Payns");
        $email->setSubject("Verifica tu correo");
        $email->addTo("rpayns16@gmail.com", "Rafael Payns");
        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
        $email->addContent(
            "text/html",
            "<strong>and easy to do anywhere, even with PHP</strong>"
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