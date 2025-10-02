<?php
$root = realpath(__DIR__ . '/projects');
$exclude = ['admin', '.', '..', '.git', 'Projects-Manager'];
$items = scandir($root);
$projects = [];
$totalSize = 0;
$totalFiles = 0;
$projectsByType = ['php' => 0, 'nodejs' => 0, 'html' => 0, 'react' => 0, 'vue' => 0, 'general' => 0];
$recentProjects = 0;
$gitProjects = 0;
$todayProjects = 0;

foreach ($items as $name) {
    if (in_array($name, $exclude, true)) continue;
    $path = $root . DIRECTORY_SEPARATOR . $name;
    if (is_dir($path)) {
        $created = filemtime($path);
        $size = 0;
        $fileCount = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
                $fileCount++;
            }
        }

        $totalSize += $size;
        $totalFiles += $fileCount;

        $type = 'general';
        $isGitProject = false;

        if (file_exists($path . '/package.json')) {
            $packageContent = @file_get_contents($path . '/package.json');
            if ($packageContent && strpos($packageContent, 'react') !== false) {
                $type = 'react';
            } elseif ($packageContent && strpos($packageContent, 'vue') !== false) {
                $type = 'vue';
            } else {
                $type = 'nodejs';
            }
        } elseif (file_exists($path . '/composer.json')) {
            $type = 'php';
        } elseif (file_exists($path . '/index.html')) {
            $type = 'html';
        } elseif (file_exists($path . '/index.php')) {
            $type = 'php';
        }

        // Vérifier si c'est un projet Git (présence de dossier .git ou fichiers Git typiques)
        if (
            file_exists($path . '/.git') || file_exists($path . '/README.md') ||
            file_exists($path . '/.gitignore') || file_exists($path . '/LICENSE')
        ) {
            $isGitProject = true;
            $gitProjects++;
        }

        $projectsByType[$type]++;

        // Compter les projets récents
        $daysSinceCreation = (time() - $created) / (24 * 60 * 60);
        if ($daysSinceCreation <= 7) {
            $recentProjects++;
        }
        if ($daysSinceCreation < 1) {
            $todayProjects++;
        }

        $projects[] = [
            'name' => $name,
            'created' => date("Y-m-d H:i:s", $created),
            'timestamp' => $created,
            'size' => $size,
            'fileCount' => $fileCount,
            'type' => $type,
            'isGit' => $isGitProject,
            'lastModified' => date("Y-m-d H:i:s", $created)
        ];
    }
}

