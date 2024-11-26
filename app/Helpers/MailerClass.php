<?php

namespace App\Helpers;

use Mail;
use App\Mail\AppMail;
use App\Mail\PasswordMail;
use Illuminate\Support\Facades\URL;

class MailerClass
{
    public $uuid;
    public $email;
    public $name;

    public function __construct($email, $uuid, $name = "")
    {
        $this->email = $email;
        $this->uuid = $uuid;
        $this->name = $name;
    }
    public function sendWelcomeMail()
    {

        $mailData = [
            'title' => 'Mail from Sprillgame.com',
            'body' => 'This is for testing email using smtp.',
            'url' => URL::temporarySignedRoute(
                "verify-email", now()->addDays(7), ['user' => $this->uuid]
            )
        ];
        Mail::to($this->email)->send(new AppMail($mailData));
        
    }

    public function sendForgotPasswordMail()
    {

        $mailData = [
            'name' => $this->name,
            'url' => URL::temporarySignedRoute(
                "verify-email", now()->addDays(7), ['user' => $this->uuid]
            )
        ];
        Mail::to($this->email)->send(new PasswordMail($mailData));
        
    }
}
