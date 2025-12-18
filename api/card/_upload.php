<?php
declare(strict_types=1);

function card_upload_image_file(array $file, int $uid): array {
  if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    card_json_out(['ok'=>false,'error'=>'NO_FILE'], 400);
  }

  $max = 6 * 1024 * 1024;
  if (($file['size'] ?? 0) > $max) {
    card_json_out(['ok'=>false,'error'=>'FILE_TOO_LARGE'], 413);
  }

  $mime = mime_content_type($file['tmp_name']) ?: '';
  $map = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
  if (!isset($map[$mime])) {
    card_json_out(['ok'=>false,'error'=>'INVALID_TYPE'], 415);
  }
  $ext = $map[$mime];

  $relDir = '/uploads/cards/' . $uid . '/' . date('Y/m') . '/';
  $absDir = $_SERVER['DOCUMENT_ROOT'] . $relDir;
  if (!is_dir($absDir) && !mkdir($absDir, 0775, true)) {
    card_json_out(['ok'=>false,'error'=>'MKDIR_FAILED'], 500);
  }

  $name = bin2hex(random_bytes(16)) . '.' . $ext;
  $abs = $absDir . $name;
  if (!move_uploaded_file($file['tmp_name'], $abs)) {
    card_json_out(['ok'=>false,'error'=>'UPLOAD_FAILED'], 500);
  }

  return ['url' => $relDir . $name, 'mime' => $mime];
}
