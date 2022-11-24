# Koriym.EnvJson

環境変数をセットするためにINIファイルの代わりにJSONを使います。
[JSONスキーマ](https://json-schema.org/) によるバリデーションは、JSONだけでなくENVの値に対しても行われます。

## インストール

    composer require koriym/env-json

## 使用方法

`env.schema.json`スキーマファイルのディレクトリを指定して`export()`します。

プロダクション環境など環境変数が既に設定されている場合は、その環境変数が正しか`env.schema.json` によってバリデートされます。
そうでない場合は、`env.json` または `env.dist.json` が読み込まれ、検証され、環境変数の値としてエクスポートされます。

```php
(new EnvJson)->export($dir);
```

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

## iniファイルを変換

`ini2json`でenv(ini)ファイルからJSONとそのJSONスキーマファイルが生成されます。

```
./vendor/bin/ini2json .env
```
