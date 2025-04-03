# Koriym.EnvJson

<img src="https://koriym.github.io/Koriym.EnvJson/images/envjson.jpg" width="400px" alt="env.json logo">

[English](./README.md)

`.env`ファイルの代わりにJSONを使って環境変数を設定します。
[JSON schema](https://json-schema.org/)によるバリデーションにより、環境変数の型安全性を確保します。

## 特徴

- **型安全な環境変数**: JSON schemaによる検証機能
- **充実したドキュメント**: スキーマによる説明と制約条件の定義
- **変換ツール**: `.env`からJSONフォーマットへの移行ツール
- **CLIユーティリティ**: シェル連携と様々な出力形式に対応
- **フォールバック機能**: 開発環境向けのデフォルト値設定

## インストール

```bash
composer require koriym/env-json
```

## 基本的な使い方

```php
// 環境変数の読み込みとバリデーション
$env = (new EnvJson())->load(__DIR__);

// オブジェクトプロパティとしてアクセス
echo $env->DATABASE_URL;

// または従来のgetenv()を使用
echo getenv('DATABASE_URL');
```

## 設定ファイル

### JSONスキーマ (env.schema.json)

型、説明、制約条件付きで環境変数を定義します:

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "DATABASE_URL", "API_KEY"
    ],
    "properties": {
        "DATABASE_URL": {
            "description": "データベース接続文字列",
            "type": "string",
            "pattern": "^mysql://.*"
        },
        "API_KEY": {
            "description": "外部APIの認証キー",
            "type": "string",
            "minLength": 32
        },
        "DEBUG_MODE": {
            "description": "デバッグ出力を有効にする",
            "type": "boolean",
            "default": false
        }
    }
}
```

### 環境ファイル (env.json / env.dist.json)

実際の設定値:

```json
{
    "$schema": "./env.schema.json",
    "DATABASE_URL": "mysql://user:pass@localhost/mydb",
    "API_KEY": "1234567890abcdef1234567890abcdef",
    "DEBUG_MODE": true
}
```

## 運用フローとベストプラクティス

### 開発環境

1. **スキーマ作成**: すべての必要な変数、型、制約を`env.schema.json`で定義
2. **デフォルト値**: チーム内で共有可能なデフォルト値を`env.dist.json`に定義（gitリポジトリにコミット可能）
3. **ローカルオーバーライド**: 個人環境固有の値を`env.json`に定義（`.gitignore`に追加）
4. **読み込みプロセス**:
    - EnvJsonはまず既存の環境変数を検証
    - 検証に失敗した場合、`env.json`を読み込み
    - `env.json`が存在しない場合、`env.dist.json`にフォールバック

### 本番環境

1. **CI/CD設定**:
    - デプロイ時に`env.dist.json`を削除（本番では不要）
    - `env.json`は含めない（`.gitignore`に追加済み）
2. **設定**: すべての環境変数を本番環境に直接設定
3. **検証**: EnvJsonがすべての必須変数が存在し有効であることを検証

## .envからの変換

既存の`.env`ファイルをJSON形式に変換:

```bash
bin/ini2json .env
```

これにより`env.schema.json`と`env.dist.json`の両方が生成されます。

## コマンドラインツール: envjson

`envjson`コマンドラインツールは様々な環境との連携をサポートします:

```bash
# 現在のシェルに変数を読み込む
source <(bin/envjson)

# カスタムディレクトリを指定
source <(bin/envjson -d ./config)

# PHP-FPM形式で出力: env[FOO] = "foo1"
bin/envjson -d ./config -o fpm > .env.fpm

# INI形式で出力: FOO="foo1"
bin/envjson -d ./config -o ini > env.ini

# シェル形式で出力: export FOO="foo1"
bin/envjson -d ./config -o shell > env.sh
```

### オプション

```
  -d --dir=DIR     envファイルを含むディレクトリ（デフォルト: カレントディレクトリ）
  -f --file=FILE   読み込むJSONファイル名（デフォルト: env.json）
  -o --output=FMT  出力形式: shell, fpm, ini（デフォルト: shell）
  -v --verbose     詳細メッセージを表示
  -q --quiet       警告メッセージを抑制
  -h --help        ヘルプメッセージを表示
```

## なぜ.envではなくJSONか？

- **型安全性**: アプリケーション起動前に型や制約条件を検証
- **豊富なドキュメント**: スキーマに説明、例、制約を直接追加可能
- **IDE対応**: エディタでのJSONスキーマバリデーションによる優れたツーリング
- **制約**: JsonSchemaの制約適用

## Story

<img src="https://koriym.github.io/Koriym.EnvJson/images/story/ja4.jpg" width="500px" alt="env.json story">

## Link

- [GitHub](https://github.com/koriym/Koriym.EnvJson)
- [Packagist](https://packagist.org/packages/koriym/env-json)
