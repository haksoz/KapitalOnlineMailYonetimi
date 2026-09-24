<?php

namespace App\Automation;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

final class NotificationMail
{
    /**
     * @param  list<string>|string  $to
     */
    public static function send(array|string $to, string $subject, string $plainBody, ?string $bcc = null): void
    {
        $html = self::htmlFromPlain($plainBody);
        $bcc = self::bccNotAlreadyRecipient($to, $bcc);

        Mail::html($html, function (Message $message) use ($to, $subject, $plainBody, $bcc): void {
            $message->to($to)->subject($subject)->text($plainBody);
            if ($bcc !== null) {
                $message->bcc($bcc);
            }
        });
    }

    /**
     * @param  list<string>|string  $to
     */
    private static function bccNotAlreadyRecipient(array|string $to, ?string $bcc): ?string
    {
        $bcc = trim((string) $bcc);
        if ($bcc === '') {
            return null;
        }

        $recipients = array_map(
            static fn (string $email): string => strtolower(trim($email)),
            is_array($to) ? $to : [$to]
        );

        return in_array(strtolower($bcc), $recipients, true) ? null : $bcc;
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
