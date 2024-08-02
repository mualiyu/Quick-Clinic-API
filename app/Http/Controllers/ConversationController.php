<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = $request->user()->tokenCan('patient')
            ? Conversation::where('patient_id', $request->user()->id)->with('doctor')->with('messages')->get()
            : Conversation::where('doctor_id', $request->user()->id)->with('patient')->with('messages')->get();

        // return response()->json($conversations);
        if (count($conversations) > 0) {
            return response()->json([
                'status' => true,
                'conversations' => $conversations,
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => "No conversations yet",
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required',
            'patient_id' => 'required',
        ]);

        if (!count(Conversation::where([
            'patient_id' => $request->user()->tokenCan('patient') ? $request->user()->id : $request->patient_id,
            'doctor_id' =>  $request->user()->tokenCan('doctor') ? $request->user()->id : $request->doctor_id,
            ])->get()) > 0) {

                $conversation = Conversation::create([
                    'patient_id' => $request->user()->tokenCan('patient') ? $request->user()->id : $request->patient_id,
                    'doctor_id' =>  $request->user()->tokenCan('doctor') ? $request->user()->id : $request->doctor_id,
                ]);

                if ($conversation) {
                    return response()->json([
                        'status' => true,
                        'conversation' => $conversation
                    ], 201);
                }
        }else{
            return response()->json([
                'status' => true,
                'message' => "Conversation already exist."
            ], 422);
        }

    }

    public function show(Conversation $conversation)
    {
        $messages = $conversation->messages()->with('sender')->get();

        if (count($messages) > 0) {
            return response()->json([
                'status' => true,
                'messages' => $messages,
            ], 200);
        }else{
            return response()->json([
                'status' => true,
                'messages' => [],
            ], 200);
        }
    }

    public function storeMsg(Request $request, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => $request->user() instanceof \App\Models\Patient ? 'App\Models\Patient' : 'App\Models\Doctor',
            'message' => $request->message,
        ]);

        if ($message) {
            return response()->json([
                'status' => true,
                'data' => $message,
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => "Failed to send message",
            ], 422);
        }
    }
}
