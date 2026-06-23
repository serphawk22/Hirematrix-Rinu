<?php

namespace App\Libraries;

class CustomMailboxClient
{
    /** @var resource|null */
    private $stream;
    private int $tag = 0;

    public function test(array $settings): void
    {
        $this->testImap($settings);
        $this->testSmtp($settings);
    }

    public function fetchInbox(array $settings, int $sinceTimestamp, int $limit = 50): array
    {
        $this->connectImap($settings);
        try {
            $this->command('LOGIN ' . $this->quote((string) $settings['username']) . ' ' . $this->quote((string) $settings['password']));
            $this->command('SELECT INBOX');
            $search = $this->command('UID SEARCH SINCE ' . date('d-M-Y', $sinceTimestamp));
            preg_match('/\* SEARCH([^\r\n]*)/i', $search, $matches);
            $uids = array_values(array_filter(preg_split('/\s+/', trim((string) ($matches[1] ?? ''))) ?: [], 'ctype_digit'));
            $uids = array_slice($uids, -$limit);
            $messages = [];
            foreach ($uids as $uid) {
                $response = $this->command('UID FETCH ' . $uid . ' (BODY.PEEK[])');
                $raw = $this->extractFetchLiteral($response);
                if ($raw === '') {
                    continue;
                }
                $parsed = $this->parseMessage($raw);
                $parsed['uid'] = $uid;
                $messages[] = $parsed;
            }
            $this->command('LOGOUT', false);
            return $messages;
        } finally {
            $this->close();
        }
    }

    private function testImap(array $settings): void
    {
        $this->connectImap($settings);
        try {
            $this->command('LOGIN ' . $this->quote((string) $settings['username']) . ' ' . $this->quote((string) $settings['password']));
            $this->command('LOGOUT', false);
        } finally {
            $this->close();
        }
    }

    private function connectImap(array $settings): void
    {
        $this->stream = $this->open((string) $settings['imap_host'], (int) $settings['imap_port'], (string) $settings['imap_encryption']);
        $greeting = $this->readLine();
        if (stripos($greeting, '* OK') !== 0) {
            throw new \RuntimeException('IMAP server did not accept the connection.');
        }
        if (($settings['imap_encryption'] ?? '') === 'tls') {
            $this->command('STARTTLS');
            if (!stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('Could not establish IMAP TLS encryption.');
            }
        }
    }

