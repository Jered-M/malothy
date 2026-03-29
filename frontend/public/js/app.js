// Fonction de déconnexion globale (accessible partout)
window.logout = async function() {
    console.log('--- Déconnexion Initiale ---');
    try {
        await api.logout();
        window.location.href = '/login';
    } catch (e) {
        console.error('Erreur Logout:', e);
        window.location.href = '/login';
    }
};

class App {
    constructor() {
        this.currentPage = null;
        this.currentUser = JSON.parse(localStorage.getItem('user'));
        this.init();
    }

    async init() {
        // Tenter de restaurer la session depuis l'API si le localStorage est vide ou expiré
        if (!this.currentUser) {
            try {
                const result = await api.getProfile();
                if (result && result.success) {
                    this.currentUser = result.user;
                    localStorage.setItem('user', JSON.stringify(this.currentUser));
                }
            } catch (e) {
                console.log('--- Session non trouvée au démarrage ---');
            }
        }

        // Ajouter les événements de navigation haut niveau (Délégation)
        document.addEventListener('click', async (e) => {
            // Gérer les liens de menu
            const link = e.target.closest('[data-page]');
            if (link) {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                
                // Si on va au dashboard, on nettoie l'URL (racine)
                const url = page === 'dashboard' ? '/' : `/${page}`;
                history.pushState({ page }, '', url);
                this.navigate(page);
                
                // Fermer la sidebar après clic sur un lien mobile
                this.setSidebarOpen(false);
                return;
            }

            // Gérer le toggle de la sidebar mobile
            if (e.target.closest('#sidebarToggle')) {
                this.setSidebarOpen(true);
            } else if (e.target.closest('#sidebarClose') || e.target.closest('#sidebarOverlay')) {
                this.setSidebarOpen(false);
            }
        });

        // Gérer le bouton retour du navigateur
        window.addEventListener('popstate', (e) => {
            const page = e.state ? e.state.page : this.getPageFromUrl();
            this.navigate(page);
        });

        // Afficher la page initiale basée sur l'URL
        const initialPage = this.getPageFromUrl();
        
        // Si on est sur la page de login mais déjà authentifié, on redirige vers le dashboard
        if (initialPage === 'login' && this.currentUser) {
            this.navigate('dashboard');
            history.replaceState({ page: 'dashboard' }, '', '/');
        } else {
            this.navigate(initialPage);
        }
    }

