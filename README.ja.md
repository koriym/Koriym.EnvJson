# Koriym.EnvJson

`.env`ファイルに変わってJSONで環境変数をセットします。
[JSONスキーマ](https://json-schema.org/) によるバリデーションは、JSONだけでなく環境変数に対しても行われます。

## インストール

    composer require koriym/env-json

## 使用方法

`env.schema.json`スキーマファイルのディレクトリを指定して`load()`します。

```php
(new EnvJson())->load($dir);
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

```
./vendor/bin/ini2json .env
```
