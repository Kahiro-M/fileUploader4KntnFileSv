<?php
// デバッグ用フィールド情報表示
function dbg_dump($fields, $title = ''){
    echo("<pre>");
    echo("[".$title."]\n");
    var_dump($fields);
    echo("</pre>");
}

// kintoneフィールド情報取得
function getKintoneFields() {
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
        if(
            in_array($f['type'], EXCLUDE_TYPE, true) == FALSE
            && in_array($f['code'], EXCLUDE_FIELD_CODE, true) == FALSE
        ){
            $fields[] = [
                'code'    => $f['code'],
                'label'   => $f['label'],
                'type'    => $f['type'],
                'options' => $f['options'] ?? [],
                'hide'    => in_array($f['code'], HIDE_FIELD_CODE, true)
            ];
      }
    }
    return $fields;
}


?>