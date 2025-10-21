<?php
// デバッグ用フィールド情報表示
function dbg_dump($fields, $title = ''){
    if(defined('DEBUG_MODE') == TRUE && DEBUG_MODE == TRUE){
        echo("<pre>");
        echo("[".$title."]\n");
        var_dump($fields);
        echo("</pre>");
    }
}

// kintoneフィールド情報取得
function getKintoneFields($type='') {
    // kintone REST APIのURLとヘッダー設定
    $url = "https://" . KINTONE_SUBDOMAIN . ".cybozu.com/k/v1/app/form/fields.json" . "?app=" . KINTONE_APP_ID;
    $headers = [
        "X-Cybozu-API-Token: " . KINTONE_API_TOKEN,
    ];

    // cURLでAPIリクエスト
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);

    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("cURLエラー: " . curl_error($ch));
    }
    curl_close($ch);

    // レスポンスをデコードしてフィールド情報を抽出
    $data = json_decode($res, true);
    if (!isset($data['properties'])) {
        throw new Exception("フィールド情報を取得できませんでした: " . $res);
    }

    $fields = [];
    foreach ($data['properties'] as $f) {
        if(empty($type)){
            if(
                in_array($f['type'], EXCLUDE_TYPE, true) == FALSE
                && in_array($f['code'], EXCLUDE_FIELD_CODE, true) == FALSE
            ){
                $fields[$f['label']] = [
                    'code'         => $f['code'],
                    'label'        => $f['label'],
                    'type'         => $f['type'],
                    'required'     => $f['required'],
                    'options'      => $f['options'] ?? [],
                    'defaultValue' => $f['defaultValue'] ?? '',
                    'hide'         => in_array($f['code'], HIDE_FIELD_CODE, true)
                ];
            }
        }elseif(strcasecmp($type,'all')){
            $fields[$f['label']] = [
                'code'         => $f['code'],
                'label'        => $f['label'],
                'type'         => $f['type'],
                'required'     => $f['required'],
                'options'      => $f['options'] ?? [],
                'defaultValue' => $f['defaultValue'] ?? '',
                'hide'         => in_array($f['code'], HIDE_FIELD_CODE, true)
            ];
        }else{
            $fields[$f['label']] = [
                'code'         => $f['code'],
                'label'        => $f['label'],
                'type'         => $f['type'],
                'required'     => $f['required'],
                'options'      => $f['options'] ?? [],
                'defaultValue' => $f['defaultValue'] ?? '',
                'hide'         => in_array($f['code'], HIDE_FIELD_CODE, true)
            ];
        }
    }
    return $fields;
}

// getKintoneFields()の返り値から特定フィールドのtypeを取得
function getKintoneFieldType($fieldList,$code){
    if(isset($fieldList[$code])){  // getKintoneFields()の返り値の配列から特定フィールドのtypeを取得
        return $fieldList[$code]['type'] ?? null;
    }else{  // getKintoneFields()の返り値の配列の要素から特定フィールドのtypeを取得
        foreach($fieldList as $field){
            if($field['code'] === $code){
                return $field['type'] ?? null;
            }
        }
        return null;
    }
}

// kintoneレコード追加
function addKintoneRecord($appId=KINTONE_APP_ID, $record, $apiToken=KINTONE_API_TOKEN) {
    $url = "https://" . KINTONE_SUBDOMAIN . ".cybozu.com/k/v1/record.json";

    $headers = [
        "X-Cybozu-API-Token: {$apiToken}",
        "Content-Type: application/json"
    ];

    $data = [
        "app" => $appId,
        "record" => $record
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("kintone API error: " . $response);
    }

    return json_decode($response, true);
}

// kintoneレコード取得
function getKintoneRecordList($appId=KINTONE_APP_ID, $apiToken=KINTONE_API_TOKEN, $fields=[], $query='', $offset=0, $limit=500) {
    $url = "https://" . KINTONE_SUBDOMAIN . ".cybozu.com/k/v1/records.json";

    $headers = [
        "X-Cybozu-API-Token: {$apiToken}",
    ];

    $params = [
        "app" => $appId,
        "fields" => $fields,
        "totalCount" => "true",
    ];
    if (!empty($query)) {
        $params["query"] = $query . " limit {$limit} offset {$offset}";
    } else {
        $params["query"] = "limit {$limit} offset {$offset}";
    }

    $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("kintone API error: " . $response);
    }

    return json_decode($response, true);
}

// intone全件レコード取得
function getKintoneAllRecordList($appId=KINTONE_APP_ID, $apiToken=KINTONE_API_TOKEN, $fields = [], $query = '', $limit = 500) {
    $allRecords = [];
    $offset = 0;
    $totalCount = null;

    do {
        $batch = getKintoneRecordList(
            $appId,
            $apiToken,
            $fields,
            $query,
            $offset,
            $limit
        );

        if (!isset($batch['records'])) {
            break;
        }

        // レコードをマージ
        $allRecords = array_merge($allRecords, $batch['records']);

        // totalCount は最初のレスポンスから取得
        if ($totalCount === null && isset($batch['totalCount'])) {
            $totalCount = (int)$batch['totalCount'];
        }

        $fetched = count($batch['records']);
        $offset += $fetched;

    } while ($fetched > 0 && ($totalCount === null || $offset < $totalCount));

    return [
        'records' => $allRecords,
        'totalCount' => $totalCount ?? count($allRecords)
    ];
}


