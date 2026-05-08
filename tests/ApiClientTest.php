<?php
/**
 * Copyright (c) STÜBER SYSTEMS GmbH
 * Licensed under the MIT License, Version 2.0.
 */

namespace RestCaptcha;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RestCaptcha\ApiClient;
use RestCaptcha\ProblemDetailsException;
use RestCaptcha\VerifyStatus;

class ApiClientTest extends TestCase
{
    public function testVerifyResponseToString(): void
    {
        $response = new VerifyResponse(VerifyStatus::SUCCESS, 'www.mydomain.eu');

        $this->assertSame(
            "Status: success" . PHP_EOL . "HostName: www.mydomain.eu",
            (string)$response
        );
    }

    public function testSuccess(): void
    {
        $success = [
            'status' => 'success',
            'hostName' => 'www.mydomain.eu'
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($success)),
        ]);
        
        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);
        
        $response = $apiClient->verifySolution('token', 'solution', '127.0.0.1');

        $this->assertInstanceOf(VerifyResponse::class, $response);
        $this->assertSame(VerifyStatus::SUCCESS, $response->status);
        $this->assertSame('www.mydomain.eu', $response->hostName);
    }

    public function testUnexpectedStatus(): void
    {
        $unexpected = [
            'status' => 'unexpected-status-value'
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($unexpected)),
        ]);
        
        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);
        
        $response = $apiClient->verifySolution('token', 'solution', '127.0.0.1');

        $this->assertInstanceOf(VerifyResponse::class, $response);
        $this->assertSame(VerifyStatus::UNKNOWN, $response->status);
    }

    public function testProblemDetails(): void
    {
        $problem = [
            'type' => 'https://tools.ietf.org/html/rfc9110#section-15.5.1',
            'title' => 'Invalid token',
            'status' => 400
        ];

        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/problem+json'], json_encode($problem)),
        ]);
        
        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);

        $this->expectException(ProblemDetailsException::class);
        $apiClient->verifySolution('bad-token', 'solution', '127.0.0.1');
    }

    public function testNetworkError(): void
    {
        $mock = new MockHandler([
            new RequestException('Connection refused', new Request('POST', 'verify'))
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);

        $this->expectException(RequestException::class);
        $apiClient->verifySolution('token', 'solution', '127.0.0.1');     
    }

    public function testMalformedProblemDetailsFallsBackToRequestException(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/problem+json'], '{invalid-json')
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);

        $this->expectException(RequestException::class);
        $apiClient->verifySolution('token', 'solution', '127.0.0.1');
    }

    public function testIncompleteProblemDetailsFallsBackToRequestException(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/problem+json'], '{}')
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://localhost:44303/v1/']);
        $apiClient = new ApiClient('test-key', 'test-secret', 'en', ['client' => $httpClient]);

        $this->expectException(RequestException::class);
        $apiClient->verifySolution('token', 'solution', '127.0.0.1');
    }
}
?>
