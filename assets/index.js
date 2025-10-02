class ProjectManager {
    constructor() {
        this.currentView = 'grid';
        this.projects = [];
        this.filteredProjects = [];
        this.sortBy = 'date';
        this.sortOrder = 'desc';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadTheme();
        this.loadProjects();
        this.setupKeyboardShortcuts();
        this.setupNotifications();
        this.startAutoRefresh();
        this.addLoadingStates();
        this.initAccordion();
    }

    bindEvents() {
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const sortBy = document.getElementById('sortBy');
        const viewToggle = document.getElementById('viewToggle');
        const refreshBtn = document.getElementById('refreshBtn');
        const themeToggle = document.getElementById('themeToggle');

        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.filterProjects();
            }, 300));
        }

        if (typeFilter) {
            typeFilter.addEventListener('change', () => this.filterProjects());
        }

        if (sortBy) {
            sortBy.addEventListener('change', (e) => {
                this.sortBy = e.target.value;
                this.sortProjects();
            });
        }

        if (viewToggle) {
            viewToggle.addEventListener('click', () => this.toggleView());
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshProjects());
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        const createForm = document.getElementById('createForm');
        if (createForm) {
            createForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        this.bindDeleteButtons();
        this.setupCardAnimations();

        document.querySelectorAll('.settingsBtn').forEach(btn => {
            btn.addEventListener('click', (e) => this.openSettings(e));
        });
    }

    bindDeleteButtons() {
        document.querySelectorAll('.delBtn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDelete(e));
        });
    }

    setupCardAnimations() {
        const cards = document.querySelectorAll('.project-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-fade-in');
        });
    }

    handleFormSubmit(e) {
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création...';
        submitBtn.disabled = true;

        // Re-enable after a delay (form will redirect anyway)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    }

    handleDelete(e) {
        const btn = e.target.closest('.delBtn');
        const name = btn.dataset.name;
        
        this.showCustomConfirm(
            `Supprimer le projet "${name}"`,
            `Êtes-vous sûr de vouloir supprimer "${name}" ? Cette action est irréversible.`,
            () => {
                const confirmName = prompt(`Pour confirmer la suppression, tapez le nom du projet : ${name}`);
                if (confirmName === name) {
                    this.deleteProject(name);
                } else {
                    this.showNotification('Suppression annulée - nom incorrect', 'warning');
                }
            }
        );
    }

    deleteProject(name) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'actions.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="project" value="${name}">
            <input type="hidden" name="confirm" value="${name}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    loadProjects() {
        this.projects = Array.from(document.querySelectorAll('.project-card, .project-row')).map(el => ({
            element: el,
            name: el.dataset.name,
            type: el.dataset.type,
            date: parseInt(el.dataset.date),
            size: parseInt(el.dataset.size)
        }));
        this.filteredProjects = [...this.projects];
    }

    filterProjects() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const typeFilter = document.getElementById('typeFilter')?.value || '';

        this.filteredProjects = this.projects.filter(project => {
            const matchesSearch = project.name.includes(searchTerm);
            const matchesType = !typeFilter || project.type === typeFilter;
            return matchesSearch && matchesType;
        });

        this.updateDisplay();
    this.showNotification(`${this.filteredProjects.length} projets trouvés`, 'info', 2000);
    }

    sortProjects() {
        this.filteredProjects.sort((a, b) => {
            let valueA, valueB;
            
            switch(this.sortBy) {
                case 'name':
                    valueA = a.name;
                    valueB = b.name;
                    break;
                case 'size':
                    valueA = a.size;
                    valueB = b.size;
                    break;
                case 'date':
                default:
                    valueA = a.date;
                    valueB = b.date;
                    break;
            }

            if (this.sortOrder === 'desc') {
                return valueB > valueA ? 1 : -1;
            } else {
                return valueA > valueB ? 1 : -1;
            }
        });

        this.updateDisplay();
    }

    updateDisplay() {
        // Hide all projects first
        this.projects.forEach(project => {
            project.element.style.display = 'none';
        });

        // Show filtered projects
        this.filteredProjects.forEach((project, index) => {
            project.element.style.display = '';
            project.element.style.animationDelay = `${index * 0.05}s`;
        });
    }

    toggleView() {
        const gridView = document.getElementById('projectsGrid');
        const listView = document.getElementById('projectsList');
        const toggleIcon = document.querySelector('#viewToggle i');
        const toggleText = document.querySelector('#viewToggle');

            if (this.currentView === 'grid') {
            gridView.classList.add('hidden');
            listView.classList.remove('hidden');
            toggleIcon.className = 'fas fa-list';
                toggleText.innerHTML = '<i class="fas fa-list"></i> Vue liste';
            this.currentView = 'list';
        } else {
            gridView.classList.remove('hidden');
            listView.classList.add('hidden');
            toggleIcon.className = 'fas fa-th-large';
                toggleText.innerHTML = '<i class="fas fa-th-large"></i> Vue grille';
            this.currentView = 'grid';
        }

        this.showNotification(`Vue basculée : ${this.currentView === 'grid' ? 'Grille' : 'Liste'}`, 'info', 2000);
    }

    toggleTheme() {
        // Toggle dark mode on body and persist choice
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        // Update toggle button icon
        const icon = document.querySelector('#themeToggle i');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
        // Show notification
        const label = isDark ? 'Mode sombre' : 'Mode clair';
        this.showNotification(`${label} activé`, 'info', 2000);
    }
    
    loadTheme() {
        // Load persisted theme or default to light
        const saved = localStorage.getItem('theme');
        const isDark = (saved === 'dark');
        if (isDark) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        // Set initial icon
        const icon = document.querySelector('#themeToggle i');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    refreshProjects() {
        const refreshIcon = document.querySelector('#refreshBtn i');
        refreshIcon.classList.add('fa-spin');
        
        setTimeout(() => {
            location.reload();
        }, 1000);
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+N: Focus on new project input
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                document.querySelector('input[name="project"]')?.focus();
                this.showNotification('Prêt pour créer un nouveau projet', 'info', 2000);
            }

            // Ctrl+F: Focus on search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput')?.focus();
                this.showNotification('Recherche de projets', 'info', 2000);
            }

            // Ctrl+R: Refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshProjects();
            }

            // V: Toggle view
            if (e.key === 'v' && !e.ctrlKey && !e.altKey && !e.shiftKey) {
                if (document.activeElement.tagName !== 'INPUT') {
                    this.toggleView();
                }
            }

            // Escape: Clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value) {
                    searchInput.value = '';
                    this.filterProjects();
                    this.showNotification('Recherche effacée', 'info', 2000);
                }
            }
        });
    }

    setupNotifications() {
        // Check URL parameters for notifications
        const params = new URLSearchParams(window.location.search);
        
        if (params.get('success') === 'created') {
            this.showNotification(`Le projet "${params.get('project')}" a été créé !`, 'success');
        } else if (params.get('success') === 'deleted') {
            this.showNotification(`Le projet "${params.get('project')}" a été supprimé !`, 'success');
        } else if (params.get('success') === 'renamed') {
            this.showNotification(`Le projet "${params.get('project')}" a été renommé !`, 'success');
        } else if (params.get('success') === 'cloned') {
            this.showNotification(`Le projet "${params.get('project')}" a été cloné depuis GitHub !`, 'success');
        } else if (params.get('success') === 'versioned') {
            this.showNotification(`Version créée pour "${params.get('project')}" : ${params.get('archive')}`, 'success');
        } else if (params.get('success') === 'restored') {
            this.showNotification(`Projet "${params.get('project')}" restauré depuis ${params.get('archive')}`, 'success');
        } else if (params.get('error')) {
            const error = params.get('error');
            let message = 'Une erreur est survenue';
            
            switch(error) {
                case 'invalid_name':
                    message = 'Nom de projet invalide';
                    break;
                case 'exists':
                    message = 'Le projet existe déjà';
                    break;
                case 'mkdir_failed':
                    message = 'Échec de création du dossier du projet';
                    break;
                case 'confirm_failed':
                    message = 'Confirmation de suppression échouée';
                    break;
                case 'rename_failed':
                    message = 'Échec du renommage du projet';
                    break;
                case 'invalid_target':
                    message = 'Nom de dossier cible invalide';
                    break;
                case 'invalid_repo':
                    message = 'URL de dépôt GitHub invalide';
                    break;
                case 'target_exists':
                    message = 'Le dossier cible existe déjà';
                    break;
                case 'repo_not_found':
                    message = 'Dépôt GitHub introuvable (404)';
                    break;
                case 'auth_failed':
                    message = 'Échec d\'authentification GitHub';
                    break;
                case 'timeout':
                    message = 'Timeout lors du clonage - vérifiez votre connexion';
                    break;
                case 'clone_failed':
                    message = 'Échec du clonage GitHub';
                    const cloneMsg = params.get('msg');
                    if (cloneMsg) {
                        message += ': ' + decodeURIComponent(cloneMsg);
                    }
                    break;
                case 'missing_fields':
                    message = 'Tous les champs sont requis';
                    break;
                case 'project_not_found':
                    message = 'Projet non trouvé';
                    break;
                case 'backup_failed':
                    message = 'Échec de la création de la version';
                    break;
                case 'archive_not_found':
                    message = 'Archive de version introuvable';
                    break;
                case 'corrupted_archive':
                    message = 'Archive corrompue - intégrité compromise';
                    break;
                case 'extraction_failed':
                    message = 'Échec de l\'extraction de l\'archive';
                    break;
                case 'missing_project':
                    message = 'Nom de projet manquant';
                    break;
            }
            
            this.showNotification(message, 'error');
        }

        // Clear URL parameters
        if (params.toString()) {
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notifications');
        const notification = document.createElement('div');

        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        notification.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
                <button class="notification-close" aria-label="Fermer la notification">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('notification-show');
        }, 100);

        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('notification-show');
            setTimeout(() => notification.remove(), 300);
        });

        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('notification-show');
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }

    showCustomConfirm(title, message, onConfirm) {
        if (confirm(`${title}\n\n${message}`)) {
            onConfirm();
        }
    }

    openSettings(e) {
        const btn = e.target.closest('.settingsBtn');
        if (!btn) return;
        const name = btn.dataset.name;
        const modal = document.getElementById('settingsModal');
        
        this.populateProjectInfo(name, btn);
        
        const original = document.getElementById('originalName');
        const newName = document.getElementById('newName');
        
        if (original) original.value = name;
        if (newName) newName.value = name;

        modal.style.display = 'flex'; 
        document.body.classList.add('modal-open');
        
        this.setupModalHandlers(name);
    }

    populateProjectInfo(name, btn) {
        const projectCard = btn.closest('.project-card');
        if (!projectCard) return;

        document.getElementById('projectInfoName').textContent = name;
        document.getElementById('projectInfoType').textContent = projectCard.dataset.type || 'Inconnu';
        document.getElementById('projectInfoPath').textContent = `/projects/${name}/`;
        
        const timestamp = parseInt(projectCard.dataset.date);
        const date = new Date(timestamp * 1000);
        document.getElementById('projectInfoDate').textContent = date.toLocaleDateString('fr-FR');
    }

    createVersion(projectName) {
        const statusDiv = document.getElementById('versionStatus');
        const statusText = document.getElementById('versionStatusText');
        
        if (statusDiv && statusText) {
            statusDiv.style.display = 'block';
            statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création de la version en cours...';
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'actions.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="versioning">
            <input type="hidden" name="project" value="${projectName}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    initAccordion() {
        if (this.accordionInitialized) return;
        this.accordionInitialized = true;

        setTimeout(() => {
            const toggles = document.querySelectorAll('.accordion-toggle');
            
            toggles.forEach((toggle) => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const targetId = toggle.dataset.target;
                    const content = document.getElementById(targetId);
                    const chevron = toggle.querySelector('.fa-chevron-down');
                    
                    if (!content) return;
                    
                    if (content.classList.contains('hidden')) {
                        document.querySelectorAll('.accordion-content').forEach(c => {
                            if (c !== content) {
                                c.classList.add('hidden');
                                const otherToggle = c.previousElementSibling;
                                const otherChevron = otherToggle?.querySelector('.fa-chevron-down');
                                if (otherChevron) otherChevron.style.transform = 'rotate(0deg)';
                            }
                        });
                        
                        content.classList.remove('hidden');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        content.classList.add('hidden');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                });
            });
        }, 100);
    }

    setupModalHandlers(projectName) {
        const modal = document.getElementById('settingsModal');
        
        const closeModal = () => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        };
        
        const cancelBtn = document.getElementById('modalCancel');
        if (cancelBtn) {
            cancelBtn.onclick = closeModal;
        }
        
        const closeBtn = document.getElementById('modalClose');
        if (closeBtn) {
            closeBtn.onclick = closeModal;
        }
        
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.onclick = closeModal;
        }
        
        const deleteBtn = document.getElementById('deleteProjectBtn');
        if (deleteBtn) {
            deleteBtn.onclick = () => {
                closeModal();
                this.showCustomConfirm(
                    `Supprimer le projet "${projectName}"`,
                    `Êtes-vous sûr de vouloir supprimer "${projectName}" ? Cette action est irréversible.`,
                    () => {
                        const confirmName = prompt(`Pour confirmer la suppression, tapez le nom du projet : ${projectName}`);
                        if (confirmName === projectName) {
                            this.deleteProject(projectName);
                        } else {
                            this.showNotification('Suppression annulée - nom incorrect', 'warning');
                        }
                    }
                );
            };
        }
        
        const openBtn = document.getElementById('openProjectBtn');
        if (openBtn) {
            openBtn.onclick = () => {
                window.open(`/projects/${projectName}/`, '_blank');
                closeModal();
            };
        }
        
        const createVersionBtn = document.getElementById('createVersionBtn');
        if (createVersionBtn) {
            createVersionBtn.onclick = () => {
                this.createVersion(projectName);
                closeModal();
            };
        }
        
        const viewVersionsBtn = document.getElementById('viewVersionsBtn');
        if (viewVersionsBtn) {
            viewVersionsBtn.onclick = () => {
                window.location.href = `versions.php?project=${encodeURIComponent(projectName)}`;
                closeModal();
            };
        }
    }

    startAutoRefresh() {
        setInterval(() => {
            if (!document.querySelector('input:focus') && !document.querySelector('select:focus')) {
                const currentCount = this.projects.length;
                const timeElement = document.querySelector('.fa-clock').parentElement;
                if (timeElement) {
                    timeElement.innerHTML = `<i class="fas fa-clock"></i> ${new Date().toLocaleTimeString()}`;
                }
            }
        }, 30000);
    }

    addLoadingStates() {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!btn.disabled && btn.type === 'submit') {
                    btn.classList.add('loading');
                    setTimeout(() => btn.classList.remove('loading'), 2000);
                }
            });
        });
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ProjectManager();
});