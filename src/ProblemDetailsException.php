<?php
/**
 * Copyright (c) STÜBER SYSTEMS GmbH
 * Licensed under the MIT License, Version 2.0.
 */

namespace RestCaptcha;

/**
 * Exception used to handle Problem Details as per RFC 9457.
 */
class ProblemDetailsException extends \Exception
{
    /**
     * A URI reference identifying the error type
     * 
     * @var ?string
     */
    public string $type;

    /**
     * A short, human-readable summary of the problem
     * 
     * @var ?string
     */
    public string $title;

    /**
     * The HTTP status code for the error
     * 
     * @var int
     */
    public int $status;

    /**
     * A detailed, human-readable explanation of the error.
     * 
     * @var string
     */
    public ?string $detail;

    /**
     * A URI pointing to the specific instance of the error.
     * 
     * @var string
     */
    public ?string $instance;

    /**
     * Used for correlation and troubleshooting in distributed systems. 
     * 
     * @var string
     */
    public ?string $traceId;

    /**
     * A grouped list of specific validation errors
     * 
     * @var array
     */
    public array $errors;

    /**
     * Initializes a new instance of the ProblemDetailsException class.
     *
     * @param string  $type      A URI reference identifying the error type
     * @param string  $title     A short, human-readable summary of the problem
     * @param int     $status    The HTTP status code for the error
     * @param ?string $detail    A detailed, human-readable explanation of the error.
     * @param ?string $instance  A URI pointing to the specific instance of the error.
     * @param ?string $traceId   Used for correlation and troubleshooting in distributed systems.
     * @param array   $errors    A grouped list of specific validation errors
     */
    public function __construct(
        string $type, 
        string $title, 
        int $status, 
        ?string $detail = null,
        ?string $instance = null,
        ?string $traceId = null,
        array $errors = [])
    {
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = $instance;
        $this->traceId = $traceId;
        $this->errors = $errors;

        parent::__construct($this->__toString());
    }

    /**
     * String representation of this exception.
     * 
     * @return string  The string representation of the exception.
     */
    public function __toString()
    {
        $details = [
            'Type: ' . $this->type,
            'Title: ' . $this->title,
            'Status: ' . $this->status,
            'Detail: ' . $this->detail,
            'Instance: ' . $this->instance,
            'TraceId: ' . $this->traceId,
        ];
        return implode(PHP_EOL, array_filter($details));
    }
}

