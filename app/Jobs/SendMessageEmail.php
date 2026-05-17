<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMessageEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $to;
    protected string $subject;
    protected string $body;
    protected string $fromName;
    protected string $fromEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(string $to, string $subject, string $body, string $fromName, string $fromEmail)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::raw($this->body, function ($m) {
                $m->to($this->to)
                  ->from($this->fromEmail, $this->fromName)
                  ->subject($this->subject);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send email message: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}