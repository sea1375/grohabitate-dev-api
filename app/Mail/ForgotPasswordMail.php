<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $confirm_link)
    {
        $this->user = $user;
        $this->link = $confirm_link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->user->reset_url = env('APP_URL').'/reset_password/'.$this->link;
        // $locale = App::getLocale();
        $locale = 'en';

        return $this->to($this->user->email, $this->user->username)
            ->subject('Reset your password')
            ->view('emails.'.$locale.'.forgot')
            ->with([
                'user' => $this->user
            ]);
    }
}
