# timmit/phpcs-framework-rules

PHPCS rules shared by applications on an aItem based framework.

## Install

```bash
composer require --dev timmit/phpcs-framework-rules
```

Allow the PHPCS Composer installer in the root `composer.json`:

```json
{
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}
```

The installer registers the `TiMMiT` standard. Check with `vendor/bin/phpcs -i`.

## Usage

`PSR1` includes `Squiz.Classes.ValidClassName`, which only allows PascalCase. Exclude that sniff and use this standard instead:

```xml
<rule ref="PSR1">
    <exclude name="Squiz.Classes.ValidClassName"/>
</rule>

<rule ref="TiMMiT"/>
```

## Class names

| Kind | Name |
| --- | --- |
| Abstract class | lowercase `a`, then PascalCase: `aItem`, `aConnector` |
| Trait | lowercase `t`, then PascalCase: `tItem` |
| Class, interface, enum | PascalCase: `Sim` |

`aitem` and `titem` are still reported: the part after the prefix must be PascalCase.
