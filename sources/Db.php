<?php
namespace NineteenQ;

// FROM https://github.com/fulldecent/thin-pdo

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
