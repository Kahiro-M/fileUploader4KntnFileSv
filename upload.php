<?php
session_start();
require_once "config.php";
require_once "common.php";

// CSRFチェック
if (!isset($_POST['token'], $_SESSION['token']) || $_POST['token'] !== $_SESSION['token']) {
    die("不正なリクエストです。");
}
unset($_SESSION['token']);

// === アップロード先フォルダ確認 ===
if (!is_dir(UPLOAD_DIR)) {
    die("アップロード先フォルダが存在しません: " . UPLOAD_DIR);
}
if (!is_writable(UPLOAD_DIR)) {
    die("アップロード先フォルダに書き込み権限がありません: " . UPLOAD_DIR);
}

// UUIDv4生成
function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// kintoneフィールド情報取得
$fields = getKintoneFields();

// アップロード先フォルダ確認・作成
$uploadDir = UPLOAD_DIR;
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// ファイル名生成
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$timestamp = date("Ymd_His");
$uuid = generateUuidV4();
echo("ファイル識別子 (UUIDv4): " . $uuid . "<br>");
$csvFileNoBOM = $uploadDir . "/files_{$timestamp}_{$ip}_{$uuid}.csv";
$csvFileBOM   = $uploadDir . "/files_{$timestamp}_{$ip}_{$uuid}_bom.csv";

// CSVデータ構築
$header = [];
$row    = [];

foreach ($fields as $f) {
    // フィールドコードやタイプごとの処理
    if (in_array($f['code'], HIDE_FIELD_CODE, true)) { // 非表示フィールド
        $header[] = $f['code'];
        if(strcmp($f['code'], FILE_UUID_FIELD_CODE) == 0){ // ファイル識別子フィールド
            $row[] = $uuid;
        }else{
            $row[] = htmlspecialchars($_POST[$f['code']] ?? "", ENT_QUOTES, "UTF-8");
        }

    } elseif ($f['type'] === 'FILE') {  // ファイル
        $header[] = $f['code'];
        if (isset($_FILES[$f['code']]) && $_FILES[$f['code']]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$f['code']]['name'], PATHINFO_EXTENSION);
            $newName = uniqid("file_", true) . "." . $ext;
            move_uploaded_file($_FILES[$f['code']]['tmp_name'], $uploadDir . $newName);
            $row[] = $newName;
        } else {
            $row[] = "";
        }

    } elseif ($f['type'] === 'CHECK_BOX' || $f['type'] === 'MULTI_SELECT') { // 複数選択
        $selected = isset($_POST[$f['code']]) ? (array)$_POST[$f['code']] : [];
        foreach ($f['options'] as $optCode => $opt) {
            $header[] = $f['code'] . "[" . $opt['label'] . "]";
            $row[] = in_array($optCode, $selected, true) ? "1" : "";
        }

    } elseif ($f['type'] === 'RADIO_BUTTON' || $f['type'] === 'DROP_DOWN') {  // 単一選択
        $header[] = $f['code'];
        $row[] = $_POST[$f['code']] ?? "";

    } else { // その他のフィールドタイプ
        $header[] = $f['code'];
        $row[] = htmlspecialchars($_POST[$f['code']] ?? "", ENT_QUOTES, "UTF-8");
    }
}

if (isset($_FILES['public_file']) && $_FILES['public_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['public_file']['name'], PATHINFO_EXTENSION);
    $filename = $uuid . '.' . $ext;
    $targetPath = UPLOAD_DIR_PUBLIC . '/' . $filename;

    if(move_uploaded_file($_FILES['public_file']['tmp_name'], $targetPath)) {
        echo('公開用ファイルを保存しました。<br>');
    }else{
        die('公開用ファイルの保存に失敗しました<br>');
    }
    // CSV出力用にファイル名を保持
    $data['public_file'] = $filename;
}

if (isset($_FILES['original_file']) && $_FILES['original_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['original_file']['name'], PATHINFO_EXTENSION);
    $filename = $uuid . '.' . $ext;
    $targetPath = UPLOAD_DIR_ORIGINAL . '/' . $filename;

    if(move_uploaded_file($_FILES['original_file']['tmp_name'], $targetPath)) {
        echo('原本ファイルを保存しました。<br>');
    }else{
        die('原本ファイルの保存に失敗しました<br>');
    }
    // CSV出力用にファイル名を保持
    $data['original_file'] = $filename;
}

// CSV出力（BOMなし）
$fp1 = fopen($csvFileNoBOM, 'w');
fputcsv($fp1, $header);
fputcsv($fp1, $row);
fclose($fp1);
echo("kintoneアップロード用のCSVを生成しました:<br>");

// CSV出力（BOMあり）
$fp2 = fopen($csvFileBOM, 'w');
fwrite($fp2, "\xEF\xBB\xBF");
fputcsv($fp2, $header);
fputcsv($fp2, $row);
fclose($fp2);
echo("kintoneアップロード用のCSV（BOM付き）を生成しました:<br>");

echo "✅ アップロード成功<br>";
echo basename($csvFileNoBOM) . "<br>";
echo basename($csvFileBOM) . "<br>";

echo('<p><a href="form.php">ファイル登録画面へ戻る</a></p>');
