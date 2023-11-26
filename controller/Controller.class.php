<?php
class Controller
{
    private $domain;
    private $apiKey;

    public function __construct($domain, $apiKey)
    {
        $this->domain = $domain;
        $this->apiKey = $apiKey; // Fix the variable name here
        if ($this->domain == "") {
            echo json_encode(['success' => false, 'message' => '未设置api地址']);
            exit;
        }else if ($this->apiKey == "") {
            echo json_encode(['success' => false, 'message' => '未设置apiKey']);
            exit;
        }
    }
    public function generateSpeechFile($inputText, $outputFilePath,$timbre)
    {
        $url = $this->domain . '/v1/audio/speech';

        $data = array(
            "model" => "tts-1",
            "input" => $inputText,
            "voice" => $timbre
        );

        $headers = array(
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (not recommended in production)

        $response = curl_exec($ch);
        $response_json = json_decode($response, true);
        if (isset($response_json['error'])) {
            echo json_encode(['success' => false, 'message' => $response_json['error']['message']]);
            exit;
        } else {
            file_put_contents($outputFilePath, $response); // Save response as speech file
            // echo 'Speech file generated successfully!';
        }

        curl_close($ch);
    }

    public function transcribeAudio($audioFilePath)
    {

        $url = $this->domain . "/v1/audio/transcriptions";
        $headers = [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: multipart/form-data",
        ];

        $postFields = [
            'file' => new CURLFile($audioFilePath),
            'model' => 'whisper-1',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disable SSL verification (not recommended for production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);
        $response_json = json_decode($response, true);
        if (isset($response_json['error'])) {
            echo json_encode(['success' => false, 'message' => $response_json['error']['message']]);
            exit;
        } else {
            return $response_json['text'];
        }
    }

    public function callOpenAIChatAPI($postData)
    {
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_URL, $this->domain . '/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_json = json_decode($response, true);
        if (isset($response_json['error'])) {
            echo json_encode(['success' => false, 'message' => $response_json['error']['message']]);
            exit;
        } else {
            return $response_json['choices'][0]['message']['content'];
        }

    }
    public function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}
}


