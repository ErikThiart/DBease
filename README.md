# DBease

A small PHP library that wraps PDO for MySQL, so everyday queries take one line instead of ten.

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/erikthiart/dbease)](https://packagist.org/packages/erikthiart/dbease)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777bb4)](composer.json)
[![Issues](https://img.shields.io/github/issues/ErikThiart/DBease)](https://github.com/ErikThiart/DBease/issues)

```php
$db = new DBease\Database(['database' => 'shop', 'username' => 'app', 'password' => 'secret']);

$db->insert('users', ['name' => 'Ada', 'email' => 'ada@example.com']);
$active = $db->findAll('users', ['status' => 'active']);
```

Values are always sent as prepared-statement parameters. Exceptions are on, results come back as associative arrays, and every query is logged for debugging.

## Why use it

- You want prepared statements and PDO exceptions without writing the boilerplate each time.
- You do not want an ORM, models or migrations. DBease only wraps the SQL you would write anyway.
- It is one class with no dependencies, so it is easy to read, vendor or fork.

## Install

```bash
composer require erikthiart/dbease
```

Requires PHP 7.2 or newer with the `pdo_mysql` extension.

## Connect

Pass settings to the constructor, or set environment variables. The constructor reads them in this order: the array, then the environment, then the defaults.

| Array key  | Environment variable | Default       |
|------------|----------------------|---------------|
| `host`     | `DB_HOST`            | `127.0.0.1`   |
| `port`     | `DB_PORT`            | `3306`        |
| `database` | `DB_NAME`            | `my_database` |
| `username` | `DB_USER`            | `my_user`     |
| `password` | `DB_PASS`            | empty         |
| `charset`  | `DB_CHARSET`         | `utf8mb4`     |

```php
use DBease\Database;

$db = new Database([
    'host'     => '127.0.0.1',
    'database' => 'shop',
    'username' => 'app',
    'password' => getenv('SHOP_DB_PASSWORD'),
]);
```

A failed connection throws `PDOException`.

## Usage

### Create

```php
$db->insert('users', ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]);

$db->insertMultiple('users', [
    ['name' => 'Ada',   'email' => 'ada@example.com',   'age' => 36],
    ['name' => 'Grace', 'email' => 'grace@example.com', 'age' => 45],
]);

$id = $db->lastInsertId();
```

### Read

```php
$user        = $db->find('users', ['id' => 1]);              // one row, or null
$activeUsers = $db->findAll('users', ['status' => 'active']); // array of rows
$total       = $db->count('users', ['status' => 'active']);
```

### Update and delete

```php
$db->update('users', ['status' => 'active'], ['id' => 1]);
$db->delete('users', ['id' => 1]);
```

### Raw SQL

```php
$rows = $db->raw(
    'SELECT * FROM products WHERE category = :category',
    ['category' => 'Electronics']
);
```

### Pagination

```php
$rows = $db
    ->select('name, price')
    ->limit(5)
    ->offset(10)
    ->fetchWithOffset('products', ['category' => 'Electronics']);
```

### Schema checks and debugging

```php
$db->tableExists('users');          // true or false
$db->columnExists('users', 'email'); // true or false
$db->getQueryLog();                  // every query run so far
```

## Security notes

- **Values** are bound as parameters, so they are safe to pass user input.
- **Table and column names** are placed into the SQL as written. Never pass user input as a table name, a column name or a `select()` field list. Use a fixed list in your code.
- Keep database credentials in environment variables or protected config, not in source control.

## Method reference

| Method | Returns |
|--------|---------|
| `insert($table, $data)` | `bool` |
| `insertMultiple($table, $rows)` | `bool` |
| `update($table, $data, $where)` | `bool` |
| `delete($table, $conditions)` | `bool` |
| `find($table, $conditions = [])` | row array or `null` |
| `findAll($table, $conditions = [])` | array of rows |
| `count($table, $conditions = [])` | `int` |
| `raw($sql, $params = [])` | array of rows |
| `select()`, `limit()`, `offset()`, `fetchWithOffset()` | chainable builder |
| `tableExists()`, `columnExists()` | `bool` |
| `lastInsertId()`, `getQueryLog()` | last ID, log array |

## Contributing

Issues and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GNU General Public License v3.0 or later. See [LICENSE](LICENSE).

## Author

Built and maintained by [Erik Thiart](https://github.com/ErikThiart), a freelance PHP developer in South Africa. Questions or work enquiries: [erikthiart.com](https://erikthiart.com).
