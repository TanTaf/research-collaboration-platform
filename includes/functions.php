<?php

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), (string)$posted)) {
        http_response_code(419);
        die('Security check failed. Please go back and submit the form again.');
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) { return $user; }
    $loaded = true;

    if (!isLoggedIn()) { return null; }

    $user = fetchOne('SELECT * FROM users WHERE id = ? AND status = "active"', [$_SESSION['user_id']]);
    if (!$user) {
        session_destroy();
        return null;
    }
    return $user;
}

function userId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('<p style="font-family:system-ui;padding:60px;text-align:center">403 &mdash; You do not have permission to view this page.</p>');
    }
}

function isAdmin(): bool
{
    $u = currentUser();
    return $u !== null && $u['role'] === 'admin';
}

function fullName(array $user): string
{
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

function initials(array $user): string
{
    return strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
}

function avatarUrl(?array $user): string
{
    if (!empty($user['avatar']) && is_file(UPLOAD_PATH . '/avatars/' . $user['avatar'])) {
        return url('uploads/avatars/' . rawurlencode($user['avatar']));
    }
    return '';
}

function avatar(?array $user, int $size = 40, string $extraClass = ''): string
{
    if (!$user) { return ''; }
    $src = avatarUrl($user);
    $style = 'width:' . $size . 'px;height:' . $size . 'px;';

    if ($src !== '') {
        return '<img class="avatar ' . e($extraClass) . '" style="' . $style . '" src="' . e($src)
             . '" alt="' . e(fullName($user)) . '">';
    }

    $palette = ['#12305B', '#F57C1F', '#2F6FB3', '#B9541B', '#3E5C76', '#8A4B08'];
    $bg      = $palette[((int)$user['id']) % count($palette)];
    $font    = max(10, (int)round($size * 0.38));

    return '<span class="avatar avatar-initials ' . e($extraClass) . '" style="' . $style
         . 'background:' . $bg . ';font-size:' . $font . 'px" title="' . e(fullName($user)) . '">'
         . e(initials($user)) . '</span>';
}

function roleBadge(string $role): string
{
    $map = ['student' => 'Student', 'faculty' => 'Faculty', 'admin' => 'Admin'];
    return '<span class="badge badge-' . e($role) . '">' . e($map[$role] ?? $role) . '</span>';
}

function timeAgo(?string $datetime): string
{
    if (!$datetime) { return ''; }
    $ts   = strtotime($datetime);
    $diff = time() - $ts;

    if ($diff < 60)     { return 'just now'; }
    if ($diff < 3600)   { return floor($diff / 60) . 'm ago'; }
    if ($diff < 86400)  { return floor($diff / 3600) . 'h ago'; }
    if ($diff < 172800) { return 'Yesterday'; }
    if ($diff < 604800) { return floor($diff / 86400) . 'd ago'; }
    return date('d M Y', $ts);
}

function fmtDate(?string $datetime, string $format = 'd M Y'): string
{
    return $datetime ? date($format, strtotime($datetime)) : '--';
}

function fmtBytes(int $bytes): string
{
    if ($bytes <= 0) { return '0 KB'; }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    $i = min($i, count($units) - 1);
    return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1) . ' ' . $units[$i];
}

function fmtCount(int $n): string
{
    if ($n >= 1000000) { return round($n / 1000000, 1) . 'M'; }
    if ($n >= 1000)    { return round($n / 1000, 1) . 'k'; }
    return (string)$n;
}

function excerpt(?string $text, int $limit = 140): string
{
    $text = trim(strip_tags((string)$text));
    if (mb_strlen($text) <= $limit) { return $text; }
    return mb_substr($text, 0, $limit) . '...';
}

function allInterests(): array
{
    static $cache = null;
    if ($cache === null) { $cache = fetchAll('SELECT * FROM interests ORDER BY name'); }
    return $cache;
}

function allSkills(): array
{
    static $cache = null;
    if ($cache === null) { $cache = fetchAll('SELECT * FROM skills ORDER BY name'); }
    return $cache;
}

function userInterestIds(int $uid): array
{
    return array_map('intval', array_column(
        fetchAll('SELECT interest_id FROM user_interests WHERE user_id = ?', [$uid]), 'interest_id'));
}

