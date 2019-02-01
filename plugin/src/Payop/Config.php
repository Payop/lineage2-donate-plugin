<?php

namespace Payop;

/**
 * Class Config
 * @package Payop
 */
class Config extends ParameterBag
{
    /**
     * @var string
     */
    private $file;

    /**
     * @return \Payop\Config
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->file = __DIR__.'/../../config.json';
        if (\file_exists($this->file)) {
            $params = \json_decode(\file_get_contents($this->file), true);
        }
        if (!\is_array($params) || !$params) {
            $params = [
                'publicKey'   => '',
                'secretKey'   => '',
                'failUrl'     => '',
                'resultUrl'   => '',
                'enableLogs'  => 0,
                'currency'    => 'RUB',
                'itemPrice'   => 50,
                'minItemsQty' => 20,
                'itemId'      => 0,
                'itemTable'   => 'items_delayed',
                'dbHost'      => 'localhost',
                'dbUser'      => 'l2',
                'dbPass'      => 'l2',
                'dbName'      => 'l2',
                'dbPort'      => 3306,
            ];
        }

        parent::__construct($params);
    }

    /**
     * @throws \RuntimeException
     */
    public function save(array $params)
    {
        $this->parameters = $params;
        
//        $data = '<?php return [';
//        foreach ($params as $key => $value) {
//            $data .= "\n\t'".$key."' => '".$value."',";
//        }
/*        $data .= "\n".']; ?>';*/

        if (!\file_put_contents($this->file, \json_encode($this->parameters))) {
            throw new \RuntimeException('Could not write to file config.json. Please check if this file writable.');
        }
    }
}