    getPageFromUrl() {
        const path = window.location.pathname.replace(/^\//, '') || 'dashboard';
        if (!this.currentUser && path !== 'login') {
            return 'login';
        }
        return path;
    }

    async navigate(page) {
        try {
            // Vérifier l'authentification
            if (page !== 'login' && !this.currentUser) {
                console.warn('Authentication required, redirecting to login');
                this.navigate('login');
                history.replaceState({ page: 'login' }, '', '/login');
                return;
            }

            // Normaliser le rôle pour éviter les erreurs d'accents (trésorier -> tresorier)
            // Gère aussi les erreurs d'encodage (tr??sorier)
            const roleInput = (this.currentUser?.role || 'visiteur').toLowerCase();
            let role = roleInput.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            
            if (role.includes('adm')) role = 'admin';
            else if (role.includes('sorier') || role.startsWith('tr')) role = 'tresorier';
            else if (role.includes('ecretaire') || role.startsWith('sec')) role = 'secretaire';

            const perms = {
                'dashboard': ['admin', 'tresorier', 'secretaire'],
                'members': ['admin', 'secretaire'],
                'members-form': ['admin', 'secretaire'],
                'tithes': ['admin', 'tresorier'],
                'tithe-form': ['admin', 'tresorier'],
                'offerings': ['admin', 'tresorier'],
                'offering-form': ['admin', 'tresorier'],
                'finance': ['admin', 'tresorier'],
                'expenses': ['admin', 'tresorier'],
                'expense-form': ['admin', 'tresorier'],
                'reports': ['admin', 'tresorier'],
                'audit-logs': ['admin'],
                'settings': ['admin']
            };

            if (page !== 'login' && perms[page] && !perms[page].includes(role)) {
                console.warn(`Accès refusé: page=${page}, rôle=${role}`);
                if (page === 'dashboard') {
                    this.navigate('login');
                    history.replaceState({ page: 'login' }, '', '/login');
                } else {
                    this.navigate('dashboard');
                }
                return;
            }

            this.currentPage = page;
            const app = document.getElementById('app');

            // Charger la page appropriée
            switch (page) {
                case 'login':
                    app.innerHTML = await Pages.loginPage();
                    this.attachLoginEvents();
                    break;

                case 'dashboard':
                    app.innerHTML = await Pages.dashboardPage();
                    this.initDashboard();
                    break;

                case 'members':
                    app.innerHTML = await Pages.membersPage();
                    break;

                case 'members-form':
                    app.innerHTML = await Pages.memberFormPage();
                    this.attachMemberEvents();
                    break;

                case 'finance':
                    app.innerHTML = await Pages.financePage();
                    break;

                case 'tithes':
                    app.innerHTML = await Pages.titheListPage();
                    break;
                case 'tithe-form':
                    app.innerHTML = await Pages.titheFormPage();
                    this.attachFinanceEntryEvents('tithe');
                    break;

                case 'offerings':
                    app.innerHTML = await Pages.offeringListPage();
                    break;
                case 'offering-form':
                    app.innerHTML = await Pages.offeringFormPage();
                    this.attachFinanceEntryEvents('offering');
                    break;

                case 'expenses':
                    app.innerHTML = await Pages.expensesPage();
                    this.attachExpenseActionEvents();
                    break;
                case 'expense-form':
                    app.innerHTML = await Pages.expenseFormPage();
                    this.attachExpenseEvents();
                    break;

                case 'reports':
                    app.innerHTML = await Pages.reportsPage();
                    break;

                case 'audit-logs':
                    app.innerHTML = await Pages.auditLogsPage();
                    break;

                case 'settings':
                    app.innerHTML = await Pages.settingsPage();
                    this.attachSettingsEvents();
                    break;

                default:
                    this.navigate('dashboard');
            }

            // Mettre à jour le titre
            document.title = `MALOTY - ${page.charAt(0).toUpperCase() + page.slice(1)}`;
            
        } catch (error) {
            console.error('Erreur de navigation:', error);
        }
    }

    setSidebarOpen(isOpen) {
        const sidebar = document.querySelector('.app-sidebar');
        const overlay = document.querySelector('#sidebarOverlay');
        if (!sidebar) return;

        if (isOpen) {
            sidebar.classList.add('is-open');
            if (overlay) {
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
            }
            document.body.style.overflow = 'hidden'; // Empêcher le scroll
        } else {
            sidebar.classList.remove('is-open');
            if (overlay) {
                overlay.classList.remove('opacity-100');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
            document.body.style.overflow = '';
        }
    }


    attachLoginEvents() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.onsubmit = async (e) => {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const submitButton = loginForm.querySelector('button[type="submit"]');

                try {
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                    }

                    const result = await api.loginWith(email, password);
                    console.log('--- Login Debug ---');
                    console.log('Result:', result);
                    if (result.user) console.log('Found Role in Result:', result.user.role);

                    if (result.success) {
                        this.currentUser = result.user;
                        console.log('Current User now set to:', this.currentUser);
                        history.replaceState({ page: 'dashboard' }, '', '/');
                        this.navigate('dashboard');
                    } else {
                        alert(result.error || 'Identifiants invalides');
                    }
                } catch (error) {
                    alert(error.message || 'Identifiants invalides');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
                    }
                }
            };
        }
    }

    initDashboard() {
        // Initialiser les graphiques Chart.js s'il y a des données
        const ctx = document.getElementById('financeChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Recettes',
                        data: [1200, 1900, 3000, 2500, 2200, 3000],
                        borderColor: '#3b82f6',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }

    attachMemberEvents() {
        const form = document.querySelector('.member-form');
        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const photoFile = formData.get('photo');
                
                // Préparer les données sans la photo (on l'upload séparément)
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (key !== 'photo' && value) {
                        data[key] = value;
                    }
                }
                
                try {
                    const result = await api.post('/members', data);
                    
                    if (result.success) {
                        const memberId = result.id;
                        
                        // Si une photo est fournie, l'uploader
                        if (photoFile && photoFile.size > 0) {
                            const photoFormData = new FormData();
                            photoFormData.append('photo', photoFile);
                            await api.request('POST', `/members/${memberId}/photo`, photoFormData);
                        }
                        
                        alert('Membre enregistré avec succès !');
                        this.navigate('members');
                    } else {
                        alert(result.error || 'Erreur lors de l\'enregistrement');
                    }
                } catch (error) {
                    alert('Erreur: ' + error.message);
                }
            };
        }
    }

    attachFinanceEntryEvents(type) {
        const form = document.querySelector('.finance-form');
        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                const endpoint = type === 'tithe' ? '/finances/tithes' : '/finances/offerings';
                const result = await api.post(endpoint, data);
                
                if (result.success) {
                    alert('Enregistrement financier réussi !');
                    this.navigate(type === 'tithe' ? 'tithes' : 'offerings');
                } else {
                    alert(result.error || 'Erreur lors de l\'enregistrement');
                }
            };
        }
    }

    attachExpenseEvents() {
        const form = document.querySelector('.expense-form');
        if (form) {
            form.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                const result = await api.post('/expenses', data);
                
                if (result.success) {
                    alert('Dépense enregistrée avec succès !');
                    this.navigate('expenses');
                } else {
                    alert(result.error || 'Erreur lors de l\'enregistrement');
                }
            };
        }
    }

    attachSettingsEvents() {
        const profileForm = document.querySelector('#profileForm');
        if (profileForm) {
            profileForm.onsubmit = async (e) => {
                e.preventDefault();
                alert('Profil mis à jour (simulation)');
            };
        }
    }

    attachExpenseActionEvents() {
        const handleAction = async (id, action, confirmMsg = null, data = null) => {
            if (confirmMsg && !confirm(confirmMsg)) return;
            try {
                const result = await api.post(`/expenses/${id}/${action}`, data);
                if (result.success) {
                    alert(`${action === 'approve' ? 'Dépense approuvée' : 'Dépense rejetée'} !`);
                    this.navigate('expenses');
                }
            } catch (err) {
                alert(err.message);
            }
        };

        const container = document.querySelector('.expenses-content') || document.querySelector('#app');
        if (!container) return;

        container.onclick = (e) => {
            const approveBtn = e.target.closest('.approve-expense');
            const rejectBtn = e.target.closest('.reject-expense');

            if (approveBtn) {
                handleAction(approveBtn.dataset.id, 'approve', 'Approuver cette dépense ?');
            } else if (rejectBtn) {
                const reason = prompt('Raison du rejet (facultatif) :');
                if (reason !== null) {
                    handleAction(rejectBtn.dataset.id, 'reject', null, { reason });
                }
            }
        };
    }
}

// Lancer l'app quand le DOM est prêt
window.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});
