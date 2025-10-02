<?php

$BACKUP_ROOT = __DIR__;
$PROJECTS_ROOT = realpath(__DIR__ . '/projects');
$EXCLUDE_DIRS = ['admin', '.', '..', '.git', 'Projects-Manager'];
$MAX_BACKUPS_PER_PROJECT = 7;
$LOG_FILE = __DIR__ . '/backup_auto.log';

function writeLog($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($LOG_FILE, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

function formatBytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function createProjectBackup($projectName) {
    global $PROJECTS_ROOT, $BACKUP_ROOT, $MAX_BACKUPS_PER_PROJECT;
    
    $projectPath = $PROJECTS_ROOT . DIRECTORY_SEPARATOR . $projectName;
    if (!is_dir($projectPath)) {
        writeLog("ERREUR: Projet '$projectName' introuvable");
        return false;
    }
    
    // Créer le répertoire de backup
    $backupsDir = $BACKUP_ROOT . '/backups/' . $projectName;
    if (!is_dir($backupsDir)) {
        mkdir($backupsDir, 0755, true);
    }
    
    // Générer le nom de l'archive
    $timestamp = date('Ymd_His');
    $archiveFile = $backupsDir . '/' . $projectName . '_' . $timestamp . '.zip';
    
    // Créer l'archive ZIP
    $zip = new ZipArchive();
    if ($zip->open($archiveFile, ZipArchive::CREATE) !== true) {
        writeLog("ERREUR: Impossible de créer l'archive pour '$projectName'");
        return false;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $fileCount = 0;
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        
        $filePath = $file->getRealPath();
        $localPath = substr($filePath, strlen($projectPath) + 1);
        
        // Exclure certains fichiers/dossiers
        if (strpos($localPath, 'node_modules') === 0 || 
            strpos($localPath, '.git') === 0 ||
            strpos($localPath, 'vendor') === 0 ||
            strpos($localPath, '.env') !== false) {
            continue;
        }
        
        $zip->addFile($filePath, $localPath);
        $fileCount++;
    }
    
    $zip->close();
    
    if (!file_exists($archiveFile)) {
        writeLog("ERREUR: Archive non créée pour '$projectName'");
        return false;
    }
    
    // Calculer le checksum
    $sha256 = hash_file('sha256', $archiveFile);
    file_put_contents($archiveFile . '.sha256', $sha256);
    
    // Mettre à jour les métadonnées
    $metadataFile = $backupsDir . '/versioning.json';
    $metadata = [];
    if (file_exists($metadataFile)) {
        $metadata = json_decode(file_get_contents($metadataFile), true) ?: [];
    }
    
    $record = [
        'timestamp' => $timestamp,
        'archive' => basename($archiveFile),
        'sha256' => $sha256,
        'size' => filesize($archiveFile),
        'auto_backup' => true,
        'files_count' => $fileCount
    ];
    $metadata[] = $record;
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    
    // Nettoyage: conserver seulement les X derniers backups
    $backups = glob($backupsDir . '/*.zip');
    if (count($backups) > $MAX_BACKUPS_PER_PROJECT) {
        // Trier par date de modification
        usort($backups, function($a, $b) { 
            return filemtime($b) - filemtime($a); 
        });
        
        $toDelete = array_slice($backups, $MAX_BACKUPS_PER_PROJECT);
        foreach ($toDelete as $oldBackup) {
            @unlink($oldBackup);
            @unlink($oldBackup . '.sha256');
        }
        writeLog("Nettoyé " . count($toDelete) . " anciens backups pour '$projectName'");
    }
    
    $size = formatBytes(filesize($archiveFile));
    writeLog("✅ Backup créé pour '$projectName': $size ($fileCount fichiers)");
    return true;
}

// Script principal
try {
    writeLog("=== DÉBUT DU BACKUP AUTOMATIQUE ===");
    
    // Vérifier les arguments
    $targetProject = $argv[1] ?? null;
    
    if ($targetProject) {
        // Backup d'un projet spécifique
        writeLog("Backup du projet spécifique: '$targetProject'");
        if (createProjectBackup($targetProject)) {
            writeLog("Backup terminé avec succès pour '$targetProject'");
        } else {
            writeLog("Échec du backup pour '$targetProject'");
            exit(1);
        }
    } else {
        // Backup de tous les projets
        writeLog("Backup de tous les projets");
        
        $items = scandir($PROJECTS_ROOT);
        $successCount = 0;
        $totalCount = 0;
        
        foreach ($items as $item) {
            if (in_array($item, $EXCLUDE_DIRS, true)) continue;
            
            $itemPath = $PROJECTS_ROOT . DIRECTORY_SEPARATOR . $item;
            if (!is_dir($itemPath)) continue;
            
            $totalCount++;
            writeLog("Traitement: '$item'");
            
            if (createProjectBackup($item)) {
                $successCount++;
            }
        }
        
        writeLog("Backup terminé: $successCount/$totalCount projets sauvegardés");
        
        if ($successCount < $totalCount) {
            exit(1); // Code d'erreur pour signaler des échecs partiels
        }
    }
    
    writeLog("=== FIN DU BACKUP AUTOMATIQUE ===");
    
} catch (Exception $e) {
    writeLog("ERREUR FATALE: " . $e->getMessage());
    exit(1);
}
?>