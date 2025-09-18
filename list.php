<?php
session_start();
require_once "config.php";
require_once "common.php";

// kintoneフィールド情報取得（表示順序調整用）
$kntnFields = getKintoneFields('all');
$fieldCodeList = filterAndSortKintoneFields($kntnFields,SHOW_FIELD_CODE_LIST);

// kintoneレコード情報取得
$records = getKintoneAllRecordList(appId:KINTONE_APP_ID, apiToken:KINTONE_API_TOKEN, fields:array_keys($fieldCodeList));

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, nofollow">
  <title>kintone連携 | データ閲覧</title>
  <link rel="stylesheet" href="./css/list.css">
</head>
  <body>
  <h2>kintone連携 データ閲覧</h2>

<?php
if (!isset($records['records']) || !is_array($records['records'])) {
    echo "レコードが見つかりません。";
    exit(__FILE__);
}else{
    echo "<p>" . count($records['records']) . "件 / " . htmlspecialchars($records['totalCount'], ENT_QUOTES, 'UTF-8') . "件 表示</p>";
}

$data = $records['records'];

echo('<div class="table-container">');
echo('<table class="modern-table">');

// ヘッダー行
echo('<thead><tr class="row-header">');

foreach ($fieldCodeList as $field) {
    echo('<th class="COL_'.$field['type'].'">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . '</th>');
}
echo('</tr></thead>');

// データ行
foreach ($data as $row) {
    echo('<tr>');
    foreach ($fieldCodeList as $field) {
        $fieldCode = $field['code'];
        $value = "";

        if (isset($row[$fieldCode])) {
            $value = $row[$fieldCode]['value'];

            // 値が配列（作成者など）の場合は name/code を優先表示
            if (is_array($value)) {
                if (isset($value['name'])) {
                    $value = $value['name'];
                } elseif (isset($value['code'])) {
                    $value = $value['code'];
                } else {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }
        }
        $outValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $url = $outValue;
        $parts = parse_url($url);
        if (isset($parts['scheme'], $parts['host'], $parts['path'], $parts['query'])) {
            echo('<td class="VAL_'.$row[$fieldCode]['type'].'"><a href="' . $outValue . '" target="_blank">' . $outValue . '</a></td>');
        }else{
            echo('<td class="VAL_'.$row[$fieldCode]['type'].'">' . $outValue . '</td>');
        }
    }
    echo "</tr>";
}

echo "</table>";

?>
</body>
<script src="https://js.cybozu.com/jquery/3.7.1/jquery.min.js"></script>
</html>
