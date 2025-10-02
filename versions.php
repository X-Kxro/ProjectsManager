<?php
$root = realpath(__DIR__ . '/projects');
$project = preg_replace('/[^A-Za-z0-9_-]/', '', trim($_GET['project'] ?? ''));

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
$metadataFile = $backupsDir . '/versioning.json';
$versions = [];

if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
    if (is_array($metadata)) {
        // Trier par timestamp décroissant (plus récent en premier)
        usort($metadata, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        $versions = $metadata;
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

function formatTimestamp($timestamp) {
    $year = substr($timestamp, 0, 4);
    $month = substr($timestamp, 4, 2);
    $day = substr($timestamp, 6, 2);
    $hour = substr($timestamp, 9, 2);
    $minute = substr($timestamp, 11, 2);
    $second = substr($timestamp, 13, 2);
    
    return "$day/$month/$year à $hour:$minute:$second";
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Versions - <?= htmlspecialchars($project) ?> | Gestionnaire de projets</title>
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="assets/style.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="app-background">
    <!-- Navigation -->
    <nav class="nav-container">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <div class="flex items-center justify-between" style="height: 4rem;">
                <div class="flex items-center gap-md">
                    <a href="index.php" style="text-decoration: none; color: inherit;">
                        <div style="width: 40px; height: 40px; background: var(--color-primary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                            PM
                        </div>
                    </a>
                    <div>
                        <h1 class="heading-3" style="margin: 0; color: var(--color-primary);">Versions - <?= htmlspecialchars($project) ?></h1>
                        <p class="text-muted" style="margin: 0; font-size: 0.75rem;">Historique des sauvegardes</p>
                    </div>
                </div>
                <div class="flex items-center gap-md">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </a>
                    <button id="createVersionBtn" class="btn btn-primary" data-project="<?= htmlspecialchars($project) ?>">
                        <i class="fas fa-save"></i>
                        Nouvelle version
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <section class="content-section animate-fade-in-up">
            <div class="section-header">
                <h2 class="heading-2" style="margin: 0;">Historique des versions</h2>
                <p class="text-secondary" style="margin: 0;"><?= count($versions) ?> version(s) disponible(s)</p>
            </div>
            
            <div class="section-content">
                <?php if (empty($versions)): ?>
                <!-- Empty State -->
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">🕒</div>
                    <h3 class="heading-3">Aucune version sauvegardée</h3>
                    <p class="text-secondary" style="margin-bottom: 2rem;">Créez votre première version pour commencer le versioning</p>
                    <button onclick="document.getElementById('createVersionBtn').click()" 
                            class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Créer la première version
                    </button>
                </div>
                <?php else: ?>
                <!-- Versions List -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($versions as $index => $version): ?>
                    <div class="version-card" style="background: white; border: 1px solid var(--color-gray-200); border-radius: var(--radius-lg); padding: var(--space-lg); transition: all var(--transition-fast);">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-lg" style="flex: 1;">
                                <div style="width: 50px; height: 50px; background: <?= $index === 0 ? 'var(--color-success)' : 'var(--color-info)' ?>; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                                    <?= $index === 0 ? '<i class="fas fa-star"></i>' : ($index + 1) ?>
                                </div>
                                <div style="flex: 1;">
                                    <h3 class="text-secondary" style="margin: 0; font-size: 1.125rem; font-weight: 600;">
                                        Version <?= formatTimestamp($version['timestamp']) ?>
                                        <?= $index === 0 ? '<span style="background: var(--color-success); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; margin-left: 0.5rem;">ACTUELLE</span>' : '' ?>
                                    </h3>
                                    <p class="text-muted" style="margin: 0; font-size: 0.875rem;">
                                        Taille: <?= formatBytes($version['size']) ?> • 
                                        SHA256: <code style="font-size: 0.75rem; color: var(--color-gray-500);"><?= substr($version['sha256'], 0, 16) ?>...</code>
                                    </p>
                                </div>
                                <div class="text-secondary" style="font-size: 0.875rem; min-width: 150px; text-align: right;">
                                    <?php
                                    $createdTime = DateTime::createFromFormat('Ymd_His', $version['timestamp']);
                                    $now = new DateTime();
                                    $diff = $now->diff($createdTime);
                                    
                                    if ($diff->d > 0) {
                                        echo "Il y a " . $diff->d . " jour" . ($diff->d > 1 ? "s" : "");
                                    } elseif ($diff->h > 0) {
                                        echo "Il y a " . $diff->h . " heure" . ($diff->h > 1 ? "s" : "");
                                    } elseif ($diff->i > 0) {
                                        echo "Il y a " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "");
                                    } else {
                                        echo "À l'instant";
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-sm" style="margin-left: var(--space-lg);">
                                <a href="backups/<?= htmlspecialchars($project) ?>/<?= htmlspecialchars($version['archive']) ?>" 
                                   download
                                   class="btn btn-secondary btn-sm" style="text-decoration: none;">
                                    <i class="fas fa-download"></i>
                                    Télécharger
                                </a>
                                <?php if ($index !== 0): ?>
                                <button class="btn btn-primary btn-sm restoreBtn" 
                                        data-project="<?= htmlspecialchars($project) ?>"
                                        data-archive="<?= htmlspecialchars($version['archive']) ?>"
                                        title="Restaurer cette version">
                                    <i class="fas fa-undo"></i>
                                    Restaurer
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; align-items: center; justify-content: center;" role="dialog">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
        <div style="position: relative; background: white; border-radius: var(--radius-xl); padding: var(--space-xl); box-shadow: var(--shadow-xl); max-width: 500px; width: 90%;" role="document">
            <div style="text-align: center;">
                <div style="width: 80px; height: 80px; background: var(--color-warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 2rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 600; color: var(--color-gray-800);">Confirmer la restauration</h3>
                <p style="color: var(--color-gray-600); margin: 0 0 2rem 0; line-height: 1.6;">
                    Cette action va <strong>remplacer complètement</strong> le contenu actuel du projet par la version sélectionnée. 
                    Une sauvegarde automatique sera créée avant la restauration.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button id="cancelRestore" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button id="confirmRestore" class="btn btn-warning">
                        <i class="fas fa-undo"></i>
                        Confirmer la restauration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Container -->
    <div id="notifications"></div>

    <!-- JavaScript -->
    <script src="assets/index.js"></script>
    <script>
        // Gestion spécifique pour la page versions
        document.addEventListener('DOMContentLoaded', function() {
            let restoreProject = '';
            let restoreArchive = '';
            
            // Créer une nouvelle version
            document.getElementById('createVersionBtn').addEventListener('click', function() {
                const project = this.dataset.project;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="versioning">
                    <input type="hidden" name="project" value="${project}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
            
            // Restaurer une version
            document.querySelectorAll('.restoreBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    restoreProject = this.dataset.project;
                    restoreArchive = this.dataset.archive;
                    document.getElementById('confirmModal').style.display = 'flex';
                });
            });
            
            // Annuler la restauration
            document.getElementById('cancelRestore').addEventListener('click', function() {
                document.getElementById('confirmModal').style.display = 'none';
                restoreProject = '';
                restoreArchive = '';
            });
            
            // Confirmer la restauration
            document.getElementById('confirmRestore').addEventListener('click', function() {
                if (restoreProject && restoreArchive) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'actions.php';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="restore_version">
                        <input type="hidden" name="project" value="${restoreProject}">
                        <input type="hidden" name="archive" value="${restoreArchive}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>