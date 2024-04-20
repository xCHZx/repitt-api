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

    public function sendPasswordRecoveryEmail($encryptedToken,$userEmail,$userName)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("admin@medik.mx", "Rafael Payns");
        $email->setSubject("Recupera tu Contraseña");
        $email->addTo($userEmail, $userName);
        $email->addContent(
            "text/html",
            "<p> Hola ".$userName." te enviamos este correo porque solicitaste recuperar tu contraseña </p>
            <p> Da clic a este enlaze para recuperar tu contraseña : ".getenv('APP_URL')."/api/recoverPassword/".$encryptedToken."</p>
            <p> si no fuiste tu el que solicito recupérar la contraseña ignora este correo </p>"
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