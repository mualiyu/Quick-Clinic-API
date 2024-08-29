<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentRequest;
use App\Models\Appointment;
use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class DoctorAppointmentController extends Controller
{
    public function get_all_appointments(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {
            $appointments = Appointment::where(['doctor_id'=>$request->user()->doctor->id])->with('patient')->get();

            if (count($appointments)>0) {
                return response()->json([
                    'status' => true,
                    'data' => $appointments,
                ], 200);
            }else {
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
        if ($request->user()->tokenCan('doctor')) {
            if ($appointment) {
                $patient = $appointment->patient;

                $healthRecord = HealthRecord::where('patient_id', '=', $appointment->patient->id)->get();

                $appointment['patient'] = $patient;
                $appointment['healthRecord'] = $healthRecord;

                return response()->json([
                    'status' => true,
                    'data' => $appointment,
                ], 200);
            }else {
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

    public function doctor_update_status(Request $request)
    {

        if ($request->user()->tokenCan('doctor')) {

            $request->validate([
                'appointment_id' => 'required',
                'status' => 'required|string', //['Voice', 'Video', 'Message']
                'remark' => 'nullable',
            ]);

            $appointment = Appointment::where('id', '=', $request->appointment_id)->update([
                'status' => $request->status,
            ]);

            $appointment = Appointment::where('id', '=', $request->appointment_id)->get();

            if (count($appointment)>0) {
                $appointment = $appointment[0];

                if ($request->status == "Scheduled") {
                    $mailData = [
                        'title' => 'Virtual Appointment Confirmed',
                        'body' => [
                            "Dear ".$appointment->patient->first_name.",",
                            "We are pleased to inform you that your virtual appointment with Dr. ".$appointment->doctor->first_name." has been confirmed and scheduled.",
                            "Appointment Details:",
                            "Date and Time: ".$appointment->appointment_date." at ".$appointment->appointment_time." ",
                            "Doctor: Dr. ".$appointment->doctor->first_name." ",
                            // "To join the virtual appointment, please use the link below:",
                            // "Virtual Meeting Link: [Join Appointment]($appointment->meeting_link)",
                            "Please make sure to join the meeting a few minutes before the scheduled time. If you need to reschedule or have any questions, feel free to contact us at support@quick-clinic.org.",
                            "We look forward to assisting you with your healthcare needs.",
                            "Best regards,",
                            "Quick Clinic Team",
                        ],
                    ];
                    Mail::to($appointment->patient->user->email)->send(new AppointmentRequest($mailData));
                }

                if ($request->status == "Cancelled") {
                    $mailData = [
                        'title' => 'Appointment Cancelled',
                        'body' => [
                            "Dear ".$appointment->patient->first_name.",",
                            "We regret to inform you that your appointment with Dr. ".$appointment->doctor->first_name." on ".$appointment->appointment_date." at ".$appointment->appointment_time." has been cancelled.",
                            "Reason For Cancelation: ".$request->remark." ",
                            "We apologize for any inconvenience this may cause. Please note that you can reschedule your appointment or choose another doctor by visiting our app.",
                            "If you have any questions or need assistance with rescheduling, feel free to reach out to our support team at support@quick-clinic.org.",
                            "Thank you for your understanding.",
                            "Best regards,",
                            "Quick Clinic Team",
                        ],
                    ];
                    Mail::to($appointment->patient->user->email)->send(new AppointmentRequest($mailData));
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Appointment has been successfuly requested. you will be updated once the doctor confirms it.',
                    'data' => $appointment,
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Failed, please try again.',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }
}
