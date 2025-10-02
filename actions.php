<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = realpath(__DIR__ . '/projects');
$exclude = ['admin', '.', '..', '.git', 'Projects-Manager'];
$action = $_POST['action'] ?? null;

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }
    rmdir($dir);
}

function createProjectTemplate($path, $template, $projectName) {
    switch($template) {
        case 'php':
            file_put_contents($path . '/index.php', "<?php\necho '<h1>Bienvenue sur $projectName</h1>';\necho '<p>Ceci est un projet PHP. Commencez à coder !</p>';\n?>");
            file_put_contents($path . '/config.php', "<?php\ndefine('PROJECT_NAME', '$projectName');\ndefine('VERSION', '1.0.0');\n?>");
            mkdir($path . '/includes');
            mkdir($path . '/assets');
            file_put_contents($path . '/assets/style.css', "body { font-family: Arial, sans-serif; margin: 20px; }\nh1 { color: #333; }");
            break;
            
        case 'html':
            file_put_contents($path . '/index.html', "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>$projectName</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <h1>Bienvenue sur $projectName</h1>\n    <p>Ceci est un projet HTML. Commencez à construire !</p>\n    <script src=\"script.js\"></script>\n</body>\n</html>");
            file_put_contents($path . '/style.css', "body {\n    font-family: Arial, sans-serif;\n    margin: 20px;\n    background: #f5f5f5;\n}\n\nh1 {\n    color: #333;\n    text-align: center;\n}");
            file_put_contents($path . '/script.js', "console.log('$projectName chargé avec succès !');\n");
            break;
            
        case 'react':
            file_put_contents($path . '/package.json', "{\n  \"name\": \"" . strtolower($projectName) . "\",\n  \"version\": \"1.0.0\",\n  \"scripts\": {\n    \"start\": \"react-scripts start\",\n    \"build\": \"react-scripts build\"\n  },\n  \"dependencies\": {\n    \"react\": \"^18.0.0\",\n    \"react-dom\": \"^18.0.0\",\n    \"react-scripts\": \"5.0.1\"\n  }\n}");
            mkdir($path . '/src');
            mkdir($path . '/public');
            file_put_contents($path . '/public/index.html', "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>$projectName</title>\n</head>\n<body>\n    <div id=\"root\"></div>\n</body>\n</html>");
            file_put_contents($path . '/src/App.js', "import React from 'react';\n\nfunction App() {\n  return (\n    <div>\n      <h1>Bienvenue sur $projectName</h1>\n      <p>Ceci est un projet React. Commencez à coder !</p>\n    </div>\n  );\n}\n\nexport default App;");
            file_put_contents($path . '/src/index.js', "import React from 'react';\nimport ReactDOM from 'react-dom/client';\nimport App from './App';\n\nconst root = ReactDOM.createRoot(document.getElementById('root'));\nroot.render(<App />);");
            break;
            
        case 'vue':
            file_put_contents($path . '/package.json', "{\n  \"name\": \"" . strtolower($projectName) . "\",\n  \"version\": \"1.0.0\",\n  \"scripts\": {\n    \"serve\": \"vue-cli-service serve\",\n    \"build\": \"vue-cli-service build\"\n  },\n  \"dependencies\": {\n    \"vue\": \"^3.0.0\"\n  }\n}");
            file_put_contents($path . '/index.html', "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>$projectName</title>\n    <script src=\"https://unpkg.com/vue@3/dist/vue.global.js\"></script>\n</head>\n<body>\n    <div id=\"app\">\n        <h1>{{ title }}</h1>\n        <p>{{ message }}</p>\n    </div>\n    <script>\n        const { createApp } = Vue;\n        createApp({\n            data() {\n                return {\n                    title: 'Bienvenue sur $projectName',\n                    message: 'Ceci est un projet Vue.js. Commencez à coder!'\n                }\n            }\n        }).mount('#app');\n    </script>\n</body>\n</html>");
            break;
            
        default:
            file_put_contents($path . '/index.php', "<?php\necho '<h1>Bienvenue sur $projectName</h1>';\necho '<p>Projet créé avec succès !</p>';\n?>");
            break;
    }
}

