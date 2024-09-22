<?php

namespace App\Services;

use GuzzleHttp\Client;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\View;

class MukeeyMailService
{

    public static function send($to, $mailData)
    {
        // \Log::info('Mukeey mail service called');
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $client = new Client([
            'headers' => $headers
        ]);

        $url = "https://my-email-server.onrender.com/send-email";

        $body = View::make('email.register_verification', ['mailData' => $mailData])->render();

        $query = json_encode([
            "smtp_host" =>  'smtp.gmail.com', //env('MAIL_HOST', 'smtp.gmail.com'),
            "smtp_port" =>  465, //env('MAIL_PORT', 465),
            "smtp_user" =>  'quick.clinic.app@gmail.com',//env('MAIL_USERNAME', 'quick.clinic.app@gmail.com'),
            "smtp_pass" =>  'wqft nyvp ouqk gkvn',//env('MAIL_PASSWORD', 'wqft nyvp ouqk gkvn'),
            // data body
            "from_name" => 'Quick Clinic',
            "from_email" => 'hello@quickclinic.org',
            "to" => $to,
            "subject" => $mailData['title'],
            "body" => $body,
        ], true);

        $data = null;
        try {
            $response = $client->post($url, [
                'body' => $query,
            ]);

            // Checking the network ststus of the response
            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $data = json_decode($response->getBody(), true);
                return $data;
                // Successful response
                \Log::info("Email sent successfully. Status code: $statusCode, Email: $to");
            } else {
                // Unsuccessful response
                \Log::error("Failed to send email. Status code: $statusCode, Email: $to");
                // throw new \Exception("Email sending failed with status code: $statusCode");
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $data; //Failed to send email
    }

}
