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
            $sendgrid->send($email);
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    public function sendPasswordRecoveryEmail($encryptedToken,$userEmail,$userName)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("no-reply@repitt.com", "Repitt");
        $email->setSubject("Recupera tu Contraseña");
        $email->addTo($userEmail, $userName);
        $email->addContent(
            "text/html",
            "<p> Hola ".$userName." te enviamos este correo porque solicitaste recuperar tu contraseña </p>
            <p> Da clic a este enlaze para recuperar tu contraseña : ".getenv('FRONT_URL')."/auth/recuperar-contrasena/".$encryptedToken."</p>
            <p> si no fuiste tu el que solicito recupérar la contraseña ignora este correo </p>"
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $sendgrid->send($email);
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }

    }

    public function notifyPasswordChange($userName,$userEmail)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("admin@medik.mx","Rafael Payns");
        $email->setSubject("Se ha cambiado tu contraseña");
        $email->addTo($userEmail,$userName);
        $email->addContent(
            "text/html",
            "<p> Hola".$userName." te enviamos este correo para notificarte que se ha modificado tu contraseña  </p>
            <p> Si no fuiste tu el que realizo este cambio porfavor contacta a soporte en el siguiente correo : </p>
            <p>En reppit nos preocupamos por la seguridad de tus datos y te recordamos mantener tus contraseñas seguras</p>"
        );
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $sendgrid->send($email);
        } catch (Exception $e) {
            return $e;
        }
    }
}
