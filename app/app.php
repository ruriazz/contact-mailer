<?php

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "mailer.php";

class App
{
    private $mailer;

    function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
        $dotenv->load();

        $this->mailer = new Mailer();
        setup_cors();
    }

    public function success($content = null)
    {
        $response = array(
            "success" => true,
            "code" => 200
        );

        if (is_array($content) || is_object($content)) {
            $response["data"] = $content;
        } else if ($content && is_string($content)) {
            $response["message"] = $content;
        }

        $response["time"] = time();

        http_response_code(200);
        echo json_encode($response);
        die;
    }

    public function error(String $message, int $code = 400)
    {
        $response = array(
            "success" => false,
            "code" => $code
        );

        if ($message)
            $response["message"] = $message;

        $response["time"] = time();

        http_response_code($code);
        echo json_encode($response);
        die;
    }

    public function sendMessage(String $emailFrom, String $nameFrom, String $message): bool
    {
        $emailData = new EmailData(
            $emailFrom,
            $nameFrom,
            $message
        );

        return $this->mailer->send($emailData);
    }

    public function tokenValid(String $token) : bool
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        $post_data = http_build_query(
            array(
                'secret' => $_ENV['HCAPTCHA_SECRET'],
                'response' => $token
            )
        );

        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            )
        );
        $context  = stream_context_create($opts);
        $response = file_get_contents('https://hcaptcha.com/siteverify', false, $context);
        $result = json_decode($response);
        if (!$result->success)
            return false;

        return true;
    }

    public function post($keys, bool $isHTML = false)
    {
        if (is_array($keys)) {
            $results = new stdClass();
            foreach ($keys as $key) {
                $key = trim($key);
                $value = $this->_input($key);
                if ($value !== null && $value !== '') {
                    if (is_string($value)) {
                        $value = trim($value);
                        if (!$isHTML) {
                            $value = htmlspecialchars($value);
                        }
                    } else if (is_array($value)) {
                        if (!$isHTML) {
                            array_walk_recursive($value, array($this, '_filter_html_chars'));
                        }
                        $value = json_encode($value);
                        $value = json_decode($value);
                    }
                } else if ($value == '') {
                    $value = null;
                }

                $results->$key = $value;
            }

            return $results;
        } else if (is_string($keys)) {
            $keys = explode(',', $keys);

            return $this->post($keys, $isHTML);
        }

        throw new Exception("App::post() only allows parameters in strings or arrays");
    }

    private function _input(String $key)
    {
        $data = file_get_contents('php://input');

        if (!$data || !$this->isJson($data))
            return null;

        $data = json_decode($data, true);
        if (array_key_exists($key, $data))
            return $data["$key"];

        return null;
    }

    private function _filter_html_chars(&$value)
    {
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function isJson(String $string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

// helpers

if (!function_exists('http_response_code')) {
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if ($newcode !== NULL) {
            header('X-PHP-Response-Code: ' . $newcode, true, $newcode);
            if (!headers_sent())
                $code = $newcode;
        }
        return $code;
    }
}

if(!function_exists('setup_cors')) {
    function setup_cors() {
        header("Access-Control-Allow-Origin: https://devoff.cf");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }
    }
}