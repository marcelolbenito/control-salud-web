<?php

declare(strict_types=1);

interface WhatsAppWebProvider
{
    public function isConfigured(): bool;

    /** @return array{ok:bool,connected:bool,message:string} */
    public function sessionStatus(): array;

    /**
     * @return array{ok:bool,message_id:?string,error:?string}
     */
    public function sendText(string $telefonoE164, string $texto): array;
}
