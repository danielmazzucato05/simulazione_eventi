<?php
class Database {
    // You should replace these with your actual Supabase credentials
    // Note: For Railway deployment, we will use environment variables
    private $host = "aws-0-eu-central-1.pooler.supabase.com"; // Default for European Supabase, but use Env later
    private $port = "6543";
    private $db_name = "postgres";
    private $username = "postgres.xxxx";
    private $password = "yourpassword";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Try getting from Env vars first (for Railway)
        $db_url = getenv('DATABASE_URL');
        if($db_url) {
            // parse postgres://user:pass@host:port/dbname
            $parsed = parse_url($db_url);
            $this->host = $parsed['host'];
            $this->port = isset($parsed['port']) ? $parsed['port'] : "5432";
            $this->db_name = ltrim($parsed['path'], '/');
            $this->username = $parsed['user'];
            $this->password = $parsed['pass'];
        }

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set error mode
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
