<?php

namespace App\Services;

use App\Models\Payment;
use Yabacon\Paystack;

class PaystackService
{
    protected $paystack;

    public function __construct()
    {
        $this->paystack = new Paystack(config('services.paystack.secret_key'));
    }

    public function initiatePayment($amount, $email, $patientId, $doctorId)
    {
        try {
            $reference = 'Q_CLINIC_' . uniqid();
            $tranx = $this->paystack->transaction->initialize([
                'amount' => $amount * 100,  // Amount in kobo
                'email' => $email,
                'reference' => $reference,
                'callback_url' => route('pay.callback')
            ]);

            Payment::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'pending',
            ]);

            return [
                'authorization_url' => $tranx->data->authorization_url,
                'reference' => $reference,
            ];
        } catch (\Exception $e) {
            // Handle any exceptions
            return null;
        }
    }

    public function verifyPayment($reference)
    {
        // try {
            $tranx = $this->paystack->transaction->verify([
                'reference' => $reference,
            ]);

            $payment = Payment::where('reference', $reference)->firstOrFail();

            if ('success' === $tranx->data->status) {
                $payment->update([
                    'status' => 'success',
                    'payment_channel' => $tranx->data->channel,
                    'payment_data' => $tranx->data,
                ]);
                return true;
            } else {
                $payment->update([
                    'status' => 'failed',
                    'payment_data' => $tranx->data,
                ]);
            }
        // } catch (\Exception $e) {
        //     // Handle any exceptions
        // }

        return false;
    }
}
