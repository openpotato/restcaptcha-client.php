<?php
/**
 * Copyright (c) STÜBER SYSTEMS GmbH
 * Licensed under the MIT License, Version 2.0.
 */

namespace RestCaptcha;

/**
 * Verification status returned by the RESTCaptcha API.
 */
enum VerifyStatus: string
{
    /** The verification was successful */
    case SUCCESS = 'success';

    /** The provided token is invalid or expired */
    case INVALID_TOKEN = 'invalid-token';
    
    /** The provided solution is incorrect */
    case INVALID_SOLUTION = 'invalid-solution';

    /** Unknown status */
    case UNKNOWN = 'unknown';
}
