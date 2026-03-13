<?php
/* Configuration */
$rootPath = __DIR__;
$dateFormat = "M d, Y H:i";
$uploadPassword = "1350"; // Required password for uploads & deletes

/* Handle AJAX Actions (Upload & Delete) */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // 1. Validate Password
    if (!isset($_POST['password']) || $_POST['password'] !== $uploadPassword) {
        echo json_encode(['success' => false, 'error' => 'Invalid or missing password.']);
        exit;
    }

    // --- UPLOAD ACTION ---
    if ($_POST['action'] === 'upload') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Unknown upload error.';
            if (isset($_FILES['file']['error'])) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE: $errorMsg = 'File exceeds upload_max_filesize in php.ini.'; break;
                    case UPLOAD_ERR_FORM_SIZE: $errorMsg = 'File exceeds MAX_FILE_SIZE directive.'; break;
                    case UPLOAD_ERR_PARTIAL: $errorMsg = 'The uploaded file was only partially uploaded.'; break;
                    case UPLOAD_ERR_NO_FILE: $errorMsg = 'No file was uploaded.'; break;
                }
            }
            echo json_encode(['success' => false, 'error' => $errorMsg]); exit;
        }

        $file = $_FILES['file'];
        $filename = basename($file['name']);
        
        // Security: Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        
        // Security: Block dangerous file extensions
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $blockedExts = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'htaccess', 'sh', 'exe', 'cgi', 'pl', 'jsp', 'asp', 'aspx', 'bat', 'cmd'];
        if (in_array($ext, $blockedExts)) {
            echo json_encode(['success' => false, 'error' => 'Security: This file type is not allowed.']); exit;
        }

        $destination = $rootPath . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($destination)) {
            $filename = time() . '_' . $filename;
            $destination = $rootPath . DIRECTORY_SEPARATOR . $filename;
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => true, 'message' => 'File uploaded successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to move the uploaded file. Check permissions.']);
        }
        exit;
    }

    // --- DELETE ACTION ---
    if ($_POST['action'] === 'delete') {
        if (!isset($_POST['filename']) || empty($_POST['filename'])) {
            echo json_encode(['success' => false, 'error' => 'No file specified.']); exit;
        }

        $filename = basename($_POST['filename']);
        // Prevent deleting core script components
        if ($filename === '.' || $filename === '..' || $filename === 'index.php' || $filename === 'readme.md') {
            echo json_encode(['success' => false, 'error' => 'This file cannot be deleted.']); exit;
        }

        $targetPath = $rootPath . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($targetPath) && is_file($targetPath)) {
            if (unlink($targetPath)) {
                echo json_encode(['success' => true, 'message' => 'File successfully deleted!']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not delete file. Check permissions.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'File not found.']);
        }
        exit;
    }
}

/* Logic: Get, Filter, and Sort Files */
$items = scandir($rootPath);
$folders = [];
$files = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..' || $item === 'index.php' || $item === 'nahalparkan' || $item === 'arontech') continue;
    
    $fullPath = $rootPath . DIRECTORY_SEPARATOR . $item;
    $isDir = is_dir($fullPath);
    
    $data = [
        'name' => $item,
        'path' => urlencode($item),
        'size' => $isDir ? count(scandir($fullPath)) - 2 . ' items' : humanFilesize(filesize($fullPath)),
        'date' => date($dateFormat, filemtime($fullPath)),
        'type' => $isDir ? 'dir' : pathinfo($fullPath, PATHINFO_EXTENSION),
        'is_dir' => $isDir
    ];

    if ($isDir) {
        $folders[] = $data;
    } else {
        $files[] = $data;
    }
}

