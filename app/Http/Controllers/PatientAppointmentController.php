<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\MukeeyMailService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class PatientAppointmentController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    public function get_all_appointments(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $appointments = Appointment::where(['patient_id' => $request->user()->patient->id])
                ->with(['review', 'doctor', 'payment'])
                ->orderBy('appointment_date', 'desc')
                ->get()
                ->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'appointment_date' => $appointment->appointment_date,
                        'appointment_time' => $appointment->appointment_time,
                        'status' => $appointment->status,
                        'doctor_name' => $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
                        'doctor_remark' => $appointment->doctor_remark,
                        'has_report' => !is_null($appointment->report_url),
                        'has_prescription' => !is_null($appointment->prescription_url),
                        'review' => $appointment->review,
                        'payment' => $appointment->payment,
                        'type' => $appointment->type,
                        'meeting_link' => $appointment->meeting_link,
                        // 'payment_status' => $appointment->payment->status,
                    ];
                });

            if ($appointments->isNotEmpty()) {
                return response()->json([
                    'status' => true,
                    'data' => $appointments,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No Appointment Found.',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

    public function get_single_appointment(Request $request, Appointment $appointment)
    {
        if ($request->user()->tokenCan('patient')) {
            if ($appointment->patient_id === $request->user()->patient->id) {
                $appointment->load(['review', 'doctor', 'payment']);

                $previousAppointments = Appointment::where('patient_id', $request->user()->patient->id)
                    ->whereIn('status', ['Scheduled', 'Completed'])
                    ->where('appointment_date', '<', $appointment->appointment_date)
                    ->orderBy('appointment_date', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($prevAppointment) {
                        return [
                            'id' => $prevAppointment->id,
                            'appointment_date' => $prevAppointment->appointment_date,
                            'doctor_name' => $prevAppointment->doctor->first_name . ' ' . $prevAppointment->doctor->last_name,
                            'doctor_remark' => $prevAppointment->doctor_remark,
                            'type' => $prevAppointment->type,
                            'doctor' => $prevAppointment->doctor,
                        ];
                    });

                // Check if appointment has payment
                $hasPayment = $appointment->payment()->exists();

                // Add payment status to the appointment data
                $paymentStatus = $hasPayment ? $appointment->payment->status : 'Pending';
                $appointmentData = [
                    'id' => $appointment->id,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'description_of_problem' => $appointment->description_of_problem,
                    'doctor_name' => $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
                    'doctor_remark' => $appointment->doctor_remark,
                    'report_url' => $appointment->report_url,
                    'prescription_url' => $appointment->prescription_url,
                    'review' => $appointment->review,
                    'previous_appointments' => $previousAppointments,
                    'payment' => $appointment->payment,
                    'type' => $appointment->type,
                    'doctor' => $appointment->doctor,
                    'meeting_link' => $appointment->meeting_link,
                    'payment_status' => $paymentStatus,
                ];

                return response()->json([
                    'status' => true,
                    'data' => $appointmentData,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to view this appointment.',
                ], 403);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

    public function schedule_appointment(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,id',
                'appointment_date' => 'required|date',
                'appointment_time' => 'required',
                'description_of_problem' => 'required|string',
                'attachment' => 'nullable|file',
                'type' => 'required|in:Voice,Video,Message',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $doctor = Doctor::find($request->doctor_id);

            $attachment = '';
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment')->store('public/appointment');
                $attachment = url('/storage/' . str_replace('public/', '', $attachment));
            }

            $appointment = Appointment::create([
                'patient_id' => $request->user()->patient->id,
                'doctor_id' => $request->doctor_id,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'description_of_problem' => $request->description_of_problem,
                'attachment' => $attachment,
                'type' => $request->type,
                'status' => 'Pending',
            ]);

            if ($appointment) {
                // Send email to doctor about new appointment request
                $mailData = [
                    'title' => 'New Appointment Request',
                    'body' => [
                        "Dear Dr. " . $doctor->first_name . ",",
                        "We are pleased to inform you that you have a new appointment request at Quick Clinic.",
                        "Patient Details:",
                        "Name: " . $request->user()->patient->first_name . " " . $request->user()->patient->last_name,
                        "Email: " . $request->user()->email,
                        "Appointment Date and Time: " . $appointment->appointment_date . " at " . $appointment->appointment_time,
                        "Reason for Appointment: " . $appointment->description_of_problem,
                        "Please log in to your Quick Clinic account to review and confirm this appointment. If you have any questions or need further assistance, feel free to contact our support team at support@quick-clinic.org.",
                        "Thank you for your dedication and for being a valued member of the Quick Clinic team.",
                        "Best regards,",
                        "Quick Clinic Team",
                    ],
                ];

                MukeeyMailService::send($doctor->user->email, $mailData);

                return response()->json([
                    'status' => true,
                    'message' => 'Appointment has been successfully requested. You will be updated once the doctor confirms it.',
                    'data' => $appointment,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create appointment, please try again.',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

    public function initiate_appointment_payment($appointment) //Request $request,
    {
        // if ($request->user()->tokenCan('patient')) {

        // $validator = Validator::make($request->all(), [
        //     'appointment_id' => 'required|exists:appointments,id',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $validator->errors()->first()
        //     ], 422);
        // }

        // $appointment = Appointment::findOrFail($request->appointment_id);

        // $a_id = base64_decode($appointment);
        $a_id = Crypt::decrypt($appointment);
        try {
            $appointment = Appointment::findOrFail($a_id);
            // Appointment exists, continue with your logic
        } catch (ModelNotFoundException $e) {

            // return response()->json([
            //     'status' => false,
            //     'message' => 'Appointment not found.'
            // ], 404);
            $title = "Not Found";
            $msg = "Appoint ment not found";
            return view('payments.error', compact("title", 'msg'));
        }
        // if ($appointment->patient_id !== $request->user()->patient->id) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Unauthorized to initiate payment for this appointment.',
        //     ], 403);
        // }

        if ($appointment->status !== 'Scheduled') {
            // return response()->json([
            //     'status' => false,
            //     'message' => 'Payment can only be initiated for scheduled appointments.',
            // ], 422);

            $title = "Not Scheduled";
            $msg = "Appointment not scheduled, please try again later!!";
            return view('payments.error', compact("title", 'msg'));
        }

        $doctor = $appointment->doctor;
        $patient = $appointment->patient;
        $fee = $doctor->{strtolower($appointment->type) . '_consultation_fee'};

        $paymentData = $this->paystackService->initiatePayment(
            $fee,
            $patient->user->email,
            $patient->id,
            $doctor->id
        );

        if ($paymentData) {
            // Update the appointment with the payment reference
            $appointment->update(['payment_reference' => $paymentData['reference']]);

            return redirect($paymentData['authorization_url']);

            // return response()->json([
            //     'status' => true,
            //     'payment_url' => $paymentData['authorization_url'],
            //     'reference' => $paymentData['reference'],
            // ], 200);
        } else {
            // return response()->json([
            //     'status' => false,
            //     'message' => 'Failed to initiate payment.',
            // ], 422);

            $title = "Failed to Initiate Payment.";
            $msg = "System can't accept payment at the moment, Please Try Again Later!!!";
            return view('payments.error', compact("title", 'msg'));
        }
        // } else {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Failed to Authorize Token!',
        //     ], 401);
        // }
    }

    public function handlePaystackCallback(Request $request)
    {
        $paymentVerified = $this->paystackService->verifyPayment($request->reference);

        if ($paymentVerified) {
            $payment = Payment::where('reference', $request->reference)->firstOrFail();
            $appointment = Appointment::where('payment_reference', $request->reference)->firstOrFail();

            // Update appointment status to 'Paid'
            // $appointment->update(['status' => 'Paid']);

            // Send email to doctor about paid and scheduled appointment
            $doctor = $appointment->doctor;
            $patient = $appointment->patient;

            $mailData = [
                'title' => 'Appointment Payment Confirmed',
                'body' => [
                    "Dear Dr. " . $doctor->first_name . " " . $doctor->last_name . ",",
                    "We are pleased to inform you that the appointment you previously accepted has now been paid for and is officially scheduled.",
                    "Appointment Details:",
                    "Patient: " . $patient->first_name . " " . $patient->last_name,
                    "Date: " . $appointment->appointment_date,
                    "Time: " . $appointment->appointment_time,
                    "Type: " . $appointment->type,
                    "Payment Status: Paid",
                    "Please ensure you're prepared for this appointment at the scheduled time. You can log in to your account for more details if needed.",
                    "If you have any questions or need to make any changes, please contact our support team.",
                    "Thank you for your service.",
                    "Best regards,",
                    "Quick Clinic Team"
                ]
            ];

            MukeeyMailService::send($doctor->user->email, $mailData);

            return response()->json([
                'status' => true,
                'message' => 'Payment successful',
                'payment_status' => $paymentVerified,
                'appointment_status' => $appointment->status,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Payment failed',
                'payment_status' => $paymentVerified,
            ], 422);
        }
    }

    public function handlePaystackCallbackWeb($reference)
    {
        $paymentVerified = $this->paystackService->verifyPayment($reference);

        if ($paymentVerified) {
            $payment = Payment::where('reference', $reference)->firstOrFail();
            $appointment = Appointment::where('payment_reference', $reference)->firstOrFail();

            // Update appointment status to 'Paid'
            // $appointment->update(['status' => 'Paid']);

            // Send email to doctor about paid and scheduled appointment
            $doctor = $appointment->doctor;
            $patient = $appointment->patient;

            $mailDataD = [
                'title' => 'Appointment Payment Confirmed',
                'body' => [
                    "Dear Dr. " . $doctor->first_name . " " . $doctor->last_name . ",",
                    "We are pleased to inform you that the appointment you previously accepted has now been paid for and is officially scheduled.",
                    "Appointment Details:",
                    "Patient: " . $patient->first_name . " " . $patient->last_name,
                    "Date: " . $appointment->appointment_date,
                    "Time: " . $appointment->appointment_time,
                    "Type: " . $appointment->type,
                    "Payment Status: Paid",
                    "Please ensure you're prepared for this appointment at the scheduled time. You can log in to your account for more details if needed.",
                    "If you have any questions or need to make any changes, please contact our support team.",
                    "Thank you for your service.",
                    "Best regards,",
                    "Quick Clinic Team"
                ]
            ];

            $mailDataP = [
                'title' => 'Appointment Payment Confirmed',
                'body' => [
                    "Dear " . $appointment->patient->first_name . ",",
                    "We are happy to inform you that your payment for the virtual appointment with Dr. " . $appointment->doctor->first_name . " has been successfully processed.",
                    "Appointment Details:",
                    "Date and Time: " . $appointment->appointment_date . " at " . $appointment->appointment_time . " ",
                    "Doctor: Dr. " . $appointment->doctor->first_name . " ",
                    "To join the virtual appointment, please use the link below:",
                    "Virtual Meeting Link: " . $appointment->meeting_link,
                    "Thank you for confirming your appointment. Please be sure to join the meeting a few minutes before the scheduled time.",
                    "If you have any questions or need further assistance, feel free to contact us at support@quick-clinic.org.",
                    "We look forward to helping you with your healthcare needs.",
                    "Best regards,",
                    "Quick Clinic Team",
                ],
            ];

            MukeeyMailService::send($doctor->user->email, $mailDataD);

            MukeeyMailService::send($patient->user->email, $mailDataP);

            // return response()->json([
            //     'status' => true,
            //     'message' => 'Payment successful',
            //     'payment_status' => $paymentVerified,
            //     'appointment_status' => $appointment->status,
            // ], 200);

            $title = "Successfull";
            $msg = "Payment has been recieved successfully, an email has been sent to you with your appointment details.";
            return view('payments.success', compact("title", "msg"));
        } else {
            // return response()->json([
            //     'status' => false,
            //     'message' => 'Payment failed',
            //     'payment_status' => $paymentVerified,
            // ], 422);

            $title = "Payment Failed";
            $msg = "Transaction failed, Please try again. Thank you 👍";
            return view('payments.error', compact("title", 'msg'));
        }
    }
}
