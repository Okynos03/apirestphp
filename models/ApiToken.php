<?php
class ApiToken
{
    private $conn;
    private $table_name = "api_tokens";

    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($user_id)
    {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, token=:token, expires_at=:expires_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires_at);

        if ($stmt->execute()) {
            return [
                'token' => $token,
                'expires_at' => $expires_at
            ];
        }
        return false;
    }

    public function validate($token)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " 
                  WHERE token = :token AND revoked = 0 AND expires_at > NOW() LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['user_id'];
        }

        return false; 
    }

    public function revoke($token)
    {
        $query = "UPDATE " . $this->table_name . " SET revoked = 1 WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);

        return $stmt->execute();
    }
}
?>