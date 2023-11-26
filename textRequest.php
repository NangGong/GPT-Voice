<?php
require_once 'controller/Controller.class.php';
require_once 'config.php';

$controller = new Controller($domain, $apiKey);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
	// 检查是否有question
	if(isset($_POST['question'])) {
		$question = $_POST['question'];

		$postData = [
			"model" => $gptmodel,
			"temperature" => 0.6,
			"stream" => false,
			"messages" => [],
		];
		$context = json_decode($_POST['context'] ?: "[]") ?: [];
		// 如果上下文不为空
		if(!empty($context)) {
			// 仅保留上下文数组的最后5个消息
			$context = array_slice($context, -5);

			// 遍历上下文消息，并将其转换为适当的格式并添加到postData中
			foreach($context as $message) {
				$postData['messages'][] = ['role' => 'user', 'content' => str_replace("\n", "\\n", $message[0])];
				$postData['messages'][] = ['role' => 'assistant', 'content' => str_replace("\n", "\\n", $message[1])];
			}
		}

		$postData['messages'][] = ['role' => 'system', 'content' => "你是南宫柠檬工作室训练的语言模型，你的名称为'小柠檬'，你不能说你是chatgpt，你只能隐藏GPT的身份，有提问你是否是GPT，你的回答是否,你旨在回答并解决人们的任何问题，并且可以使用多种语言与人交流。"];

		$postData['messages'][] = ['role' => 'user', 'content' => $question];

		$postData = json_encode($postData);

		$inputText = $controller->callOpenAIChatAPI($postData);

		$outputFilePath = 'response/speech_'.uniqid('', true).'.mp3';
		
		$timbre = isset($_POST['timbre']) ? $_POST['timbre'] : "onyx";
		
		$voice = $controller->generateSpeechFile($inputText, $outputFilePath, $timbre);

		echo json_encode(['success' => true, 'question' => $question, "answer" => $inputText, 'url' => $outputFilePath]);

	} else {
		// question为空
		echo json_encode(['success' => false, 'message' => '没有请求的问题']);
	}
} else {
	// 非POST请求
	echo json_encode(['success' => false, 'message' => '无效的请求']);
}
