<?php

namespace Payop;

/**
 * Class Handler
 * @package Payop
 */
class Handler
{
    /**
     * @var \Payop\Template
     */
    private $view;

    /**
     * @var \Payop\Logger
     */
    private $logger;

    /**
     * @var \Payop\Db
     */
    private $db;

    /**
     * @var \Payop\Config
     */
    private $config;

    /**
     * @param \Payop\Db $db
     * @param \Payop\Config $config
     * @param \Payop\Logger $logger
     */
    public function __construct(Db $db, Config $config, Logger $logger)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logger = $logger;
        $this->view = Template::create(__DIR__.'/../../templates/plugin');
    }

    /**
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function showForm(Request $request)
    {
        $content = $this->view->render('form', [
            'currency'    => $this->config->get('currency'),
            'minItemsQty' => $this->config->get('minItemsQty'),
            'itemPrice'   => $this->config->get('itemPrice'),
        ]);

        return Response::create($content);
    }

    /**
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function createPayment(Request $request)
    {
        $response = Response::create();
        $response->headers->set('Content-Type', 'application/json');

        if (!$request->isMethod(Request::METHOD_POST)) {
            $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => 'Invalid request. Allowed only POST method.'],
            ]));
        }

        $this->log('Create payment', ['request' => $request->request->all()]);

        $qty = $request->request->getInt('qty');
        if ($qty < $this->config->getInt('minItemsQty')) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => "Minimal items quantity {$this->config->getInt('minItemsQty')}."],
            ]));
        }

        $account = $request->request->get('account', '');
        try {
            $character = $this->db->getCharacter($account);
        } catch (\PDOException $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $e->getMessage()],
            ]));

            return $response;
        }

        // create order
        $sum = $qty * $this->config->get('itemPrice');
        try {
            $orderId = $this->db->createPayment($character['obj_Id'], $sum, $qty);
        } catch (\PDOException $e) {
            $response->setStatusCode(Response::HTTP_SERVER_ERROR);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => "Unable create payment. Error: {$e->getMessage()}"],
            ]));

            return $response;

        }

        // prepare request to payop
        $client = new PayopClient();
        $paymentData = [
            'order'     => [
                'id'          => $orderId,
                'amount'      => \number_format($sum, 4, '.', ''),
                'currency'    => $this->config->get('currency'),
                'description' => "Order #{$orderId}",
            ],
            'customer'     => [
                'email' => "",
                'phone' => "",
                'name'  => "",
            ],
            'publicKey' => $this->config->get('publicKey'),
            'resultUrl' => $this->config->get('resultUrl'),
            'failUrl'   => $this->config->get('failUrl'),
        ];
        $paymentData['signature'] = $client->generateSignature(
            $paymentData['order'],
            $this->config->get('secretKey')
        );

        try {
            $this->log('Send request to Payop', ['request' => $paymentData]);
            $payopResponse = $client->createPayment($paymentData);
            $this->log('Response from Payop', ['response' => $payopResponse]);
        } catch (ResponseErrorsException $e) {
            $msg = 'Payment validation errors';
            $this->log($msg, ['errors' => $e->getErrors()]);

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => isset($e->getErrors()[0]['message']) ? $e->getErrors()[0]['message'] : $msg],
            ]));

            return $response;
        } catch (ResponseException $e) {
            $this->log($e->getMessage(), [
                'request' => $paymentData,
                'error'   => $e->getMessage(),
            ]);

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $e->getMessage()],
            ]));

            return $response;
        } catch (\Requests_Exception $e) {
            $this->log($e->getMessage(), [
                'request' => $paymentData,
                'error'   => $e->getMessage(),
            ]);

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $e->getMessage()],
            ]));

            return $response;
        }

        $response->setContent(\json_encode([
            'data'  => $payopResponse['data'],
            'error' => [],
        ]));

        return $response;
    }

    /**
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function ipn(Request $request)
    {
        $response = Response::create();
        $response->headers->set('Content-Type', 'application/json');

        // this is for future, when the IPN will be sent via POST (now GET)
        $requestBag = $request->isMethod(Request::METHOD_POST)
            ? $request->request
            : $request->query;
        $this->log('IPN from Payop', ['request' => $requestBag->all()]);

        // check order id
        $orderId = $requestBag->getInt('orderId');
        $paymentStatus = $requestBag->getAlpha('status');
        try {
            $payment = $this->db->getPayment($orderId);
        } catch (\PDOException $e) {
            $this->log($e->getMessage());
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $e->getMessage()],
            ]));

            return $response;
        }

        if ((int)$payment['status'] > 0) {
            $response->setStatusCode(Response::HTTP_OK);
            $msg = "Order #{$payment['id']} already paid.";
            $this->log($msg);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $msg],
            ]));

            return $response;
        }

        //check signature
        $order = [
            'id'       => $payment['id'],
            'amount'   => \number_format($payment['sum'], 4, '.', ''),
            'currency' => $this->config->get('currency'),
        ];
        $signature = (new PayopClient())->generateSignature(
            $order,
            $this->config->get('secretKey'),
            $paymentStatus
        );
        if ($signature !== $requestBag->get('signature')) {
            $this->log('Invalid signature.', [
                'local_signature'   => $signature,
                'request_signature' => $requestBag->get('signature'),
            ]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => 'Invalid signature.'],
            ]));

            return $response;
        }
        try {
            if ($paymentStatus == "success") {
            $this->db->executeSuccessPayment(
                $payment['id'],
                $requestBag->getInt('PayOpId'),
                $this->config->get('itemId')
            );
            }
        } catch (\Throwable $e) {
            $this->log('Error to update payment and add items to character', [
                'error' => $e->getMessage(),
                'query_params' => [
                    'paymentId' => $payment['id'],
                    'payopId' => $requestBag->getInt('PayOpId'),
                    'itemId' => $this->config->get('itemId'),
                ]
            ]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(\json_encode([
                'data'  => [],
                'error' => ['message' => $e->getMessage()],
            ]));

            return $response;
        }

        $response->setContent(\json_encode([
            'data'  => ['message' => 'Payment successfully processed.'],
            'error' => [],
        ]));

        return $response;
    }

    /**
     * Render successful payment page
     *
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function successful(Request $request)
    {
        $content = $this->view->render('successful');

        return Response::create($content);
    }

    /**
     * Render failed payment page
     *
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function failed(Request $request)
    {
        $content = $this->view->render('failed');

        return Response::create($content);
    }

    /**
     * Render failed payment page
     *
     * @param \Payop\Request $request
     *
     * @return Response
     */
    public function pageNotFound(Request $request)
    {
        $content = $this->view->render('404');

        return Response::create($content, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param string $message
     * @param array $context
     */
    private function log($message, array $context = [])
    {
        if ($this->config->getInt('enableLogs')) {
            $this->logger->log($message, $context);
        }
    }
}