    private function testSmtp(array $settings): void
    {
        $stream = $this->open((string) $settings['smtp_host'], (int) $settings['smtp_port'], (string) $settings['smtp_encryption']);
        try {
            $this->readSmtp($stream, 220);
            $this->writeSmtp($stream, 'EHLO hirematrix.local');
            $this->readSmtp($stream, 250);
            if (($settings['smtp_encryption'] ?? '') === 'tls') {
                $this->writeSmtp($stream, 'STARTTLS');
                $this->readSmtp($stream, 220);
                if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Could not establish SMTP TLS encryption.');
                }
                $this->writeSmtp($stream, 'EHLO hirematrix.local');
                $this->readSmtp($stream, 250);
            }
            $this->writeSmtp($stream, 'AUTH LOGIN');
            $code = $this->readSmtp($stream, null);
            if ($code !== 334) {
                throw new \RuntimeException('SMTP server does not support AUTH LOGIN.');
            }
            $this->writeSmtp($stream, base64_encode((string) $settings['username']));
            $this->readSmtp($stream, 334);
            $this->writeSmtp($stream, base64_encode((string) $settings['password']));
            $this->readSmtp($stream, 235);
            $this->writeSmtp($stream, 'QUIT');
        } finally {
            fclose($stream);
        }
    }

    /** @return resource */
    private function open(string $host, int $port, string $encryption)
    {
        $scheme = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
            'peer_name' => $host, 'SNI_enabled' => true,
        ]]);
        $stream = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $error, 12, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($stream)) {
            throw new \RuntimeException("Could not connect to {$host}:{$port} ({$error}).");
        }
        stream_set_timeout($stream, 15);
        return $stream;
    }

    private function command(string $command, bool $requireOk = true): string
    {
        $tag = 'HM' . str_pad((string) ++$this->tag, 4, '0', STR_PAD_LEFT);
        fwrite($this->stream, $tag . ' ' . $command . "\r\n");
        $response = '';
        while (!feof($this->stream)) {
            $line = $this->readLine();
            $response .= $line;
            if (preg_match('/\{(\d+)\}\r\n$/', $line, $literal)) {
                $length = (int) $literal[1];
                $data = '';
                while (strlen($data) < $length && !feof($this->stream)) {
                    $data .= fread($this->stream, $length - strlen($data));
                }
                $response .= $data;
            }
            if (str_starts_with($line, $tag . ' ')) {
                if ($requireOk && stripos($line, $tag . ' OK') !== 0) {
                    throw new \RuntimeException('Mailbox command failed: ' . trim(preg_replace('/^' . preg_quote($tag, '/') . '\s+/i', '', $line) ?? $line));
                }
                break;
            }
        }
        return $response;
    }

    private function extractFetchLiteral(string $response): string
    {
        if (!preg_match('/\{(\d+)\}\r\n/', $response, $match, PREG_OFFSET_CAPTURE)) {
            return '';
        }
        $offset = $match[0][1] + strlen($match[0][0]);
        return substr($response, $offset, (int) $match[1][0]);
    }

    private function parseMessage(string $raw): array
    {
        [$headerText, $body] = preg_split("/\r?\n\r?\n/", $raw, 2) + ['', ''];
        $headers = $this->parseHeaders($headerText);
        $body = $this->decodeMimePart($headers, $body);
        $subject = $this->decodeHeader((string) ($headers['subject'] ?? ''));
        return [
            'message_id' => trim((string) ($headers['message-id'] ?? ''), '<> '),
            'thread_id' => trim((string) ($headers['in-reply-to'] ?? $headers['references'] ?? ''), '<> '),
            'from' => $this->extractEmail((string) ($headers['from'] ?? '')),
            'to' => $this->extractEmail((string) ($headers['to'] ?? '')),
            'subject' => $subject,
            'body' => mb_substr($body, 0, 10000),
            'timestamp' => strtotime((string) ($headers['date'] ?? '')) ?: time(),
        ];
    }

    public function cleanStoredBody(string $body): string
    {
        if (preg_match('/^--([^\r\n]+)/', ltrim($body), $match)) {
            return $this->decodeMimePart(['content-type' => 'multipart/mixed; boundary="' . trim($match[1]) . '"'], $body);
        }
        return trim($body);
    }

    private function parseHeaders(string $headerText): array
    {
        $headerText = preg_replace("/\r?\n[ \t]+/", ' ', $headerText) ?? $headerText;
        $headers = [];
        foreach (preg_split('/\r?\n/', $headerText) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }
        return $headers;
    }

    private function decodeMimePart(array $headers, string $body): string
    {
        $contentType = strtolower((string) ($headers['content-type'] ?? 'text/plain'));
        if (str_starts_with($contentType, 'multipart/') && preg_match('/boundary\s*=\s*(?:"([^"]+)"|([^;\s]+))/i', $contentType, $boundaryMatch)) {
            $boundary = (string) ($boundaryMatch[1] ?: $boundaryMatch[2]);
            $plain = [];
            $html = [];
            foreach (preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?\r?\n/', $body) ?: [] as $part) {
                $part = trim($part);
                if ($part === '' || $part === '--') {
                    continue;
                }
                [$partHeadersText, $partBody] = preg_split("/\r?\n\r?\n/", $part, 2) + ['', ''];
                $partHeaders = $this->parseHeaders($partHeadersText);
                if (stripos((string) ($partHeaders['content-disposition'] ?? ''), 'attachment') !== false) {
                    continue;
                }
                $decoded = $this->decodeMimePart($partHeaders, $partBody);
                if ($decoded === '') {
                    continue;
                }
                if (str_starts_with(strtolower((string) ($partHeaders['content-type'] ?? '')), 'text/html')) {
                    $html[] = $decoded;
                } else {
                    $plain[] = $decoded;
                }
            }
            return trim((string) ($plain[0] ?? $html[0] ?? ''));
        }

        $encoding = strtolower((string) ($headers['content-transfer-encoding'] ?? ''));
        if ($encoding === 'base64') {
            $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? '', true);
            if ($decoded !== false) {
                $body = $decoded;
            }
        } elseif ($encoding === 'quoted-printable') {
            $body = quoted_printable_decode($body);
        }
        if (str_starts_with($contentType, 'text/html')) {
            $body = preg_replace('/<(br|\/p|\/div)>/i', "\n", $body) ?? $body;
        }
        return trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function decodeHeader(string $value): string
    {
        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if (is_string($decoded)) {
                return $decoded;
            }
        }
        return $value;
    }

    private function extractEmail(string $value): string
    {
        return preg_match('/<([^>]+)>/', $value, $match) ? strtolower(trim($match[1])) : strtolower(trim(explode(',', $value)[0] ?? ''));
    }

    private function quote(string $value): string
    {
        return '"' . addcslashes($value, "\\\"") . '"';
    }

    private function readLine(): string
    {
        $line = fgets($this->stream, 65536);
        if ($line === false) {
            throw new \RuntimeException('Mailbox server closed the connection.');
        }
        return $line;
    }

    private function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    private function writeSmtp($stream, string $command): void
    {
        fwrite($stream, $command . "\r\n");
    }

    private function readSmtp($stream, ?int $expected): int
    {
        $code = 0;
        do {
            $line = fgets($stream, 4096);
            if ($line === false) {
                throw new \RuntimeException('SMTP server closed the connection.');
            }
            $code = (int) substr($line, 0, 3);
        } while (isset($line[3]) && $line[3] === '-');
        if ($expected !== null && $code !== $expected) {
            throw new \RuntimeException('SMTP authentication failed (server response ' . $code . ').');
        }
        return $code;
    }
}
