<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\MukeeyMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PatientAppointmentController extends Controller
{
    public function get_all_appointments(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $appointments = Appointment::where(['patient_id' => $request->user()->patient->id])
                ->with(['review', 'doctor'])
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
                $appointment->load(['review', 'doctor']);

                $previousAppointments = Appointment::where('patient_id', $request->user()->patient->id)
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
                        ];
                    });

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

                MukeeyMailService::send($doctor->user->email, $mailData);

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
