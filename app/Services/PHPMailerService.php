<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class PHPMailerService
{

    public static function send($to, $mailData)
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'quick.clinic.app@gmail.com';              // SMTP username
            $mail->Password   = 'wqft nyvp ouqk gkvn';  // SMTP password or app password if using 2FA
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS for ssl
            $mail->Port       = 465;                                    // TCP port to connect to

            // Recipients
            $mail->setFrom(env('MAIL_FROM_ADDRESS', "hello@quickclinic.org"), 'Quick Clinic');
            $mail->addAddress($to);                  // Add a recipient

            // Content
            $viewContent = View::make('email.register_verification', ['mailData' => $mailData])->render();
            // return $viewContent;
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = $mailData['title'];
            $mail->Body    = $viewContent;
            $mail->AltBody = $mailData['title'];

            $mail->send();
            return true;
        } catch (Exception $e) {
           return false;
        }

        return false; //Failed to send email
    }
}

