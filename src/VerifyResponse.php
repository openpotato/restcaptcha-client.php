<?php
/**
 * Copyright (c) STÜBER SYSTEMS GmbH
 * Licensed under the MIT License, Version 2.0.
 */

namespace RestCaptcha;

/**
 * Verification response returned by the RESTCaptcha API.
 */
class VerifyResponse
{
    /**
     * The verification status
     * 
     * @var VerifyStatus
     */
    public VerifyStatus $status;

    /**
     * The host name to be verified on the client side.
     * 
     * @var ?string
     */
    public ?string $hostName;

    /**
     * Initializes a new instance of the VerifyResponse class.
     *
     * @param VerifyStatus $status    The verification status
     * @param ?string      $hostName  The host name to be verified on the client side.
     */
    public function __construct(
        VerifyStatus $status, 
        ?string $hostName)
    {
        $this->status = $status;
        $this->hostName = $hostName;
    }

    /**
     * String representation of this instance.
     * 
     * @return string  The string representation of the instance.
     */
    public function __toString(): string
    {
        $details = [
            'Status: ' . $this->status->value,
            'HostName: ' . $this->hostName
        ];
        return implode(PHP_EOL, array_filter($details));
    }
}

