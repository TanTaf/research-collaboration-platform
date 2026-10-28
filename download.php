<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me   = currentUser();
$uid  = (int)$me['id'];
$type = (string)input('type', 'publication');
$id   = (int)input('id', 0);

$absPath  = null;
$niceName = 'download';

if ($type === 'publication') {
    $row = fetchOne('SELECT * FROM publications WHERE id = ?', [$id]);
    if ($row && $row['file_path']) {
        if ($row['status'] === 'draft' && (int)$row['user_id'] !== $uid && !isAdmin()) {
            http_response_code(403);
            die('This file is private.');
        }
        $absPath  = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'publications' . DIRECTORY_SEPARATOR . $row['file_path'];
        $niceName = $row['file_name'];
        q('UPDATE publications SET downloads = downloads + 1 WHERE id = ?', [$id]);
    }

} elseif ($type === 'version') {

    $row = fetchOne(
        'SELECT v.*, p.status AS pub_status, p.user_id AS owner_id, p.id AS pub_id
         FROM publication_versions v
         JOIN publications p ON p.id = v.publication_id
         WHERE v.id = ?', [$id]);

    if ($row && $row['file_path']) {
        if ($row['pub_status'] === 'draft' && (int)$row['owner_id'] !== $uid && !isAdmin()) {
            http_response_code(403);
            die('This file is private.');
        }
        $absPath  = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'publications' . DIRECTORY_SEPARATOR . $row['file_path'];
        $niceName = $row['file_name'];
        q('UPDATE publications SET downloads = downloads + 1 WHERE id = ?', [(int)$row['pub_id']]);
    }

} elseif ($type === 'project') {
    $row = fetchOne('SELECT * FROM project_files WHERE id = ?', [$id]);
    if ($row) {
        $allowed = fetchValue('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?',
                              [$row['project_id'], $uid]);
        if (!$allowed && !isAdmin()) {
            http_response_code(403);
            die('You must be a member of this project to download its files.');
        }
        $absPath  = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $row['file_path'];
        $niceName = $row['file_name'];
    }
}

if (!$absPath || !is_file($absPath)) {
    http_response_code(404);
    setFlash('error', 'That file is not available. It may have been removed, or this is demo data with no real attachment.');
    redirect($type === 'project' ? 'projects.php' : 'repository.php');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($niceName) . '"');
header('Content-Length: ' . filesize($absPath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($absPath);
exit;
