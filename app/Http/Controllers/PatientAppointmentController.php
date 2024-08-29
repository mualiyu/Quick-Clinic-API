<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PatientAppointmentController extends Controller
{
    public function get_all_appointments(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $appointments = Appointment::where(['patient_id'=>$request->user()->patient->id])->get();

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

        if ($request->user()->tokenCan('patient')) {
            if ($appointment) {
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

    public function schedule_appointment(Request $request)
    {

        if ($request->user()->tokenCan('patient')) {

            $request->validate([
                'doctor_id' => 'required',
                'appointment_date' => 'required',
                'appointment_time' => 'required',
                'description_of_problem' => 'required|string',
                'attachment' => 'nullable',
                'type' => 'required|string', //['Voice', 'Video', 'Message']
            ]);

            $doctor = Doctor::find($request->doctor_id);

            $attachment = '';
            if ($request->has('attachment') && !empty($request->attachment)) {
                $attachment = $request->file('attachment')->store('public/appointment');
                $attachment = explode('/', $attachment);
                $attachment = url('/storage/appointment/' . $attachment[2]);
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

                $mailData = [
                    'title' => 'New Appointment Request',
                    'body' => [
                        "Dear Dr. ".$doctor->first_name.",",
                        "We are pleased to inform you that you have a new appointment request at Quick Clinic.",
                        "Patient Details:",
                        "Name: ".$request->user()->patient->name." ",
                        "Email: ".$request->user()->patient->email." ",
                        "Appointment Date and Time: ".$appointment->appointment_date." at ".$appointment->appointment_time." ",
                        "Reason for Appointment: ".$appointment->description_of_problem." ",
                        "Please log in to your Quick Clinic account to review and confirm this appointment. If you have any questions or need further assistance, feel free to contact our support team at support@quick-clinic.org.",
                        "Thank you for your dedication and for being a valued member of the Quick Clinic team.",
                        "Best regards,",
                        "Quick Clinic Team",
                    ],
                ];

                Mail::to($doctor->user->email)->send(new AppointmentRequest($mailData));

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
