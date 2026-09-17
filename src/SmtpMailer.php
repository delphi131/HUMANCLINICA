<?php

declare(strict_types=1);

/**
 * Minimal dependency-free SMTP client (AUTH LOGIN, STARTTLS/SSL, HTML email).
 * Written by hand instead of pulling in PHPMailer/Composer so the app stays a
 * plain set of PHP files that can be dropped onto shared hosting as-is.
 */
final class SmtpMailer
{
    private string $host;
    private int $port;
    private string $encryption; // tls | ssl | none
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    /** @var resource|null */
    private $socket = null;

    public function __construct(array $settings)
    {
        $this->host = (string)($settings['host'] ?? '');
        $this->port = (int)($settings['port'] ?? 587);
        $this->encryption = strtolower((string)($settings['encryption'] ?? 'tls'));
        $this->username = (string)($settings['username'] ?? '');
        $this->password = (string)($settings['password'] ?? '');
        $this->fromEmail = (string)($settings['from_email'] ?? $this->username);
        $this->fromName = (string)($settings['from_name'] ?? '');
    }

    /**
     * @param string[] $to
     * @throws RuntimeException on any SMTP-level failure.
     */
    public function send(array $to, string $subject, string $htmlBody, ?string $replyTo = null): void
    {
        if ($this->host === '') {
            throw new RuntimeException('Configurazione SMTP mancante: imposta i parametri nella pagina Messaggi.');
        }

        $this->connect();
        try {
            $this->expect(220);
            $this->command('EHLO ' . $this->localHostname(), 250);

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Impossibile avviare TLS con il server SMTP.');
                }
                $this->command('EHLO ' . $this->localHostname(), 250);
            }

            if ($this->username !== '') {
                $this->command('AUTH LOGIN', 334);
                $this->command(base64_encode($this->username), 334);
                $this->command(base64_encode($this->password), 235);
            }

            $this->command('MAIL FROM:<' . $this->fromEmail . '>', 250);
            foreach ($to as $recipient) {
                $this->command('RCPT TO:<' . $recipient . '>', 250);
            }

            $this->command('DATA', 354);
            $this->write($this->buildMessage($to, $subject, $htmlBody, $replyTo));
            $this->command('.', 250);

            $this->command('QUIT', 221);
        } finally {
            $this->disconnect();
        }
    }

    private function buildMessage(array $to, string $subject, string $htmlBody, ?string $replyTo): string
    {
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->localHostname() . '>';
        $headers[] = 'From: ' . $this->encodeHeader($this->fromName, $this->fromEmail);
        $headers[] = 'To: ' . implode(', ', array_map(fn($addr) => '<' . $addr . '>', $to));
        if ($replyTo) {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }
        $headers[] = 'Subject: ' . $this->encodeSubject($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';

        $body = $this->dotStuff($htmlBody);

        return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n";
    }

    private function dotStuff(string $body): string
    {
        // Lines starting with "." must be escaped per RFC 5321 before the terminating "." line.
        return preg_replace('/^\./m', '..', $body);
    }

    private function encodeSubject(string $subject): string
    {
        if (preg_match('/[^\x20-\x7E]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    private function encodeHeader(string $name, string $email): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }
        $encodedName = preg_match('/[^\x20-\x7E]/', $name)
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : $name;
        return $encodedName . ' <' . $email . '>';
    }

    private function localHostname(): string
    {
        return $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost';
    }

    private function connect(): void
    {
        $transport = $this->encryption === 'ssl' ? 'ssl://' : '';
        $address = $transport . $this->host . ':' . $this->port;
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $socket = stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if ($socket === false) {
            throw new RuntimeException("Connessione SMTP fallita ({$this->host}:{$this->port}): {$errstr}");
        }

        stream_set_timeout($socket, 15);
        $this->socket = $socket;
    }

    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    private function write(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function command(string $line, int $expectedCode): void
    {
        $this->write($line . "\r\n");
        $this->expect($expectedCode);
    }

    private function expect(int $expectedCode): void
    {
        $response = '';
        do {
            $line = fgets($this->socket, 515);
            if ($line === false) {
                throw new RuntimeException('Connessione SMTP interrotta durante la lettura della risposta.');
            }
            $response .= $line;
            $continues = isset($line[3]) && $line[3] === '-';
        } while ($continues);

        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new RuntimeException("Risposta SMTP inattesa (atteso {$expectedCode}): " . trim($response));
        }
    }
}
