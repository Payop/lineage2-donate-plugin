<?php

namespace Payop;

/**
 * Class Db
 * @package Payop
 */
class Db
{
    /**
     * @var \PDO
     */
    public $connection;

    /**
     * @param string $host
     * @param string $name
     * @param string $user
     * @param string $password
     * @param int $port
     * @param array $options
     *
     * @return void
     *
     * @throws \PDOException
     */
    public function __construct(
        string $host,
        string $name,
        string $user,
        string $password,
        int $port = 3306,
        array $options = []
    ) {
        $driverOptions = \array_merge([
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_PERSISTENT         => true,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ], $options);

        $dsn = "mysql:dbname={$name};host={$host}";
        if ($port) {
            $dsn .= ";port={$port}";
        }

        $this->connection = new \PDO($dsn, $user, $password, $driverOptions);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @return string
     */
    public function payopPaymentsTableQuery() : string
    {
        return "CREATE TABLE IF NOT EXISTS `payop_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `payopId` varchar(255) NOT NULL,
            `account` varchar(255) NOT NULL,
            `sum` float NOT NULL,
            `itemsCount` int(11) NOT NULL DEFAULT '1',
            `dateCreate` datetime NOT NULL,
            `dateComplete` datetime DEFAULT NULL,
            `status` tinyint(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8";

    }

    /**
     * @return int
     *
     * @throws \PDOException
     */
    public function createPayopPaymentsTable() : int
    {
        return $this->connection->exec($this->payopPaymentsTableQuery());
    }

    /**
     * @param string $tableName
     *
     * @return void
     *
     * @throws \PDOException
     */
    public function checkItemsTable(string $tableName) : void
    {
        $stmt = $this->connection->query("SELECT 1 FROM `{$tableName}`");
        $stmt->execute();
        $stmt->closeCursor();
    }


    /**
     * @return void
     *
     * @throws \PDOException
     */
    public function checkCharsTable() : void
    {
        $stmt = $this->connection->query("SELECT 1 FROM `characters`");
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param string $charName
     *
     * @return array
     *
     * @throws \PDOException
     */
    public function getCharacter(string $charName) : array
    {
        $stmt = $this->connection->prepare('SELECT * FROM `characters`  WHERE `char_name` = :char_name LIMIT 1');
        $stmt->execute(['char_name' => $charName]);

        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$item) {
            throw new \PDOException("Unable find character with name: {$charName}");
        }

        return $item;
    }

    /**
     * @param int $id
     *
     * @return array
     *
     * @throws \PDOException
     */
    public function getPayment(int $id) : array
    {
        $stmt = $this->connection->prepare('SELECT * FROM `payop_payments`  WHERE `id` = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$item) {
            throw new \PDOException("Unable find character payment with id: {$id}");
        }

        return $item;
    }

    /**
     * @param int $characterId
     * @param float $sum
     * @param int $itemsCount
     *
     * @return int id
     *
     * @throws \PDOException
     */
    public function createPayment(int $characterId, float $sum, int $itemsCount) : int
    {
        $query = "INSERT INTO `payop_payments`
                    (`payopId`, `account`, `sum`, `itemsCount`, `dateCreate`, `status`)
                  VALUES
                    ('', :account, :sum, :items, NOW(), 0);";
        $stmt = $this->connection->prepare($query);
        $stmt->execute([
            'account' => $characterId,
            'sum'     => $sum,
            'items'   => $itemsCount,
        ]);

        return (int)$this->connection->lastInsertId();
    }

    /**
     * @param int $id
     * @param int $payopId
     * @param string $itemId
     *
     * @return void
     *
     * @throws \PDOException
     * @throws \Throwable
     */
    public function executeSuccessPayment(int $id, int $payopId, string $itemId) : void
    {
        $payment = $this->getPayment($id);

        try {
            $this->connection->beginTransaction();

            $query = "UPDATE `payop_payments` SET payopId=:payopId, dateComplete=NOW(), status=1 WHERE id=:id;";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(['payopId' => $payopId, 'id' => $id]);

            $query = "INSERT INTO `items_delayed`
                        (`owner_id`, `item_id`, `count`, `payment_status`, `description`)
                      VALUES
                        (:owner_id, :item_id, :count, 0, 'PayOp');";
            $stmt = $this->connection->prepare($query);
            $stmt->execute([
                'owner_id' => $payment['account'],
                'item_id'  => $itemId,
                'count'    => $payment['itemsCount'],
            ]);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}