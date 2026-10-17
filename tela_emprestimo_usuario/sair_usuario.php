
```php
<?php
session_start();

$_SESSION = [];

session_destroy();

header('Location: ../tela_principal/index.php');
exit;
```