if ($action === 'create') {
    $name = preg_replace('/[^A-Za-z0-9_-]/', '', ($_POST['project'] ?? ''));
    $template = $_POST['template'] ?? '';
    
    if (!$name) {
        header('Location: index.php?error=invalid_name');
        exit;
    }
    
    if (in_array($name, $exclude, true)) {
        header('Location: index.php?error=reserved_name');
        exit;
    }
    
    $path = $root . DIRECTORY_SEPARATOR . $name;
    
    if (file_exists($path)) {
        header('Location: index.php?error=exists');
        exit;
    }
    
    if (!mkdir($path, 0777, true)) {
        header('Location: index.php?error=mkdir_failed');
        exit;
    }
    
    createProjectTemplate($path, $template, $name);
    
    header('Location: index.php?success=created&project=' . urlencode($name));
    exit;
}

if ($action === 'delete') {
    $name = $_POST['project'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    if ($name !== $confirm) {
        header('Location: index.php?error=confirm_failed');
        exit;
    }
    
    if (in_array($name, $exclude, true)) {
        header('Location: index.php?error=protected');
        exit;
    }
    
    $path = $root . DIRECTORY_SEPARATOR . $name;
    
    if (is_dir($path)) {
        rrmdir($path);
        header('Location: index.php?success=deleted&project=' . urlencode($name));
    } else {
        header('Location: index.php?error=not_found');
    }
    exit;
}
if ($action === 'star') {
    header('Location: index.php');
    exit;
}

if ($action === 'rename') {
    $original = preg_replace('/[^A-Za-z0-9_-]/', '', ($_POST['original'] ?? ''));
    $newName = preg_replace('/[^A-Za-z0-9_-]/', '', ($_POST['newName'] ?? ''));

    if (!$original || !$newName) {
        header('Location: index.php?error=invalid_name');
        exit;
    }

    if (in_array($original, $exclude, true) || in_array($newName, $exclude, true)) {
        header('Location: index.php?error=reserved_name');
        exit;
    }

    $origPath = $root . DIRECTORY_SEPARATOR . $original;
    $newPath = $root . DIRECTORY_SEPARATOR . $newName;

    if (!is_dir($origPath)) {
        header('Location: index.php?error=not_found');
        exit;
    }

    if (file_exists($newPath)) {
        header('Location: index.php?error=exists');
        exit;
    }

    if (!@rename($origPath, $newPath)) {
        header('Location: index.php?error=rename_failed');
        exit;
    }

    header('Location: index.php?success=renamed&project=' . urlencode($newName));
    exit;
}

if ($action === 'git_clone') {
    $repo = trim($_POST['repo'] ?? '');
    $target = preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['target'] ?? ''));

    if (!$target) {
        header('Location: index.php?error=invalid_target');
        exit;
    }

    if (!preg_match('#^(https?://|git@)([^/]+/)?[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(\.git)?$#i', $repo)) {
        header('Location: index.php?error=invalid_repo');
        exit;
    }

    $dest = $root . DIRECTORY_SEPARATOR . $target;
    if (file_exists($dest)) {
        header('Location: index.php?error=target_exists');
        exit;
    }

    if (in_array($target, $exclude, true)) {
        header('Location: index.php?error=reserved_name');
        exit;
    }

    $repoEscaped = escapeshellarg($repo);
    $destEscaped = escapeshellarg($dest);
    $cmd = "git clone --depth 1 $repoEscaped $destEscaped 2>&1";
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    if ($returnCode === 0) {
        $gitDir = $dest . DIRECTORY_SEPARATOR . '.git';
        if (is_dir($gitDir)) {
            rrmdir($gitDir);
        }
        
        header('Location: index.php?success=cloned&project=' . urlencode($target));
        exit;
    } else {
        if (is_dir($dest)) {
            rrmdir($dest);
        }
        
        $errorOutput = implode(' ', $output);
        $errorType = 'clone_failed';
        
        if (strpos($errorOutput, 'not found') !== false || strpos($errorOutput, '404') !== false) {
            $errorType = 'repo_not_found';
        } elseif (strpos($errorOutput, 'permission denied') !== false || strpos($errorOutput, 'authentication') !== false) {
            $errorType = 'auth_failed';
        } elseif (strpos($errorOutput, 'timeout') !== false) {
            $errorType = 'timeout';
        }
        
        header('Location: index.php?error=' . $errorType . '&msg=' . urlencode(substr($errorOutput, 0, 200)));
        exit;
    }
}

