<?php

namespace ProcessMaker\Mail;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MicrosoftGraphMessageConverter
{
    public static function toSendMailPayload(Email $email): array
    {
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();

        $message = [
            'subject' => $email->getSubject() ?? '',
            'body' => [
                'contentType' => $htmlBody !== null ? 'HTML' : 'Text',
                'content' => $htmlBody ?? $textBody ?? '',
            ],
            'toRecipients' => self::convertAddresses($email->getTo()),
        ];

        $ccRecipients = self::convertAddresses($email->getCc());
        if ($ccRecipients) {
            $message['ccRecipients'] = $ccRecipients;
        }

        $bccRecipients = self::convertAddresses($email->getBcc());
        if ($bccRecipients) {
            $message['bccRecipients'] = $bccRecipients;
        }

        $attachments = self::convertAttachments($email);
        if ($attachments) {
            $message['attachments'] = $attachments;
        }

        return [
            'message' => $message,
            'saveToSentItems' => true,
        ];
    }

    /**
     * @param  Address[]  $addresses
     */
    private static function convertAddresses(array $addresses): array
    {
        return array_map(function (Address $address) {
            $emailAddress = ['address' => $address->getAddress()];

            if ($address->getName()) {
                $emailAddress['name'] = $address->getName();
            }

            return ['emailAddress' => $emailAddress];
        }, $addresses);
    }

    private static function convertAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $attachment->getFilename() ?: 'attachment',
                'contentType' => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                'contentBytes' => base64_encode($attachment->getBody()),
            ];
        }

        return $attachments;
    }
}
