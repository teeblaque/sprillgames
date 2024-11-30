<?php

namespace App\Jobs;

use App\Mail\VerifyAccount as MailVerifyAccount;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class VerifyAccount implements ShouldQueue
{
    use Queueable;
    private $user;
    private $data;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $data)
    {
        $this->user =  $user;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user)->send(new MailVerifyAccount($this->user, $this->data));
    }
}