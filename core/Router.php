<?php
class Router
{
    private $routes = [];
    private $version;
    private $basePath;

    public function __construct($version = 'v1', $basePath = '')
    {
        $this->version = $version;
        $this->basePath = rtrim($basePath, '/');
    }

    public function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => "/api/{$this->version}" . $path,
            'handler' => $handler
        ];
    }

    private function getAuthorizationHeader()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        // Fallback por si Apache no lo expone directo
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) return trim($headers['Authorization']);
            if (isset($headers['authorization'])) return trim($headers['authorization']);
        }

        return null;
    }

    private function getBearerToken()
    {
        $auth = $this->getAuthorizationHeader();
        if (!$auth) return null;

        if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Asegurar que la URI comience con 
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                    $publicRoutes = [
                        "POST:/api/{$this->version}/login",
                        "POST:/api/{$this->version}/apiusers",
                    ];

                    $routeKey = $method . ":" . $route['path'];

                    //Si NO es pública, exigir token válido
                    if (!in_array($routeKey, $publicRoutes, true)) {

                        require_once __DIR__ . '/../config/database.php';
                        require_once __DIR__ . '/../models/ApiToken.php';

                        $database = new Database();
                        $db = $database->getConnection();
                        $apiToken = new ApiToken($db);

                        $token = $this->getBearerToken();

                        if (!$token) {
                            http_response_code(401);
                            echo json_encode(["message" => "Falta token. Usa Authorization: Bearer <token>"]);
                            return;
                        }

                        $userId = $apiToken->validate($token);

                        if (!$userId) {
                            http_response_code(401);
                            echo json_encode(["message" => "Token inválido o expirado"]);
                            return;
                        }

                        // (Opcional) Dejar disponible el usuario autenticado a los resources
                        $_SERVER['AUTH_USER_ID'] = $userId;
                    }

                    array_shift($matches);
                    return call_user_func_array($route['handler'], $matches);
            }
        }

        http_response_code(404);
        echo json_encode(['message' => 'Ruta no encontrada', 'uri' => $uri]);
    }
}
?>