function userInterestNames(int $uid): array
{
    return array_column(fetchAll(
        'SELECT i.name FROM interests i
         JOIN user_interests ui ON ui.interest_id = i.id
         WHERE ui.user_id = ? ORDER BY i.name', [$uid]), 'name');
}

function projectInterestNames(int $pid): array
{
    return array_column(fetchAll(
        'SELECT i.name FROM interests i
         JOIN project_interests pi ON pi.interest_id = i.id
         WHERE pi.project_id = ? ORDER BY i.name', [$pid]), 'name');
}

function projectSkillNames(int $pid): array
{
    return array_column(fetchAll(
        'SELECT s.name FROM skills s
         JOIN project_skills ps ON ps.skill_id = s.id
         WHERE ps.project_id = ? ORDER BY s.name', [$pid]), 'name');
}

function matchScore(int $userA, int $userB): int
{
    $a = userInterestIds($userA);
    $b = userInterestIds($userB);
    if (!$a || !$b) { return 0; }

    $shared = count(array_intersect($a, $b));
    $union  = count(array_unique(array_merge($a, $b)));
    if ($union === 0) { return 0; }

    $score = ($shared / $union) * 100;

    $ua = fetchOne('SELECT department FROM users WHERE id = ?', [$userA]);
    $ub = fetchOne('SELECT department FROM users WHERE id = ?', [$userB]);
    if ($ua && $ub && $ua['department'] === $ub['department']) { $score += 12; }

    return (int)min(99, round($score + ($shared * 6)));
}

function profileCompletion(array $u): int
{
    $checks = [
        !empty($u['bio']),
        !empty($u['department']),
        !empty($u['phone']),
        !empty($u['avatar']),
        !empty($u['linkedin']),
        !empty($u['availability']),
        count(userInterestIds((int)$u['id'])) >= 3,
        fetchValue('SELECT COUNT(*) FROM user_skills WHERE user_id = ?', [$u['id']]) >= 2,
    ];
    $done = count(array_filter($checks));
    return (int)round($done / count($checks) * 100);
}

function notify(int $userId, string $text, ?string $link = null): void
{
    if ($userId <= 0) { return; }
    q('INSERT INTO notifications (user_id, text, link) VALUES (?,?,?)', [$userId, $text, $link]);
}

function unreadNotifications(int $uid): int
{
    return (int)fetchValue('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$uid]);
}

function unreadMessages(int $uid): int
{
    return (int)fetchValue('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$uid]);
}

function handleUpload(string $field, string $folder, array $allowedExt, int $maxBytes = 10485760): ?array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Upload failed (error code ' . $file['error'] . ').');
        return null;
    }
    if ($file['size'] > $maxBytes) {
        setFlash('error', 'That file is larger than ' . fmtBytes($maxBytes) . '.');
        return null;
    }

    $original = basename($file['name']);
    $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        setFlash('error', 'Only these file types are allowed: ' . implode(', ', $allowedExt) . '.');
        return null;
    }

    $dir = UPLOAD_PATH . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }

    $stored = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $stored)) {
        setFlash('error', 'The file could not be saved to the uploads folder.');
        return null;
    }

    return [$stored, $original, (int)$file['size']];
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function isActive(string $page): bool
{
    return basename($_SERVER['PHP_SELF']) === $page;
}

function statusLabel(string $status): string
{
    return match ($status) {
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'pending'     => 'Pending Approval',
        default       => ucfirst(str_replace('_', ' ', $status)),
    };
}

function pubVersions(int $pubId): array
{
    return fetchAll(
        'SELECT v.*, u.first_name, u.last_name
         FROM publication_versions v
         JOIN users u ON u.id = v.created_by
         WHERE v.publication_id = ?
         ORDER BY v.version_no DESC', [$pubId]);
}

function pubVersionCount(int $pubId): int
{
    return (int)fetchValue('SELECT COUNT(*) FROM publication_versions WHERE publication_id = ?', [$pubId]);
}

function addPubVersion(int $pubId, array $pub, ?string $note, int $userId): int
{
    $next = (int)fetchValue(
        'SELECT COALESCE(MAX(version_no), 0) + 1 FROM publication_versions WHERE publication_id = ?',
        [$pubId], 1);

    q('INSERT INTO publication_versions
         (publication_id, version_no, title, abstract, file_name, file_path, file_size, change_note, created_by)
       VALUES (?,?,?,?,?,?,?,?,?)',
      [$pubId, $next, $pub['title'], $pub['abstract'], $pub['file_name'],
       $pub['file_path'], (int)$pub['file_size'], ($note !== '' ? $note : null), $userId]);

    q('UPDATE publications SET current_version = ? WHERE id = ?', [$next, $pubId]);

    return $next;
}

function versionBadge(int $n): string
{
    return '<span class="version-badge" title="Version ' . $n . '">v' . $n . '</span>';
}

function deletePubFiles(int $pubId): void
{
    $paths = array_column(
        fetchAll('SELECT file_path FROM publication_versions
                  WHERE publication_id = ? AND file_path IS NOT NULL', [$pubId]), 'file_path');

    $live = fetchValue('SELECT file_path FROM publications WHERE id = ?', [$pubId], null);
    if ($live) { $paths[] = $live; }

    foreach (array_unique($paths) as $rel) {
        $abs = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'publications' . DIRECTORY_SEPARATOR . $rel;
        if (is_file($abs)) { @unlink($abs); }
    }
}
