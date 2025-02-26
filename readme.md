# Codeception Module Yii2

A Codeception module for Yii2 framework

[![Actions Status](https://github.com/Codeception/module-yii2/workflows/CI/badge.svg)](https://github.com/Codeception/module-yii2/actions)
[![Latest Stable Version](https://poser.pugx.org/codeception/module-yii2/v/stable)](https://github.com/Codeception/module-yii2/releases)
[![Total Downloads](https://poser.pugx.org/codeception/module-yii2/downloads)](https://packagist.org/packages/codeception/module-yii2)
[![License](https://poser.pugx.org/codeception/module-yii2/license)](/LICENSE)

## Installation

```
composer require "codeception/module-yii2" --dev
```

## Documentation

- [Getting started](https://codeception.com/for/yii)
- [Module documentation](https://codeception.com/docs/modules/Yii2)
- [Changelog](https://github.com/Codeception/module-yii2/releases)

## License

`Codeception Module Yii2` is open-sourced software licensed under the [MIT](/LICENSE) License.

© Codeception PHP Testing Framework

## Test with docker-compose

```bash
docker-compose run php8.4 composer install
docker-compose run php8.4 vendor/bin/codecept build
docker-compose run php8.4 vendor/bin/codecept run
```
