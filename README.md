# MySQL-PHP-PreparedStatements

A simple MySQLi prepared statements wrapper for insert, update, select, and delete queries.

## Installation

### 1. Using Composer (recommended)

```bash
composer require spbcodes/mysqlps
```

Then in your PHP code:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use App\Database\PreparedDB;

$mysqli = new mysqli("localhost", "user", "pass", "dbname");
$db = new PreparedDB($mysqli);
```

---

### 2. Manual inclusion

Clone the repository:

```bash
git clone https://github.com/spbcodes/MySQL-PHP-PreparedStatements.git
```

Include the class directly:

```php
<?php
require_once __DIR__ . '/MySQL-PHP-PreparedStatements/src/Database/PreparedDB.php';

use App\Database\PreparedDB;

$mysqli = new mysqli("localhost", "user", "pass", "dbname");
$db = new PreparedDB($mysqli);
```

---

## Usage Examples

### Insert

```php
$sql = "INSERT INTO users SET #fields# ON DUPLICATE KEY UPDATE #dupes#";
$db->insert($sql, [
    'username' => 'steve',
    'email'    => 'steve@example.com'
], [
    'last_updated' => date('Y-m-d H:i:s')
]);
```

### Select

```php
$sql = "SELECT * FROM users WHERE id = ||1||";
$result = $db->select($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
}
```

### Update / Delete

```php
$db->update("UPDATE users SET #fields# WHERE id = ||1||", ['email'=>'new@example.com']);
$db->delete("DELETE FROM users WHERE id = ||1||");
```

---

## Notes

- `#fields#` and `#dupes#` are placeholders replaced by the class.  
- `insert()`, `update()`, `delete()` → return `true` or `false`.  
- `select()` → returns a `mysqli_result` object (or `false` on failure).  

---

## mysqli_ps.php contains procedural style versions of the functions in the class

## AI Assistance

Portions of this library were refactored and optimized with the assistance of an AI language model.  

The original functions and overall design were created and directed by Steve Burgess. All copyright and licensing remains with the human author.

## License

MIT

