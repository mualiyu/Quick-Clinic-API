<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentRequest;
use App\Models\Appointment;
use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\MukeeyMailService;
// use Google\Client;
// use Google_Client;
// use Google\Service\Calendar;
// use Google\Service\Calendar\Event;
// use Google\Service\YouTube;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;

class DoctorAppointmentController extends Controller
{
    public function get_all_appointments(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {
            $appointments = Appointment::where(['doctor_id' => $request->user()->doctor->id])->with(['patient', 'payment'])->get();

            if (count($appointments) > 0) {
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
        if ($request->user()->tokenCan('doctor')) {
            if ($appointment->doctor_id === $request->user()->doctor->id) {
                $patient = $appointment->patient;
                $previousAppointments = $appointment->patient->appointments;
                // $healthRecord = HealthRecord::where('patient_id', '=', $appointment->patient->id)->get();

                $appointmentData = $appointment->toArray();
                $appointmentData['patient'] = $patient;
                $appointmentData['previousAppointments'] = $previousAppointments;
                $appointmentData['review'] = $appointment->review;
                $appointmentData['payment'] = $appointment->payment;
                $appointmentData['meeting_link'] = $appointment->meeting_link;
                $appointmentData['payment_status'] = $appointment->payment->status;
                // $appointmentData['healthRecord'] = $healthRecord;

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

    public function doctor_update_status(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {
            $request->validate([
                'appointment_id' => 'required',
                'status' => 'required|string', //['Pending', 'Scheduled', 'No Response', 'Ongoing', 'Completed', 'Cancelled']
                'remark' => 'nullable',
            ]);

            $appointment = Appointment::findOrFail($request->appointment_id);
            $appointment->status = $request->status;
            $appointment->save();

            // if ($request->status == "Scheduled" && in_array($appointment->type, ['Voice', 'Video'])) {
            //     $meetLink = $this->createGoogleMeetLink($appointment);
            //     $appointment->meeting_link = $meetLink;
            //     $appointment->save();
            // }

            if ($request->status == "Scheduled") {
                $mailData = [
                    'title' => 'Virtual Appointment Confirmed',
                    'body' => [
                        "Dear " . $appointment->patient->first_name . ",",
                        "We are pleased to inform you that your virtual appointment with Dr. " . $appointment->doctor->first_name . " has been confirmed and scheduled.",
                        "Appointment Details:",
                        "Date and Time: " . $appointment->appointment_date . " at " . $appointment->appointment_time . " ",
                        "Doctor: Dr. " . $appointment->doctor->first_name . " ",
                        "To join the virtual appointment, please use the link below:",
                        "Virtual Meeting Link: " . $appointment->meeting_link,
                        "Please make sure to join the meeting a few minutes before the scheduled time. If you need to reschedule or have any questions, feel free to contact us at support@quick-clinic.org.",
                        "Please proceed to make the payment for your appointment through our app to confirm your booking.",
                        "We look forward to assisting you with your healthcare needs.",
                        "Best regards,",
                        "Quick Clinic Team",
                    ],
                ];
                MukeeyMailService::send($appointment->patient->user->email, $mailData);
            }

            if ($request->status == "Cancelled") {
                $mailData = [
                    'title' => 'Appointment Cancelled',
                    'body' => [
                        "Dear " . $appointment->patient->first_name . ",",
                        "We regret to inform you that your appointment with Dr. " . $appointment->doctor->first_name . " on " . $appointment->appointment_date . " at " . $appointment->appointment_time . " has been cancelled.",
                        "Reason For Cancelation: " . $request->remark . " ",
                        "We apologize for any inconvenience this may cause. Please note that you can reschedule your appointment or choose another doctor by visiting our app.",
                        "If you have any questions or need assistance with rescheduling, feel free to reach out to our support team at support@quick-clinic.org.",
                        "Thank you for your understanding.",
                        "Best regards,",
                        "Quick Clinic Team",
                    ],
                ];
                MukeeyMailService::send($appointment->patient->user->email, $mailData);
            }

            if ($request->status == "No Response") {
                $mailData = [
                    'title' => 'Appointment Cancelled - No Response',
                    'body' => [
                        "Dear " . $appointment->patient->first_name . ",",
                        "We regret to inform you that your appointment with Dr. " . $appointment->doctor->first_name . " on " . $appointment->appointment_date . " at " . $appointment->appointment_time . " has been cancelled.",
                        "Reason For Cancelation: " . $request->remark . " ",
                        "We apologize for any inconvenience this may cause. Please note that you can reschedule your appointment or choose another doctor by visiting our app.",
                        "If you have any questions or need assistance with rescheduling, feel free to reach out to our support team at support@quick-clinic.org.",
                        "Thank you for your understanding.",
                        "Best regards,",
                        "Quick Clinic Team",
                    ],
                ];
                MukeeyMailService::send($appointment->patient->user->email, $mailData);
            }

            return response()->json([
                'status' => true,
                'message' => 'Appointment status updated successfully.',
                'data' => $appointment,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

    public function addDoctorRemark(Request $request, Appointment $appointment)
    {
        if ($request->user()->tokenCan('doctor')) {
            $validator = Validator::make($request->all(), [
                'doctor_remark' => 'required|string',
                'report' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:2048',
                'prescription' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($appointment->doctor_id !== $request->user()->doctor->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to add remark to this appointment.',
                ], 403);
            }

            $appointment->update([
                'doctor_remark' => $request->doctor_remark,
            ]);

            if ($request->hasFile('report')) {
                $appointment->addFile('report', $request->file('report'));
            }

            if ($request->hasFile('prescription')) {
                $appointment->addFile('prescription', $request->file('prescription'));
            }

            // Prepare data for email
            $mailData = [
                'title' => 'Doctor Remark for Your Appointment',
                'body' => [
                    "Dear " . $appointment->patient->first_name . ",",
                    "Dr. " . $request->user()->doctor->first_name . " " . $request->user()->doctor->last_name . " has added a remark to your appointment on " . $appointment->appointment_date . ".",
                    "Remark: " . $request->doctor_remark,
                    $appointment->report_url ? "A medical report has been attached to this email." : "",
                    $appointment->prescription_url ? "A prescription has been attached to this email." : "",
                    "If you have any questions, please don't hesitate to contact us.",
                ],
            ];

            // Send email to patient
            $emailSent = MukeeyMailService::send($appointment->patient->user->email, $mailData);

            if ($emailSent) {
                return response()->json([
                    'status' => true,
                    'message' => 'Doctor remark added and notification sent to patient.',
                    'data' => $appointment,
                ], 200);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'Doctor remark added but failed to send notification to patient.',
                    'data' => $appointment,
                ], 200);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

    // public function createGoogleMeetLink(Appointment $appointment)
    // {
    //     // $client = new Google_Client();
    //     $client = new Client();
    //     // $client->setDeveloperKey('AIzaSyAxwtNl10ZFsNi58tO5kQYboU-FCuanGHM');
    //     $client->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
    //     $client->addScope(Calendar::CALENDAR);

    //     $service = new Calendar($client);

    //     $event = new Event(array(
    //         'summary' => 'Appointment with Dr. ' . $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
    //         'description' => $appointment->description_of_problem,
    //         'start' => array(
    //             'dateTime' => $appointment->appointment_date . 'T' . $appointment->appointment_time . ':00',
    //             'timeZone' => 'UTC',
    //         ),
    //         'end' => array(
    //             'dateTime' => date('Y-m-d\TH:i:s', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time . ' +1 hour')),
    //             'timeZone' => 'UTC',
    //         ),
    //         'attendees' => array(
    //             array('email' => $appointment->patient->user->email),
    //             array('email' => $appointment->doctor->user->email),
    //         ),
    //         'conferenceData' => array(
    //             'createRequest' => array(
    //                 'requestId' => 'quickclinic-' . $appointment->id,
    //                 'conferenceSolutionKey' => array('type' => 'hangoutsMeet'),
    //             ),
    //         ),
    //     ));

    //     $calendarId = 'primary';
    //     $event = $service->events->insert($calendarId, $event, array('conferenceDataVersion' => 1));

    //     return $event->hangoutLink;
    // }

    public function test(Request $request, Appointment $appointment)
    {
        // Create a new event
        $event = new Event;

        $event->name = 'Appointment with Dr. ' . $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name;
        $event->description = $appointment->description_of_problem;

        // Parse the date and time correctly
        $dateTime = Carbon::createFromFormat('Y-m-d H-i', $appointment->appointment_date . ' ' . $appointment->appointment_time);
        if (!$dateTime) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date or time format',
            ], 400);
        }

        $event->startDateTime = $dateTime;
        $event->endDateTime = $dateTime->copy()->addHour();

        // Add attendees
        $event->addAttendee(['email' => $appointment->patient->user->email]);
        $event->addAttendee(['email' => $appointment->doctor->user->email]);

        // Enable Google Meet
        $event->addMeetLink();

        // Save the event
        $createdEvent = $event->save();

        // Get the Google Meet link
        $meetLink = $createdEvent->meetLink;

        // Update the appointment with the meet link
        $appointment->meeting_link = $meetLink;
        $appointment->save();

        return response()->json([
            'status' => true,
            'message' => 'Google Meet event created successfully',
            'data' => [
                'event_id' => $createdEvent->id,
                'meet_link' => $meetLink,
            ],
        ], 200);
    }
}
