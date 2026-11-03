<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTest extends Command
{
    protected $signature = 'mail:test {to : Email address na pagpapadalhan ng test}';

    protected $description = 'Magpadala ng test email para ma-check ang Gmail/SMTP settings';

    public function handle(): int
    {
        try {
            Mail::raw('Test email from De Guzman Drugmart. Your mail settings work!', function ($message) {
                $message->to($this->argument('to'))->subject('De Guzman Drugmart mail test');
            });
        } catch (Throwable $e) {
            $this->error('Failed to send: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Sent! Check the inbox (and Spam folder) of ' . $this->argument('to'));
        return self::SUCCESS;
    }
}
