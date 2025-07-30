<?php

namespace MrNewport\LaravelPlaid\DTOs;

class LinkToken extends BaseDTO
{
    public string $link_token;
    public string $expiration;
    public string $request_id;
}