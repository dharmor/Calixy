<?php

declare(strict_types=1);

namespace UnifiedAppointments\Notifications;

use RuntimeException;

/**
 * SmtpEmailDispatcher.
 */
final class SmtpEmailDispatcher
{
    private const SOCKET_TIMEOUT_SECONDS = 15;

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function dispatch(
        array $config,
        string $recipient,
        string $subject,
        string $textBody,
    ): array {
        $recipientAddress = $this->emailAddress($recipient, 'Recipient email address is required.');
        $host = $this->requiredString($config['host'] ?? null, 'SMTP host is required.');
        $port = $this->port($config['port'] ?? null);
        $encryption = $this->encryption($config['encryption'] ?? 'tls');
        $username = $this->nullableString($config['username'] ?? null);
        $password = $this->nullableString($config['password'] ?? null);
        $fromAddress = $this->emailAddress($config['from_address'] ?? null, 'From address is required.');
        $fromName = $this->nullableString($config['from_name'] ?? null);
        $replyTo = $this->nullableEmailAddress($config['reply_to'] ?? null);

        if ($password !== null && $username === null) {
            throw new RuntimeException('SMTP username is required when a password is set.');
        }

        $socket = $this->openSocket($host, $port, $encryption);

        try {
            $responses = [];
            $responses[] = $this->readResponse($socket, [220]);

            $helloDomain = $this->helloDomain();
            $responses[] = $this->sendCommand($socket, 'EHLO ' . $helloDomain, [250]);

            if ($encryption === 'tls') {
                $responses[] = $this->sendCommand($socket, 'STARTTLS', [220]);

                if (@stream_socket_enable_crypto($socket, true, $this->tlsCryptoMethod()) !== true) {
                    throw new RuntimeException('Unable to start TLS encryption for the SMTP connection.');
                }

                $responses[] = $this->sendCommand($socket, 'EHLO ' . $helloDomain, [250]);
            }

            if ($username !== null) {
                $responses[] = $this->sendCommand($socket, 'AUTH LOGIN', [334]);
                $responses[] = $this->sendCommand($socket, base64_encode($username), [334]);
                $responses[] = $this->sendCommand($socket, base64_encode($password ?? ''), [235]);
            }

            $responses[] = $this->sendCommand($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
            $responses[] = $this->sendCommand($socket, 'RCPT TO:<' . $recipientAddress . '>', [250, 251]);
            $responses[] = $this->sendCommand($socket, 'DATA', [354]);

            $payload = $this->messagePayload(
                recipient: $recipientAddress,
                subject: $subject,
                textBody: $textBody,
                fromAddress: $fromAddress,
                fromName: $fromName,
                replyTo: $replyTo,
            );

            $this->write($socket, $payload . "\r\n.\r\n");
            $responses[] = $this->readResponse($socket, [250]);

            try {
                $responses[] = $this->sendCommand($socket, 'QUIT', [221]);
            } catch (RuntimeException) {
                // The message has already been accepted at this point.
            }

            return [
                'transport' => 'smtp',
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'recipient' => $recipientAddress,
                'responses' => $responses,
            ];
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return resource
     */
    private function openSocket(string $host, int $port, string $encryption)
    {
        $scheme = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $remote = $scheme . $host . ':' . $port;
        $errorNumber = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            $remote,
            $errorNumber,
            $errorMessage,
            self::SOCKET_TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT,
        );

        if (!is_resource($socket)) {
            throw new RuntimeException(sprintf(
                'Unable to connect to the SMTP server at %s:%d%s',
                $host,
                $port,
                $errorMessage !== '' ? ' (' . $errorMessage . ')' : '.',
            ));
        }

        stream_set_timeout($socket, self::SOCKET_TIMEOUT_SECONDS);

        return $socket;
    }

    /**
     * @param resource $socket
     * @param array<int, int> $expectedCodes
     */
    private function sendCommand($socket, string $command, array $expectedCodes): string
    {
        $this->write($socket, $command . "\r\n");

        return $this->readResponse($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     * @param array<int, int> $expectedCodes
     */
    private function readResponse($socket, array $expectedCodes): string
    {
        $response = '';
        $code = null;

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (preg_match('/^(\d{3})([ -])/', $line, $matches) === 1) {
                $code = (int) $matches[1];

                if ($matches[2] === ' ') {
                    break;
                }
            }
        }

        if ($response === '') {
            throw new RuntimeException('SMTP server did not return a response.');
        }

        if ($code === null || !in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
        }

        return trim($response);
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $payload): void
    {
        $written = @fwrite($socket, $payload);

        if ($written === false || $written < strlen($payload)) {
            throw new RuntimeException('Unable to write to the SMTP connection.');
        }
    }

    /**
     * Message Payload.
     */
    private function messagePayload(
        string $recipient,
        string $subject,
        string $textBody,
        string $fromAddress,
        ?string $fromName,
        ?string $replyTo,
    ): string {
        $headers = [
            'Date: ' . gmdate('D, d M Y H:i:s O'),
            'From: ' . $this->addressHeader($fromAddress, $fromName),
            'To: ' . $this->addressHeader($recipient),
            'Subject: ' . $this->mimeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . $this->addressHeader($replyTo);
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $this->dotStuff($this->normalizeBody($textBody));
    }

    /**
     * Address Header.
     */
    private function addressHeader(string $email, ?string $name = null): string
    {
        if ($name === null) {
            return '<' . $email . '>';
        }

        return $this->mimeHeader($name) . ' <' . $email . '>';
    }

    /**
     * Mime Header.
     */
    private function mimeHeader(string $value): string
    {
        $sanitized = trim(str_replace(["\r", "\n"], ' ', $value));

        return '=?UTF-8?B?' . base64_encode($sanitized) . '?=';
    }

    /**
     * Normalize Body.
     */
    private function normalizeBody(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($value));

        return str_replace("\n", "\r\n", $normalized);
    }

    /**
     * Dot Stuff.
     */
    private function dotStuff(string $value): string
    {
        return preg_replace('/(^|\r\n)\./', '$1..', $value) ?? $value;
    }

    /**
     * Hello Domain.
     */
    private function helloDomain(): string
    {
        $hostname = gethostname();

        if (!is_string($hostname) || $hostname === '') {
            return 'localhost';
        }

        $hostname = strtolower($hostname);
        $hostname = preg_replace('/[^a-z0-9.-]/', '-', $hostname) ?? 'localhost';

        return trim($hostname, '-.') !== '' ? trim($hostname, '-.') : 'localhost';
    }

    /**
     * Tls Crypto Method.
     */
    private function tlsCryptoMethod(): int
    {
        return defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')
            ? STREAM_CRYPTO_METHOD_TLS_CLIENT
            : STREAM_CRYPTO_METHOD_ANY_CLIENT;
    }

    /**
     * Required String.
     */
    private function requiredString(mixed $value, string $message): string
    {
        $string = $this->nullableString($value);

        if ($string === null) {
            throw new RuntimeException($message);
        }

        return $string;
    }

    /**
     * Nullable String.
     */
    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * Email Address.
     */
    private function emailAddress(mixed $value, string $message): string
    {
        $email = $this->nullableEmailAddress($value);

        if ($email === null) {
            throw new RuntimeException($message);
        }

        return $email;
    }

    /**
     * Nullable Email Address.
     */
    private function nullableEmailAddress(mixed $value): ?string
    {
        $email = $this->nullableString($value);

        if ($email === null) {
            return null;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Email addresses must be valid.');
        }

        return $email;
    }

    /**
     * Port.
     */
    private function port(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new RuntimeException('SMTP port is required.');
        }

        $port = (int) $value;

        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('SMTP port must be between 1 and 65535.');
        }

        return $port;
    }

    /**
     * Encryption.
     */
    private function encryption(mixed $value): string
    {
        $encryption = strtolower($this->requiredString($value, 'SMTP encryption is required.'));

        if (!in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            throw new RuntimeException('SMTP encryption must be none, tls, or ssl.');
        }

        return $encryption;
    }
}

