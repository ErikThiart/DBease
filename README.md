# DBease: A Lightweight Database Abstraction Library for MySQL

[![License](https://img.shields.io/badge/License-GNU-blue.svg)](LICENSE)
[![Latest Version](https://img.shields.io/github/v/release/erikthiart/dbease)](https://github.com/erikthiart/dbease/releases)
[![Packagist](https://img.shields.io/packagist/v/erikthiart/dbease)](https://packagist.org/packages/erikthiart/dbease)
[![GitHub Issues](https://img.shields.io/github/issues/erikthiart/dbease)](https://github.com/erikthiart/dbease/issues)

DBease is a lightweight and easy-to-use PHP database abstraction library designed for MySQL, using the power of PDO. It simplifies common database operations and offers a flexible query builder, making it a valuable tool for developers who want to interact with databases efficiently.

## Features

- **Simplified CRUD Operations:** Perform Create, Read, Update, and Delete (CRUD) operations with ease.
- **Flexible Query Builder:** Build complex queries with a fluent interface.
- **Custom SQL Execution:** Execute raw SQL queries when needed.
- **Exception Handling:** Robust error handling with detailed exceptions.
- **Query Logging:** Keep track of executed queries for debugging.
- **Table and Column Existence Checks:** Verify the existence of tables and columns.
- **Composer-Friendly:** Easily install and manage using Composer.

## Installation

DBease can be installed via [Composer](https://getcomposer.org/):

```bash
composer require erikthiart/dbease
```

## Getting Started

### Initialize DBease

```php
use DBease\Database;

// Initialize the database connection
$db = new Database();
```

### Insert Data

```php
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
];

// Insert into the 'users' table
$db->insert('users', $data);
```

### Update Data

```php
$updateData = ['status' => 'active'];

// Update records in the 'users' table where 'id' is 1
$db->update('users', $updateData, ['id' => 1]);
```

### Query Data

```php
// Find a single record from the 'users' table where 'id' is 1
$user = $db->find('users', ['id' => 1]);

// Find all active users
$activeUsers = $db->findAll('users', ['status' => 'active']);
```

### Execute Raw SQL

```php
// Execute a custom SQL query
$sql = "SELECT * FROM products WHERE category = :category";
$params = ['category' => 'Electronics'];
$results = $db->raw($sql, $params);
```

### Query Builder

```php
// Build complex queries
$results = $db
    ->select('name, price')
    ->limit(5)
    ->offset(10)
    ->fetchWithOffset('products', ['category' => 'Electronics']);
```

### More Examples and Documentation

For more examples and detailed documentation, please refer to the [DBease Wiki](https://github.com/erikthiart/dbease/wiki).

## Contributing

Contributions are welcome! Please check the [contribution guidelines](CONTRIBUTING.md) for details.

## License

DBease is open-source software licensed under the [GNU General Public License](LICENSE).

## Credits

DBease is developed and maintained by [Erik Thiart]([https://github.com/yourusername](https://github.com/ErikThiart)).

## Support

If you have questions or need assistance, feel free to [open an issue](https://github.com/erikthiart/dbease/issues).

---
