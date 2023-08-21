<?php

namespace Meta\BusinessExtension\Model;

class ApiKeyGenerator
{
    /**
     * Generate a 32-character hexadecimal API key
     *
     * @return string
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
