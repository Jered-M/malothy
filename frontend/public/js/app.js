// Fonction de dÃ©connexion globale (accessible partout)
window.logout = async function() {
    console.log('--- DÃ©connexion Initiale ---');
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
        const token = localStorage.getItem('token');

        if (this.currentUser && !token) {
            api.clearSession();
            this.currentUser = null;
        }

        // Tenter de restaurer la session depuis l'API si le localStorage est vide ou expire
        if (!this.currentUser && token) {
            try {
                const result = await api.getProfile();
                if (result && result.success) {
                    this.currentUser = result.user;
                    localStorage.setItem('user', JSON.stringify(this.currentUser));
                }
            } catch (e) {
                console.log('--- Session non trouvee au demarrage ---');
            }
        } else if (this.currentUser && token) {
            try {
                const result = await api.getProfile();
                if (result && result.success) {
                    this.currentUser = result.user;
                    localStorage.setItem('user', JSON.stringify(this.currentUser));
                } else {
                    // garder la session locale si l'API n'est pas disponible
                }
            } catch (e) {
                console.log('--- Session invalide au demarrage ---');
                // garder la session locale si l'API n'est pas disponible
            }
        }

        // Ajouter les Ã©vÃ©nements de navigation haut niveau (DÃ©lÃ©gation)
        document.addEventListener('click', async (e) => {
            // GÃ©rer les liens de menu
            const link = e.target.closest('[data-page]');
            if (link) {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                
                // Si on va au dashboard, on nettoie l'URL (racine)
                const url = page === 'dashboard' ? '/' : `/${page}`;
                history.pushState({ page }, '', url);
                this.navigate(page);
                
                // Fermer la sidebar aprÃ¨s clic sur un lien mobile
                this.setSidebarOpen(false);
                return;
            }

            // GÃ©rer le toggle de la sidebar mobile
            if (e.target.closest('#sidebarToggle')) {
                this.setSidebarOpen(true);
            } else if (e.target.closest('#sidebarClose') || e.target.closest('#sidebarOverlay')) {
                this.setSidebarOpen(false);
            }
        });

        // GÃ©rer le bouton retour du navigateur
        window.addEventListener('popstate', (e) => {
            const page = e.state ? e.state.page : this.getPageFromUrl();
            this.navigate(page);
        });

        // Afficher la page initiale basÃ©e sur l'URL
        const initialPage = this.getPageFromUrl();
        
        // Si on est sur la page de login mais dÃ©jÃ  authentifiÃ©, on redirige vers le dashboard
        if (initialPage === 'login' && this.currentUser) {
            this.navigate('dashboard');
            history.replaceState({ page: 'dashboard' }, '', '/');
        } else {
            await this.navigate(initialPage);
            
            // Post-payment messages (?status=...)
            const params = new URLSearchParams(window.location.search);
            if (initialPage === 'contribute' && params.get('status')) {
                const status = params.get('status');
                const successMsg = document.getElementById('contributionSuccess');
                if (status === 'success' && successMsg) {
                    successMsg.classList.remove('hidden');
                } else if (status === 'cancel') {
                    alert('Le paiement a Ã©tÃ© annulÃ©.');
                }
                // Nettoyer l'URL
                history.replaceState({ page: 'contribute' }, '', '/contribute');
            }
        }
    }

    getPageFromUrl() {
        const rawPath = window.location.pathname.replace(/^\//, '') || 'home';
        const path = rawPath.replace(/\.php$/i, '');

        const legacyMap = {
            'admin/dashboard': 'dashboard',
            'admin/members': 'members',
            'admin/finances': 'finance',
            'admin/expenses': 'expenses',
            'admin/reports': 'reports',
            'admin/settings': 'settings',
            'admin/logs': 'audit-logs',
            'treasurer/dashboard': 'dashboard',
            'secretary/dashboard': 'dashboard',
            'login': 'login',
            'dashboard': 'dashboard',
            'members': 'members',
            'members-form': 'members-form',
            'finance': 'finance',
            'tithes': 'tithes',
            'tithe-form': 'tithe-form',
            'offerings': 'offerings',
            'offering-form': 'offering-form',
            'expenses': 'expenses',
            'expense-form': 'expense-form',
            'reports': 'reports',
            'audit-logs': 'audit-logs',
            'settings': 'settings',
            'contribute': 'contribute',
            'home': 'home'
        };

        const normalized = legacyMap[path] || legacyMap[path.replace(/^frontend\//, '')] || 'home';
        if (!this.currentUser && path !== 'login' && path !== 'contribute' && path !== 'home') {
            return 'home';
        }
        return normalized;
    }

    async navigate(page) {
        try {
            // VÃ©rifier l'authentification
            if (page !== 'login' && page !== 'contribute' && page !== 'home' && !this.currentUser) {
                console.warn('Authentication required, redirecting to home');
                this.navigate('home');
                history.replaceState({ page: 'home' }, '', '/');
                return;
            }

            // Normaliser le rÃ´le pour Ã©viter les erreurs d'accents (trÃ©sorier -> tresorier)
            // GÃ¨re aussi les erreurs d'encodage (tr??sorier)
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
                console.warn(`AccÃ¨s refusÃ©: page=${page}, rÃ´le=${role}`);
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

            // Charger la page appropriÃ©e
            switch (page) {
                case 'login':
                    app.innerHTML = await Pages.loginPage();
                    this.attachLoginEvents();
                    break;

                case 'home':
                    app.innerHTML = await Pages.homePage();
                    break;

                case 'dashboard':
                    app.innerHTML = await Pages.dashboardPage();
                    this.initDashboard();
                    break;

                case 'members':
                    app.innerHTML = await Pages.membersPage();
                    this.attachMembersFilters();
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

                case 'contribute':
                    app.innerHTML = await Pages.memberContributionPage();
                    this.attachPublicContributionEvents();
                    break;

                default:
                    this.navigate('dashboard');
            }

            // Mettre Ã  jour le titre
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
            document.body.style.overflow = 'hidden'; // EmpÃªcher le scroll
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
        // Initialiser les graphiques Chart.js s'il y a des donnees
        const ctx = document.getElementById('financeChart');
        if (ctx) {
            const chartData = window.app?.dashboardChartData;
            if (!chartData || !Array.isArray(chartData.labels)) {
                return;
            }

            const tithes = chartData.tithes || [];
            const offerings = chartData.offerings || [];
            const expenses = chartData.expenses || [];
            const labels = chartData.labels || [];
            const income = labels.map((_, idx) => (Number(tithes[idx]) || 0) + (Number(offerings[idx]) || 0));
            const expenseSeries = labels.map((_, idx) => Number(expenses[idx]) || 0);
            const hasData = [...income, ...expenseSeries].some((value) => Number(value) > 0);

            if (!hasData) {
                const holder = ctx.parentElement;
                if (holder) {
                    holder.innerHTML = '<div class="text-sm text-slate-500">Aucune donnee graphique disponible pour la periode.</div>';
                }
                return;
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Recettes',
                            data: income,
                            borderColor: '#2563eb',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(37, 99, 235, 0.12)'
                        },
                        {
                            label: 'Depenses',
                            data: expenseSeries,
                            borderColor: '#e11d48',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(225, 29, 72, 0.12)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
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
                
                // PrÃ©parer les donnÃ©es sans la photo (on l'upload sÃ©parÃ©ment)
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
                        
                        alert('Membre enregistrÃ© avec succÃ¨s !');
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

    attachMembersFilters() {
        const searchInput = document.getElementById('membersSearch');
        const departmentFilter = document.getElementById('membersDepartmentFilter');
        const dateFilter = document.getElementById('membersDateFilter');
        const statusFilter = document.getElementById('membersStatusFilter');
        const countBadge = document.getElementById('membersCountBadge');
        const rows = Array.from(document.querySelectorAll('[data-member-row]'));

        if (!searchInput || !departmentFilter || !statusFilter || !dateFilter || rows.length === 0) {
            return;
        }

        const applyFilters = () => {
            const query = (searchInput.value || '').trim().toLowerCase();
            const department = (departmentFilter.value || '').toLowerCase();
            const status = (statusFilter.value || '').toLowerCase();
            const joinDate = (dateFilter.value || '').trim();
            let visibleCount = 0;

            rows.forEach((row) => {
                const name = (row.dataset.name || '').toLowerCase();
                const email = (row.dataset.email || '').toLowerCase();
                const phone = (row.dataset.phone || '').toLowerCase();
                const rowDepartment = (row.dataset.department || '').toLowerCase();
                const rowStatus = (row.dataset.status || '').toLowerCase();
                const rowJoinDate = row.dataset.joinDate || '';

                const matchesQuery =
                    !query ||
                    name.includes(query) ||
                    email.includes(query) ||
                    phone.includes(query) ||
                    rowDepartment.includes(query);
                const matchesDepartment = !department || rowDepartment === department;
                const matchesStatus = !status || rowStatus === status;
                const matchesDate = !joinDate || rowJoinDate === joinDate;
                const isVisible = matchesQuery && matchesDepartment && matchesStatus && matchesDate;

                row.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (countBadge) {
                countBadge.textContent = `${visibleCount} résultat(s)`;
            }
        };

        searchInput.addEventListener('input', applyFilters);
        departmentFilter.addEventListener('change', applyFilters);
        dateFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
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
                    alert('Enregistrement financier rÃ©ussi !');
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
                    alert('DÃ©pense enregistrÃ©e avec succÃ¨s !');
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
                alert('Profil mis Ã  jour (simulation)');
            };
        }
    }

    attachExpenseActionEvents() {
        const handleAction = async (id, action, confirmMsg = null, data = null) => {
            if (confirmMsg && !confirm(confirmMsg)) return;
            try {
                const result = await api.post(`/expenses/${id}/${action}`, data);
                if (result.success) {
                    alert(`${action === 'approve' ? 'DÃ©pense approuvÃ©e' : 'DÃ©pense rejetÃ©e'} !`);
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
                handleAction(approveBtn.dataset.id, 'approve', 'Approuver cette dÃ©pense ?');
            } else if (rejectBtn) {
                const reason = prompt('Raison du rejet (facultatif) :');
                if (reason !== null) {
                    handleAction(rejectBtn.dataset.id, 'reject', null, { reason });
                }
            }
        };
    }

    attachPublicContributionEvents() {
        const form = document.getElementById('publicContributionForm');
        if (!form) return;

        // GÃ©rer le basculement entre DÃ®me, Offrande et DÃ©pÃ´t
        const typeRadios = form.querySelectorAll('input[name="contribution_type"]');
        const offeringTypeRow = document.getElementById('offeringTypeRow');
        const offeringSelect = form.querySelector('select[name="type"]');

        const currencyRadios = form.querySelectorAll('input[name="currency"]');
        const currencyPrefix = document.getElementById('currencyPrefix');

        const memberSelect = form.querySelector('select[name="member_id"]');
        const manualNameRow = document.getElementById('manualNameRow');
        const manualNameInput = form.querySelector('input[name="member_name"]');

        // Toggle nom manuel vs selection
        if (memberSelect) {
            memberSelect.addEventListener('change', (e) => {
                if (e.target.value === 'new') {
                    manualNameRow.classList.remove('hidden');
                    manualNameInput.required = true;
                } else {
                    manualNameRow.classList.add('hidden');
                    manualNameInput.required = false;
                }
            });
        }

        currencyRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (currencyPrefix) {
                    currencyPrefix.textContent = e.target.value === 'USD' ? '$' : 'FC';
                }
            });
        });

        typeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'offering') {
                    offeringTypeRow.classList.remove('hidden');
                    offeringSelect.required = true;
                } else {
                    offeringTypeRow.classList.add('hidden');
                    offeringSelect.required = false;
                }
            });
        });

        form.onsubmit = async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const successMsg = document.getElementById('contributionSuccess');
            
            const formData = new FormData(form);
            const type = formData.get('contribution_type');
            const currency = formData.get('currency') || 'CDF';
            
            const memberId = formData.get('member_id');
            const manualName = formData.get('member_name');
            
            // RÃ©soudre le nom d'affichage pour MaishaPay
            let displayMemberName = "Donateur";
            if (memberId && memberId !== 'new') {
                const selectedOption = memberSelect.options[memberSelect.selectedIndex];
                displayMemberName = selectedOption.text;
            } else if (manualName) {
                displayMemberName = manualName;
            }

            const data = {
                member_id: memberId === 'new' ? null : memberId,
                member_name: displayMemberName,
                amount: formData.get('amount'),
                currency: currency,
                comment: formData.get('comment'),
                date: formData.get('date')
            };

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50');
                }
                
                let result;
                if (type === 'tithe') {
                    result = await api.createPublicTithe({
                        member_id: data.member_id,
                        member_name: data.member_name,
                        amount: data.amount,
                        currency: data.currency,
                        tithe_date: data.date,
                        comment: data.comment
                    });
                } else if (type === 'deposit') {
                    // Pour un dÃ©pÃ´t, on utilise le mÃªme endpoint que l'offrande mais avec un type interne
                    result = await api.createPublicOffering({
                        member_id: data.member_id,
                        member_name: data.member_name,
                        type: 'autre',
                        amount: data.amount,
                        currency: data.currency,
                        offering_date: data.date,
                        description: `DÃ‰PÃ”T - ${data.member_name} - ${data.comment || ''}`
                    });
                } else {
                    result = await api.createPublicOffering({
                        member_id: data.member_id,
                        member_name: data.member_name,
                        type: formData.get('type'),
                        amount: data.amount,
                        currency: data.currency,
                        offering_date: data.date,
                        description: `OFFRANDE (${formData.get('type')}) - ${data.member_name} - ${data.comment || ''}`
                    });
                }

                if (result.success) {
                    if (result.payment_url) {
                        submitBtn.innerHTML = '<span>Redirection...</span> <i class="fas fa-spinner fa-spin"></i>';
                        window.location.href = result.payment_url;
                        return;
                    }

                    successMsg.classList.remove('hidden');
                    form.reset();
                    manualNameRow.classList.add('hidden');
                    offeringTypeRow.classList.add('hidden');
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    
                    setTimeout(() => {
                        successMsg.classList.add('hidden');
                    }, 8000);
                } else {
                    alert(result.error || 'Une erreur est survenue');
                }
            } catch (err) {
                alert(err.message);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50');
                }
            }
        };
    }

}

// Lancer l'app quand le DOM est prÃªt
window.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});

