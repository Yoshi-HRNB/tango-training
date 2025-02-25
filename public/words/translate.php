<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$text = isset($_POST['text']) ? $_POST['text'] : '';
$language = isset($_POST['sourceLanguage']) ? $_POST['sourceLanguage'] : '英語';
$level = isset($_POST['level']) ? (int)$_POST['level'] : 5;

if (!$text) {
    echo json_encode(['error' => '翻訳する文章が入力されていません。']);
    exit;
}

$levelDescription = [
    1 => "全ての単語を抽出（初学者）",
    2 => "簡単な単語を抽出(学習開始4週間)",
    3 => "学習3か月程度レベルの単語を抽出",
    4 => "基本会話レベル",
    5 => "日常会話ができるレベルの単語を抽出",
    6 => "旅行会話レベルの単語を抽出",
    7 => "少し複雑な文章レベルの単語を抽出",
    8 => "ビジネス会話レベルの単語を抽出",
    9 => "高レベルのレベルの単語を抽出",
    10 => "ネイティブでも難しいレベルの単語を抽出"
];

$apiKey = "AIzaSyDEOFn-7w_hlqJn8hQFe9oHfciqoIgeJI4";  // ※実際のキーに変更
$apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent?key={$apiKey}";

$prompt = "
以下の {$language} の文章を日本語に翻訳し、指定されたレベル(1〜10)に応じた単語を、熟語ごとに抽出してください。
アラビア数字の抽出は不要。抽出する単語の単位は熟語を優先してください。
複合語の場合は、意味を分けて表示してください。
低いレベルでは抽出する単語の量を多くし、高いレベルでは難易度の高い単語のみを抽出してください。
結果は必ず以下の JSON 形式のみで出力してください（meaningも必ず出力）。

余計な説明文やテキストは含めないでください。

### フォーマット例:
{
  \"translated_text\": \"（翻訳された文章）\",
  \"extracted_words\": [
    {
      \"word\": \"（単語）\",
      \"meaning\": \"（意味）\"
    },
    {
      \"word\": \"（単語）\",
      \"meaning\": \"（意味）\"
    }
  ]
}

### 翻訳元の文章:
{$text}

### 単語抽出レベル:
{$level} - {$levelDescription[$level]}
";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];
$jsonData = json_encode($data);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(["error" => "APIリクエストエラー: $error"]);
    exit;
}

$responseData = json_decode($response, true);

// JSONとしてパースできなかった場合はHTMLなどのエラーページが返っている可能性があります
if ($responseData === null) {
    echo json_encode(["error" => "APIからの応答が正しいJSON形式ではありません。", "raw" => $response]);
    exit;
}

$candidateText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// 応答がHTML形式の場合のチェック
if (strpos(trim($candidateText), '<') === 0) {
    echo json_encode(["error" => "Gemini APIの応答がHTML形式です。", "raw" => $candidateText]);
    exit;
}

if (!$candidateText) {
    echo json_encode(["error" => "Geminiからの有効な応答が得られませんでした。"]);
    exit;
}

$cleanText = preg_replace('/^```json\s*/', '', $candidateText);
$cleanText = preg_replace('/\s*```$/', '', $cleanText);

$jsonOutput = json_decode($cleanText, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "error" => "Gemini応答のJSONデコードに失敗: " . json_last_error_msg(),
        "raw" => $candidateText
    ]);
    exit;
}

echo json_encode([
    "translated_text" => $jsonOutput["translated_text"] ?? "",
    "extracted_words" => $jsonOutput["extracted_words"] ?? []
]);
?>
