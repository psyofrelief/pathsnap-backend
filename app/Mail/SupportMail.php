<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class SupportMail extends Mailable
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject("New Support Request")
            ->view("emails.support")
            ->with("data", $this->data);
    }
}
