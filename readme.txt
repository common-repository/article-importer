=== Article Importer ===
Contributors: kijitsuku
Donate link: 
Tags: importer, csv
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CSVデータから記事を投稿します。

== Description ==

このプラグインは指定のフォーマットのCSVデータをインポートし記事投稿を行います。
サンプルのCSVファイルは`/wp-content/plugins/article_importer/sample` にあります。
このプラグインのCSVデータは記事ツクから作成することでより簡単に使用できるようになります。
詳細は以下のページを参照してください。
https://kijitsuku.work/

== Installation ==

1. Upload All files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Import page under Tools menu.
4. Click Article Importer link, read the notification, then just upload and import.

== Frequently asked questions ==

= どのようなレイアウトのデータが対応していますか？ =
対応レイアウトが2種類あります。全てカンマ区切りのデータとなります。
■旧記事ツク
タイトル, 動画URL, サムネイル画像URL, カテゴリ, タグ, 備考, 公開日, テンプレート構文
■新記事ツク
タイトル, タグ, カテゴリ, 公開日, サムネイル画像URL, 本文, 公開ステータス

== Screenshots ==



== Changelog ==

= 2.0 =
新レイアウトのCSVデータに対応

= 1.2 =
全角スペースがあった場合タグが一つの文字で認識されていたため修正

= 1.1 =
Column 'Remars' add

= 1.0 =
Initial release

== Upgrade notice ==
No information


== Arbitrary section 1 ==