if ($action === 'versioning') {
    $project = preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['project'] ?? ''));
    if (!$project) {
        header('Location: index.php?error=missing_project');
        exit;
    }
    $projectPath = $root . DIRECTORY_SEPARATOR . $project;
    if (!is_dir($projectPath)) {
        header('Location: index.php?error=project_not_found');
        exit;
    }
    
    $backupsDir = __DIR__ . '/backups/' . $project;
    if (!is_dir($backupsDir)) {
        mkdir($backupsDir, 0755, true);
    }
    
    $timestamp = date('Ymd_His');
    $archiveFile = $backupsDir . '/' . $project . '_' . $timestamp . '.zip';
    
    $zip = new ZipArchive();
    if ($zip->open($archiveFile, ZipArchive::CREATE) !== true) {
        header('Location: index.php?error=backup_failed');
        exit;
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $filePath = $file->getRealPath();
        $localPath = substr($filePath, strlen($projectPath) + 1);
        $zip->addFile($filePath, $localPath);
    }
    $zip->close();
    
    $sha256 = hash_file('sha256', $archiveFile);
    file_put_contents($archiveFile . '.sha256', $sha256);
    
    $metadataFile = $backupsDir . '/versioning.json';
    if (file_exists($metadataFile)) {
        $metadata = json_decode(file_get_contents($metadataFile), true);
        if (!is_array($metadata)) {
            $metadata = [];
        }
    } else {
        $metadata = [];
    }
    $record = [
        'timestamp' => $timestamp,
        'archive' => basename($archiveFile),
        'sha256' => $sha256,
        'size' => filesize($archiveFile)
    ];
    $metadata[] = $record;
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    
    $backups = glob($backupsDir . '/*.zip');
    if (count($backups) > 7) {
        usort($backups, function($a, $b) { return filemtime($b) - filemtime($a); });
        foreach (array_slice($backups, 3) as $oldBackup) {
            @unlink($oldBackup);
            @unlink($oldBackup . '.sha256');
        }
    }
    
    header('Location: index.php?success=versioned&project=' . urlencode($project) . '&archive=' . urlencode(basename($archiveFile)));
    exit;
}

if ($action === 'restore_version') {
    $project = preg_replace('/[^A-Za-z0-9_-]/', '', trim($_POST['project'] ?? ''));
    $archive = preg_replace('/[^A-Za-z0-9_.-]/', '', trim($_POST['archive'] ?? ''));
    
    if (!$project || !$archive) {
        header('Location: index.php?error=missing_fields');
        exit;
    }
    
    $projectPath = $root . DIRECTORY_SEPARATOR . $project;
    $backupsDir = __DIR__ . '/backups/' . $project;
    $archiveFile = $backupsDir . '/' . $archive;
    
    if (!file_exists($archiveFile) || !file_exists($archiveFile . '.sha256')) {
        header('Location: index.php?error=archive_not_found');
        exit;
    }
    
    $expectedSha = trim(file_get_contents($archiveFile . '.sha256'));
    $actualSha = hash_file('sha256', $archiveFile);
    if ($expectedSha !== $actualSha) {
        header('Location: index.php?error=corrupted_archive');
        exit;
    }
    
    if (is_dir($projectPath)) {
        $tempBackupDir = __DIR__ . '/backups/' . $project;
        if (!is_dir($tempBackupDir)) {
            mkdir($tempBackupDir, 0755, true);
        }
        $tempBackup = $tempBackupDir . '/before_restore_' . date('Ymd_His') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($tempBackup, ZipArchive::CREATE) === true) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath));
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                $filePath = $file->getRealPath();
                $localPath = substr($filePath, strlen($projectPath) + 1);
                $zip->addFile($filePath, $localPath);
            }
            $zip->close();
            $sha256 = hash_file('sha256', $tempBackup);
            file_put_contents($tempBackup . '.sha256', $sha256);
        }
        
        rrmdir($projectPath);
    }
    
    if (!mkdir($projectPath, 0755, true)) {
        header('Location: index.php?error=mkdir_failed');
        exit;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($archiveFile) === true) {
        $zip->extractTo($projectPath);
        $zip->close();
        
        header('Location: index.php?success=restored&project=' . urlencode($project) . '&archive=' . urlencode($archive));
        exit;
    } else {
        header('Location: index.php?error=extraction_failed');
        exit;
    }
}

header('Location: index.php');
exit;
?>
