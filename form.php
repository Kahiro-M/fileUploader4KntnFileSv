<?php
require_once "config.php";
require_once "common.php";

// iframe埋め込み
if (ALLOW_IFRAME_EMBED) {
    // オリジン配列をCSP形式に整形
    $origins = implode(' ', ALLOWED_IFRAME_ORIGINS);

    // frame-ancestorsヘッダを出力
    header("Content-Security-Policy: frame-ancestors 'self' {$origins};");
} else {
    // iframe埋め込みを全面拒否
    header("Content-Security-Policy: frame-ancestors 'none';");
}

// セッション設定（iframe運用想定）
session_set_cookie_params([
    'samesite' => 'None',
    'secure'   => true,
    'httponly' => true,
]);

session_start();
// 初回アクセス時のみID再発行
if (empty($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// CSRFトークン生成
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// kintoneフィールド情報取得
$fieldsOrgOrder = getKintoneFields();
$fields = changeFieldOrder($fieldsOrgOrder,FIELD_CODE_DISPLAY_ORDER);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, nofollow">
  <title><?= FORM_TITLE ?? 'kintone連携 | ファイルアップロードフォーム' ?></title>
  <link rel="stylesheet" href="./css/form.css">
  <?php if(DEBUG_MODE){echo('<link rel="stylesheet" href="./css/dbg.css">');} ?>
  <?php phpToJs(EXIST_ORG_FILED_CODE, 'hasOrgFiledCode'); ?>
  <?php phpToJs(getKintoneFieldType($fields,EXIST_ORG_FILED_CODE), 'hasOrgFiledType'); ?>
</head>
  <body>
  <?php if(DEBUG_MODE){echo('<h1 style="color:red;">システムメンテナンス中<br>アップロードしないでください。</h1>');} ?>
  <h2><?= FORM_TITLE ?? 'kintone連携 ファイルアップロードフォーム' ?></h2>
  <?= FORM_MSG_HEADER ?>
  <form action="upload.php" method="post" enctype="multipart/form-data">

    <ul class="field">
      <li class="field-label">
        <label class="field-label-text" for="public_file">公開用ファイル(MAX:<?= UPLOAD_MAX_FILESIZE ?>B)</label><span class="req-text">*</span>
      </li>
      :
      <li class="field-content">
        <input type="file" id="public_file" name="public_file" accept="<?= ALLOWED_FILE_EXTS ?>" required>
      </li>
    </ul>
    <ul class="field">
      <li class="field-label">
        <label class="field-label-text" for="original_file">原本(MAX:<?= UPLOAD_MAX_FILESIZE ?>B)</label>
      </li>
      :
      <li class="field-content">
        <input type="file" id="original_file" name="original_file" accept="<?= ALLOWED_FILE_EXTS ?>">
      </li>
    </ul>
    <?= FORM_MSG_FILES ?>

    <hr>

    <?php foreach ($fields as $f): ?>
      <ul class="field <?php if($f['hide'] === TRUE){ echo 'hide'; } ?>">
        <?php if($f['hide'] === FALSE){ ?>
        <li class="field-label">
          <span class="field-label-text"><?= htmlspecialchars($f['label']) ?></span>
          <?php if ($f['required'] === TRUE){ ?><span class="req-text">*</span><?php } ?>
          <span class="field-code">(<?= $f['code'] ?>)</span>
        </li>
        :
        <?php } ?>

        <li class="field-content">
          <?php if ($f['hide'] === TRUE): ?>
            <input type="hidden" name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> value="<?= htmlspecialchars($f['defaultValue']) ?>">

          <?php elseif ($f['type'] === 'FILE'): ?>
            <input type="file" name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?>>

          <?php elseif ($f['type'] === 'DATE'): ?>
            <input type="date" name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> value="<?= htmlspecialchars($f['defaultValue']) ?>">

          <?php elseif ($f['type'] === 'DATETIME'): ?>
            <input type="datetime-local" name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> value="<?= htmlspecialchars($f['defaultValue']) ?>">

          <?php elseif ($f['type'] === 'MULTI_LINE_TEXT'): ?>
            <textarea name="<?= $f['code'] ?>" rows="4" cols="40" <?php if ($f['required'] === TRUE){ echo 'required'; } ?>><?= htmlspecialchars($f['defaultValue']) ?></textarea>

          <?php elseif ($f['type'] === 'RADIO_BUTTON'): ?>
            <?php foreach (sortByLabel($f['options']) as $optCode => $opt): ?>
              <label>
                <input type="radio" name="<?= $f['code'] ?>" value="<?= htmlspecialchars($optCode) ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> <?php if ($f['defaultValue'] === htmlspecialchars($optCode)){ echo 'checked="true"'; } ?>>
                <?= htmlspecialchars($opt['label']) ?>
              </label>
            <?php endforeach; ?>

          <?php elseif ($f['type'] === 'DROP_DOWN'): ?>
            <select name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?>>
              <?php foreach (sortByLabel($f['options']) as $optCode => $opt): ?>
                <option value="<?= htmlspecialchars($optCode) ?>" <?php if ($f['defaultValue'] === htmlspecialchars($optCode)){ echo 'selected'; } ?>><?= htmlspecialchars($opt['label']) ?></option>
              <?php endforeach; ?>
            </select>

          <?php elseif ($f['type'] === 'CHECK_BOX' || $f['type'] === 'MULTI_SELECT'): ?>
            <?php foreach (sortByLabel($f['options']) as $optCode => $opt): ?>
              <label>
                <input type="checkbox" name="<?= $f['code'] ?>[]" value="<?= htmlspecialchars($optCode) ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> <?php if ($f['defaultValue'] === htmlspecialchars($optCode)){ echo 'checked="true"'; } ?>>
                <?= htmlspecialchars($opt['label']) ?>
              </label>
            <?php endforeach; ?>

          <?php else: ?>
            <input type="text" name="<?= $f['code'] ?>" <?php if ($f['required'] === TRUE){ echo 'required'; } ?> value="<?= htmlspecialchars($f['defaultValue']) ?>">
          <?php endif; ?>
        </li>
      </ul>
    <?php endforeach; ?>

    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
    <?= FORM_MSG_FOOTER ?>
    <button type="submit">登録</button>
  </form>
</body>
<script src="https://js.cybozu.com/jquery/3.7.1/jquery.min.js"></script>
<script src="./js/form.js"></script>
</html>
