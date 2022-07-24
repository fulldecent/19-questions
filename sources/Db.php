<?php
namespace NineteenQ;

/**
 * Minimal wrapper as per https://phpdelusions.net/pdo/
 * 
 * Loads from constant DB_DSN
 */
class Db extends \PDO {
    public function __construct() {
        $options = [
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        try {
            parent::__construct(constant('DB_DSN'), '', '', $options);
        } catch (\PDOException $e) {
            trigger_error($e->getMessage());
            return false;
        }
    }
}
