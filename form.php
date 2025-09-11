<?php
session_start();
require_once "config.php";
require_once "common.php";

// CSRFトークン生成
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// kintoneフィールド情報取得
$fields = getKintoneFields();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>kintone連携 | ファイルアップロードフォーム</title>
</head>
<body>
<h2>kintone連携 ファイルアップロードフォーム</h2>
<form action="upload.php" method="post" enctype="multipart/form-data">

  <div>
    <label for="public_file">公開用ファイル</label><font color="red">*</font>
    <input type="file" name="public_file" required>
  </div>
  <div>
    <label for="original_file">原本</label>
    <input type="file" name="original_file">
  </div>

  <hr>

  <?php foreach ($fields as $f): ?>
    <div>
      <?php if($f['hide'] === FALSE){ ?>
      <label>
        <?= htmlspecialchars($f['label']) ?>
        <span style="display:none;">(<?= $f['code'] ?>)</span>
        :
      </label>
      <?php } ?>

      <?php if ($f['hide'] === TRUE): ?>
        <input type="hidden" name="<?= $f['code'] ?>">

      <?php elseif ($f['type'] === 'FILE'): ?>
        <input type="file" name="<?= $f['code'] ?>">

      <?php elseif ($f['type'] === 'MULTI_LINE_TEXT'): ?>
        <textarea name="<?= $f['code'] ?>" rows="4" cols="40"></textarea>

      <?php elseif ($f['type'] === 'RADIO_BUTTON'): ?>
        <?php foreach ($f['options'] as $optCode => $opt): ?>
          <label>
            <input type="radio" name="<?= $f['code'] ?>" value="<?= htmlspecialchars($optCode) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </label>
        <?php endforeach; ?>

      <?php elseif ($f['type'] === 'DROP_DOWN'): ?>
        <select name="<?= $f['code'] ?>">
          <?php foreach ($f['options'] as $optCode => $opt): ?>
            <option value="<?= htmlspecialchars($optCode) ?>"><?= htmlspecialchars($opt['label']) ?></option>
          <?php endforeach; ?>
        </select>

      <?php elseif ($f['type'] === 'CHECK_BOX' || $f['type'] === 'MULTI_SELECT'): ?>
        <?php foreach ($f['options'] as $optCode => $opt): ?>
          <label>
            <input type="checkbox" name="<?= $f['code'] ?>[]" value="<?= htmlspecialchars($optCode) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </label>
        <?php endforeach; ?>

      <?php else: ?>
        <input type="text" name="<?= $f['code'] ?>">
      <?php endif; ?>
    </div>
    <br>
  <?php endforeach; ?>

  <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
  <button type="submit">送信</button>
</form>
</body>
</html>
