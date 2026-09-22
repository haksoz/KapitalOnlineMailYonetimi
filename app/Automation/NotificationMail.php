<?php

namespace App\Automation;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

final class NotificationMail
{
    /**
     * @param  list<string>|string  $to
     */
    public static function send(array|string $to, string $subject, string $plainBody): void
    {
        $html = self::htmlFromPlain($plainBody);

        Mail::html($html, function (Message $message) use ($to, $subject, $plainBody): void {
            $message->to($to)->subject($subject)->text($plainBody);
        });
    }

    public static function htmlFromPlain(string $plainBody): string
    {
        $body = nl2br(e($plainBody), false);

        return '<!DOCTYPE html>'
            .'<html><head><meta charset="UTF-8"></head>'
            .'<body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#111111;">'
            .$body
            .'</body></html>';
    }
}
