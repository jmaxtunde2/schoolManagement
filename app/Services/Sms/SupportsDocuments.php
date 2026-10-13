<?php

namespace App\Services\Sms;

/**
 * Fournisseur capable d'envoyer un document (pièce jointe).
 * Implémenté par Meta WhatsApp Cloud API via un lien public.
 */
interface SupportsDocuments
{
    public function sendDocument(
        string $phone,
        string $link,
        ?string $filename = null,
        ?string $caption = null
    ): SmsResponse;
}
