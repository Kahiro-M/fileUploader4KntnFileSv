<?php
session_start();
require_once "config.php";
require_once "common.php";

$uploadMaxFilesize = UPLOAD_MAX_FILESIZE;
$postMaxSize = POST_MAX_SIZE;
$memoryLimit = MEMORY_LIMIT;

// CSRFチェック
if (!isset($_POST['token'], $_SESSION['token']) || $_POST['token'] !== $_SESSION['token']) {
    echo("不正なリクエストです。<br>");
    echo("アップロード画面で再読み込み、もしくは、データ容量が超過した可能性があります。<br>");
    echo "<h2>再度ファイル登録を行ってください。<br>何度も発生する場合はこの画面のスクリーンショットを撮影して、システム担当者へ連絡してください。</h2>";
    echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
    exit();
}
unset($_SESSION['token']);

// ファイルサイズチェック
$fields = [
    'public_file'=>'公開用ファイル', 
    'original_file'=>'原本',
];
foreach ($fields as $field=>$label) {
    if (!isset($_FILES[$field])) {
        continue; // フィールドが無い場合はスキップ
    }

    $fileError = $_FILES[$field]['error'];
    $fileSize  = $_FILES[$field]['size'];

    if ($fileError !== UPLOAD_ERR_OK) {
        switch ($fileError) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                echo("アップロードされたファイル「{$label}」がサイズ制限({$uploadMaxFilesize})を超えています。<br>");
                echo("ファイルサイズを縮小して再アップロード、もしくは、管理者へ連絡してください。<br>");
                echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
                exit();
            case UPLOAD_ERR_PARTIAL:
                echo("アップロードに失敗しました（途中で中断されました: {$label}）<br>");
                echo("再アップロードしてください。<br>");
                echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
                exit();
            case UPLOAD_ERR_NO_FILE:
                // 未添付ならスキップでOK
                break;
            default:
                echo("不明なアップロードエラーが発生しました（{$label}）<br>");
                echo("再アップロードしてください。<br>");
                echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
                exit();
        }
    }
}

// === アップロード先フォルダ確認 ===
if (!is_dir(UPLOAD_DIR_CSV)) {
    echo("アップロード先フォルダが存在しません: " . UPLOAD_DIR_CSV);
}
if (!is_writable(UPLOAD_DIR_CSV)) {
    die("アップロード先フォルダに書き込み権限がありません: " . UPLOAD_DIR_CSV);
}
if (!is_dir(UPLOAD_DIR_CSV_BOM)) {
    echo("アップロード先フォルダが存在しません: " . UPLOAD_DIR_CSV_BOM);
}
if (!is_writable(UPLOAD_DIR_CSV_BOM)) {
    die("アップロード先フォルダに書き込み権限がありません: " . UPLOAD_DIR_CSV_BOM);
}

$uploadDirCsv = UPLOAD_DIR_CSV;
$uploadDirCsvBom = UPLOAD_DIR_CSV_BOM;

// UUIDv4生成
function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// kintoneフィールド情報取得
$fields = getKintoneFields();

// ファイル名生成
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$timestamp = date("Ymd_His");
$uuid = generateUuidV4();
echo("<div class='dbg-msg' style='display:none;'>ファイル識別子 (UUIDv4): " . $uuid . "</div>");
$csvFileNoBOM = $uploadDirCsv . "/files_{$timestamp}_{$ip}_{$uuid}.csv";
$csvFileBOM   = $uploadDirCsvBom . "/files_{$timestamp}_{$ip}_{$uuid}_bom.csv";

echo("<div class='dbg-msg' style='display:none;'>POST: ");
echo(var_dump($_POST));
echo("</div>");

// CSVデータ構築
$header = [];
$row    = [];

foreach ($fields as $f) {
    // フィールドコードやタイプごとの処理
    if (in_array($f['code'], HIDE_FIELD_CODE, true)) { // 非表示フィールド
        $header[] = $f['code'];
        if(strcmp($f['code'], FILE_UUID_FIELD_CODE) == 0){ // ファイル識別子フィールド
            $row[] = $uuid;
        }elseif(strcmp($f['code'], PUBLIC_FILE_TYPE) == 0){ // ファイル形式フィールド
            $row[] = $_FILES['public_file']['type'];
        }elseif(strcmp($f['code'], ORIGINAL_FILE_TYPE) == 0){ // 原本ファイル形式フィールド
            $row[] = $_FILES['original_file']['type'];
        }else{
            $row[] = htmlspecialchars($_POST[$f['code']] ?? "", ENT_QUOTES, "UTF-8");
        }

    } elseif ($f['type'] === 'FILE') {  // ファイル
        $header[] = $f['code'];
        if (isset($_FILES[$f['code']]) && $_FILES[$f['code']]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$f['code']]['name'], PATHINFO_EXTENSION);
            $newName = uniqid("file_", true) . "." . $ext;
            move_uploaded_file($_FILES[$f['code']]['tmp_name'], $uploadDirCsv . $newName);
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
        echo('<div class="dbg-msg" style="display:none;">公開用ファイルを保存しました。</div>');
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
        echo('<div class="dbg-msg" style="display:none;">原本ファイルを保存しました。</div>');
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
echo("<div class='dbg-msg' style='display:none;'>kintoneアップロード用のCSVを生成しました:</div>");

// CSV出力（BOMあり）
$fp2 = fopen($csvFileBOM, 'w');
fwrite($fp2, "\xEF\xBB\xBF");
fputcsv($fp2, $header);
fputcsv($fp2, $row);
fclose($fp2);
echo("<div class='dbg-msg' style='display:none;'>kintoneアップロード用のCSV（BOM付き）を生成しました:</div>");


// $header と $row から kintone用の record 配列を組み立て
$record = [];
$checkboxBuffer = []; // チェックボックス用バッファ
foreach ($header as $i => $fieldCode) {
    $value = $row[$i] ?? "";

    // 「フィールドコード[選択肢]」形式を検出
    if (preg_match('/^(.+?)\[(.+)\]$/u', $fieldCode, $m)) {
        $baseField = $m[1];   // 例: "本社審査"
        $option    = $m[2];   // 例: "コンプライアンス"

        if ($value === "1") {
            $checkboxBuffer[$baseField][] = $option;
        }
        continue; // 通常処理には回さない
    }

    // 通常フィールド処理
    if (strpos($value, ",") !== false) {
        $record[$fieldCode] = [ "value" => explode(",", $value) ];
    } else {
        $record[$fieldCode] = [ "value" => $value ];
    }
}
// チェックボックス項目をまとめて追加
foreach ($checkboxBuffer as $field => $options) {
    $record[$field] = [ "value" => $options ];
}

try {
    $result = addKintoneRecord(KINTONE_APP_ID, $record, KINTONE_API_TOKEN);
    echo "<div class='dbg-msg' style='display:none;'>登録成功: recordId=" . $result["id"]. "</div>";
} catch (Exception $e) {
    echo "<h1>登録エラー: " . $e->getMessage()."</h1>";
    echo "<h2>再度ファイル登録を行ってください。<br>何度も発生する場合はこの画面のスクリーンショットを撮影して、システム担当者へ連絡してください。</h2>";
    echo "<style>.dbg-msg { display:block!important; }</style>";
    echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
    exit();
}

echo "<h1>✅ アップロード成功</h1>";
echo("<div class='dbg-msg' style='display:none;'>".basename($csvFileNoBOM) . "</div>");
echo("<div class='dbg-msg' style='display:none;'>".basename($csvFileBOM) . "</div>");

echo('<h3><a href="form.php">ファイル登録画面へ戻る</a></h3>');