// kintoneレコード検索
function getKintoneRecord($apiToken=KINTONE_API_TOKEN, $appId=KINTONE_APP_ID, $fieldCode=FILE_UUID_FIELD_CODE, $fieldData) {
    $urlBase = "https://" . KINTONE_SUBDOMAIN . ".cybozu.com/k/v1/records.json";

    $headers = [
        "X-Cybozu-API-Token: {$apiToken}",
    ];

    // 検索条件をkintoneクエリで指定
    $query = $fieldCode.' = "' . $fieldData . '"';
    $params = [
        "app" => $appId,
        "query" => $query
    ];

    // URLエンコードしてクエリパラメータを構築
    $url = $urlBase . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("kintone API error: " . $response);
    }

    return json_decode($response, true);
}

// フィールド表示順序の調整
function changeFieldOrder($fields,$orderList=FIELD_CODE_DISPLAY_ORDER) {
    // 優先フィールドを先頭に移動
    if (!empty($orderList)) {
        $reordered = [];

        // 優先フィールドを先に追加（存在する場合のみ）
        foreach ($orderList as $code) {
            foreach ($fields as $f) {
                if ($f['code'] === $code) {
                    $reordered[$code] = $f;
                    break;
                }
            }
        }

        // 残りのフィールドを順序維持で追加
        foreach ($fields as $f) {
            if (!in_array($f['code'], $orderList, true)) {
                $reordered[] = $f;
            }
        }

        // 最終的な表示用の並び替え結果を $fields に反映
        $fields = $reordered;
    }
    return $fields;
}

// kintoneフィールド情報のうち、指定リストに合致するものを順番通り返す
function filterAndSortKintoneFields(array $fields, array $orderList=SHOW_FIELD_CODE_LIST): array {
    $result = [];
    foreach ($orderList as $fieldLabel) {
        if (isset($fields[$fieldLabel])) {
            // 該当するフィールド情報を順番通りに追加
            $result[$fieldLabel] = $fields[$fieldLabel];
        } else {
            // 万一フィールドが見つからない場合もキーだけ確保しておくならこちら
            // $result[$fieldLabel] = null;
        }
    }
    return $result;
}



// options部分を["label"]で昇順ソート
function sortByLabel(array $options): array {
    if (empty($options)) {
        return $options; // 空配列ならそのまま返す
    }

    uasort($options, function($a, $b) {
        return strcmp($a["label"], $b["label"]);
    });

    return $options; // ソート済み配列を返す
}

// ダウンロードファイル名生成
function getDownloadFileName($record) {
    $fileTitleParts = [];
    foreach (DOWNLOAD_NAME_FIELD_CODE as $code) {
        if (isset($record[$code]) && !empty($record[$code]['value'])) {
            $fileTitleParts[] = $record[$code]['value'] ?? '';
        }
    }
    return implode(DOWNLOAD_NAME_SPLIT, $fileTitleParts);
}

// PHP変数をJavaScript変数に変換して出力
function phpToJs($phpVar, $jsVarName) {
    $json = json_encode($phpVar, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>const {$jsVarName} = {$json};</script>";
}

// 配列やオブジェクトなどをvar_dump風にログ出力する関数
function logDump($data, $path = './debug.log', $append = true)
{
    // 出力内容を文字列として取得
    $output = formatDump($data);

    // タイムスタンプを付ける
    $timestamp = '[' . date('Y-m-d H:i:s') . "]\n";
    $log = $timestamp . $output . "\n";

    // ファイルへ書き込み
    file_put_contents($path, $log, $append ? FILE_APPEND : 0);
}

// var_dump風に再帰整形して文字列化
function formatDump($data, $indent = 0)
{
    $spaces = str_repeat('  ', $indent);
    $result = '';

    if (is_array($data)) {
        $result .= "array(" . count($data) . ") {\n";
        foreach ($data as $key => $value) {
            $result .= $spaces . "  [" . $key . "] => ";
            if (is_array($value) || is_object($value)) {
                $result .= formatDump($value, $indent + 1);
            } else {
                $result .= getValueStr($value) . "\n";
            }
        }
        $result .= $spaces . "}\n";
    } elseif (is_object($data)) {
        $result .= "object(" . get_class($data) . ") {\n";
        foreach (get_object_vars($data) as $key => $value) {
            $result .= $spaces . "  [" . $key . "] => ";
            if (is_array($value) || is_object($value)) {
                $result .= formatDump($value, $indent + 1);
            } else {
                $result .= getValueStr($value) . "\n";
            }
        }
        $result .= $spaces . "}\n";
    } else {
        $result .= getValueStr($data) . "\n";
    }

    return $result;
}

// 値の型に応じて出力形式を整える
function getValueStr($value)
{
    if (is_null($value)) return "NULL";
    if (is_bool($value)) return $value ? "bool(true)" : "bool(false)";
    if (is_int($value)) return "int($value)";
    if (is_float($value)) return "float($value)";
    if (is_string($value)) return "string(" . strlen($value) . ") \"$value\"";
    return gettype($value);
}

?>