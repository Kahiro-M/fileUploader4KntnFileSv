kintone連携 ファイルアップロード
=====================================

## 概要
添付ファイルをサーバ上に保存し、添付ファイルのダウンロードURLを含めたレコード情報をkintoneへ登録します。  
以下のWEBページで構成されています。
 - ファイル情報を登録するフォーム
 - ダウンロードを実行するページ
 - kintoneに登録されたファイルの一覧を表示するページ

## 構築方法
1. kintone上でアプリを作成  
    サンプルの[アプリテンプレート](https://github.com/Kahiro-M/fileUploader4KntnFileSv/releases/download/v.1.0.0/default.zip)は文書管理を目的としたアプリです。  
    サンプルの[アプリテンプレート](https://github.com/Kahiro-M/fileUploader4KntnFileSv/releases/download/v.1.0.0/default.zip)を利用した場合は、以下の項目をご利用の環境に合わせて変更してください。
    - 資料ナンバー（自動計算のジャンル、HP掲載、作成日、作成者部分）
    - 公開URL（自動計算のドメイン部分）
    - 原本URL（自動計算のドメイン部分）
2. 作成したkintoneでAPIトークンを作成
3. 対象のWEBサーバに以下のファイルを設置する
    ```bash
    ドキュメントルート
    │  common.php
    │  config.php (config.php.sampleをリネーム)
    │  download.php
    │  form.php
    │  list.php
    │  upload.php
    │
    ├─css
    │      form.css
    │      list.css
    │
    └─js
            form.js
    ```
4. WEBサーバ上にファイルを保管するディレクトリを作成
5. config.phpを編集
    ```php
    // kintone接続設定
    define('KINTONE_SUBDOMAIN', 'your-subdomain'); // 例: "example"  ←利用環境に合わせて変更する
    define('KINTONE_APP_ID', 123);                 // アプリID       ←利用環境に合わせて変更する
    define('KINTONE_API_TOKEN', 'xxxxxxxx');       // APIトークン    ←利用環境に合わせて変更する

    // アップロード先フォルダ（絶対パス推奨）
    define('UPLOAD_DIR_CSV', '/path/to/uploads');          //  手動アップロード用のCSV  ←利用環境に合わせて変更する
    define('UPLOAD_DIR_CSV_BOM', '/path/to/uploads/bom');  //  手動アップロード用のCSV（BOMあり） ←利用環境に合わせて変更する
    define('UPLOAD_DIR_PUBLIC', '/path/to/pub');           //  公開ファイル保存場所     ←利用環境に合わせて変更する
    define('UPLOAD_DIR_ORIGINAL', '/path/to/org');         //  原本ファイル保存場所     ←利用環境に合わせて変更する
    ```

## 利用方法
- アップロード
    1. `https://[ご利用の環境]/form.php`にアクセス
    2. フォームに従い、ファイルとファイル情報を入力して送信
    3. 「✅ アップロード成功」の画面が出たらOK

- 一覧表示
    1. `https://[ご利用の環境]/list.php`にアクセス
    2. kintoneに登録されている情報が表示される

- ダウンロード
    1. 公開URL/原本URLのリンクをクリック
    2. 死活がダウンロード有効の場合はダウンロード実行される  
        ※ダウンロード無効のパラメータは以下の設定で変更可能です。
        ```php
        // 死活用フィールドコード
        define('IS_DISABLE_FILED_CODE', [
            '死活' => [
                '無効',
            ],
        ]);
        ```
