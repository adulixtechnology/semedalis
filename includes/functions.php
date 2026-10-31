<?php
/**
 * Simedalis — shared helpers.
 *
 * Every shared helper is declared EXACTLY ONCE here and nowhere else (notably
 * not in config/config.php) to avoid "Cannot redeclare function" fatals.
 * Included by config/config.php after BASE_PATH is defined.
 */

/**
 * Escape a value for safe HTML output.
 */
function e($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Build a site-relative URL.
 *   url()           -> BASE_PATH
 *   url('a/b.php')  -> BASE_PATH.'/a/b.php'
 * A leading slash on $p is trimmed, so url('/a/b.php') and url('a/b.php') match.
 */
function url(string $p = ''): string {
    return BASE_PATH . ($p === '' ? '' : '/' . ltrim($p, '/'));
}

/**
 * The per-session CSRF token, generated once with 256 bits of entropy.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Hidden input to embed the CSRF token in a POST form.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the POST CSRF token in constant time. On mismatch the request is
 * aborted (no state change) and the event is logged. Returns true on success.
 */
function verify_csrf(): bool {
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || $sent === '' || $known === '' || !hash_equals($known, $sent)) {
        error_log('CSRF token mismatch from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(403);
        echo 'Invalid or missing security token. Please go back, reload the page, and try again.';
        exit;
    }
    return true;
}

/**
 * Resolve a stored image/screenshot value to a renderable URL.
 *
 * Branches:
 *   (a) http(s)://...         -> pass through (absolute URL)
 *   (b) //host/...            -> protocol-relative -> reject (return '')
 *   (c) /abs/...              -> leading-slash absolute path -> pass through
 *   (d) path/with/dir.ext     -> relative path (legacy rows) -> BASE_PATH.'/'.$file
 *                                (NO basename() — preserves 'assets/images/x.jpg')
 *   (e) barefile.ext          -> new upload -> BASE_PATH.'/backend/uploads/'.$type.'/'.$file
 *   (f) '' / null             -> ''
 *
 * Why each branch is safe:
 *   (a)/(b) handle or prevent cross-host loads; (b) drops protocol-relative
 *   rather than loading a third-party URL in an <img>; (c) is for legacy
 *   relative paths only — new uploads never produce a slash, so (c) fires only
 *   on pre-existing data; (d) only fires when there is NO slash, so it cannot
 *   traverse directories ('..' needs a slash). basename() is deliberately NOT
 *   applied to (c) (it would corrupt 'assets/images/background.jpg').
 *
 * @param string $type  uploads subfolder: slider|gallery|donations|announcements
 * @param string|null $file  the stored image_path / screenshot value
 */
function image_url(string $type, $file): string {
    if ($file === '' || $file === null) {
        return '';
    }
    $file = (string) $file;
    if (preg_match('#^https?://#i', $file)) {
        return $file;                          // (a) absolute URL -> pass through
    }
    if (strpos($file, '//') === 0) {
        return '';                             // (b) protocol-relative -> reject
    }
    if ($file[0] === '/') {
        return $file;                          // (c) leading-slash absolute path
    }
    if (strpos($file, '/') !== false) {
        return BASE_PATH . '/' . $file;        // (d) relative path with a dir (NO basename())
    }
    return BASE_PATH . '/backend/uploads/' . $type . '/' . $file; // (e) bare filename
}

/**
 * Store a submitted value so a form field can be repopulated after a
 * validation failure. The page re-renders the form inline in the same request.
 */
function set_old(string $key, $value): void {
    if (!isset($GLOBALS['_simed_old'])) {
        $GLOBALS['_simed_old'] = [];
    }
    $GLOBALS['_simed_old'][$key] = $value;
}

/**
 * Read a previously stored "old" input value, or $default if none.
 */
function old(string $key, string $default = ''): string {
    return isset($GLOBALS['_simed_old'][$key]) ? (string) $GLOBALS['_simed_old'][$key] : $default;
}

/**
 * Format a timestamp / datetime string for display.
 */
function format_date($ts, string $fmt = 'M j, Y'): string {
    $t = strtotime((string) $ts);
    return $t === false ? '' : date($fmt, $t);
}

/**
 * One-time flash messages stored in the session.
 *   flash($key, $val) -> store a message under $key (returns null)
 *   flash($key)       -> read AND clear the message under $key ('' if none)
 */
function flash(string $key, $val = null) {
    if ($val !== null) {
        $_SESSION['_flash'][$key] = $val;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return $v;
}

/**
 * Guard for admin pages: redirect to the admin login when there is no valid
 * admin session. Call at the top of every admin page (via admin_header.php).
 */
function require_admin(): void {
    if (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

/**
 * Validate and move an uploaded image file (single-owner: design fix #3).
 *
 * SINGLE-OWNER RULE:
 *   This helper ONLY validates the upload and moves the temp file to
 *   backend/uploads/<type>/, then RETURNS the bare filename to store. It does
 *   NOT touch the database. THE CALLING PAGE owns the single PDO INSERT/UPDATE
 *   that stores that filename, and — if that DB write fails — the calling page
 *   is responsible for unlink()-ing the moved file so no orphan is left behind.
 *
 * Return contract (string):
 *   ''                      no file was uploaded (UPLOAD_ERR_NO_FILE). The
 *                           caller decides whether that is an error (required
 *                           field) or acceptable (optional field).
 *   '<digits>_<hex>.<ext>'  SUCCESS: the bare filename that was moved. Always
 *                           starts with a digit (from time()).
 *   '<human-readable text>' FAILURE: an error message (starts with a letter,
 *                           never looks like a filename). Nothing is moved.
 *
 * Distinguish a success from an error: the return is a filename iff it is
 * non-empty and its first character is a digit, i.e. ctype_digit($r[0]).
 *
 * @param string $field  the $_FILES key (e.g. 'screenshot', 'image')
 * @param string $type   uploads subfolder: slider|gallery|donations|announcements
 */
function validate_upload(string $field, string $type): string {
    static $allowedTypes = ['slider' => 1, 'gallery' => 1, 'donations' => 1, 'announcements' => 1];
    if (!isset($allowedTypes[$type])) {
        return 'Invalid upload destination.';
    }

    if (!isset($_FILES[$field])) {
        return ''; // field not present in the submitted form
    }
    $f = $_FILES[$field];

    // No file selected -> empty (caller decides required vs optional).
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    // Any other PHP upload error -> failure.
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'The upload failed. Please try again.';
    }

    // Size cap (5 MB), enforced server-side regardless of php.ini.
    if (($f['size'] ?? 0) <= 0) {
        return 'The uploaded file is empty.';
    }
    if ($f['size'] > 5000000) {
        return 'The file is too large (max 5 MB).';
    }

    // Extension whitelist (case-insensitive; lowercased on save).
    $ext = strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION));
    $extOk = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];
    if (!isset($extOk[$ext])) {
        return 'Only JPG, JPEG, PNG, GIF and WebP images are allowed.';
    }

    // MIME check via finfo (defends against renamed extensions).
    $tmp = (string) ($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return 'The uploaded file is not valid.';
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : false;
    if ($finfo) {
        finfo_close($finfo);
    }
    $mimeOk = ['image/jpeg' => 1, 'image/png' => 1, 'image/gif' => 1, 'image/webp' => 1];
    if ($mime === false || !isset($mimeOk[$mime])) {
        return 'The file is not a valid image.';
    }

    // Safe, collision-proof bare filename.
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $destDir = dirname(__DIR__) . '/backend/uploads/' . $type;
    $dest = $destDir . '/' . $filename;
    if (!is_dir($destDir) || !move_uploaded_file($tmp, $dest)) {
        return 'Could not save the uploaded file. Please try again.';
    }
    return $filename; // SUCCESS: bare filename; caller does the DB write.
}

/**
 * Validate and store a video upload for the gallery.
 * Returns the bare filename on success, '' if no file, or an error string.
 * Size limit: 100 MB. Allowed: mp4, mov, webm, avi.
 */
function validate_upload_video(string $field): string {
    if (!isset($_FILES[$field])) return '';

    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return 'The upload failed. Please try again.';
    if (($f['size'] ?? 0) <= 0) return 'The uploaded file is empty.';
    if ($f['size'] > 104857600) return 'The video is too large (max 100 MB).';

    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
    $extOk = ['mp4' => 1, 'mov' => 1, 'webm' => 1, 'avi' => 1];
    if (!isset($extOk[$ext])) return 'Only MP4, MOV, WebM and AVI videos are allowed.';

    $tmp = (string)($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) return 'The uploaded file is not valid.';

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $tmp) : false;
    if ($finfo) finfo_close($finfo);

    $mimeOk = [
        'video/mp4'       => 1,
        'video/quicktime' => 1,
        'video/webm'      => 1,
        'video/x-msvideo' => 1,
        'application/octet-stream' => 1, // some browsers report this for video
    ];
    if ($mime === false || !isset($mimeOk[$mime])) return 'The file is not a valid video.';

    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir  = dirname(__DIR__) . '/backend/uploads/gallery';
    $dest     = $destDir . '/' . $filename;

    if (!is_dir($destDir) || !move_uploaded_file($tmp, $dest)) {
        return 'Could not save the uploaded video. Please try again.';
    }
    return $filename;
}
