<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Storage;

class UserGmailService
{
    public function sendInvoice(User $user, Invoice $invoice, string $pdfPath, string $toEmail): void
    {
        if (! $user->google_refresh_token) {
            throw new \RuntimeException('Gmail není propojen.');
        }

        $client = $this->makeClient($user);
        $gmail = new Gmail($client);

        $raw = $this->buildMimeMessage(
            from: $user->email,
            to: $toEmail,
            subject: 'Faktura '.$invoice->number,
            body: "Dobrý den,\n\nv příloze zasílám fakturu č. {$invoice->number}.\n\nS pozdravem\n{$user->displayCompanyName()}",
            attachmentPath: Storage::disk('local')->path($pdfPath),
            attachmentName: "faktura-{$invoice->number}.pdf",
        );

        $message = new Message();
        $message->setRaw($raw);

        $gmail->users_messages->send('me', $message);
    }

    private function makeClient(User $user): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.send',
        ]);
        $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

        return $client;
    }

    private function buildMimeMessage(
        string $from,
        string $to,
        string $subject,
        string $body,
        string $attachmentPath,
        string $attachmentName,
    ): string {
        $boundary = uniqid('boundary_', true);
        $pdfContent = base64_encode(file_get_contents($attachmentPath));

        $mime = "From: {$from}\r\n";
        $mime .= "To: {$to}\r\n";
        $mime .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";
        $mime .= "--{$boundary}\r\n";
        $mime .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $mime .= $body."\r\n\r\n";
        $mime .= "--{$boundary}\r\n";
        $mime .= "Content-Type: application/pdf; name=\"{$attachmentName}\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n";
        $mime .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
        $mime .= chunk_split($pdfContent)."\r\n";
        $mime .= "--{$boundary}--";

        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
    }
}