usort($projects, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

// Calculs pour le dashboard
$totalProjects = count($projects);
$averageSize = $totalProjects > 0 ? $totalSize / $totalProjects : 0;
$mostUsedType = array_keys($projectsByType, max($projectsByType))[0] ?? 'general';

function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

function getProjectIcon($type)
{
    switch ($type) {
        case 'nodejs':
            return '🟢';
        case 'php':
            return '🔵';
        case 'html':
            return '🟠';
        case 'react':
            return '⚛️';
        case 'vue':
            return '💚';
        default:
            return '📁';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Projects Manager Pro</title>
    
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
                    <div style="width: 40px; height: 40px; background: var(--color-primary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                        PM
                    </div>
                    <div>
                        <h1 class="heading-3" style="margin: 0; color: var(--color-primary);">Gestionnaire de projets</h1>
                        <p class="text-muted" style="margin: 0; font-size: 0.75rem;">Espace professionnel pour développeurs</p>
                    </div>
                </div>
                <div class="flex items-center gap-md">
                    <button id="refreshBtn" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                        Actualiser
                    </button>
                    <button id="themeToggle" class="btn btn-secondary" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="text-muted" style="font-size: 0.875rem;">
                        <i class="fas fa-clock"></i>
                        <?= date('H:i:s') ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <!-- Statistics Dashboard -->
        <section class="stats-grid animate-fade-in-up">
            <div class="stat-card stat-card--projects">
                <div class="flex justify-between items-center mb-md">
                    <div>
                        <h3 class="text-muted" style="margin: 0; font-size: 0.875rem; font-weight: 500;">Total des projets</h3>
                        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: var(--color-primary);"><?= $totalProjects ?></p>
                    </div>
                    <div style="color: var(--color-primary); font-size: 2rem; opacity: 0.8;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                </div>
                <p class="text-muted" style="margin: 0; font-size: 0.75rem;">
                    <i class="fas fa-arrow-up" style="color: var(--color-success);"></i>
                    <?= $recentProjects ?> cette semaine
                </p>
            </div>

            <div class="stat-card stat-card--size">
                <div class="flex justify-between items-center mb-md">
                    <div>
                        <h3 class="text-muted" style="margin: 0; font-size: 0.875rem; font-weight: 500;">Taille totale</h3>
                        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: var(--color-info);"><?= formatBytes($totalSize) ?></p>
                    </div>
                    <div style="color: var(--color-info); font-size: 2rem; opacity: 0.8;">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
                <p class="text-muted" style="margin: 0; font-size: 0.75rem;">
                    <?= number_format($totalFiles) ?> fichiers
                </p>
            </div>

            <div class="stat-card stat-card--git">
                <div class="flex justify-between items-center mb-md">
                    <div>
                        <h3 class="text-muted" style="margin: 0; font-size: 0.875rem; font-weight: 500;">Projets Git</h3>
                        <p style="margin: 0; font-size: 2rem; font-weight: 700; color: var(--color-success);"><?= $gitProjects ?></p>
                    </div>
                    <div style="color: var(--color-success); font-size: 2rem; opacity: 0.8;">
                        <i class="fab fa-git-alt"></i>
                    </div>
                </div>
                <p class="text-muted" style="margin: 0; font-size: 0.75rem;">
                    <?= $totalProjects > 0 ? round($gitProjects / $totalProjects * 100) : 0 ?>% du total
                </p>
            </div>

            <div class="stat-card stat-card--popular">
                <div class="flex justify-between items-center mb-md">
                    <div>
                        <h3 class="text-muted" style="margin: 0; font-size: 0.875rem; font-weight: 500;">Type le plus courant</h3>
                        <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--color-accent); display: flex; align-items: center; gap: 0.5rem;">
                            <?= getProjectIcon($mostUsedType) ?>
                            <?= ucfirst($mostUsedType) ?>
                        </p>
                    </div>
                    <div style="color: var(--color-accent); font-size: 2rem; opacity: 0.8;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
                <p class="text-muted" style="margin: 0; font-size: 0.75rem;">
                    <?= $projectsByType[$mostUsedType] ?> projets
                </p>
            </div>
        </section>

        <!-- Project Actions -->
        <section class="content-section animate-fade-in-up">
                <div class="section-header">
                <h2 class="heading-2" style="margin: 0;">Actions rapides</h2>
                <p class="text-secondary" style="margin: 0;">Créez de nouveaux projets ou gérez les existants</p>
            </div>
            <div class="section-content">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Create Project -->
                    <div>
                        <h3 class="heading-3">Créer un nouveau projet</h3>
                        <form id="createForm" action="actions.php" method="post" style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                            <input type="hidden" name="action" value="create">
                            <div>
                    <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Nom du projet</label>
                    <input name="project" required pattern="[A-Za-z0-9_-]+" 
                        class="form-input" 
                        placeholder="mon-projet-genial" />
                            </div>
                            <div>
                                <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Modèle</label>
                                <select name="template" class="form-select">
                                    <option value="">Projet basique</option>
                                    <option value="php">Projet PHP</option>
                                    <option value="html">Projet HTML/CSS/JS</option>
                                    <option value="react">Application React</option>
                                    <option value="vue">Application Vue.js</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Créer
                            </button>
                        </form>
                        
                        <!-- GitHub Clone -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--color-gray-200);">
                            <h4 class="text-primary" style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600;">Cloner depuis GitHub</h4>
                            <form id="cloneFormMain" action="actions.php" method="post" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                                <input type="hidden" name="action" value="git_clone">
                                <div>
                     <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">URL du dépôt</label>
                     <input name="repo" required 
                         class="form-input" 
                         placeholder="https://github.com/utilisateur/repo.git" />
                                </div>
                                <div>
                     <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Nom local</label>
                     <input name="target" required pattern="[A-Za-z0-9_-]+" 
                         class="form-input" 
                         placeholder="nom-projet-local" />
                                </div>
                                <button type="submit" class="btn btn-accent">
                                    <i class="fab fa-github"></i>
                                    Cloner
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Search & Filters -->
                    <div>
                        <h3 class="heading-3">Recherche & filtres</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                    <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Rechercher des projets</label>
                    <input id="searchInput" 
                        class="form-input" 
                        placeholder="Rechercher par nom..." />
                            </div>
                            <div>
                                <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Filtrer par type</label>
                                <select id="typeFilter" class="form-select">
                                    <option value="">Tous les types</option>
                                    <option value="php">PHP</option>
                                    <option value="nodejs">Node.js</option>
                                    <option value="html">HTML</option>
                                    <option value="react">React</option>
                                    <option value="vue">Vue.js</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-secondary" style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Trier par</label>
                                <select id="sortBy" class="form-select">
                                    <option value="date">Date modifiée</option>
                                    <option value="name">Nom</option>
                                    <option value="size">Taille</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects Grid -->
        <section class="content-section animate-fade-in-up">
            <div class="section-header">
                <div class="flex items-center">
                    <div>
                        <h2 class="heading-2" style="margin: 0;">Mes projets</h2>
                        <p class="text-secondary" style="margin: 0;"><?= count($projects) ?> projets trouvés</p>
                    </div>
                    <div class="flex gap-sm justify-end" style="margin-left: auto;">
                        <button id="viewToggle" class="btn btn-secondary">
                            <i class="fas fa-th-large"></i>
                            Vue grille
                        </button>
                        <a href="/phpmyadmin">
                            <button class="btn btn-secondary" id="listViewBtn">
                                <i class="fas fa-database"></i>
                                Base de données
                            </button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <?php if (empty($projects)): ?>
                <!-- Empty State -->
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">📁</div>
                    <h3 class="heading-3">Aucun projet pour le moment</h3>
                    <p class="text-secondary" style="margin-bottom: 2rem;">Créez votre premier projet pour commencer le développement</p>
                    <button onclick="document.querySelector('input[name=project]').focus()" 
                            class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Créez votre premier projet
                    </button>
                </div>
                <?php else: ?>
                <!-- Projects Grid -->
                <div id="projectsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($projects as $p): ?>
                    <div class="project-card" 
                         data-name="<?= strtolower($p['name']) ?>" 
                         data-type="<?= $p['type'] ?>"
                         data-date="<?= $p['timestamp'] ?>"
                         data-size="<?= $p['size'] ?>">
                        
                        <div class="flex justify-between items-start mb-lg">
                            <div class="flex items-center gap-md">
                                <div style="font-size: 1.5rem;"><?= getProjectIcon($p['type']) ?></div>
                                <div>
                                    <h3 class="text-primary" style="margin: 0; font-size: 1.125rem; font-weight: 600;"><?= htmlspecialchars($p['name']) ?></h3>
                                    <p class="text-muted" style="margin: 0; font-size: 0.875rem;"><?php echo ucfirst($p['type']) . ' Projet'; ?></p>
                                </div>
                            </div>
                            <div class="flex gap-sm">
                <button class="text-muted" style="border: none; background: none; cursor: pointer; padding: 0.25rem;" 
                    title="Marquer le projet" aria-label="Marquer ce projet">
                                    <i class="far fa-star"></i>
                                </button>
                <button class="settingsBtn text-muted" style="border: none; background: none; cursor: pointer; padding: 0.25rem;" 
                    data-name="<?= htmlspecialchars($p['name']) ?>" 
                    title="Paramètres du projet" 
                    aria-label="Ouvrir les paramètres du projet">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <div class="flex justify-between mb-sm">
                                <span class="text-secondary" style="font-size: 0.875rem;">Fichiers :</span>
                                <span class="text-primary" style="font-size: 0.875rem; font-weight: 500;"><?= $p['fileCount'] ?></span>
                            </div>
                            <div class="flex justify-between mb-sm">
                                <span class="text-secondary" style="font-size: 0.875rem;">Taille :</span>
                                <span class="text-primary" style="font-size: 0.875rem; font-weight: 500;"><?= formatBytes($p['size']) ?></span>
                            </div>
                            <div class="flex justify-between mb-sm">
                                <span class="text-secondary" style="font-size: 0.875rem;">Modifié :</span>
                                <span class="text-primary" style="font-size: 0.875rem; font-weight: 500;"><?= date('M j, Y', $p['timestamp']) ?></span>
                            </div>
                            
                            <!-- Progress indicator based on size -->
                            <div style="margin-top: 1rem;">
                                <div style="background: var(--color-gray-200); height: 4px; border-radius: 2px; overflow: hidden;">
                                    <div style="background: linear-gradient(90deg, var(--color-primary), var(--color-accent)); height: 100%; width: <?= min(100, ($p['size'] / max($totalSize/count($projects), 1)) * 100) ?>%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-sm">
                            <a href="/projects/<?= htmlspecialchars($p['name']) ?>/" 
                               target="_blank"
                               class="btn btn-primary" style="flex: 1; text-decoration: none; justify-content: center;">
                                <i class="fas fa-external-link-alt"></i>
                                Ouvrir
                            </a>
                                    <button class="btn btn-secondary delBtn" 
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    title="Supprimer le projet"
                                    aria-label="Supprimer le projet <?= htmlspecialchars($p['name']) ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Projects List View -->
                <div id="projectsList" class="hidden" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($projects as $p): ?>
                    <div class="project-row" 
                         data-name="<?= strtolower($p['name']) ?>" 
                         data-type="<?= $p['type'] ?>"
                         data-date="<?= $p['timestamp'] ?>"
                         data-size="<?= $p['size'] ?>"
                         style="background: white; border: 1px solid var(--color-gray-200); border-radius: var(--radius-lg); padding: var(--space-lg); display: flex; align-items: center; justify-content: space-between; transition: all var(--transition-fast);">
                        
                        <div class="flex items-center gap-lg" style="flex: 1;">
                            <div style="font-size: 1.5rem;"><?= getProjectIcon($p['type']) ?></div>
                            <div style="flex: 1;">
                                <h3 class="text-primary" style="margin: 0; font-size: 1.125rem; font-weight: 600;"><?= htmlspecialchars($p['name']) ?></h3>
                                <p class="text-muted" style="margin: 0; font-size: 0.875rem;"><?php echo ucfirst($p['type']) . ' Projet • ' . $p['fileCount'] . ' fichiers • ' . formatBytes($p['size']); ?></p>
                            </div>
                            <div class="text-secondary" style="font-size: 0.875rem; min-width: 120px; text-align: right;">
                                <?= date('M j, Y', $p['timestamp']) ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-sm" style="margin-left: var(--space-lg);">
                            <a href="/projects/<?= htmlspecialchars($p['name']) ?>/" 
                               target="_blank"
                               class="btn btn-primary btn-sm" style="text-decoration: none;">
                                <i class="fas fa-external-link-alt"></i>
                                    Ouvrir
                            </a>
                <button class="settingsBtn btn btn-secondary btn-sm" 
                    data-name="<?= htmlspecialchars($p['name']) ?>" 
                    title="Paramètres du projet" 
                    aria-label="Ouvrir les paramètres du projet">
                                <i class="fas fa-cog"></i>
                            </button>
                <button class="btn btn-secondary btn-sm delBtn" 
                    data-name="<?= htmlspecialchars($p['name']) ?>"
                    title="Supprimer le projet"
                    aria-label="Supprimer le projet <?= htmlspecialchars($p['name']) ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <footer style="background: var(--color-gray-50); border-top: 1px solid var(--color-gray-200); margin-top: var(--space-3xl); padding: var(--space-2xl) 0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 var(--space-xl);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-2xl); margin-bottom: var(--space-xl);">
                <!-- Colonne 1: Info principale -->
                <div>
                    <h3 style="color: var(--color-primary); font-size: 1.25rem; font-weight: 600; margin: 0 0 var(--space-md) 0;">
                        <i class="fas fa-code" style="margin-right: 0.5rem;"></i>
                        Gestionnaire de projets
                    </h3>
                    <p style="color: var(--color-gray-600); margin: 0 0 var(--space-md) 0; line-height: 1.6;">
                        Gestionnaire de projets professionnel pour développeurs. 
                        Organisez, gérez et déployez vos projets en toute simplicité.
                    </p>
                    <div style="display: flex; gap: var(--space-sm);">
                        <a href="#" style="color: var(--color-primary); font-size: 1.25rem; text-decoration: none; transition: color var(--transition-fast);" 
                           onmouseover="this.style.color='var(--color-accent)'" 
                           onmouseout="this.style.color='var(--color-primary)'">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" style="color: var(--color-primary); font-size: 1.25rem; text-decoration: none; transition: color var(--transition-fast);" 
                           onmouseover="this.style.color='var(--color-accent)'" 
                           onmouseout="this.style.color='var(--color-primary)'">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: var(--color-primary); font-size: 1.25rem; text-decoration: none; transition: color var(--transition-fast);" 
                           onmouseover="this.style.color='var(--color-accent)'" 
                           onmouseout="this.style.color='var(--color-primary)'">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Colonne 2: Liens rapides -->
                <div>
                    <h4 style="color: var(--color-gray-800); font-size: 1rem; font-weight: 600; margin: 0 0 var(--space-md) 0;">
                        Liens rapides
                    </h4>
                    <ul style="list-style: none; margin: 0; padding: 0;">
                        <li style="margin-bottom: var(--space-sm);">
                            <a href="#" style="color: var(--color-gray-600); text-decoration: none; transition: color var(--transition-fast);" 
                               onmouseover="this.style.color='var(--color-primary)'" 
                               onmouseout="this.style.color='var(--color-gray-600)'">
                                <i class="fas fa-folder" style="margin-right: 0.5rem; width: 16px;"></i>
                                Mes projets
                            </a>
                        </li>
                        <li style="margin-bottom: var(--space-sm);">
                            <a href="#" style="color: var(--color-gray-600); text-decoration: none; transition: color var(--transition-fast);" 
                               onmouseover="this.style.color='var(--color-primary)'" 
                               onmouseout="this.style.color='var(--color-gray-600)'">
                                <i class="fas fa-plus" style="margin-right: 0.5rem; width: 16px;"></i>
                                Nouveau projet
                            </a>
                        </li>
                        <li style="margin-bottom: var(--space-sm);">
                            <a href="#" style="color: var(--color-gray-600); text-decoration: none; transition: color var(--transition-fast);" 
                               onmouseover="this.style.color='var(--color-primary)'" 
                               onmouseout="this.style.color='var(--color-gray-600)'">
                                <i class="fas fa-cog" style="margin-right: 0.5rem; width: 16px;"></i>
                                Paramètres
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Colonne 3: Technologies -->
                <div>
                    <h4 style="color: var(--color-gray-800); font-size: 1rem; font-weight: 600; margin: 0 0 var(--space-md) 0;">
                        Technologies supportées
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-sm);">
                        <span style="background: var(--color-primary); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 500;">PHP</span>
                        <span style="background: var(--color-accent); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 500;">Node.js</span>
                        <span style="background: var(--color-info); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 500;">React</span>
                        <span style="background: var(--color-success); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 500;">Vue.js</span>
                        <span style="background: var(--color-gray-600); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 500;">HTML/CSS</span>
                    </div>
                </div>
            </div>
            
            <!-- Barre de copyright -->
            <div style="border-top: 1px solid var(--color-gray-200); padding-top: var(--space-lg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md);">
                <p style="color: var(--color-gray-500); margin: 0; font-size: 0.875rem;">
                    © 2025 Gestionnaire de projets. Tous droits réservés.
                </p>
                <div style="display: flex; gap: var(--space-lg);">
                    <a href="#" style="color: var(--color-gray-500); text-decoration: none; font-size: 0.875rem; transition: color var(--transition-fast);" 
                       onmouseover="this.style.color='var(--color-primary)'" 
                       onmouseout="this.style.color='var(--color-gray-500)'">
                        Politique de confidentialité
                    </a>
                    <a href="#" style="color: var(--color-gray-500); text-decoration: none; font-size: 0.875rem; transition: color var(--transition-fast);" 
                       onmouseover="this.style.color='var(--color-primary)'" 
                       onmouseout="this.style.color='var(--color-gray-500)'">
                        Conditions d'utilisation
                    </a>
                    <a href="#" style="color: var(--color-gray-500); text-decoration: none; font-size: 0.875rem; transition: color var(--transition-fast);" 
                       onmouseover="this.style.color='var(--color-primary)'" 
                       onmouseout="this.style.color='var(--color-gray-500)'">
                        Contact
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Project Settings Modal -->
    <div id="settingsModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; align-items: center; justify-content: center;" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div id="modalBackdrop" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);" aria-hidden="true"></div>
        <div style="position: relative; background: white; border-radius: var(--radius-xl); padding: var(--space-xl); box-shadow: var(--shadow-xl); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;" role="document">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--color-gray-200);">
                <h3 id="modalTitle" style="margin: 0; font-size: 1.5rem; font-weight: 600; color: var(--color-primary);">Paramètres du projet</h3>
                <button id="modalCancel" style="background: none; border: none; color: var(--color-gray-400); font-size: 1.5rem; cursor: pointer; padding: 0.5rem; border-radius: var(--radius-md); transition: all 0.2s;" 
                        onmouseover="this.style.background='var(--color-gray-100)'; this.style.color='var(--color-gray-600)'" 
                        onmouseout="this.style.background='none'; this.style.color='var(--color-gray-400)'"
                        aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin-bottom: 2rem;">
                <!-- Rename Section -->
                <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--color-gray-200);">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-edit" style="color: var(--color-primary);"></i>
                        Renommer le projet
                    </h4>
                    <form id="renameForm" method="post" action="actions.php" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="original" id="originalName" value="">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--color-gray-600); font-weight: 500;">Nouveau nom du projet</label>
                            <input name="newName" id="newName" required pattern="[A-Za-z0-9_-]+" 
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-gray-300); border-radius: var(--radius-md); font-size: 1rem; transition: border-color 0.2s;"
                                   placeholder="nouveau-nom-projet">
                        </div>
                        <button type="submit" 
                                style="background: var(--color-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 1rem;"
                                onmouseover="this.style.background='var(--color-primary-dark)'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='var(--color-primary)'; this.style.transform='translateY(0)'">
                            <i class="fas fa-save"></i>
                            Renommer
                        </button>
                    </form>
                </div>

                <!-- Versioning Section -->
                <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--color-gray-200);">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-history" style="color: var(--color-success);"></i>
                        Gestion des versions
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <button type="button" id="createVersionBtn"
                                style="background: var(--color-success); color: white; border: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.875rem;"
                                onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='var(--color-success)'; this.style.transform='translateY(0)'">
                            <i class="fas fa-save"></i>
                            Créer une version
                        </button>
                        <button type="button" id="viewVersionsBtn"
                                style="background: var(--color-info); color: white; border: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.875rem;"
                                onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.background='var(--color-info)'; this.style.transform='translateY(0)'">
                            <i class="fas fa-list"></i>
                            Voir l'historique
                        </button>
                    </div>
                    <div id="versionStatus" style="margin-top: 1rem; padding: 0.75rem; background: var(--color-gray-50); border-radius: var(--radius-md); font-size: 0.875rem; color: var(--color-gray-600); display: none;">
                        <i class="fas fa-info-circle"></i>
                        <span id="versionStatusText">Prêt à créer une version</span>
                    </div>
                </div>

                <!-- Project Info Section -->
                <div>
                    <h4 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-info-circle" style="color: var(--color-info);"></i>
                        Informations du projet
                    </h4>
                    <div style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--color-gray-600);">Nom du projet:</span>
                            <span style="color: var(--color-primary); font-weight: 500;" id="projectInfoName">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--color-gray-600);">Type:</span>
                            <span style="color: var(--color-primary); font-weight: 500;" id="projectInfoType">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--color-gray-600);">Chemin:</span>
                            <span style="color: var(--color-gray-500); font-family: monospace; font-size: 0.75rem;" id="projectInfoPath">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--color-gray-600);">Dernière modification:</span>
                            <span style="color: var(--color-primary); font-weight: 500;" id="projectInfoDate">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div style="padding: 1.5rem 0; border-top: 1px solid var(--color-gray-200); display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 2rem;">
                <button type="button" id="modalClose"
                        style="background: var(--color-gray-200); color: var(--color-gray-700); border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                        onmouseover="this.style.background='var(--color-gray-300)'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='var(--color-gray-200)'; this.style.transform='translateY(0)'">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
                <button type="button" id="deleteProjectBtn" 
                        style="background: var(--color-error); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                        onmouseover="this.style.background='#b91c1c'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='var(--color-error)'; this.style.transform='translateY(0)'">
                    <i class="fas fa-trash"></i>
                    Supprimer
                </button>
                <button type="button" id="openProjectBtn" 
                        style="background: var(--color-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                        onmouseover="this.style.background='var(--color-primary-dark)'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='var(--color-primary)'; this.style.transform='translateY(0)'">
                    <i class="fas fa-external-link-alt"></i>
                    Ouvrir
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications Container -->
    <div id="notifications"></div>

    <!-- JavaScript -->
    <script src="assets/index.js"></script>
</body>
</html>