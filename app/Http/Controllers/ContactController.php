<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Services\Messenger360Service;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
     protected Messenger360Service $whatsapp;

    public function __construct(Messenger360Service $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

      public function send(Request $request)
    {
        // ✅ Validator inside controller
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => ['required','digits:10'],
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        // ✅ Save to DB
        $contact = ContactMessage::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'message' => $request->message,
        ]);

        // dd(config('mail.default'), config('mail.mailers.smtp.host'));

//         dd(
//     config('mail.default'),
//     config('mail.mailers.smtp.host'),
//     config('mail.mailers.smtp.port')
// );


        // ✅ Send Email
        // Mail::raw(
        //     "New Contact Message\n\n".
        //     "Name: {$request->name}\n".
        //     "Email: {$request->email}\n".
        //     "Phone: {$request->phone}\n".
        //     "Message: {$request->message}",
        //     function ($message) {
        //         $message->to('sk.asif0490@gmail.com')
        //                 ->subject('New Contact Form Submission');
        //     }
        // );

        // ✅ Send WhatsApp Alert
        // $this->whatsapp->send(
        //     $request->phone,
        //     "New Contact Message\nName: {$request->name}\nMessage: {$request->message}"
        // );


    // dd(9440161007);

 $this->whatsapp->send(
            9966465050,
            "New Contact Message\nName: {$request->name}\nMessage: {$request->message}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}
