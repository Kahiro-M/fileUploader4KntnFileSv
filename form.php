<?php
session_start();
require_once "config.php";
require_once "common.php";

// CSRFトークン生成
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// kintoneフィールド情報取得
$fieldsOrgOrder = getKintoneFields();
$fields = changeFieldOrder($fieldsOrgOrder);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>kintone連携 | ファイルアップロードフォーム</title>
  <link rel="stylesheet" href="./css/form.css">
  <?php phpToJs(EXIST_ORG_FILED_CODE, 'hasOrgFiledCode'); ?>
  <?php phpToJs(getKintoneFieldType($fields,EXIST_ORG_FILED_CODE), 'hasOrgFiledType'); ?>
</head>
  <body>
  <h2>kintone連携 ファイルアップロードフォーム</h2>
  <form action="upload.php" method="post" enctype="multipart/form-data">

    <ul class="field">
      <li class="field-label">
        <label for="public_file">公開用ファイル</label><font color="red">*</font> :
      </li>
      <li class="field-content">
        <input type="file" id="public_file" name="public_file" required>
      </li>
    </ul>
    <ul class="field">
      <li class="field-label">
        <label for="original_file">原本</label> :
      </li>
      <li class="field-content">
        <input type="file" id="original_file" name="original_file">
      </li>
    </ul>

    <hr>

    <?php foreach ($fields as $f): ?>
      <ul class="field">
        <?php if($f['hide'] === FALSE){ ?>
        <li class="field-label">
          <?= htmlspecialchars($f['label']) ?>
          <?php if ($f['required'] === TRUE){ ?><font color="red">*</font><?php } ?>
          <span style="display:none;">(<?= $f['code'] ?>)</span>
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
    <button type="submit">送信</button>
  </form>
</body>
<script src="https://js.cybozu.com/jquery/3.7.1/jquery.min.js"></script>
<script src="./js/form.js"></script>
</html>
