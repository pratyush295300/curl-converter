<?php
/**
 * cURL to PHP Converter API
 * Made with ❤ by Pratyush • 2025
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

class APIHandler {
    private $url;
    private $method;
    private $headers;
    private $formData;

    public function __construct($data) {
        $this->url = filter_var($data['url'] ?? '', FILTER_SANITIZE_URL);
        $this->method = filter_var($data['method'] ?? '', FILTER_SANITIZE_STRING);
        $this->headers = $data['headers'] ?? [];
        $this->formData = $data['formData'] ?? [];
    }

    public function validate() {
        if (empty($this->url) || !filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL provided');
        }

        if (!in_array($this->method, ['GET', 'POST'])) {
            throw new Exception('Invalid HTTP method');
        }
    }

    public function makeRequest() {
        try {
            $this->validate();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // Set headers
            if (!empty($this->headers)) {
                $headerArray = [];
                foreach ($this->headers as $key => $value) {
                    $headerArray[] = "$key: $value";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
            }

            // Handle POST request
            if ($this->method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($this->formData)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->formData));
                }
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL Error: $error");
            }

            return [
                'success' => true,
                'statusCode' => $httpCode,
                'response' => json_decode($response, true) ?? $response,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $handler = new APIHandler($input);
    echo json_encode($handler->makeRequest());
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests are allowed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
