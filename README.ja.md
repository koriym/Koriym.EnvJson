# Koriym.EnvJson

`.env`ファイルに変わってJSONで環境変数をセットします。
[JSONスキーマ](https://json-schema.org/) によるバリデーションは、JSONだけでなく環境変数に対しても行われます。

## インストール

    composer require koriym/env-json

## 使用方法

`env.schema.json`スキーマファイルのディレクトリを指定して`load()`します。

```php
$env = (new EnvJson())->load($dir);
assert($env instanceof stdClass);
// 環境変数はプロパティとしてアクセス、またはgetenv()で取得できます。
assert($env->FOO === 'foo1');
assert(getenv('FOO') === 'foo1');
```

 1) 既に環境変数が設定されている場合は、`env.schema.json`によって検証されます。
 2) 検証できない場合は`env.json` または `env.dist.json` の値を検証して環境変数にします。


$dir/env.json

```json
{
    "$schema": "./env.schema.json",
    "FOO": "foo1",
    "BAR": "bar1"
}
```

$dir/env.schema.json

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "FOO", "BAR"
    ],
    "properties": {
        "FOO": {
            "description": "Foo's value",
            "minLength": 3
        },
        "BAR": {
            "description": "Bar's value",
            "enum": ["bar1", "bar2"]
        }
    }
}
```

`.env`ファイルと比べて、より適切なドキュメンテーションや制約を表すことができます。

## iniファイルを変換

`ini2json`でenv(ini)ファイルからJSONとそのJSONスキーマファイルが生成されます。

```bash
bin/ini2json .env
```

## コマンドラインツール: envjson

`env.schema.json` によって検証された `env.json` (または `env.dist.json`) から環境変数を読み込みます。

**使用方法:**

```bash
# 現在のシェルに変数を読み込む
source <(bin/envjson)

# envファイルが含まれるディレクトリを指定
source <(bin/envjson -d ./config)

# FPM形式で出力
bin/envjson -d ./config -o fpm > .env.fpm

# INI形式で出力
bin/envjson -d ./config -o ini > env.ini
```

**オプション:**

```bash
  -d --dir=DIR     env.json と env.schema.json ファイルが含まれるディレクトリ (デフォルト: カレントディレクトリ)
  -f --file=FILE   読み込むJSONファイル名 (デフォルト: env.json)
  -o --output=FMT  出力形式: shell fpm ini (デフォルト: shell)
  -v --verbose     詳細メッセージを表示
  -q --quiet       全ての警告メッセージを抑制
  -h --help        このヘルプメッセージを表示
```
