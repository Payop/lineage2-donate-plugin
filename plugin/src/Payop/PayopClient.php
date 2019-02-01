<?php

namespace Payop;

/**
 * Class PayopClient
 * @package Payop
 */
class PayopClient
{
    /**
     * @var string
     */
    private $apiUri = 'https://payop.com/api/v1.1';

    /**
     * @param array $paymentData
     *
     * @return array
     *
     * @throws ResponseException
     * @throws ResponseErrorsException
     */
    public function createPayment(array $paymentData) : array
    {
        $response = \Requests::post("{$this->apiUri}/payments/payment", [], $paymentData);

        $result = \json_decode($response->body, true);
        if (!$result) {
            throw new ResponseException("Invalid response from Payop: {$response->body}");
        }
        if ($response->status_code >= 400) {
            throw new ResponseErrorsException($result['errors']);
        }

        return $result;
    }

    /**
     * @param array $order
     * @param string $secretKey
     * @param string|null $status
     *
     * @return string
     */
    public function generateSignature(
        array $order,
        string $secretKey,
        ?string $status = null
    ) : string {
        unset($order['items'], $order['description']);
        \ksort($order, SORT_STRING);
        $dataSet = \array_values($order);
        if ($status) {
            $dataSet[] = $status;
        }
        $dataSet[] = $secretKey;
        Logger::create()->log('Signature', $dataSet);

        return \hash('sha256', \implode(':', $dataSet));
    }

    /**
     * @param string $url
     * @param array $params
     *
     * @return array
     *
     * @throws RequestException
     */
    private function post(string $url, array $params)
    {
        $streamContext = \stream_context_create([
            'http' => [
                'header'  => [
                    'Content-type: application/json',
                ],
                'method'  => 'POST',
                'content' => \http_build_query($params),
            ],
        ]);
        $fp = \fopen($url, 'r', false, $streamContext);

        Logger::create()->log('Response TEST:', [
            'content' => \stream_get_contents($fp),
        ]);

        if ($fp === false) {
            throw new RequestException("Request to {$url} failed");
        }

        $result = \stream_get_contents($fp);
        \fclose($fp);
        $response = \json_decode($result, true);
        if (!$response) {
            throw new RequestException("Invalid response from {$url}");
        }

        return $response;
    }
}

\Requests::register_autoloader();