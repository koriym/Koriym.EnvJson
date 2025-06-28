# Koriym.EnvJson

<img src="https://koriym.github.io/Koriym.EnvJson/images/envjson.jpg" width="400px" alt="env.json logo">

[English](./README.md)

`.env`ファイルの代わりにJSONを使用し、[JSONスキーマ](https://json-schema.org/)による組み込みバリデーション機能を備えた、環境変数への現代的なアプローチです。

## 特徴

- **JSONスキーマバリデーション**による型安全な環境変数
- **スキーマの説明と制約**を通じたより良いドキュメント化
- **変換ツール**で`.env`からJSON形式への移行をサポート
- **CLIユーティリティ**でシェル統合と様々な出力形式に対応
- **フォールバック機能**で開発環境をサポート

## インストール

```bash
composer require koriym/env-json
```

## 基本的な使用方法

```php
// 環境変数を読み込んでバリデーション
$env = (new EnvJson())->load(__DIR__);

// オブジェクトプロパティとしてアクセス
echo $env->DATABASE_URL;

// または従来のgetenv()を使用
echo getenv('DATABASE_URL');
```

## 設定ファイル

### JSONスキーマ (env.schema.json)

型、説明、制約とともに環境変数を定義します：

```json
{
   "$schema": "http://json-schema.org/draft-07/schema#",
   "type": "object",
   "required": [
      "DATABASE_URL", "API_KEY"
   ],
   "properties": {
      "DATABASE_URL": {
         "description": "データベースの接続文字列",
         "pattern": "^mysql://.*"
      },
      "API_KEY": {
         "description": "外部API用の認証キー",
         "minLength": 32
      },
      "DEBUG_MODE": {
         "description": "デバッグ出力を有効にする (true/false)",
         "enum": ["true", "false"],
         "default": "false"
      },
      "PORT": {
         "description": "サーバーのポート番号",
         "pattern": "^[0-9]+$",
         "default": "3000"
      }
   }
}
```

### 環境ファイル (env.json)

実際の設定値：

```json
{
   "$schema": "./env.schema.json",
   "DATABASE_URL": "mysql://user:pass@localhost/mydb",
   "API_KEY": "1234567890abcdef1234567890abcdef",
   "DEBUG_MODE": "true",
   "PORT": "8080"
}
```

## ⚠️ 重要：環境変数の型制約について

**環境変数は常に文字列として扱われます。** JSONスキーマで文字列以外の型を指定すると、バリデーションエラーが発生します。

> **警告**: スキーマで`"type": "boolean"`、`"type": "integer"`、または`"type": "number"`を指定すると、環境変数が数値やBoolean風の値を含んでいても常に文字列であるため、EnvJsonはバリデーションエラーを発生させます。

### ❌ これらはバリデーションエラーを引き起こします

```json
{
    "DEBUG_MODE": {
        "type": "boolean",
        "default": false
    },
    "PORT": {
        "type": "integer",
        "default": 3000
    },
    "TIMEOUT": {
        "type": "number",
        "default": 30.5
    }
}
```

**エラーメッセージ**: 値は文字列（例：`"3000"`）になりますが、スキーマは整数を期待するため、バリデーションが失敗します。

### ✅ 正しいアプローチ

```json
{
    "DEBUG_MODE": {
        "description": "デバッグ出力を有効にする (true/false)",
        "enum": ["true", "false"],
        "default": "false"
    },
    "PORT": {
        "description": "サーバーのポート番号",
        "pattern": "^[0-9]+$",
        "default": "3000"
    }
}
```

### 推奨パターン

**Boolean値の場合：**
```json
"FEATURE_ENABLED": {
    "enum": ["true", "false"],
    "default": "false"
}
```

**数値の場合：**
```json
"TIMEOUT": {
    "pattern": "^[0-9]+$",
    "default": "30"
}
```

**列挙値の場合：**
```json
"LOG_LEVEL": {
    "enum": ["debug", "info", "warning", "error"],
    "default": "info"
}
```

## ワークフロー & ベストプラクティス

### 開発環境

1. **スキーマ作成**: 必要な変数、パターン、制約をすべて含む`env.schema.json`を定義
2. **デフォルト値**: チームで共有できるデフォルト/サンプル値を含む`env.dist.json`を作成
3. **ローカルオーバーライド**: 特定のローカル値を含む`env.json`を作成（`.gitignore`に追加）
4. **読み込みプロセス**:
   - EnvJsonは最初に既存の環境変数をバリデーションしようとします
   - バリデーションが失敗した場合、存在すれば`env.json`を読み込みます
   - `env.json`が見つからない場合、`env.dist.json`にフォールバックします

### 本番環境

1. **CI/CD設定**:
   - デプロイ時に`env.dist.json`を削除（本番では不要）
   - `env.json`を含めない（`.gitignore`に含めるべき）
2. **設定**: すべての環境変数を本番環境で直接設定
3. **バリデーション**: EnvJsonは必要な変数がすべて存在し、有効であることを検証

## .envからの変換

既存の`.env`ファイルをJSON形式に変換：

```bash
bin/ini2json .env
```

これにより`env.schema.json`と`env.dist.json`の両方のファイルが生成されます。

## コマンドラインツール: envjson

`envjson`コマンドラインツールは、様々な環境との統合を支援します：

```bash
# 現在のシェルに変数を読み込み
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
  -d --dir=DIR     環境ファイルを含むディレクトリ（デフォルト：現在のディレクトリ）
  -f --file=FILE   読み込むJSONファイル名（デフォルト：env.json）
  -o --output=FMT  出力形式：shell、fpm、ini（デフォルト：shell）
  -v --verbose     詳細なメッセージを表示
  -q --quiet       すべての警告メッセージを抑制
  -h --help        ヘルプメッセージを表示
```

## なぜ.envではなくJSONなのか？

- **型安全性**: アプリケーション開始前に型と制約をバリデーション
- **豊富なドキュメント**: スキーマに直接説明、例、制約を追加
- **IDE サポート**: エディタでのJSONスキーマバリデーションによる優れたツールサポート
- **制約データ**: バリデーション用のJSONスキーマの制約機能

## ストーリー

<img src="https://koriym.github.io/Koriym.EnvJson/images/story/en1.jpg" width="500px" alt="env.json story">

## リンク

- [GitHub](https://github.com/koriym/Koriym.EnvJson)
- [Packagist](https://packagist.org/packages/koriym/env-json)
