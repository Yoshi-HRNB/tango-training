<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../src/LanguageCode.php';
use TangoTraining\LanguageCode;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$text = isset($_POST['text']) ? $_POST['text'] : '';
$language = isset($_POST['sourceLanguage']) ? $_POST['sourceLanguage'] : '英語';
$targetLanguage = isset($_POST['targetLanguage']) ? $_POST['targetLanguage'] : '日本語';
$level = isset($_POST['level']) ? (int)$_POST['level'] : 1;

if (!$text) {
    echo json_encode(['error' => '翻訳する文章が入力されていません。']);
    exit;
}

$levelDescription = [
    1 => "抽出レベル（初級：基本的な単語を含め全単語抽出）",
    2 => "抽出レベル（初中級：頻出単語から専門的単語まで全て抽出）",
    3 => "抽出レベル（中級：やや高度な単語から全て抽出）",
    4 => "抽出レベル（中上級：難易度高めの単語から全て抽出）",
    5 => "抽出レベル（上級：専門的または非常に高度な単語のみ抽出）"
];

$apiKey = "AIzaSyDEOFn-7w_hlqJn8hQFe9oHfciqoIgeJI4";  
$apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}";


$prompt = "
以下の {$language} の文章を {$targetLanguage} に翻訳し、指定されたレベル(1〜5)に応じた重要単語や熟語を抽出してください。
数値（アラビア数字）は抽出対象外とし、熟語や複数の単語で構成される表現は優先的に一つのまとまりとして抽出してください。
複数単語表現（例: 'in order to', 'make sense' など）は、1つの熟語（またはコロケーション等）として扱ってください。
複合語は意味ごとに分解し、noteに分けて記載してください。
@やダブルクォーテーションのような、単語に直接関係のない記号は、抽出対象外としてください。
日本語以外の言語でフリガナは不要です。

【抽出レベルの方針】
- レベル1：レベル1〜5（すべての未知単語を抽出）
- レベル2：レベル2〜5の単語を抽出
- レベル3：レベル3〜5の単語を抽出
- レベル4：レベル4〜5の難易度が高めの単語を抽出
- レベル5：専門的・非常に高度な単語のみ抽出

【必須の単語情報】
1. word: 必ず {$language} で表記
2. meaning: {$targetLanguage} での意味
3. part_of_speech: {$targetLanguage} 表記（名詞・動詞・形容詞・副詞・熟語・句動詞・コロケーション等）
4. note:
   - 複合語の分解や文法的注意点
   - 不規則動詞の場合は原形・過去形・過去分詞
   - 派生形（名詞・形容詞・副詞など）
   - 類義語や反意語、重要な前置詞の用法など
   - 複数形は原型を note に明記

【言語別の制約条件】

■ 日本語の場合
- フリガナ（reading）をカタカナで必ず追加

■ 英語の場合（{$language} が英語の場合のみ適用）
1. 冠詞(a, an, the) は抽出不要
2. 複数形は原型を note に明記
3. to不定詞
   - 動詞の原形として抽出（例: 'to access' → 'access'）
   - ただし、「in order to」などの定型表現は idiom または collocation としてひとまとまりで抽出
4. 熟語（idiom）、句動詞（phrasal verb）、コロケーション（collocation）は必ず抽出
5. 前置詞が重要な場合、note に用法を記載
6. 不規則動詞は note に原形・過去形・過去分詞を明記
7. 派生語がある場合、note に派生形を記載
8. 類義語や反意語があれば note に補足

ベトナム語の場合:
- 類別詞（分類語）は必ずnoteに明記
- 声調記号を含め正確に表記
- 熟語や慣用句（thành ngữ）は「idiom」としてpart_of_speechに明記
- 類義語・反意語をnoteに記載

以下のJSONフォーマットを厳守し、それ以外の説明文を追加しないでください。

### JSONフォーマット:
{
  \"translated_text\": \"（翻訳された文章）\",
  \"extracted_words\": [
    {
      \"word\": \"（{$language}の単語）\",
";

if ($language === '日本語') {
    $prompt .= "      \"reading\": \"（フリガナ）\",";
}

$prompt .= "
      \"part_of_speech\": \"（品詞）\",
      \"meaning\": \"（{$targetLanguage}での意味）\",
      \"note\": \"（補足情報）\"
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode([
        "error" => "APIリクエストエラー: $error", 
        "details" => "cURLエラーが発生しました。ネットワーク接続を確認してください。"
    ]);
    exit;
}

// HTTPステータスコードをチェック
if ($httpCode != 200) {
    echo json_encode([
        "error" => "APIからエラーレスポンスを受信しました。HTTPステータスコード: $httpCode", 
        "raw_response" => $response,
        "details" => "APIサーバーからのレスポンスコードが正常ではありません。"
    ]);
    exit;
}

$responseData = json_decode($response, true);

// JSONとしてパースできなかった場合はHTMLなどのエラーページが返っている可能性があります
if ($responseData === null) {
    echo json_encode([
        "error" => "APIからの応答が正しいJSON形式ではありません。", 
        "raw_response" => $response,
        "details" => "応答の形式が期待されたJSONではなく、APIの仕様変更やエラーの可能性があります。"
    ]);
    exit;
}

$candidateText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// 応答がHTML形式の場合のチェック
if (strpos(trim($candidateText), '<') === 0) {
    echo json_encode([
        "error" => "Gemini APIの応答がHTML形式です。", 
        "raw_response" => $candidateText,
        "details" => "APIがHTMLエラーページを返しています。APIキーの有効期限や利用制限を確認してください。"
    ]);
    exit;
}

if (!$candidateText) {
    echo json_encode([
        "error" => "Geminiからの有効な応答が得られませんでした。",
        "raw_response" => $response,
        "details" => "APIレスポンスに期待された内容が含まれていません。"
    ]);
    exit;
}

$cleanText = preg_replace('/^```json\s*/', '', $candidateText);
$cleanText = preg_replace('/\s*```$/', '', $cleanText);

$jsonOutput = json_decode($cleanText, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "error" => "Gemini応答のJSONデコードに失敗: " . json_last_error_msg(),
        "raw_response" => $candidateText,
        "details" => "APIからの応答はJSONのような形式ですが、正しくパースできません。"
    ]);
    exit;
}

// 抽出された単語に必要なフィールドが含まれていることを確認
$extracted_words = [];
foreach ($jsonOutput["extracted_words"] ?? [] as $word) {
    $wordData = [
        "word" => $word["word"] ?? "",
        "part_of_speech" => $word["part_of_speech"] ?? "",
        "meaning" => $word["meaning"] ?? "",
        "note" => $word["note"] ?? ""
    ];
    
    // 日本語の場合はreadingを追加
    if ($language === '日本語' && isset($word["reading"])) {
        $wordData["reading"] = $word["reading"];
    }
    
    $extracted_words[] = $wordData;
}

echo json_encode([
    "translated_text" => $jsonOutput["translated_text"] ?? "",
    "extracted_words" => $extracted_words
]);
?>
