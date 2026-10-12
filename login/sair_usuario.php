```php
<?php
session_start();

unset(
    $_SESSION['id_usuario'],
    $_SESSION['nome_usuario']
);

session_destroy();

header('Location: ../index.php');
exit;
```
