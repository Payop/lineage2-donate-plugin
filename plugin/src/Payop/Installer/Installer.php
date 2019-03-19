<?php

namespace Payop\Installer;

use Payop\Config;
use Payop\Db;
use Payop\Request;
use Payop\Template;
use PDO;
date_default_timezone_set('UTC');
/**
 * Class Installer
 * @package Payop\Installer
 */
class Installer
{
    /**
     * @var \Payop\Template
     */
    private $view;

    /**
     * @return \Payop\Installer\Installer
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param \Payop\Request $request
     *
     * @return string
     */
    public function run(Request $request)
    {
        return $this->runStep('welcome', $request);
    }

    /**
     * @param string $step
     *
     * @return bool
     */
    public function hasStep($step)
    {
        return \method_exists($this, $this->formatStepHandler($step));
    }

    /**
     * @param string $step
     * @param Request $request
     *
     * @return string
     */
    public function runStep($step, Request $request)
    {
        if (!$this->hasStep($step)) {
            throw new \InvalidArgumentException('Invalid step definition.');
        }

        return $this->{$this->formatStepHandler($step)}($request);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function welcomeHandler(Request $request)
    {
        return $this->view->render('welcome');
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function checkEnvironmentHandler(Request $request)
    {
        $php = [
            'version'    => \substr(PHP_VERSION, 0, 3),
            'testPassed' => \version_compare(PHP_VERSION, '5.4') !== -1,
        ];
        $mysql = [
            'drivers'    => PDO::getAvailableDrivers(),
            'testPassed' => \in_array('mysql', PDO::getAvailableDrivers(), true)
                && \extension_loaded('pdo_mysql'),
        ];

        $configFile = __DIR__.'/../../../config.json';
        @chmod($configFile, 0666);
        $config = [
            'testPassed' => \file_exists($configFile) && \is_writable($configFile),
        ];

        return $this->view->render('check-environment', [
            'php'    => $php,
            'mysql'  => $mysql,
            'config' => $config,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function configurationHandler(Request $request)
    {
        $config = Config::create();
        $saved = false;
        if ($request->isMethod(Request::METHOD_POST)) {
            $dbPort = $request->request->getInt('dbPort');
            $params = [
                'enableLogs'  => (int)$request->request->has('enableLogs'),
                'publicKey'   => $request->request->get('publicKey'),
                'secretKey'   => $request->request->getAlnum('secretKey'),
                'failUrl'     => $request->request->get('failUrl'),
                'resultUrl'   => $request->request->get('resultUrl'),
                'currency'    => $request->request->getAlpha('currency'),
                'itemPrice'   => (float)$request->request->get('itemPrice'),
                'minItemsQty' => $request->request->getInt('minItemsQty'),
                'itemId'      => $request->request->getInt('itemId'),
                'itemTable'   => $request->request->get('itemTable'),
                'dbHost'      => $request->request->get('dbHost'),
                'dbName'      => $request->request->get('dbName'),
                'dbUser'      => $request->request->get('dbUser'),
                'dbPass'      => $request->request->get('dbPass'),
                'dbPort'      => isset($dbPort) ? $dbPort : '',
            ];

            $config->save($params);
            $saved = true;
        }

        return $this->view->render('configuration', [
            'parameters' => $config->all(),
            'saved'      => $saved,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function mysqlConnectionHandler(Request $request)
    {
        // check connection to database
        $errors = [];
        $config = Config::create();
        try {
            $db = new Db(
                $config->get('dbHost'),
                $config->get('dbName'),
                $config->get('dbUser'),
                $config->get('dbPass'),
                $config->get('dbPort')
            );
            try {
                $db->createPayopPaymentsTable();
            } catch (\PDOException $e) {
                $errors[] = <<<EOT
                    Ошибка создания таблицы платежей payop_payments.
                    <br> Error: {$e->getMessage()}
                    <br> Проверьте права пользователя на создание таблиц, либо выполните запрос в БД вручную:
                    <br><pre>{$db->payopPaymentsTableQuery()}</pre>
EOT;
            }

            try {
                $itemsTable = $config->get('itemTable');
                $db->checkItemsTable($itemsTable);
            } catch (\PDOException $e) {
                $errors[] = "Не удалось найти таблицу вещей с именем: {$itemsTable}";
            }

            try {
                $config->get('itemTable');
                $db->checkCharsTable();
            } catch (\PDOException $e) {
                $errors[] = 'Не удалось найти таблицу персонажей с именем: characters.' . $e->getMessage();
            }
        } catch (\PDOException $e) {
            $errors[] = 'Не удалось подключиться к базе данных. Проверьте настройки.';
        }

        return $this->view->render('mysql-connection', [
            'errors' => $errors
        ]);
    }


    /**
     * @param Request $request
     *
     * @return string
     */
    private function finishHandler(Request $request)
    {
        return $this->view->render('finish', [
            'ipnUrl' => "{$request->getSchemeAndHttpHost()}/index.php?action=ipn"
        ]);
    }

    /**
     * @param string $step
     *
     * @return string
     */
    private function formatStepHandler($step)
    {
        $step = (string)$step;

        return "${step}Handler";
    }

    private function __construct()
    {
        $this->view = Template::create(__DIR__.'/../../../templates/installer');
    }
}