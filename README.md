# payjp-eccube

[EC-CUBE](http://www.ec-cube.net)用の[PAY.JP](https://pay.jp/)の決済プラグインです。

このプラグインはEC-CUBE上での買い物をPAY.JPで決済する機能を提供します。
決済のみを行うシンプルなプラグインで、受注や集計はEC-CUBEで管理する方針です。
購入者のクレジットカード情報はデータベースに保存せず、ショップのオーナーも見ることはできません。

必要に応じて改造する際には[APIドキュメント](https://pay.jp/docs/api/)をご参照ください。

## 機能

- [PAY.JP](https://pay.jp/)にて簡単な登録を行うことで試せます。
- EC-CUBEで会員状態であればPAY.JPに顧客登録を行うことで次回以降はカード情報を入力せずに購入できます。
- ゲスト購入の場合は[トークン](https://pay.jp/docs/api/#token-トークン)を利用して一時的に購入します。
- カード情報は PAY.JP のサーバに安全に格納されます。

## 対応環境

- PHP 5.3 以上
- EC-CUBE 3.0.9 以上

## インストール方法

- PAY.JP の公式ライブラリをインストールします。

- `EC-CUBE/composer.json`を編集して以下の行を追加してください。

```
    "require": {
        "php": ">=5.3.3",
        （略）
        "payjp/payjp-php": "0.0.x"
    },

```

- EC-CUBEフォルダで `composer install` を実行します。

```
php composer.phar install
```

- このリポジトリをダウンロードして、構成ファイルを EC-CUBE の `app/Plugin/PayJp` に配置してください。

```
cd EC-CUBE/app/Plugin
git clone https://github.com/payjp/payjp-eccube.git PayJp
```

- プラグインをコマンドラインからインストールします。

```
cd EC-CUBE
php app/console plugin:develop install --code PayJp
```

- EC-CUBEの管理画面の「オーナーズストア＞プラグイン一覧」から「PAY.JP 決済プラグイン」の「有効にする」をクリックしてください。

- 「PAY.JP管理＞APIキー」にてご自身のAPIキーを登録してください。[PAY.JP](https://pay.jp/)にてメールアドレスを登録するだけでAPIキーを取得できます。

## 利用方法

- 商品を購入する際に、支払方法に「クレジットカード」を選択すると、カード情報を入力するフォームが表示されます。

- テスト環境では[テストカード](https://pay.jp/docs/testcard)を利用して試すことが可能です。

## テスト

- [PHPUnit](https://phpunit.de/)と[Selenium](http://www.seleniumhq.org/)によるブラックボックステストを備えています。
- Linuxのサーバ上ではXvfbを使って画面なしで実行できます。
- 実行手順は下記の通りです。

- `EC-CUBE/composer.json`を編集して以下の2行を追加または変更してください。

```
    "require-dev": {
        （略）
        "phpunit/phpunit": "4.6.*",
        "phpunit/phpunit-selenium": ">=2.0,<2.1",
        （略）
    },

```

- EC-CUBEフォルダで `composer install` を実行します。

```
php composer.phar install
```

- テストを実行できます。

```
cd app/Plugin/PayJp
./test_headless.sh
```

