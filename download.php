<?php
require_once "config.php";
require_once "common.php";

// ダウンロード用ベースディレクトリ
$baseDir = UPLOAD_DIR_PUBLIC;

// URLパラメータからUUIDv4を取得
$uuid = $_GET['file'] ?? '';

// UUIDv4形式チェック（拡張子なし）
if (!preg_match(
    '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
    $uuid
)) {
    die("Invalid ffindPath.");
}

// 該当する全ファイルを取得
$findPath = $baseDir . '/' . $uuid . '.*';
$files = glob($findPath);

if (empty($files)) {
    die("ファイルが見つかりません。ファイル識別子を確認してください。");
}

// ========== ダウンロード実行モード ==========
if (isset($_GET['ext'])) {
    // kintoneから該当レコード情報取得
    $record = getKintoneRecord(KINTONE_API_TOKEN, KINTONE_APP_ID, FILE_UUID_FIELD_CODE, $uuid);
    $record = $record['records'][0] ?? null;
    $fileTitle = $record[FILE_TITLE]['value'] ?? 'unknown';
    
    $ext = $_GET['ext'];
    $targetFile = $baseDir . '/' . $uuid . '.' . $ext;

    if (!file_exists($targetFile)) {
        die("ファイルが見つかりません。ファイル識別子、拡張子を確認してください。");
    }

    // MIMEタイプ判定
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $targetFile);
    finfo_close($finfo);

    // ダウンロード用ヘッダー送信
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; fileName="' . $fileTitle . '_' . $uuid . '.' . $ext . '"');
    header('Content-Length: ' . filesize($targetFile));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    readfile($targetFile);
    exit;
}

// ========== ファイルが1つだけなら即ダウンロード ==========
if (count($files) === 1) {
    $filePath = $files[0];
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);

    header("Location: ?file=" . urlencode($uuid) . "&ext=" . urlencode($ext));
    exit;
}

// ========== 複数あれば一覧を表示(公開ファイルがURLでアクセスできる場合のみ) ==========
echo "<h2>対象ファイルダウンロード</h2>";
// kintoneから該当レコード情報取得
$record = getKintoneRecord(KINTONE_API_TOKEN, KINTONE_APP_ID, FILE_UUID_FIELD_CODE, $uuid);
$record = $record['records'][0] ?? null;
$fileTitle = $record[FILE_TITLE]['value'] ?? 'unknown';
foreach ($files as $filePath) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    echo('<a href="'.RELATIVE_DIR_PUBLIC.'/'.$uuid.'.'.$ext.'" download="' . $fileTitle . '_' . $uuid . '.' . $ext . '"> ' . $fileTitle . '.' . $ext . '　ダウンロード </a><br>');
}
