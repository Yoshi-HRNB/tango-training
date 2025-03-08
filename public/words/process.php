<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . 'php-error.log');

require '/home/portfolio-t/www/tango_training/vendor/autoload.php';
require_once __DIR__ . '/../../src/LanguageCode.php';

use TangoTraining\LanguageCode;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageContext;

putenv('GOOGLE_APPLICATION_CREDENTIALS=/home/portfolio-t/www/tango_training/config/tango-training-bd31c1cc872c.json');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => '画像のアップロードに失敗しました。']);
    exit;
}

$targetLanguage = isset($_POST['language']) ? $_POST['language'] : LanguageCode::ENGLISH;
if (!in_array($targetLanguage, LanguageCode::getAllCodes())) {
    echo json_encode(['error' => '無効な言語が選択されました。']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['image']['type'], $allowedTypes)) {
    echo json_encode(['error' => '許可されていないファイルタイプです。']);
    exit;
}

$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($_FILES['image']['size'] > $maxFileSize) {
    echo json_encode(['error' => 'ファイルサイズが大きすぎます。']);
    exit;
}

$imagePath = $_FILES['image']['tmp_name'];

try {
    $imageAnnotator = new ImageAnnotatorClient();
    $imageData = file_get_contents($imagePath);
    $feature = new Feature();
    $feature->setType(Type::TEXT_DETECTION);

    $imageContextObj = new ImageContext();
    $imageContextObj->setLanguageHints([$targetLanguage]);

    $response = $imageAnnotator->annotateImage(
        $imageData,
        [$feature],
        ['imageContext' => $imageContextObj]
    );
    $annotations = $response->getTextAnnotations();
    $imageAnnotator->close();

    if (empty($annotations)) {
        echo json_encode(['error' => 'テキストが検出されませんでした。']);
        exit;
    }

    $extractedText = $annotations[0]->getDescription();
    echo json_encode(['extractedText' => $extractedText]);
} catch (Exception $e) {
    echo json_encode(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
    error_log('エラー詳細: ' . $e->getMessage());
}
?>
