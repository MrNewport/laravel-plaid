<?php

use MrNewport\LaravelPlaid\Exceptions\PlaidException;
use MrNewport\LaravelPlaid\Exceptions\PlaidAuthenticationException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRateLimitException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;

it('creates PlaidException with all properties', function () {
    $previous = new Exception('Previous exception');
    $displayMessage = ['en' => 'User friendly message'];
    
    $exception = new PlaidException(
        'Error message',
        400,
        $previous,
        'INVALID_REQUEST',
        'INVALID_REQUEST',
        $displayMessage,
        'req_123'
    );
    
    expect($exception->getMessage())->toBe('Error message');
    expect($exception->getCode())->toBe(400);
    expect($exception->getPrevious())->toBe($previous);
    expect($exception->getErrorCode())->toBe('INVALID_REQUEST');
    expect($exception->getErrorType())->toBe('INVALID_REQUEST');
    expect($exception->getDisplayMessage())->toBe($displayMessage);
    expect($exception->getRequestId())->toBe('req_123');
});

it('creates PlaidException with minimal properties', function () {
    $exception = new PlaidException('Error message');
    
    expect($exception->getMessage())->toBe('Error message');
    expect($exception->getCode())->toBe(0);
    expect($exception->getPrevious())->toBeNull();
    expect($exception->getErrorCode())->toBe('');
    expect($exception->getErrorType())->toBe('');
    expect($exception->getDisplayMessage())->toBeNull();
    expect($exception->getRequestId())->toBeNull();
});

it('creates PlaidAuthenticationException', function () {
    $exception = new PlaidAuthenticationException(
        'Invalid credentials',
        401,
        null,
        'INVALID_API_KEYS',
        'INVALID_INPUT'
    );
    
    expect($exception)->toBeInstanceOf(PlaidAuthenticationException::class);
    expect($exception)->toBeInstanceOf(PlaidException::class);
    expect($exception->getMessage())->toBe('Invalid credentials');
    expect($exception->getCode())->toBe(401);
    expect($exception->getErrorCode())->toBe('INVALID_API_KEYS');
});

it('creates PlaidRateLimitException', function () {
    $exception = new PlaidRateLimitException(
        'Rate limit exceeded',
        429,
        null,
        'RATE_LIMIT_EXCEEDED',
        'RATE_LIMIT'
    );
    
    expect($exception)->toBeInstanceOf(PlaidRateLimitException::class);
    expect($exception)->toBeInstanceOf(PlaidException::class);
    expect($exception->getMessage())->toBe('Rate limit exceeded');
    expect($exception->getCode())->toBe(429);
    expect($exception->getErrorCode())->toBe('RATE_LIMIT_EXCEEDED');
});

it('creates PlaidRequestException', function () {
    $exception = new PlaidRequestException(
        'Invalid field',
        400,
        null,
        'INVALID_FIELD',
        'INVALID_REQUEST'
    );
    
    expect($exception)->toBeInstanceOf(PlaidRequestException::class);
    expect($exception)->toBeInstanceOf(PlaidException::class);
    expect($exception->getMessage())->toBe('Invalid field');
    expect($exception->getCode())->toBe(400);
    expect($exception->getErrorCode())->toBe('INVALID_FIELD');
});

it('preserves exception chain', function () {
    $originalException = new Exception('Original error');
    $plaidException = new PlaidException(
        'Wrapped error',
        500,
        $originalException
    );
    
    expect($plaidException->getPrevious())->toBe($originalException);
    expect($plaidException->getPrevious()->getMessage())->toBe('Original error');
});