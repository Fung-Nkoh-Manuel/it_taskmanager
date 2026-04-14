<?php

// ════════════════════════════════════════════════════════════
//  Mailer  –  lightweight SMTP wrapper (no external library)
//  Uses PHP's built-in mail() by default; switches to SMTP
//  when MAIL_HOST is defined in config/app.php.
// ════════════════════════════════════════════════════════════

class Mailer
{
    // ── Send a plain-text + HTML email ────────────────────────────────────────

    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Mailer: invalid recipient address «{$toEmail}»");
            return false;
        }

        $fromEmail = MAIL_FROM;
        $fromName  = MAIL_FROM_NAME;
        $plainText = self::htmlToText($htmlBody);
        $boundary  = '=_Part_' . md5(uniqid('', true));

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: IT-TaskManager/1.0\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainText)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--";

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $to             = "=?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";

        // ── Try SMTP if configured, fall back to mail() ───────────────────────
        if (defined('MAIL_HOST') && MAIL_HOST) {
            return self::sendSmtp($toEmail, $toName, $subject, $htmlBody, $plainText, $fromEmail, $fromName);
        }

        $result = @mail($to, $encodedSubject, $body, $headers);

        if (!$result) {
            error_log("Mailer: mail() failed for {$toEmail} — subject: {$subject}");
        }

        return $result;
    }

    // ── SMTP sender (plain socket, no TLS/STARTTLS for simplicity) ────────────
    //    For TLS support, swap stream_socket_client for ssl:// or use PHPMailer.

    private static function sendSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainText,
        string $fromEmail,
        string $fromName
    ): bool {
        $host     = MAIL_HOST;
        $port     = defined('MAIL_PORT')       ? (int) MAIL_PORT       : 587;
        $user     = defined('MAIL_USERNAME')   ? MAIL_USERNAME         : '';
        $pass     = defined('MAIL_PASSWORD')   ? MAIL_PASSWORD         : '';
        $enc      = defined('MAIL_ENCRYPTION') ? strtolower(MAIL_ENCRYPTION) : 'tls';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,   // set true in production with a CA bundle
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $socket = @stream_socket_client(
            "{$prefix}{$host}:{$port}",
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT, $context
        );

        if (!$socket) {
            error_log("Mailer SMTP: cannot connect to {$host}:{$port} — {$errstr} ({$errno})");
            return false;
        }

        try {
            self::smtpExpect($socket, '220');
            self::smtpSend($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $ehloResp = self::smtpRead($socket);

            // STARTTLS upgrade
            if ($enc === 'tls' && strpos($ehloResp, 'STARTTLS') !== false) {
                self::smtpSend($socket, 'STARTTLS');
                self::smtpExpect($socket, '220');
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::smtpSend($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                self::smtpRead($socket);
            }

            // AUTH LOGIN
            if ($user) {
                self::smtpSend($socket, 'AUTH LOGIN');
                self::smtpExpect($socket, '334');
                self::smtpSend($socket, base64_encode($user));
                self::smtpExpect($socket, '334');
                self::smtpSend($socket, base64_encode($pass));
                self::smtpExpect($socket, '235');
            }

            self::smtpSend($socket, "MAIL FROM:<{$fromEmail}>");
            self::smtpExpect($socket, '250');
            self::smtpSend($socket, "RCPT TO:<{$toEmail}>");
            self::smtpExpect($socket, '250');
            self::smtpSend($socket, 'DATA');
            self::smtpExpect($socket, '354');

            $boundary = '=_Part_' . md5(uniqid('', true));
            $msg  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
            $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
            $msg .= "--{$boundary}\r\n";
            $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg .= chunk_split(base64_encode($plainText)) . "\r\n";
            $msg .= "--{$boundary}\r\n";
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $msg .= "--{$boundary}--\r\n.";

            fwrite($socket, $msg . "\r\n");
            self::smtpExpect($socket, '250');
            self::smtpSend($socket, 'QUIT');
            fclose($socket);
            return true;

        } catch (\RuntimeException $e) {
            error_log('Mailer SMTP error: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    private static function smtpSend($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function smtpRead($socket): string
    {
        $resp = '';
        while ($line = fgets($socket, 515)) {
            $resp .= $line;
            if ($line[3] === ' ') break;   // last line of multi-line response
        }
        return $resp;
    }

    private static function smtpExpect($socket, string $code): void
    {
        $resp = self::smtpRead($socket);
        if (substr(trim($resp), 0, 3) !== $code) {
            throw new \RuntimeException("SMTP expected {$code}, got: " . trim($resp));
        }
    }

    // ── Strip HTML to plain text ──────────────────────────────────────────────

    private static function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i',      "\n\n", $text);
        $text = preg_replace('/<\/tr>/i',     "\n", $text);
        $text = preg_replace('/<\/td>/i',     "\t", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}