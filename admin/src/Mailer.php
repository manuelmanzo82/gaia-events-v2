<?php

declare(strict_types=1);

namespace Gaia\Admin;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/Lib/PHPMailer/Exception.php';
require_once __DIR__ . '/Lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/Lib/PHPMailer/SMTP.php';

/**
 * Invio della posta del sito (notifiche delle richieste, copia alla coppia).
 *
 * Su IONOS mail() di PHP passa da un account SMTP impostato nel pannello
 * e, se quello non è configurato, fallisce (535): quindi si spedisce via
 * SMTP autenticato, con una casella vera sul dominio del mittente, così
 * SPF e DKIM sono allineati. Tutto da config.php, chiave `smtp`:
 *
 *   'smtp' => [
 *       'host' => 'smtp.ionos.it',        // produzione: smtps.aruba.it
 *       'port' => 587,                    // 587 con tls, 465 con ssl
 *       'secure' => 'tls',                // 'tls' (STARTTLS) oppure 'ssl'
 *       'username' => 'noreply@incognito.work',
 *       'password' => '',                 // la scrive chi gestisce il server
 *       'from' => 'noreply@incognito.work',
 *       'from_name' => 'Gaia Events',
 *   ],
 *
 * Senza host o password non si prova nemmeno: l'errore torna a chi chiama,
 * che lo registra sulla richiesta (la richiesta resta comunque salvata).
 * PHPMailer 7.1.1 è copiato in src/Lib/PHPMailer (LGPL 2.1, licenza
 * accanto): il back office non usa Composer.
 */
final class Mailer
{
    /**
     * @param array{to: string, subject: string, text: string, reply_to?: ?string, reply_to_name?: ?string} $message
     * @return array{ok: bool, error: ?string}
     */
    public static function send(array $message): array
    {
        $smtp = (array) Config::get('smtp', []);
        $host = trim((string) ($smtp['host'] ?? ''));
        $password = (string) ($smtp['password'] ?? '');
        $from = trim((string) ($smtp['from'] ?? Config::get('mail_from', '')));

        if ($host === '' || $password === '' || $from === '') {
            return ['ok' => false, 'error' => 'SMTP non configurato in config.php (host, password o mittente vuoti).'];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int) ($smtp['port'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) ($smtp['username'] ?? $from);
            $mail->Password = $password;
            $mail->SMTPSecure = ($smtp['secure'] ?? 'tls') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout = 15;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom($from, (string) ($smtp['from_name'] ?? 'Gaia Events'));
            $mail->addAddress($message['to']);

            $replyTo = $message['reply_to'] ?? null;
            if (is_string($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
                $mail->addReplyTo($replyTo, (string) ($message['reply_to_name'] ?? ''));
            }

            $mail->Subject = $message['subject'];
            $mail->Body = $message['text'];
            $mail->isHTML(false);

            $mail->send();

            return ['ok' => true, 'error' => null];
        } catch (PHPMailerException $exception) {
            // ErrorInfo non contiene la password: solo la risposta del server.
            $errore = trim($mail->ErrorInfo !== '' ? $mail->ErrorInfo : $exception->getMessage());
            error_log('[mailer] ' . $errore);

            return ['ok' => false, 'error' => mb_substr($errore, 0, 250)];
        }
    }
}