// Helper: File Size
function humanFilesize($bytes, $decimals = 2) {
    if ($bytes == 0) return '0 B';
    $sz = 'BKMGTP';
    $factor = floor((strlen((string)$bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

// Helper: SVG Icon Generator
function getIcon($type, $isDir) {
    $svgStart = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="file-icon ';
    $svgEnd = '</svg>';

    if ($isDir) {
        return $svgStart . 'folder-icon"><path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h15ZM1.5 10.146V6a3 3 0 0 1 3-3h5.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 0 1 3 3v1.146A4.483 4.483 0 0 0 19.5 9h-15a4.483 4.483 0 0 0-3 1.146Z" />' . $svgEnd;
    }
    
    switch (strtolower($type)) {
        case 'html': case 'php': case 'css': case 'js': case 'json': case 'xml':
            return $svgStart . 'file-code"><path fill-rule="evenodd" d="M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4Zm9.25 2.25a.75.75 0 0 1 .75.75v2.25h2.25a.75.75 0 0 1 0 1.5H13v2.25a.75.75 0 0 1-1.5 0V10.75h-2.25a.75.75 0 0 1 0-1.5h2.25V7a.75.75 0 0 1 .75-.75ZM9 15.75a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />' . $svgEnd;
        case 'png': case 'jpg': case 'jpeg': case 'gif': case 'svg': case 'webp':
            return $svgStart . 'file-img"><path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />' . $svgEnd;
        case 'pdf':
            return $svgStart . 'file-pdf"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />' . $svgEnd;
        case 'zip': case 'rar': case 'tar': case 'gz': case '7z':
            return $svgStart . 'file-zip"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd" />' . $svgEnd;
        case 'mp3': case 'wav': case 'flac': case 'ogg':
            return $svgStart . 'file-audio"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" />' . $svgEnd;
        case 'mp4': case 'mkv': case 'avi': case 'mov':
            return $svgStart . 'file-video"><path d="M4.5 4.5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h8.25a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3H4.5ZM19.94 18.75l-2.69-2.69V7.94l2.69-2.69c.944-.945 2.56-.276 2.56 1.06v11.38c0 1.336-1.616 2.005-2.56 1.06Z" />' . $svgEnd;
        case 'txt': case 'md': case 'rtf':
            return $svgStart . 'file-txt"><path fill-rule="evenodd" d="M3 4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4Zm4 3.75a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1-.75-.75Zm0 4.5a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1-.75-.75Zm0 4.5a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />' . $svgEnd;
        default:
            return $svgStart . 'file-default"><path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875ZM12.75 12V5.25a2.25 2.25 0 0 0-2.25-2.25H5.625a.375.375 0 0 0-.375.375v17.25c0 .207.168.375.375.375h12.75c.207 0 .375-.168.375-.375V12h-6Z" clip-rule="evenodd" /><path d="M14.25 5.25a5.23 5.23 0 0 0-1.279-3.434 9.768 9.768 0 0 1 6.963 6.963A5.23 5.23 0 0 0 16.5 7.5h-1.875a.375.375 0 0 1-.375-.375V5.25Z" />' . $svgEnd;
    }
}

// Function to render a single card HTML
function renderCard($file) {
    $icon = getIcon($file['type'], $file['is_dir']);
    $extBadge = $file['is_dir'] ? 'DIR' : strtoupper($file['type']);
    if (!$extBadge) $extBadge = 'FILE';

    $downloadBtn = '';
    if (!$file['is_dir']) {
        $downloadBtn = '
        <a href="'.htmlspecialchars($file['path']).'" download="'.htmlspecialchars($file['name']).'" class="download-btn" title="Force Download">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        </a>';
    }

    echo '
    <div class="card" data-name="'.htmlspecialchars(strtolower($file['name'])).'">
        <a href="'.htmlspecialchars($file['path']).'" class="card-link">
            <div class="icon-box">'.$icon.'</div>
            <div class="info">
                <span class="name" title="'.htmlspecialchars($file['name']).'">'.htmlspecialchars($file['name']).'</span>
                <span class="meta">
                    <span class="ext-badge">'.htmlspecialchars($extBadge).'</span> '.$file['size'].' &bull; '.$file['date'].'
                </span>
            </div>
        </a>
        '.$downloadBtn.'
    </div>';
}

// Merge folders first, then files
$allData = array_merge($folders, $files);

// Handle AJAX Search Requests
if (isset($_GET['ajax'])) {
    $q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
    $filtered = [];
    
    foreach ($allData as $file) {
        if (empty($q) || strpos(strtolower($file['name']), $q) !== false) {
            $filtered[] = $file;
        }
    }
    
    if (empty($filtered)) {
        echo '<div class="no-results">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="empty-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
            </svg><br>
            No files found matching your search.
        </div>';
    } else {
        foreach ($filtered as $file) {
            renderCard($file);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files &bull; <?php echo htmlspecialchars(basename(__DIR__)); ?></title>
    <link rel="icon" type="image/svg+xml" href="https://rezasadid.ir/files/rs.png" />

<style>
    :root {
        --bg-color: #0f172a;
        --card-bg: rgba(30, 41, 59, 0.7);
        --card-hover: rgba(51, 65, 85, 0.9);
        --text-main: #f8fafc;
        --text-sub: #94a3b8;
        --accent: #3b82f6;
        --accent-glow: rgba(59, 130, 246, 0.5);
        --border: rgba(255, 255, 255, 0.08);
    }

    * { box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
    
    body {
        margin: 0;
        background-color: var(--bg-color);
        background-image: 
            radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
        background-attachment: fixed;
        color: var(--text-main);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        min-height: 100vh;
    }

    header {
        padding: 40px 20px 20px;
        text-align: center;
        max-width: 1200px;
        margin: 0 auto;
    }

    h1 {
        font-weight: 300;
        margin: 0 0 10px;
        font-size: 2rem;
        letter-spacing: -1px;
    }
    h1 span { font-weight: 600; color: var(--accent); }

    .path-badge {
        background: rgba(255,255,255,0.1);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        color: var(--text-sub);
        display: inline-block;
        margin-bottom: 25px;
        backdrop-filter: blur(4px);
    }

    /* Smaller, elegant Upload Button in Top Right */
    .upload-btn-top {
        position: fixed;
        top: 15px;
        right: 15px;
        width: 36px;
        height: 36px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-main);
        cursor: pointer;
        z-index: 1000;
        backdrop-filter: blur(12px);
        transition: all 0.2s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .upload-btn-top:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px var(--accent-glow);
    }
    .upload-btn-top svg { width: 18px; height: 18px; }

    /* Controls Wrapper */
    .controls-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Search Bar */
    .search-container { position: relative; width: 100%; }
    .search-icon {
        position: absolute; left: 15px; top: 50%;
        transform: translateY(-50%); color: var(--text-sub);
        width: 20px; height: 20px;
    }
    .search-loader {
        position: absolute; right: 15px; top: 50%;
        margin-top: -10px; width: 20px; height: 20px;
        border: 2px solid var(--border); border-top-color: var(--accent);
        border-radius: 50%; animation: spin 0.8s linear infinite; display: none;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    #searchInput {
        width: 100%; background: rgba(255,255,255,0.05);
        border: 1px solid var(--border); padding: 12px 45px 12px 45px;
        border-radius: 12px; color: white; font-size: 1rem; transition: all 0.3s ease;
    }
    #searchInput:focus {
        background: rgba(255,255,255,0.1); border-color: var(--accent);
        box-shadow: 0 0 15px var(--accent-glow);
    }

    /* View Controls */
    .view-controls { display: flex; gap: 10px; }
    .view-btn {
        background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border);
        color: var(--text-sub); padding: 10px; border-radius: 10px;
        cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;
    }
    .view-btn.active, .view-btn:hover { background: rgba(255, 255, 255, 0.1); color: var(--accent); border-color: var(--accent); }
    .view-btn svg { width: 22px; height: 22px; }

    /* Grid/List Layout */
    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px 50px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; position: relative; }
    .grid.list-view { grid-template-columns: 1fr; }

    /* File Card */
    .card {
        background: var(--card-bg); border: 1px solid var(--border);
        border-radius: 16px; display: flex; align-items: stretch;
        transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        overflow: visible; backdrop-filter: blur(12px); position: relative;
    }
    .card:hover {
        transform: translateY(-4px); background: var(--card-hover);
        border-color: rgba(255,255,255,0.2); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
    }
    .card-link { display: flex; align-items: center; text-decoration: none; padding: 20px; flex-grow: 1; overflow: hidden; }
    .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
    .file-icon { width: 32px; height: 32px; }

    /* Icon Colors */
    .folder-icon { color: #facc15; } .file-code { color: #ec4899; } .file-img { color: #a855f7; }
    .file-pdf { color: #ef4444; } .file-zip { color: #f59e0b; } .file-audio { color: #06b6d4; }
    .file-video { color: #f43f5e; } .file-txt { color: #94a3b8; } .file-default { color: #64748b; }

    .info { overflow: hidden; flex-grow: 1; }
    .name {
        display: block; color: var(--text-main); font-weight: 500; font-size: 0.95rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 8px;
    }
    .meta { display: flex; align-items: center; color: var(--text-sub); font-size: 0.75rem; }
    .ext-badge {
        background: rgba(255, 255, 255, 0.1); color: var(--text-main);
        padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 600;
        margin-right: 6px; text-transform: uppercase;
    }

    /* Download Button */
    .download-btn {
        display: flex; align-items: center; justify-content: center; padding: 0 20px;
        color: var(--text-sub); border-left: 1px solid var(--border); transition: all 0.2s ease; cursor: pointer;
    }
    .download-btn:hover { color: var(--accent); background: rgba(255,255,255,0.05); }
    .download-btn svg { width: 22px; height: 22px; transition: transform 0.2s; }
    
    /* Delete Countdown UI */
    .delete-countdown {
        position: absolute; right: 10px; bottom: 10px;
        background: linear-gradient(135deg, #ef4444, #b91c1c); color: white;
        padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.5); pointer-events: none; z-index: 50;
        animation: bounceIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes bounceIn {
        0% { transform: scale(0.5); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Empty State */
    .no-results { grid-column: 1 / -1; text-align: center; padding: 50px; color: var(--text-sub); }
    .empty-icon { width: 64px; height: 64px; margin-bottom: 10px; opacity: 0.5; display: inline-block; }

    /* Custom Modern Modal */
    .custom-modal {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center;
        backdrop-filter: blur(10px); animation: fadeIn 0.2s;
    }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .modal-content {
        background: #1e293b; border: 1px solid var(--border);
        border-top: 4px solid var(--accent); border-radius: 16px;
        padding: 35px 30px 30px; width: 90%; max-width: 400px;
        text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.6); position: relative;
    }
    .modal-close-icon {
        position: absolute; top: 12px; right: 12px; background: transparent;
        border: none; color: var(--text-sub); cursor: pointer; padding: 5px; transition: color 0.2s;
    }
    .modal-close-icon:hover { color: #ef4444; }
    .modal-close-icon svg { width: 22px; height: 22px; }
    
    .modal-content h2 { margin-top: 0; margin-bottom: 8px; font-weight: 500; font-size: 1.4rem; color: #fff; }
    .modal-content p.subtitle { color: var(--text-sub); font-size: 0.9rem; margin-bottom: 25px; margin-top:0; word-break: break-all; }
    
    .custom-input {
        width: 100%; margin-bottom: 20px; padding: 14px;
        background: rgba(0,0,0,0.2); border: 1px solid var(--border);
        border-radius: 10px; color: var(--text-main); font-size: 1rem; transition: border-color 0.2s;
    }
    .custom-input.file-input { border-style: dashed; padding: 10px; color: var(--text-sub); cursor: pointer; }
    .custom-input:focus { border-color: var(--accent); }
    
    .btn {
        padding: 12px 20px; border: none; border-radius: 10px;
        cursor: pointer; font-weight: 600; font-size: 1rem; transition: 0.2s; width: 100%;
    }
    .btn-submit { background: var(--accent); color: white; }
    .btn-submit:hover { background: #2563eb; transform: translateY(-1px); }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }

    .status-text { margin-top: 15px; font-size: 0.85rem; min-height: 20px; font-weight: 500; }
    
    /* Progress Bar */
    .progress-container {
        width: 100%; height: 20px; background: rgba(0,0,0,0.3);
        border-radius: 10px; overflow: hidden; margin-top: 15px;
        border: 1px solid var(--border); display: none;
    }
    .progress-bar {
        height: 100%; width: 0%; background: var(--accent); transition: width 0.1s linear;
        text-align: center; font-size: 12px; line-height: 20px; color: white; font-weight: bold;
    }

    @media (min-width: 600px) {
        .controls-wrapper { flex-direction: row; justify-content: space-between; }
        .search-container { width: 400px; }
    }
    @media (max-width: 600px) {
        .grid { grid-template-columns: 1fr; }
        h1 { font-size: 1.5rem; }
        .view-controls { display: none !important; }
    }
</style>

</head>
<body>

<!-- Small Corner Upload Button -->
<div class="upload-btn-top" id="uploadBtnTop" title="Upload File">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
</div>

<!-- Upload & Delete Authentication Modal -->
<div class="custom-modal" id="customModal">
    <div class="modal-content">
        <!-- Close / Cancel Icon -->
        <button class="modal-close-icon" id="closeModalIcon" title="Close/Cancel">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Step 1: Password Prompt (Reused for Upload & Delete) -->
        <div id="stepPassword">
            <h2 id="modalTitle">Admin Access</h2>
            <p class="subtitle" id="modalSubtitle">Verify identity to continue</p>
            <input type="password" id="authPassword" class="custom-input" placeholder="Enter admin password...">
            <button class="btn btn-submit" id="verifyPwdBtn">Verify</button>
            <div class="status-text" id="pwdStatus"></div>
        </div>

        <!-- Step 2: File Upload View -->
        <div id="stepUpload" style="display: none;">
            <h2>Upload File</h2>
            <p class="subtitle">Select a file to upload to the server</p>
            <input type="file" id="fileInput" class="custom-input file-input">
            <button class="btn btn-submit" id="doUploadBtn">Start Upload</button>
            
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar" id="progressBar">0%</div>
            </div>
            <div class="status-text" id="uploadStatus"></div>
        </div>
    </div>
</div>

<header>
    <h1>Index of <span>/<?php echo htmlspecialchars(basename(__DIR__)); ?></span></h1>
    <div class="path-badge"><?php echo htmlspecialchars(__DIR__); ?></div>

    <div class="controls-wrapper">
        <div class="search-container">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="search-icon">
              <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
            </svg>
            <input type="text" id="searchInput" placeholder="Search files...">
            <div class="search-loader" id="searchLoader"></div>
        </div>
        <div class="view-controls">
            <button id="viewGridBtn" class="view-btn active" title="Grid View">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
            </button>
            <button id="viewListBtn" class="view-btn" title="List View">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                </svg>
            </button>
        </div>
    </div>
</header>

<div class="container">
    <div class="grid" id="fileGrid">
        <?php 
        if (empty($allData)) {
            echo '<div class="no-results">Directory is empty</div>';
        } else {
            foreach ($allData as $file) {
                renderCard($file);
            }
        }
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------
       View Modes & Search Logic
    ------------------------------ */
    const fileGrid = document.getElementById('fileGrid');
    const viewGridBtn = document.getElementById('viewGridBtn');
    const viewListBtn = document.getElementById('viewListBtn');
    const searchInput = document.getElementById('searchInput');
    const searchLoader = document.getElementById('searchLoader');

    const setView = (view) => {
        if (view === 'list') {
            fileGrid.classList.add('list-view');
            viewListBtn.classList.add('active'); viewGridBtn.classList.remove('active');
        } else {
            fileGrid.classList.remove('list-view');
            viewGridBtn.classList.add('active'); viewListBtn.classList.remove('active');
        }
        localStorage.setItem('preferredView', view);
    };
    setView(localStorage.getItem('preferredView') || 'grid');

    viewGridBtn.addEventListener('click', () => setView('grid'));
    viewListBtn.addEventListener('click', () => setView('list'));

    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value;
        searchLoader.style.display = 'block';
        
        debounceTimer = setTimeout(() => {
            fetch('?ajax=1&q=' + encodeURIComponent(query))
                .then(res => res.text())
                .then(html => {
                    fileGrid.innerHTML = html;
                    searchLoader.style.display = 'none';
                }).catch(err => {
                    console.error(err); searchLoader.style.display = 'none';
                });
        }, 400);
    });

    /* ------------------------------
       Modal & Upload/Delete Setup
    ------------------------------ */
    const customModal = document.getElementById('customModal');
    const closeModalIcon = document.getElementById('closeModalIcon');
    const stepPassword = document.getElementById('stepPassword');
    const stepUpload = document.getElementById('stepUpload');
    
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const authPassword = document.getElementById('authPassword');
    const verifyPwdBtn = document.getElementById('verifyPwdBtn');
    const pwdStatus = document.getElementById('pwdStatus');

    let currentAction = 'upload'; // 'upload' or 'delete'
    let fileToDelete = '';
    let currentXHR = null;

    // Open Modal Function
    const openModal = (action, title, subtitle, btnClass = 'btn-submit') => {
        currentAction = action;
        modalTitle.textContent = title;
        modalSubtitle.textContent = subtitle;
        
        verifyPwdBtn.className = 'btn ' + btnClass;
        verifyPwdBtn.textContent = action === 'delete' ? 'Delete File' : 'Verify';
        
        stepPassword.style.display = 'block';
        stepUpload.style.display = 'none';
        authPassword.value = '';
        pwdStatus.innerHTML = '';
        
        customModal.style.display = 'flex';
        setTimeout(() => authPassword.focus(), 100);
    };

    // Open Upload via Top Right Btn
    document.getElementById('uploadBtnTop').addEventListener('click', () => {
        openModal('upload', 'Upload Access', 'Enter password to open upload area');
    });

    // Close Modal Event
    closeModalIcon.addEventListener('click', () => {
        if (currentXHR) { currentXHR.abort(); currentXHR = null; }
        customModal.style.display = 'none';
    });

    // Password Verify Flow
    verifyPwdBtn.addEventListener('click', () => {
        const pwd = authPassword.value;
        if (!pwd) {
            pwdStatus.innerHTML = '<span style="color:#ef4444;">Please enter the password.</span>';
            return;
        }

        if (currentAction === 'upload') {
            // Frontend switch (Backend validates during actual upload)
            window.sessionPassword = pwd;
            stepPassword.style.display = 'none';
            stepUpload.style.display = 'block';
        } 
        else if (currentAction === 'delete') {
            // Immediately dispatch Delete Action
            verifyPwdBtn.disabled = true;
            pwdStatus.innerHTML = '<span style="color:#3b82f6;">Deleting...</span>';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('password', pwd);
            formData.append('filename', fileToDelete);

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    verifyPwdBtn.disabled = false;
                    if (data.success) {
                        pwdStatus.innerHTML = '<span style="color:#22c55e;">' + data.message + '</span>';
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        pwdStatus.innerHTML = '<span style="color:#ef4444;">' + data.error + '</span>';
                    }
                }).catch(() => {
                    verifyPwdBtn.disabled = false;
                    pwdStatus.innerHTML = '<span style="color:#ef4444;">Network error.</span>';
                });
        }
    });

    authPassword.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') verifyPwdBtn.click();
    });

    /* ------------------------------
       File Upload Execution Logic
    ------------------------------ */
    const doUploadBtn = document.getElementById('doUploadBtn');
    const fileInput = document.getElementById('fileInput');
    const uploadStatus = document.getElementById('uploadStatus');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.getElementById('progressContainer');

    doUploadBtn.addEventListener('click', () => {
        const file = fileInput.files[0];
        if (!file) {
            uploadStatus.innerHTML = '<span style="color:#ef4444;">Please select a file first.</span>'; return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('password', window.sessionPassword);
        formData.append('file', file);

        doUploadBtn.disabled = true;
        uploadStatus.innerHTML = '<span style="color:#3b82f6;">Uploading...</span>';
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%'; progressBar.textContent = '0%';
        progressBar.style.backgroundColor = 'var(--accent)';

        currentXHR = new XMLHttpRequest();
        currentXHR.open('POST', window.location.href, true);

        currentXHR.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const pc = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = pc + '%'; progressBar.textContent = pc + '%';
            }
        };

        currentXHR.onload = function() {
            doUploadBtn.disabled = false; currentXHR = null;
            if (this.status === 200) {
                try {
                    const data = JSON.parse(this.responseText);
                    if (data.success) {
                        progressBar.style.backgroundColor = '#22c55e';
                        uploadStatus.innerHTML = '<span style="color:#22c55e;">' + data.message + '</span>';
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        progressBar.style.backgroundColor = '#ef4444';
                        uploadStatus.innerHTML = '<span style="color:#ef4444;">' + data.error + '</span>';
                    }
                } catch(err) { uploadStatus.innerHTML = '<span style="color:#ef4444;">Server error.</span>'; }
            } else { uploadStatus.innerHTML = '<span style="color:#ef4444;">HTTP Error: ' + this.status + '</span>'; }
        };

        currentXHR.onerror = () => { doUploadBtn.disabled = false; currentXHR = null; uploadStatus.innerHTML = '<span style="color:#ef4444;">Network error.</span>'; };
        currentXHR.onabort = () => { uploadStatus.innerHTML = '<span style="color:#f59e0b;">Cancelled.</span>'; };
        currentXHR.send(formData);
    });

    /* ------------------------------
       Hold To Delete Logic (Event Delegation)
    ------------------------------ */
    let holdTimer = null;
    let cdInterval = null;
    let isLongPress = false;
    let currentBtn = null;
    let cdValue = 7;

    const clearTimers = () => {
        clearTimeout(holdTimer);
        clearInterval(cdInterval);
        document.querySelectorAll('.delete-countdown').forEach(e => e.remove());
        if (currentBtn) {
            currentBtn.querySelector('svg').style.transform = '';
            currentBtn = null;
        }
    };

    const handleDown = (e) => {
        const btn = e.target.closest('.download-btn');
        if (!btn) return;
        
        clearTimers();
        currentBtn = btn;
        isLongPress = false;
        cdValue = 7;
        const fname = btn.getAttribute('download');

        // 3 Seconds Wait
        holdTimer = setTimeout(() => {
            isLongPress = true; // Prevent standard click download
            currentBtn.querySelector('svg').style.transform = 'scale(1.2)';
            
            // Show countdown badge
            const badge = document.createElement('div');
            badge.className = 'delete-countdown';
            badge.innerHTML = `Hold ${cdValue}s to delete`;
            currentBtn.parentElement.appendChild(badge);

            // 7 Seconds Countdown
            cdInterval = setInterval(() => {
                cdValue--;
                if (cdValue > 0) {
                    badge.innerHTML = `Hold ${cdValue}s to delete`;
                } else {
                    // Reached zero
                    clearTimers();
                    fileToDelete = fname;
                    openModal('delete', 'Confirm Deletion', 'Delete: ' + fname, 'btn-danger');
                }
            }, 1000);
            
        }, 3000);
    };

    const handleUp = (e) => clearTimers();

    fileGrid.addEventListener('mousedown', handleDown);
    fileGrid.addEventListener('touchstart', handleDown, {passive: true});
    
    // Global up handlers in case user drags mouse off the element
    window.addEventListener('mouseup', handleUp);
    window.addEventListener('touchend', handleUp);
    
    // Stop click navigation if a long press was triggered
    fileGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.download-btn');
        if (btn && isLongPress) {
            e.preventDefault();
        }
    });

    // Suppress context menu on long press on mobile devices
    fileGrid.addEventListener('contextmenu', (e) => {
        const btn = e.target.closest('.download-btn');
        if (btn && isLongPress) e.preventDefault();
    });
});
</script>

</body>
</html>
