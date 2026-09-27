<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database {
    private PDO $pdo;
    public function __construct() {
        $dsn=Config::get('DATABASE_DSN','sqlite:'.dirname(__DIR__).'/data/api-hub.sqlite');
        $user=Config::get('DATABASE_USER'); $password=Config::get('DATABASE_PASSWORD');
        if (str_starts_with($dsn,'sqlite:')) { $file=substr($dsn,7); if ($file!==':memory:') @mkdir(dirname($file),0775,true); }
        $this->pdo=new PDO($dsn,$user?:null,$password?:null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite') $this->pdo->exec('PRAGMA foreign_keys=ON');
        $this->migrate();
    }
    public function one(string $sql,array $params=[]):?array {$s=$this->pdo->prepare($sql);$s->execute($params);$r=$s->fetch();return $r===false?null:$r;}
    public function all(string $sql,array $params=[]):array {$s=$this->pdo->prepare($sql);$s->execute($params);return $s->fetchAll();}
    public function run(string $sql,array $params=[]):int {$s=$this->pdo->prepare($sql);$s->execute($params);return $s->rowCount();}
    public function lastInsertId():int {return (int)$this->pdo->lastInsertId();}
    private function migrate():void {
        $driver=$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $cid=$driver==='pgsql'?'BIGSERIAL PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';
        $rid=$driver==='pgsql'?'BIGSERIAL PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS connectors (
          id {$cid}, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE, description TEXT NOT NULL DEFAULT '',
          provider VARCHAR(40) NOT NULL, model VARCHAR(180) NOT NULL, system_instructions TEXT NOT NULL,
          input_schema_json TEXT NOT NULL, output_schema_json TEXT NOT NULL, api_key_hash CHAR(64) NOT NULL,
          api_key_prefix VARCHAR(20) NOT NULL, is_active SMALLINT NOT NULL DEFAULT 1,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS api_requests (
          id {$rid}, connector_id BIGINT NOT NULL, request_id VARCHAR(80) NOT NULL UNIQUE, status VARCHAR(20) NOT NULL,
          response_time_ms INTEGER NOT NULL DEFAULT 0, input_tokens INTEGER NULL, output_tokens INTEGER NULL, total_tokens INTEGER NULL,
          estimated_cost DECIMAL(18,8) NULL, provider VARCHAR(40) NOT NULL, model VARCHAR(180) NOT NULL,
          error_type VARCHAR(80) NULL, error_message TEXT NULL, request_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY(connector_id) REFERENCES connectors(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_requests_connector_ts ON api_requests(connector_id,request_timestamp)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_requests_status ON api_requests(status)");
    }
}
