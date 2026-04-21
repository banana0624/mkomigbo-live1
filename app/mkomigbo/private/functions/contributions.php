<?php
declare(strict_types=1);

/**
 * /private/functions/contributions.php
 *
 * Contribution intake + moderation helpers.
 *
 * Assumptions:
 * - db() exists and returns PDO
 * - this file is included after bootstrap
 * - uploads live outside public web root
 */

if (!function_exists('mk_contribution_allowed_extensions')) {
    function mk_contribution_allowed_extensions(): array
    {
        return [
            'pdf',
            'doc',
            'docx',
            'txt',
            'rtf',
            'jpg',
            'jpeg',
            'png',
            'webp',
            'mp3',
            'wav',
            'mp4',
            'zip',
        ];
    }
}

if (!function_exists('mk_contribution_allowed_mimes')) {
    function mk_contribution_allowed_mimes(): array
    {
        return [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            'txt'  => ['text/plain'],
            'rtf'  => ['application/rtf', 'text/rtf'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'mp3'  => ['audio/mpeg', 'audio/mp3'],
            'wav'  => ['audio/wav', 'audio/x-wav'],
            'mp4'  => ['video/mp4'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
        ];
    }
}

if (!function_exists('mk_contribution_subject_areas')) {
    function mk_contribution_subject_areas(): array
    {
        return ['history', 'culture', 'religion', 'language', 'slavery', 'general'];
    }
}

if (!function_exists('mk_contribution_submission_types')) {
    function mk_contribution_submission_types(): array
    {
        return [
            'comment',
            'criticism',
            'correction',
            'addition',
            'question',
            'source_submission',
            'media_submission',
            'general_feedback',
        ];
    }
}

if (!function_exists('mk_contribution_position_types')) {
    function mk_contribution_position_types(): array
    {
        return ['support', 'criticism', 'correction', 'neutral', 'question'];
    }
}

if (!function_exists('mk_contribution_statuses')) {
    function mk_contribution_statuses(): array
    {
        return ['pending', 'approved', 'rejected', 'deleted'];
    }
}

if (!function_exists('mk_contribution_upload_base_dir')) {
    function mk_contribution_upload_base_dir(): string
    {
        return dirname(__DIR__) . '/uploads/contributions';
    }
}

if (!function_exists('mk_contribution_normalize_string')) {
    function mk_contribution_normalize_string($value): string
    {
        return trim((string)($value ?? ''));
    }
}

if (!function_exists('mk_contribution_client_ip')) {
    function mk_contribution_client_ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $candidate = trim(explode(',', $candidate)[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('mk_contribution_page_path_allowed')) {
    function mk_contribution_page_path_allowed(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] !== '/') {
            return false;
        }

        $allowedPrefixes = [
            '/subjects/',
            '/contribute/',
            '/contact/',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (strncmp($path, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('mk_contribution_public_ref')) {
    function mk_contribution_public_ref(): string
    {
        return 'CTRB-' . strtoupper(bin2hex(random_bytes(6)));
    }
}

if (!function_exists('mk_contribution_safe_storage_name')) {
    function mk_contribution_safe_storage_name(string $ext): string
    {
        return 'ctrb_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    }
}

if (!function_exists('mk_contribution_detect_mime')) {
    function mk_contribution_detect_mime(string $tmpPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }

        $mime = (string)finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        return trim($mime);
    }
}

if (!function_exists('mk_contribution_validate')) {
    function mk_contribution_validate(array $post, array $files): array
    {
        $errors = [];
        $data = [];

        $honeypot = mk_contribution_normalize_string($post['website'] ?? '');
        if ($honeypot !== '') {
            $errors['general'] = 'Submission rejected.';
            return [$errors, $data];
        }

        $data['contributor_name'] = mk_contribution_normalize_string($post['contributor_name'] ?? '');
        if ($data['contributor_name'] === '' || mb_strlen($data['contributor_name']) < 2 || mb_strlen($data['contributor_name']) > 150) {
            $errors['contributor_name'] = 'Enter a valid name.';
        }

        $data['contributor_email'] = mk_contribution_normalize_string($post['contributor_email'] ?? '');
        if (
            $data['contributor_email'] === '' ||
            mb_strlen($data['contributor_email']) > 190 ||
            !filter_var($data['contributor_email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors['contributor_email'] = 'Enter a valid email address.';
        }

        $data['subject_area'] = mk_contribution_normalize_string($post['subject_area'] ?? '');
        if (!in_array($data['subject_area'], mk_contribution_subject_areas(), true)) {
            $errors['subject_area'] = 'Choose a valid subject area.';
        }

        $data['page_path'] = mk_contribution_normalize_string($post['page_path'] ?? '');
        if (
            $data['page_path'] === '' ||
            mb_strlen($data['page_path']) > 255 ||
            !mk_contribution_page_path_allowed($data['page_path'])
        ) {
            $errors['page_path'] = 'Enter a valid page path.';
        }

        $data['submission_type'] = mk_contribution_normalize_string($post['submission_type'] ?? '');
        if (!in_array($data['submission_type'], mk_contribution_submission_types(), true)) {
            $errors['submission_type'] = 'Choose a valid submission type.';
        }

        $data['position_type'] = mk_contribution_normalize_string($post['position_type'] ?? '');
        if (
            $data['position_type'] !== '' &&
            !in_array($data['position_type'], mk_contribution_position_types(), true)
        ) {
            $errors['position_type'] = 'Choose a valid position.';
        }

        $data['title'] = mk_contribution_normalize_string($post['title'] ?? '');
        if ($data['title'] === '' || mb_strlen($data['title']) < 3 || mb_strlen($data['title']) > 220) {
            $errors['title'] = 'Enter a valid title.';
        }

        $data['message_text'] = trim((string)($post['message_text'] ?? ''));
        $messageLen = mb_strlen($data['message_text']);
        if ($messageLen < 20 || $messageLen > 10000) {
            $errors['message_text'] = 'Message must be between 20 and 10000 characters.';
        }

        $data['consent'] = mk_contribution_normalize_string($post['consent'] ?? '');
        if ($data['consent'] !== '1') {
            $errors['consent'] = 'Consent is required.';
        }

        $data['submitter_ip'] = mk_contribution_client_ip();
        $data['user_agent'] = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (mb_strlen($data['user_agent']) > 500) {
            $data['user_agent'] = mb_substr($data['user_agent'], 0, 500);
        }

        $data['status'] = 'pending';
        $data['public_ref'] = mk_contribution_public_ref();

        $data['file'] = null;

        if (isset($files['attachment']) && is_array($files['attachment'])) {
            $file = $files['attachment'];

            if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                    $errors['attachment'] = 'File upload failed.';
                } else {
                    $originalName = (string)($file['name'] ?? '');
                    $tmpPath = (string)($file['tmp_name'] ?? '');
                    $size = (int)($file['size'] ?? 0);

                    if ($originalName === '' || $tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        $errors['attachment'] = 'Invalid uploaded file.';
                    } else {
                        if ($size < 1 || $size > 10 * 1024 * 1024) {
                            $errors['attachment'] = 'File must not exceed 10 MB.';
                        }

                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        if ($ext === '' || !in_array($ext, mk_contribution_allowed_extensions(), true)) {
                            $errors['attachment'] = 'File type not allowed.';
                        } else {
                            $mime = mk_contribution_detect_mime($tmpPath);
                            $allowedMimes = mk_contribution_allowed_mimes()[$ext] ?? [];

                            if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
                                $errors['attachment'] = 'Detected file type is not allowed.';
                            }
                        }

                        if (!isset($errors['attachment'])) {
                            $data['file'] = [
                                'original_name' => $originalName,
                                'tmp_name'      => $tmpPath,
                                'size'          => $size,
                                'ext'           => $ext,
                                'mime'          => mk_contribution_detect_mime($tmpPath),
                            ];
                        }
                    }
                }
            }
        }

        return [$errors, $data];
    }
}

if (!function_exists('mk_contribution_rate_limit_check')) {
    function mk_contribution_rate_limit_check(string $ip, string $email): array
    {
        $pdo = db();
        $errors = [];

        if ($ip !== '') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM contributions
                WHERE submitter_ip = :ip
                  AND created_at >= (UTC_TIMESTAMP() - INTERVAL 1 HOUR)
            ");
            $stmt->execute([':ip' => $ip]);
            $countIpHour = (int)$stmt->fetchColumn();

            if ($countIpHour >= 5) {
                $errors['general'] = 'Too many submissions from this IP. Please try again later.';
            }
        }

        if ($email !== '') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM contributions
                WHERE contributor_email = :email
                  AND created_at >= (UTC_TIMESTAMP() - INTERVAL 1 DAY)
            ");
            $stmt->execute([':email' => $email]);
            $countEmailDay = (int)$stmt->fetchColumn();

            if ($countEmailDay >= 10) {
                $errors['general'] = 'Too many submissions for this email today. Please try again later.';
            }
        }

        return [empty($errors), $errors];
    }
}

if (!function_exists('mk_contribution_store_upload')) {
    function mk_contribution_store_upload(array $file): array
    {
        $baseDir = mk_contribution_upload_base_dir();
        $year = gmdate('Y');
        $month = gmdate('m');
        $targetDir = $baseDir . '/' . $year . '/' . $month;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Failed to create upload directory.');
        }

        $storageName = mk_contribution_safe_storage_name((string)$file['ext']);
        $targetPath = $targetDir . '/' . $storageName;

        if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        $sha256 = hash_file('sha256', $targetPath);
        if ($sha256 === false) {
            @unlink($targetPath);
            throw new RuntimeException('Failed to hash uploaded file.');
        }

        $relativePath = $year . '/' . $month . '/' . $storageName;

        return [
            'file_original_name' => (string)$file['original_name'],
            'file_storage_name'  => $storageName,
            'file_relative_path' => $relativePath,
            'file_mime'          => (string)$file['mime'],
            'file_ext'           => (string)$file['ext'],
            'file_size'          => (int)$file['size'],
            'file_sha256'        => $sha256,
        ];
    }
}

if (!function_exists('mk_contribution_create')) {
    function mk_contribution_create(array $data): int
    {
        $pdo = db();

        $sql = "
            INSERT INTO contributions (
                public_ref,
                contributor_name,
                contributor_email,
                subject_area,
                page_path,
                submission_type,
                position_type,
                title,
                message_text,
                file_original_name,
                file_storage_name,
                file_relative_path,
                file_mime,
                file_ext,
                file_size,
                file_sha256,
                status,
                moderation_note,
                submitter_ip,
                user_agent
            ) VALUES (
                :public_ref,
                :contributor_name,
                :contributor_email,
                :subject_area,
                :page_path,
                :submission_type,
                :position_type,
                :title,
                :message_text,
                :file_original_name,
                :file_storage_name,
                :file_relative_path,
                :file_mime,
                :file_ext,
                :file_size,
                :file_sha256,
                :status,
                :moderation_note,
                :submitter_ip,
                :user_agent
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':public_ref'         => $data['public_ref'],
            ':contributor_name'   => $data['contributor_name'],
            ':contributor_email'  => $data['contributor_email'],
            ':subject_area'       => $data['subject_area'],
            ':page_path'          => $data['page_path'],
            ':submission_type'    => $data['submission_type'],
            ':position_type'      => $data['position_type'] !== '' ? $data['position_type'] : null,
            ':title'              => $data['title'],
            ':message_text'       => $data['message_text'],
            ':file_original_name' => $data['file_original_name'] ?? null,
            ':file_storage_name'  => $data['file_storage_name'] ?? null,
            ':file_relative_path' => $data['file_relative_path'] ?? null,
            ':file_mime'          => $data['file_mime'] ?? null,
            ':file_ext'           => $data['file_ext'] ?? null,
            ':file_size'          => $data['file_size'] ?? null,
            ':file_sha256'        => $data['file_sha256'] ?? null,
            ':status'             => 'pending',
            ':moderation_note'    => null,
            ':submitter_ip'       => $data['submitter_ip'] !== '' ? $data['submitter_ip'] : null,
            ':user_agent'         => $data['user_agent'] !== '' ? $data['user_agent'] : null,
        ]);

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('mk_contribution_find')) {
    function mk_contribution_find(int $id): ?array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM contributions WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('mk_contribution_moderation_log_list')) {
    function mk_contribution_moderation_log_list(int $contributionId, int $limit = 100): array
    {
        $pdo = db();
        $limit = max(1, min(500, $limit));

        $stmt = $pdo->prepare("
            SELECT
                id,
                contribution_id,
                action_type,
                staff_user_id,
                note_text,
                created_at
            FROM contribution_moderation_log
            WHERE contribution_id = :contribution_id
            ORDER BY id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([
            ':contribution_id' => $contributionId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('mk_contribution_moderation_log_list')) {
    function mk_contribution_moderation_log_list(int $contributionId, int $limit = 100): array
    {
        $pdo = db();
        $limit = max(1, min(500, $limit));

        $stmt = $pdo->prepare("
            SELECT
                id,
                contribution_id,
                action_type,
                staff_user_id,
                note_text,
                created_at
            FROM contribution_moderation_log
            WHERE contribution_id = :contribution_id
            ORDER BY id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([
            ':contribution_id' => $contributionId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('mk_contribution_list')) {
    function mk_contribution_list(string $status = 'pending', int $limit = 50): array
    {
        $pdo = db();
        $limit = max(1, min(200, $limit));

        if ($status === 'all') {
            $sql = "SELECT * FROM contributions ORDER BY FIELD(status,'pending','approved','rejected','deleted'), created_at DESC LIMIT {$limit}";
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        if (!in_array($status, mk_contribution_statuses(), true)) {
            $status = 'pending';
        }

        $stmt = $pdo->prepare("SELECT * FROM contributions WHERE status = :status ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute([':status' => $status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('mk_contribution_set_status')) {
    function mk_contribution_set_status(int $id, string $status, int $staffUserId, ?string $note = null): bool
    {
        if (!in_array($status, ['approved', 'rejected', 'deleted'], true)) {
            return false;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE contributions
                SET
                    status = :status,
                    moderation_note = :note,
                    reviewed_by = :staff_user_id,
                    reviewed_at = UTC_TIMESTAMP()
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute([
                ':status'        => $status,
                ':note'          => $note,
                ':staff_user_id' => $staffUserId,
                ':id'            => $id,
            ]);

            if ($stmt->rowCount() < 1) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare("
                INSERT INTO contribution_moderation_log (
                    contribution_id,
                    action_type,
                    staff_user_id,
                    note_text
                ) VALUES (
                    :contribution_id,
                    :action_type,
                    :staff_user_id,
                    :note_text
                )
            ");
            $stmt->execute([
                ':contribution_id' => $id,
                ':action_type'     => $status,
                ':staff_user_id'   => $staffUserId,
                ':note_text'       => $note,
            ]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

if (!function_exists('mk_contribution_stream_file_for_staff')) {
    function mk_contribution_stream_file_for_staff(int $id): void
    {
        $row = mk_contribution_find($id);
        if (!$row || empty($row['file_relative_path'])) {
            http_response_code(404);
            exit('File not found.');
        }

        $baseDir = mk_contribution_upload_base_dir();
        $fullPath = $baseDir . '/' . ltrim((string)$row['file_relative_path'], '/');

        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('File not found.');
        }

        $mime = (string)($row['file_mime'] ?: 'application/octet-stream');
        $downloadName = (string)($row['file_original_name'] ?: basename($fullPath));
        $size = filesize($fullPath);
        if ($size === false) {
            $size = 0;
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)$size);
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $downloadName) . '"');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $fp = fopen($fullPath, 'rb');
        if (!$fp) {
            http_response_code(500);
            exit('Cannot open file.');
        }

        while (!feof($fp)) {
            echo (string)fread($fp, 8192);
        }
        fclose($fp);
        exit;
    }
}