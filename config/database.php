<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        die(
            '<div style="font-family:system-ui;max-width:640px;margin:80px auto;padding:28px;'
          . 'border:1px solid #e3e6ea;border-radius:12px;background:#fff">'
          . '<h2 style="color:#12305B;margin:0 0 10px">Database connection failed</h2>'
          . '<p style="color:#5b6472;line-height:1.6">PHP could not reach MySQL. Check that:</p>'
          . '<ol style="color:#5b6472;line-height:1.8">'
          . '<li><strong>Apache</strong> and <strong>MySQL</strong> are both running in the XAMPP Control Panel.</li>'
          . '<li>You imported <code>database/rch_uiu.sql</code> in phpMyAdmin.</li>'
          . '<li>The credentials in <code>config/config.php</code> match your MySQL setup.</li>'
          . '</ol>'
          . '<p style="color:#98a1ae;font-size:13px">MySQL says: ' . htmlspecialchars($e->getMessage()) . '</p>'
          . '</div>'
        );
    }

    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchOne(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function fetchAll(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function fetchValue(string $sql, array $params = [], $default = 0)
{
    $val = q($sql, $params)->fetchColumn();
    return $val === false ? $default : $val;
}
