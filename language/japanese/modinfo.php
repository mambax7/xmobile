<?php
define('_MI_XMOBILE_NAME','xmobile');
define('_MI_XMOBILE_DESC','携帯対応モジュール');

define('_MI_XMOBILE_ACCESS_LEVEL','アクセス許可レベル');
define('_MI_XMOBILE_ACCESS_LEVEL_DESC','携帯用サイトにアクセスを許可するユーザグループ');
define('_MI_XMOBILE_ALLOW_GUEST','ゲストのみ許可');
define('_MI_XMOBILE_ALLOW_USER','登録ユーザのみ許可');
define('_MI_XMOBILE_ALLOW_ALL','全てのユーザを許可');
define('_MI_XMOBILE_ACCESS_TERM','アクセスを許可する端末');
define('_MI_XMOBILE_ACCESS_TERM_DESC','/modules/xmobile/ へのアクセスを許可する端末の設定。携帯端末のみ(エージェントから判別)を推奨します。動作確認等の目的でPC等からもアクセス可能にする場合は全て許可にして下さい。');
define('_MI_XMOBILE_ALLOW_MOBILE_H','携帯端末のみ(ホスト名から判別)');
define('_MI_XMOBILE_ALLOW_MOBILE_A','携帯端末のみ(エージェントから判別)');
define('_MI_XMOBILE_ALLOW_ALL_TERM','全て許可');
define('_MI_XMOBILE_LOGIN_TERM','ログイン・新規ユーザ登録を許可する端末');
define('_MI_XMOBILE_LOGIN_TERM_DESC','xmobileからログイン・新規ユーザ登録・個体識別番号の取得を許可する端末の設定。携帯端末のみ(ホスト名から判別)を推奨します。動作確認等の目的でPC等からもアクセス可能にする場合は全て許可にして下さい。');
define('_MI_XMOBILE_ALLOW_REGIST','携帯用サイトからの新規ユーザ登録');
define('_MI_XMOBILE_ALLOW_REGIST_DESC','携帯用サイトからの新規ユーザ登録の許可');
define('_MI_XMOBILE_CHK_IPADDRESS','セッションの復元の際にIPアドレスの値を照合する');
define('_MI_XMOBILE_CHK_IPADDRESS_DESC','IPアドレスの上9桁の値を照合します、ドコモ等でログイン状態が維持出来ない不具合が発生する場合はいいえにして下さい');
define('_MI_XMOBILE_USE_EZLOGIN','かんたんログインを使用可能にする');
define('_MI_XMOBILE_USE_EZLOGIN_DESC','ログイン認証の際に携帯端末の個体識別番号によるかんたんログインを使用可能にする');
define('_MI_XMOBILE_EZLOGIN_LIMIT','かんたんログイン認証の有効期間');
define('_MI_XMOBILE_EZLOGIN_LIMIT_DESC','かんたんログイン認証の為の個体識別番号を保存する期間です。');
define('_MI_XMOBILE_DEBUG_MODE','デバッグモード');
define('_MI_XMOBILE_DEBUG_MODE_DESC','デバッグ用のメッセージを表示する、必要な時以外は必ずいいえにして下さい。');
define('_MI_XMOBILE_LOGO','タイトル画像');
define('_MI_XMOBILE_LOGO_DESC','携帯用サイトのタイトルロゴに使用する画像データのurlを指定します、指定しない場合は画像表示しません');
define('_MI_XMOBILE_SITE_NAME','サイト名');
define('_MI_XMOBILE_SITE_NAME_DESC','携帯用サイトのサイト名');
define('_MI_XMOBILE_MAX_DATA_SIZE','1ページあたりのデータ最大サイズ');
define('_MI_XMOBILE_MAX_DATA_SIZE_DESC','1ページあたりに表示可能な文字データの最大サイズです(画像データ含まず)');
define('_MI_XMOBILE_SESSION_LIMIT','セッションタイムアウト秒数');
define('_MI_XMOBILE_SESSION_LIMIT_DESC','セッションタイムアウト秒数');
define('_MI_XMOBILE_USE_ACCESSKEY','アクセスキーを使用する');
define('_MI_XMOBILE_USE_ACCESSKEY_DESC','携帯電話の数字キーからアクセス出来るようになります');
define('_MI_XMOBILE_MAX_TITLE_ROW','記事一覧の最大表示件数');
define('_MI_XMOBILE_MAX_TITLE_ROW_DESC','記事一覧の1ページあたりの最大表示件数');
define('_MI_XMOBILE_MAX_TITLE_L','タイトルの最大表示文字数');
define('_MI_XMOBILE_MAX_TITLE_L_DESC','タイトルの最大表示文字数');

