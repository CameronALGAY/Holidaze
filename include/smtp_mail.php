<?php

const SMTP_DEBUG = false;

function sendSmtpMail(string $to, string $subject, string $body): bool
{
    $host = '127.0.0.1';
    $port = 1025;
    $timeout = 5;
    $from = 'no-reply@holidaze.local';

    if (SMTP_DEBUG) {
        error_log("[SMTP] Connecting to $host:$port");
    }

    $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("[SMTP] Connection failed: $errno - $errstr");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read = function () use ($socket) {
        $response = '';
        while (true) {
            $line = fgets($socket, 1024);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                if ($meta['timed_out']) {
                    error_log("[SMTP] Read timeout");
                }
                break;
            }
            if (SMTP_DEBUG) {
                error_log("[SMTP] < " . trim($line));
            }
            $response .= $line;
            // SMTP responses end with space, not dash: "250 " means end, "250-" means more lines
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $response;
    };

    $write = function (string $command) use ($socket): void {
        if (SMTP_DEBUG) {
            error_log("[SMTP] > $command");
        }
        fwrite($socket, $command . "\r\n");
        flush();
    };

    $read();
    $write('EHLO localhost');
    $read();
    $write('MAIL FROM:<' . $from . '>');
    $read();
    $write('RCPT TO:<' . $to . '>');
    $read();
    $write('DATA');
    $read();

    $headers = [
        'From: Holidaze <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $write($message);
    $write('.');
    $read();
    $write('QUIT');
    $read();
    fclose($socket);

    if (SMTP_DEBUG) {
        error_log("[SMTP] Mail sent successfully to $to");
    }

    return true;
}