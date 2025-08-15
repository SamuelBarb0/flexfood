<?php
namespace App\Mail;

use App\Models\Orden;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;

    public function __construct(Orden $orden)
    {
        $this->orden = $orden->load('mesa', 'restaurante');
    }

    public function build()
    {
        return $this->subject('Ticket - ' . $this->orden->restaurante->nombre)
            ->view('emails.ticket');
    }
}
