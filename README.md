# Koriym.EnvJson

Create env variable files in JSON. Type it in with completion by schema and validate it using standard [JsonSchema](https://json-schema.org/) rules, not the library's own rules.

## Installation

    composer require koriym/env-json

## Usage

Specify the directory of the `env.schema.json` schema file to `export()`.

If environment variables are already set, such as in a production environment, they are validated by `env.schema.json` to ensure that they are correct.

Otherwise, `env.json` or `env.dist.json` will be loaded, validated, and exported as environment variable values.

## 使用方法

`env.schema.json`スキーマファイルのディレクトリを指定して`export()`します。

プロダクション環境など環境変数が既に設定されている場合は、その環境変数が正しか`env.schema.json` によってバリデートされます。
そうでない場合は、`env.json` または `env.dist.json` が読み込まれ、検証され、環境変数の値としてエキスポートされます。

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
```
