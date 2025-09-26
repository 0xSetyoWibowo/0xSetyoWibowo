<?php
// Mulai sesi PHP
session_start();

// Kata sandi untuk akses, sangat disarankan untuk mengubahnya
$password = 'secret_password_123';

// Fungsi untuk membersihkan input dan mencegah serangan XSS
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Cek autentikasi sesi
if (isset($_POST['password']) && clean_input($_POST['password']) === $password) {
    // Jika kata sandi benar, set sesi otentikasi
    $_SESSION['authenticated'] = true;
}

// Cek apakah pengguna sudah terautentikasi melalui sesi
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Tampilan halaman login
    echo '<html><body><form method="POST"><input type="password" name="password" placeholder="Password"><br><input type="submit" value="Login"></form></body></html>';
    exit;
}

// Tampilan antarmuka utama setelah login
echo '<html><head><title>File Manager</title><style>body{font-family: monospace; background-color: #f0f0f0; padding: 20px;} .container{background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);} pre{background-color: #eee; padding: 10px; border-radius: 4px;} .form-section{margin-bottom: 20px; border-bottom: 1px dashed #ccc; padding-bottom: 20px;}</style></head><body><div class="container">';
echo '<h1>Simple PHP File Manager</h1>';

// Menentukan direktori kerja
$dir = isset($_GET['dir']) ? clean_input($_GET['dir']) : getcwd();
chdir($dir);

echo "<h2>Current Directory: " . htmlspecialchars(getcwd()) . "</h2>";
// Link navigasi tidak lagi perlu membawa password
echo '<a href="?dir=' . urlencode(dirname(getcwd())) . '">... (Up)</a><br><br>';

// --- Unggah File ---
if (isset($_FILES['file_upload'])) {
    $target_dir = getcwd() . '/';
    $target_file = $target_dir . basename($_FILES['file_upload']['name']);
    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
        echo "<p><b>File " . htmlspecialchars(basename($_FILES['file_upload']['name'])) . " berhasil diunggah.</b></p>";
    } else {
        echo "<p><b>Gagal mengunggah file.</b></p>";
    }
}

// --- Edit & Buat File ---
if (isset($_POST['action']) && $_POST['action'] === 'edit_file' && isset($_POST['file_path'])) {
    $file_path = clean_input($_POST['file_path']);
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    if (file_put_contents($file_path, $content) !== false) {
        echo "<p><b>File " . htmlspecialchars($file_path) . " berhasil disimpan.</b></p>";
    } else {
        echo "<p><b>Gagal menyimpan file " . htmlspecialchars($file_path) . ".</b></p>";
    }
}

// --- Ganti Nama File/Folder ---
if (isset($_POST['action']) && $_POST['action'] === 'rename' && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old_name = clean_input($_POST['old_name']);
    $new_name = clean_input($_POST['new_name']);
    if (rename($old_name, $new_name)) {
        echo "<p><b>Berhasil mengganti nama " . htmlspecialchars($old_name) . " menjadi " . htmlspecialchars($new_name) . ".</b></p>";
    } else {
        echo "<p><b>Gagal mengganti nama " . htmlspecialchars($old_name) . ".</b></p>";
    }
}

// --- Buat Folder ---
if (isset($_POST['action']) && $_POST['action'] === 'create_folder' && isset($_POST['folder_name'])) {
    $folder_name = clean_input($_POST['folder_name']);
    if (!is_dir($folder_name) && mkdir($folder_name)) {
        echo "<p><b>Folder " . htmlspecialchars($folder_name) . " berhasil dibuat.</b></p>";
    } else {
        echo "<p><b>Gagal membuat folder " . htmlspecialchars($folder_name) . ".</b></p>";
    }
}

// --- Menampilkan daftar file dan folder ---
echo '<h3>File & Folder:</h3><pre>';
$all_entries = scandir('.');
$folders = [];
$files = [];

foreach ($all_entries as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }
    if (is_dir($entry)) {
        $folders[] = $entry;
    } else {
        $files[] = $entry;
    }
}

sort($folders);
sort($files);

foreach ($folders as $folder) {
    echo "[DIR] <a href=\"?dir=" . urlencode(realpath($folder)) . "\">" . htmlspecialchars($folder) . "</a><br>";
}

foreach ($files as $file) {
    echo "[FILE] " . htmlspecialchars($file) . " (<a href=\"?edit=" . urlencode(realpath($file)) . "\">Edit</a>)<br>";
}
echo '</pre>';

// Jika tombol "Edit" diklik, tampilkan formulir pengeditan
if (isset($_GET['edit'])) {
    $edit_file = clean_input($_GET['edit']);
    if (file_exists($edit_file) && is_readable($edit_file)) {
        $content = htmlspecialchars(file_get_contents($edit_file));
        echo '<h3>Edit File: ' . htmlspecialchars($edit_file) . '</h3>';
        echo '<form method="POST" class="form-section">';
        echo '<input type="hidden" name="action" value="edit_file">';
        echo '<input type="hidden" name="file_path" value="' . htmlspecialchars($edit_file) . '">';
        echo '<textarea name="content" rows="20" cols="100">' . $content . '</textarea><br>';
        echo '<input type="submit" value="Save File">';
        echo '</form>';
    } else {
        echo "<p><b>File tidak ditemukan atau tidak dapat dibaca.</b></p>";
    }
}

// Formulir untuk semua fitur
?>

<hr>

<div class="form-section">
    <h3>Unggah File</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file_upload"><br>
        <input type="submit" value="Upload">
    </form>
</div>

<div class="form-section">
    <h3>Buat File Baru</h3>
    <form method="POST">
        <input type="hidden" name="action" value="edit_file">
        File Name: <input type="text" name="file_path"><br>
        Content:<br>
        <textarea name="content" rows="10" cols="80"></textarea><br>
        <input type="submit" value="Create File">
    </form>
</div>

<div class="form-section">
    <h3>Ganti Nama File/Folder</h3>
    <form method="POST">
        <input type="hidden" name="action" value="rename">
        Old Name: <input type="text" name="old_name"><br>
        New Name: <input type="text" name="new_name"><br>
        <input type="submit" value="Rename">
    </form>
</div>

<div class="form-section">
    <h3>Buat Folder</h3>
    <form method="POST">
        <input type="hidden" name="action" value="create_folder">
        Folder Name: <input type="text" name="folder_name"><br>
        <input type="submit" value="Create Folder">
    </form>
</div>

</div></body></html>
