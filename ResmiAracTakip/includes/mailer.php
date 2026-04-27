<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mail.php';

function smtp_send_mail(
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $textBody = ''
): bool {
    $mailConfig = rat_mail_config();
    $smtpUser = trim((string) ($mailConfig['username'] ?? ''));
    $smtpPass = trim((string) ($mailConfig['password'] ?? ''));

    if ($smtpUser === '' || $smtpPass === '') {
        return false;
    }

    $host = trim((string) ($mailConfig['host'] ?? ''));
    $port = (int) ($mailConfig['port'] ?? 0);
    $encryption = strtolower(trim((string) ($mailConfig['encryption'] ?? 'ssl')));
    $timeout = (int) ($mailConfig['timeout'] ?? 20);
    $timeout = $timeout > 0 ? $timeout : 20;
    $fromAddress = trim((string) ($mailConfig['from_address'] ?? ''));
    $fromName = trim((string) ($mailConfig['from_name'] ?? ''));

    if ($host === '' || $port <= 0 || $fromAddress === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!is_resource($socket)) {
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $ok = mailer_smtp_expect($socket, [220])
        && mailer_smtp_command($socket, 'EHLO localhost', [250]);

    if ($ok && $encryption === 'tls') {
        $ok = mailer_smtp_command($socket, 'STARTTLS', [220])
            && stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && mailer_smtp_command($socket, 'EHLO localhost', [250]);
    }

    $ok = $ok
        && mailer_smtp_command($socket, 'AUTH LOGIN', [334])
        && mailer_smtp_command($socket, base64_encode($smtpUser), [334])
        && mailer_smtp_command($socket, base64_encode($smtpPass), [235])
        && mailer_smtp_command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250])
        && mailer_smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251])
        && mailer_smtp_command($socket, 'DATA', [354]);

    if (!$ok) {
        mailer_smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return false;
    }

    $body = build_mime_message($toEmail, $subject, $htmlBody, $textBody, $fromAddress, $fromName);
    fwrite($socket, $body . "\r\n.\r\n");
    $sent = mailer_smtp_expect($socket, [250]);
    mailer_smtp_command($socket, 'QUIT', [221]);
    fclose($socket);

    return $sent;
}

function build_mime_message(
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $textBody,
    string $fromAddress,
    string $fromName
): string {
    $boundary = 'rat-' . bin2hex(random_bytes(10));
    $plain = trim($textBody) !== '' ? $textBody : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromAddress . '>',
        'To: <' . $toEmail . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $mime = implode("\r\n", $headers) . "\r\n\r\n";
    $mime .= '--' . $boundary . "\r\n";
    $mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mime .= chunk_split(base64_encode($plain));
    $mime .= '--' . $boundary . "\r\n";
    $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mime .= chunk_split(base64_encode($htmlBody));
    $mime .= '--' . $boundary . "--\r\n";

    return smtp_dot_stuff($mime);
}

function smtp_dot_stuff(string $message): string
{
    return preg_replace('/^\./m', '..', $message) ?? $message;
}

function mailer_smtp_command($socket, string $command, array $expectedCodes): bool
{
    fwrite($socket, $command . "\r\n");
    return mailer_smtp_expect($socket, $expectedCodes);
}

function mailer_smtp_expect($socket, array $expectedCodes): bool
{
    $response = '';
    while (($line = fgets($socket, 512)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if (strlen($response) < 3) {
        return false;
    }

    $code = (int) substr($response, 0, 3);
    return in_array($code, $expectedCodes, true);
}
