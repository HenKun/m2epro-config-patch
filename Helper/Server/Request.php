<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Server;

class Request
{
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $helperServerMaintenance;
    /** @var \Ess\M2ePro\Helper\Server */
    private $helperServer;
    /** @var \Ess\M2ePro\Helper\Module\Logger */
    private $helperModuleLogger;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $helperModuleTranslation;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $helperModuleSupport;

    /**
     * @param \Ess\M2ePro\Helper\Server\Maintenance $helperServerMaintenance
     * @param \Ess\M2ePro\Helper\Server $helperServer
     * @param \Ess\M2ePro\Helper\Module\Logger $helperModuleLogger
     * @param \Ess\M2ePro\Helper\Module\Translation $helperModuleTranslation
     * @param \Ess\M2ePro\Helper\Module\Support $helperModuleSupport
     */
    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $helperServerMaintenance,
        \Ess\M2ePro\Helper\Server $helperServer,
        \Ess\M2ePro\Helper\Module\Logger $helperModuleLogger,
        \Ess\M2ePro\Helper\Module\Translation $helperModuleTranslation,
        \Ess\M2ePro\Helper\Module\Support $helperModuleSupport
    ) {
        $this->helperServerMaintenance = $helperServerMaintenance;
        $this->helperServer = $helperServer;
        $this->helperModuleLogger = $helperModuleLogger;
        $this->helperModuleTranslation = $helperModuleTranslation;
        $this->helperModuleSupport = $helperModuleSupport;
    }

    public function single(
        array $package,
        $serverBaseUrl = null,
        $serverHostName = null,
        $tryToResendOnError = true,
        $tryToSwitchEndpointOnError = true,
        $canIgnoreMaintenance = false
    ): array {
        if (!$canIgnoreMaintenance && $this->helperServerMaintenance->isNow()) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The action is temporarily unavailable. M2E Pro Server is under maintenance. Please try again later.'
            );
        }

        !$serverBaseUrl && $serverBaseUrl = $this->helperServer->getEndpoint();
        !$serverHostName && $serverHostName = $this->helperServer->getCurrentHostName();

        $curlObject = $this->buildCurlObject($package, $serverBaseUrl, $serverHostName);
        // @codingStandardsIgnoreLine
        $responseBody = curl_exec($curlObject);

        // @codingStandardsIgnoreStart
        $response = [
            'body'               => $responseBody,
            'curl_error_number'  => curl_errno($curlObject),
            'curl_error_message' => curl_error($curlObject),
            'curl_info'          => curl_getinfo($curlObject),
        ];
        // @codingStandardsIgnoreEnd

        // @codingStandardsIgnoreLine
        curl_close($curlObject);

        if ($response['body'] === false) {
            $switchingResult = false;
            $tryToSwitchEndpointOnError && $switchingResult = $this->helperServer->switchEndpoint();

            $this->helperModuleLogger->process(
                [
                    'curl_error_number'  => $response['curl_error_number'],
                    'curl_error_message' => $response['curl_error_message'],
                    'curl_info'          => $response['curl_info'],
                ],
                'Curl Empty Response'
            );

            if (
                $this->canRepeatRequest(
                    $response['curl_error_number'],
                    $tryToResendOnError,
                    $tryToSwitchEndpointOnError,
                    $switchingResult
                )
            ) {
                return $this->single(
                    $package,
                    $tryToSwitchEndpointOnError ? $this->helperServer->getEndpoint() : $serverBaseUrl,
                    $tryToSwitchEndpointOnError ? $this->helperServer->getCurrentHostName() : $serverHostName,
                    false,
                    $tryToSwitchEndpointOnError,
                    $canIgnoreMaintenance
                );
            }

            throw new \Ess\M2ePro\Model\Exception\Connection(
                $this->helperModuleTranslation->__(
                    'M2E Pro Server connection failed. Find the solution <a target="_blank" href="%url%">here</a>',
                    $this->helperModuleSupport->getSupportUrl('/support/solutions/articles/9000200887')
                ),
                [
                    'curl_error_number'  => $response['curl_error_number'],
                    'curl_error_message' => $response['curl_error_message'],
                    'curl_info'          => $response['curl_info'],
                ]
            );
        }

        return $response;
    }

    public function multiple(
        array $packages,
        $serverBaseUrl = null,
        $serverHostName = null,
        $tryToResendOnError = true,
        $tryToSwitchEndpointOnError = true,
        $asynchronous = false,
        $canIgnoreMaintenance = false
    ): array {
        if (!$canIgnoreMaintenance && $this->helperServerMaintenance->isNow()) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The action is temporarily unavailable. M2E Pro Server is under maintenance. Please try again later.'
            );
        }

        if (empty($packages)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Packages is empty.');
        }

        !$serverBaseUrl && $serverBaseUrl = $this->helperServer->getEndpoint();
        !$serverHostName && $serverHostName = $this->helperServer->getCurrentHostName();

        $responses = [];

        if (count($packages) === 1 || !$asynchronous) {
            foreach ($packages as $key => $package) {
                $curlObject = $this->buildCurlObject($package, $serverBaseUrl, $serverHostName);
                // @codingStandardsIgnoreLine
                $responseBody = curl_exec($curlObject);

                // @codingStandardsIgnoreStart
                $responses[$key] = [
                    'body'               => $responseBody,
                    'curl_error_number'  => curl_errno($curlObject),
                    'curl_error_message' => curl_error($curlObject),
                    'curl_info'          => curl_getinfo($curlObject),
                ];
                // @codingStandardsIgnoreEnd

                // @codingStandardsIgnoreLine
                curl_close($curlObject);
            }
        } else {
            $curlObjectsPool = [];
            // @codingStandardsIgnoreLine
            $multiCurlObject = curl_multi_init();

            foreach ($packages as $key => $package) {
                $curlObjectsPool[$key] = $this->buildCurlObject($package, $serverBaseUrl, $serverHostName);
                // @codingStandardsIgnoreLine
                curl_multi_add_handle($multiCurlObject, $curlObjectsPool[$key]);
            }

            do {
                // @codingStandardsIgnoreLine
                curl_multi_exec($multiCurlObject, $stillRunning);

                if ($stillRunning) {
                    // @codingStandardsIgnoreLine
                    curl_multi_select($multiCurlObject, 1); //sleep in sec.
                }
            } while ($stillRunning > 0);

            foreach ($curlObjectsPool as $key => $curlObject) {
                // @codingStandardsIgnoreStart
                $responses[$key] = [
                    'body'               => curl_multi_getcontent($curlObject),
                    'curl_error_number'  => curl_errno($curlObject),
                    'curl_error_message' => curl_error($curlObject),
                    'curl_info'          => curl_getinfo($curlObject),
                ];

                curl_multi_remove_handle($multiCurlObject, $curlObject);
                curl_close($curlObject);
            }

            curl_multi_close($multiCurlObject);
        }
        // @codingStandardsIgnoreEnd

        $isResponseFailed = false;
        $switchingResult = false;

        foreach ($responses as $key => $response) {
            if ($response['body'] === false) {
                $isResponseFailed = true;
                $tryToSwitchEndpointOnError && $switchingResult = $this->helperServer->switchEndpoint();

                $this->helperModuleLogger->process(
                    [
                        'curl_error_number'  => $response['curl_error_number'],
                        'curl_error_message' => $response['curl_error_message'],
                        'curl_info'          => $response['curl_info'],
                    ],
                    'Curl Empty Response'
                );
                break;
            }
        }

        if ($tryToResendOnError && $isResponseFailed) {
            $failedRequests = [];

            foreach ($responses as $key => $response) {
                if ($response['body'] === false) {
                    if (
                        $this->canRepeatRequest(
                            $response['curl_error_number'],
                            $tryToResendOnError,
                            $tryToSwitchEndpointOnError,
                            $switchingResult
                        )
                    ) {
                        $failedRequests[$key] = $packages[$key];
                    }
                }
            }

            if (!empty($failedRequests)) {
                $secondAttemptResponses = $this->multiple(
                    $failedRequests,
                    $tryToSwitchEndpointOnError ? $this->helperServer->getEndpoint() : $serverBaseUrl,
                    $tryToSwitchEndpointOnError ? $this->helperServer->getCurrentHostName() : $serverHostName,
                    false,
                    $tryToSwitchEndpointOnError,
                    $asynchronous,
                    $canIgnoreMaintenance
                );

                $responses = array_merge($responses, $secondAttemptResponses);
            }
        }

        return $responses;
    }

    private function buildCurlObject(
        $package,
        $serverBaseUrl,
        $serverHostName
    ) {
        // @codingStandardsIgnoreLine
        $curlObject = curl_init();

        $preparedHeaders = [];
        $serverHostName && $preparedHeaders[] = 'Host:' . $serverHostName;

        if (!empty($package['headers'])) {
            foreach ($package['headers'] as $headerName => $headerValue) {
                $preparedHeaders[] = $headerName . ':' . $headerValue;
            }
        }

        $postData = [];
        if (!empty($package['data'])) {
            $postData = $package['data'];
        }

        $timeout = 300;
        if (isset($package['timeout'])) {
            $timeout = (int)$package['timeout'];
        }

        $sslVerifyPeer = true;
        $sslVerifyHost = 2;

        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $serverBaseUrl)) {
            $sslVerifyPeer = false;
            $sslVerifyHost = false;
        }

        // @codingStandardsIgnoreLine
        curl_setopt_array(
            $curlObject,
            [
                // set the server we are using
                CURLOPT_URL            => $serverBaseUrl,

                // stop CURL from verifying the peer's certificate
                CURLOPT_SSL_VERIFYPEER => $sslVerifyPeer,
                CURLOPT_SSL_VERIFYHOST => $sslVerifyHost,

                // disable http headers
                CURLOPT_HEADER         => false,

                // set the headers using the array of headers
                CURLOPT_HTTPHEADER     => $preparedHeaders,

                // set the data body of the request
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($postData, '', '&'),

                // set it to return the transfer as a string from curl_exec
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => $timeout,
            ]
        );

        return $curlObject;
    }

    private function canRepeatRequest(
        $curlErrorNumber,
        $tryToResendOnError,
        $tryToSwitchEndpointOnError,
        $switchingResult
    ): bool {
        return $curlErrorNumber !== CURLE_OPERATION_TIMEOUTED && $tryToResendOnError &&
            (!$tryToSwitchEndpointOnError || ($tryToSwitchEndpointOnError && $switchingResult));
    }
}
