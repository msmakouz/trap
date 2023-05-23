<?php

declare(strict_types=1);

namespace Buggregator\Client\Command;

use Buggregator\Client\Logger;
use DateTimeImmutable;
use RuntimeException;
use Socket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run application
 */
final class Test extends Command
{
    protected static $defaultName = 'test';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        // $this->dump();
        // \usleep(100_000);
        $this->mail($output, true);

        return Command::SUCCESS;
    }

    private function dump(): void
    {
        $_SERVER['VAR_DUMPER_FORMAT'] = 'server';
        $_SERVER['VAR_DUMPER_SERVER'] = '127.0.0.1:9912';

        \dump(['foo' => 'bar']);
        \dump(123);
        \dump(new DateTimeImmutable());
    }

    private function mail(OutputInterface $output, bool $multipart = false): void
    {
        try {
            $socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            \socket_connect($socket, '127.0.0.1', 9912);

            $this->sendMailPackage($output, $socket, '', '220 ');
            $this->sendMailPackage($output, $socket, "HELO\r\n", '250 ');
            $this->sendMailPackage($output, $socket, "MAIL FROM: <someusername@foo.bar>\r\n", '250 ');
            // $this->sendMailPackage($output, $socket, "RCPT TO: <user1@company.tld>\r\n", '250 ');
            // $this->sendMailPackage($output, $socket, "RCPT TO: <user2@company.tld>\r\n", '250 ');
            $this->sendMailPackage($output, $socket, "DATA\r\n", '354 ');

            // Send Data
            if ($multipart) {
                $this->sendMailPackage($output, $socket, "From: sender@example.com\r\n", '');
                $this->sendMailPackage($output, $socket, "To: recipient@example.com\r\n", '');
                $this->sendMailPackage($output, $socket, "Subject: Multipart Email Example\r\n", '');
                $this->sendMailPackage(
                    $output,
                    $socket,
                    "Content-Type: multipart/alternative; boundary=\"boundary-string\"\r\n",
                    '',
                );
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "--boundary-string\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Type: text/plain; charset=\"utf-8\"\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Transfer-Encoding: quoted-printable\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Disposition: inline\r\n", '');
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "Plain text email goes here!\r\n", '');
                $this->sendMailPackage(
                    $output,
                    $socket,
                    "This is the fallback if email client does not support HTML\r\n",
                    '',
                );
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "--boundary-string\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Type: text/html; charset=\"utf-8\"\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Transfer-Encoding: quoted-printable\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Disposition: inline\r\n", '');
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "<h1>This is the HTML Section!</h1>\r\n", '');
                $this->sendMailPackage(
                    $output,
                    $socket,
                    "<p>This is what displays in most modern email clients</p>\r\n",
                    '',
                );
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "--boundary-string--\r\n", '');
                $this->sendMailPackage($output, $socket, "\r\n", '250 ');
            } else {
                $this->sendMailPackage($output, $socket, "From: Some User <someusername@somecompany.ru>\r\n", '');
                $this->sendMailPackage($output, $socket, "To: User1 <user1@company.tld>\r\n", '');
                $this->sendMailPackage($output, $socket, "Subject: Very important theme!\r\n", '');
                $this->sendMailPackage($output, $socket, "Content-Type: text/plain\r\n", '');
                $this->sendMailPackage($output, $socket, "\r\n", '');
                $this->sendMailPackage($output, $socket, "Hi!\r\n", '');
                $this->sendMailPackage($output, $socket, ".\r\n", '250 ');
            }
            // End of data
            $this->sendMailPackage($output, $socket, "QUIT\r\n", '221 ');

            \socket_close($socket);

        } catch (\Throwable $e) {
            Logger::exception($e, 'Mail protocol error');
        }
    }

    private function sendMailPackage(
        OutputInterface $output,
        Socket $socket,
        string $content,
        string $expectedResponsePrefix,
    ): void {
        if ($content !== '') {
            \socket_write($socket, $content);
            // print green "hello" string in raw console markup
            $output->write(
                '> ' . \str_replace(["\r", "\n"], ["\e[32m\\r\e[0m", "\e[32m\\n\e[0m"], $content),
                true,
                $output::OUTPUT_RAW,
            );
        }

        if ($expectedResponsePrefix === '') {
            return;
        }
        @\socket_recv($socket, $buf, 65536, 0);
        if ($buf === null) {
            $output->writeln('<error>Disconnected</>');
            return;
        }

        $output->write(\sprintf(
            "\e[33m< \"%s\"\e[0m",
            \str_replace(["\r", "\n"], ["\e[32m\\r\e[33m", "\e[32m\\n\e[33m"], $buf)),
            true,
            $output::OUTPUT_RAW,
        );

        $prefix = \substr($buf, 0, \strlen($expectedResponsePrefix));
        if ($prefix !== $expectedResponsePrefix) {
            throw new RuntimeException("Invalid response `$buf`. Prefix `$expectedResponsePrefix` expected.");
        }
    }
}
