<?php
declare(strict_types=1);

namespace App\Service;

use PDO;
use Psr\Log\LoggerInterface;

class MySQLDB
{
    public PDO $PDO_conn;

    /* Class constructor */
    public function __construct(
        private readonly LoggerInterface $logger)
    {
        // parse de credentials voor de database uit de environment variabele DATABASE_URL uit de .env of .env.local file
        $url_parts = parse_url($_ENV['DATABASE_URL']);
        $db_user = $url_parts['user'];
        $db_pass = $url_parts['pass'];
        $db_server = $url_parts['host'];
        $db_port = $url_parts['port'];
        $db_name = substr($url_parts['path'], 1);  // haal de '/' weg aan het begin

        $this->PDO_conn = new PDO("mysql:host=" . "$db_server" . ";port=" . $db_port . ";dbname=" . $db_name, $db_user, $db_pass);
    }

    /*
     * Shorthand function to create a MySQL prepared statement using PDO, execute it with named parameters and fetch all rows.
     * Parameter names cannot be reused in the $query, in that case use different names in the $query,
     * which are set to the same value in $params
     * Always returns an array, which will be empty after an error in executing the statement or if no results
     */
    public function PDO_query_all(string $query, array $params = null): false|array
    {
        $stmt = $this->PDO_conn->prepare($query);
        if (!$stmt->execute($params)) {
            $this->logger->warning("PDO_query_all failed, query: {query}\n, params: {params}\n", ['query' >= $query, 'params' => print_r($params, true)]);
        }
        return $stmt->fetchAll();
    }

    /*
     * Same as PDO_query_all, but fetches only first result row.
     */
    public function PDO_query_first(string $query, array $params = null): false|array
    {
        $stmt = $this->PDO_conn->prepare($query);
        if (!$stmt->execute($params)) {
            $this->logger->warning("PDO_query_all failed, query: {query}\n, params: {params}\n", ['query' >= $query, 'params' => print_r($params, true)]);
        }
        return $stmt->fetch();
    }

    /*
     * Similar to PDO_query_all, but only executes without return value. Use cases like DELETE WHERE
     */
    public function PDO_execute(string $query, array $params = null): void
    {
        $stmt = $this->PDO_conn->prepare($query);
        if (!$stmt->execute($params)) {
            $this->logger->warning("PDO_execute failed, query: {query}\n, params: {params}\n", ['query' >= $query, 'params' => print_r($params, true)]);
        }
    }
}

