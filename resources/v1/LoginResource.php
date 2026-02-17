<?php

require_once '../config/database.php';
require_once '../models/ApiUser.php';
require_once '../models/ApiToken.php';

class LoginResource
{
    private $db;
    private $apiUser;
    private $apiToken;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->apiUser = new ApiUser($this->db);
        $this->apiToken = new ApiToken($this->db);
    }

    // POST /api/v1/login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Se requiere username y password"]);
            return;
        }

        $username = $data->username;
        $password = $data->password;

        if(!$this->apiUser->findByUsername($username)){
            http_response_code(401);
            echo json_encode(["message" => "Credenciales invalidas"]);
            return;
        }

        if($this->apiUser->status !== 'ACTIVE'){
            http_response_code(403);
            echo json_encode(["message" => "Usuario inactivo"]);
            return;
        }

        if (!password_verify($password, $this->apiUser->password_hash)) {
            http_response_code(401);
            echo json_encode(["message" => "Credenciales inválidas"]);
            return;
        }

        $tokenData = $this->apiToken->create($this->apiUser->id);

        if (!$tokenData) {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo generar el token"]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            "access_token" => $tokenData["token"],
            "expires_at"   => $tokenData["expires_at"]
        ]);
    }

}
?>