define('_MI_XMOBILE_TITLE_SORT','記事一覧の並び順');
define('_MI_XMOBILE_TITLE_SORT_DESC','記事一覧の並び順、昇順 or 降順 で指定');
define('_MI_XMOBILE_SORT_ASC','昇順');
define('_MI_XMOBILE_SORT_DESC','降順');

define('_MI_XMOBILE_CAT_TYPE','カテゴリの表示方法');
define('_MI_XMOBILE_CAT_TYPE_DESC','カテゴリの表示方法');
define('_MI_XMOBILE_TYPE_LIST','一覧');
define('_MI_XMOBILE_TYPE_SELECT','セレクトボックス');
define('_MI_XMOBILE_SHOW_COUNT','アイテム数の表示');
define('_MI_XMOBILE_SHOW_COUNT_DESC','カテゴリ一覧表示の際にタイトルの横にアイテム数を表示する');
define('_MI_XMOBILE_SHOW_RECENT','最新記事一覧の表示');
define('_MI_XMOBILE_SHOW_RECENT_DESC','最新記事一覧の表示の有無');
define('_MI_XMOBILE_RECENTTITLE_R','最新記事一覧の最大表示件数');
define('_MI_XMOBILE_RECENTTITLE_R_DESC','1ページあたりの最新記事の最大表示件数');
define('_MI_XMOBILE_SEARCH_R','検索結果の最大表示件数');
define('_MI_XMOBILE_SEARCH_R_DESC','1ページあたりの検索結果の最大表示件数');
define('_MI_XMOBILE_COMMENT_R','コメントの最大表示件数');
define('_MI_XMOBILE_COMMENT_R_DESC','1ページあたりのコメントの最大表示件数');
define('_MI_XMOBILE_PM_R','プライベートメッセージ一覧の最大表示件数');
define('_MI_XMOBILE_PM_R_DESC','1ページあたりのプライベートメッセージの最大表示件数');
define('_MI_XMOBILE_TAREA_ROWS','テキスト入力欄の縦幅');
define('_MI_XMOBILE_TAREA_ROWS_DESC','テキスト入力欄の縦幅');
define('_MI_XMOBILE_TAREA_COLS','テキスト入力欄の横幅');
define('_MI_XMOBILE_TAREA_COLS_DESC','テキスト入力欄の横幅');
define('_MI_XMOBILE_REPLACE_LINK','サイト内へのリンクを置換する');
define('_MI_XMOBILE_REPLACE_LINK_DESC','サイト内へのリンクをxmobileで閲覧可能なページに置換する');
define('_MI_XMOBILE_USE_THUMBNAIL','画像を縮小表示する');
define('_MI_XMOBILE_USE_THUMBNAIL_DESC','携帯電話の画面用に画像を縮小表示する');
define('_MI_XMOBILE_THUMBNAIL','画像縮小時の横幅');
define('_MI_XMOBILE_THUMBNAIL_DESC','画像を縮小表示する際の横幅を指定: px');
define('_MI_XMOBILE_THUMBPASS','サムネイルファイルの保存先ディレクトリ');
define('_MI_XMOBILE_THUMBPASS_DESC',"XOOPSインストール先からのパスを指定(最初の'/'は必要、最後の'/'は不要)<br />Unixではこのディレクトリへの書込属性をONにして下さい");
define('_MI_XMOBILE_MODULES','利用可能なモジュール');
define('_MI_XMOBILE_MODULES_DESC','xmobileから利用可能なモジュールの設定');

//NE+
define('_MI_XMOBILE_BLOCK_QR','QRコード');
define('_MI_XMOBILE_BLOCK_QR_DESC','xmobileへのアクセス用QRコード表示ブロック');
define('_MI_XMOBILE_BLOCK_REDIRECT','モバイルアクセス振り分け');
define('_MI_XMOBILE_BLOCK_REDIRECT_DESC','携帯端末からのアクセスをxmobileへ振り分ける為のブロック');

?>
