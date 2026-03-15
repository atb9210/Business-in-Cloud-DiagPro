<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use PDO;
use PDOException;

class RetryMySqlConnector extends MySqlConnector
{
    /**
     * Create a new PDO connection with retry logic.
     * Works around Docker Swarm IPVS load balancer corrupting
     * MySQL authentication handshake on overlay networks.
     */
    public function connect(array $config)
    {
        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return parent::connect($config);
            } catch (PDOException $e) {
                $lastException = $e;
                if ($attempt < $maxRetries && str_contains($e->getMessage(), 'Access denied')) {
                    usleep(50000); // 50ms wait before retry
                    continue;
                }
                throw $e;
            }
        }

        throw $lastException;
    }
}
