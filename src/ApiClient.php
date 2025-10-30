<?php
/**
 * Copyright (c) STÜBER SYSTEMS GmbH
 * Licensed under the MIT License, Version 2.0.
 */

namespace RestCaptcha;

use \Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * A client for interacting with the RESTCaptcha API.
 */
class ApiClient
{
    /**
     * The underlying Guzzle HTTP client used to send requests.
     *
     * @var Client
     */
    private Client $client;

    /**
     * The public site key used to identify the client with the RESTCaptcha service.
     *
     * @var string
     */
    private string $siteKey;

    /**
     * The private site secret used to authorize verification requests.
     *
     * @var string
     */
    private string $siteSecret;

    /**
     * The language code for message translation.
     *
     * @var ?string
     */
    private ?string $language;

    /**
     * Initializes a new instance of the ApiClient class.
     *
     * @param string $siteKey       The public site key.
     * @param string $siteSecret    The private site secret.
     * @param string $language      The language code for message translation.
     * @param array  $clientConfig  Optional configuration array:
     *                              - 'client'         => (Client) Custom Guzzle client instance.
     *                              - 'client_options' => (array) Guzzle client options if a client is not provided.
     */
    public function __construct(
        string $siteKey, 
        string $siteSecret, 
        string $language, 
        array $clientConfig = []
    ) {
        $this->siteKey = $siteKey;
        $this->siteSecret = $siteSecret;
        $this->language = $language;
        
        $configDefaults = [
            'client'         => null,
            'client_options' => [],
        ];

        $config = array_merge($configDefaults, $clientConfig);

        if ($config['client'] instanceof Client) {
            $this->client = $config['client'];
        } else {
            $this->client = new Client(is_array($config['client_options']) ? $config['client_options'] : []);
        }
    }
 
    /**
     * Verifies a RESTCaptcha solution with the server.
     * 
     * Submits the provided token and solution to the RESTCaptcha verification endpoint
     * via a POST request. The server responds with a JSON object containing a verification 
     * status string, which is mapped to a {@see \RestCaptcha\VerifyStatus} enum value.
     *
     * @param string  $token     The RESTCaptcha token received from the widget.
     * @param string  $solution  The user-submitted solution to the challenge.
     * @param ?string $callerIp  The IP address of the backend submitting the solution.
     * 
     * @return VerifyResponse    The response of the verification request
     * 
     * @throws ProblemDetailsException|RequestException|RuntimeException
     */
    public function verifySolution(string $token, string $solution, ?string $callerIp): VerifyResponse
    {
        try {
            $response = $this->client->post('verify', [
				'query' => [
					'siteKey' => $this->siteKey
				],
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Language' => $this->language ?? 'en'
                ],
				'json' => [
					'siteSecret' => $this->siteSecret,
					'token'      => $token,
					'solution'   => $solution,
					'callerIp'   => $callerIp
                ]
			]);

            $jsonBody = (string) $response->getBody();
            $jsonData = json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($jsonData['status']) || !is_string($jsonData['status'])) {
                throw new \RuntimeException('API response missing string property "status".');
            }

            return new VerifyResponse(VerifyStatus::tryfrom($jsonData['status']) ?? VerifyStatus::UNKNOWN, $jsonData['hostName'] ?? null);            

         } catch (RequestException $ex) {
            if ($ex->hasResponse()) {
                $this->handleProblemDetails($ex->getResponse()); 
            }
            throw $ex; 
        } catch (\JsonException $ex) {
            throw new \RuntimeException('Failed to parse API JSON response.', 0, $ex);
        }    
    }

    /**
     * Handles an error response by checking whether it is an RFC 9457 problem details object. 
     * If so, a ProblemDetailsException exception is thrown.
     *
     * @param ?ResponseInterface $response  The HTTP response object
     * 
     * @throws ProblemDetailsException
     */
    private function handleProblemDetails(?ResponseInterface $response): void
    {
        if (!is_null($response)) {
            $contentType = $response->getHeaderLine('Content-Type');
            
            // Try to decode structured RFC 9457 response
            if (strpos($contentType, 'application/problem+json') !== false) {
                
                $problemDetails = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

                throw new ProblemDetailsException(
                    $problemDetails['type'],
                    $problemDetails['title'],
                    (int)$problemDetails['status'],
                    $problemDetails['detail'] ?? null,
                    $problemDetails['instance'] ?? null,
                    $problemDetails['traceId'] ?? null,
                    array_diff_key($problemDetails['errors'] ?? [])
                );
            }
        }
    }
}
 