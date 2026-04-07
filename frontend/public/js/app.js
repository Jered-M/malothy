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
                const memberId = link.getAttribute('data-edit-member');
                
                // Si on va au dashboard, on nettoie l'URL (racine)
                const url = page === 'dashboard' ? '/' : `/${page}`;
                history.pushState({ page, memberId }, '', url);
                this.navigate(page, { memberId });
                
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
            const memberId = e.state ? e.state.memberId : null;
            this.navigate(page, { memberId });
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

    async navigate(page, params = {}) {
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
            else if (role.includes('membre') || role.startsWith('mem')) role = 'member';

            const perms = {
                'dashboard': ['admin', 'tresorier', 'secretaire'],
                'member-dashboard': ['admin', 'member'],
                'members': ['admin', 'secretaire'],
                'members-form': ['admin', 'secretaire'],
                'tithes': ['admin', 'tresorier'],
                'tithe-form': ['admin', 'tresorier'],
                'offerings': ['admin', 'tresorier'],
                'offering-form': ['admin', 'tresorier'],
                'finance': ['admin', 'tresorier'],
                'expenses': ['admin', 'tresorier', 'secretaire'],
                'expense-form': ['admin', 'tresorier', 'secretaire'],
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
                    this.attachHomeEvents();
                    break;

                case 'dashboard':
                    if (role === 'member') {
                        this.navigate('member-dashboard');
                        history.replaceState({ page: 'member-dashboard' }, '', '/member-dashboard');
                        return;
                    }
                    app.innerHTML = await Pages.dashboardPage();
                    this.initDashboard();
                    break;

                case 'member-dashboard':
                    app.innerHTML = await Pages.memberDashboardPage();
                    break;

                case 'members':
                    app.innerHTML = await Pages.membersPage();
                    this.attachMembersFilters();
                    break;

                case 'members-form':
                    app.innerHTML = await Pages.memberFormPage(params.memberId);
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
                    this.attachReportEvents();
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


    attachHomeEvents() {
        const navLinks = Array.from(document.querySelectorAll('.nav-link[href^="#"]'));
        if (!navLinks.length) return;

        const sections = navLinks
            .map((link) => document.querySelector(link.getAttribute('href')))
            .filter(Boolean);

        const setActiveLink = (targetId) => {
            navLinks.forEach((link) => {
                const isActive = link.getAttribute('href') === `#${targetId}`;
                link.classList.toggle('active', isActive);
                if (isActive) {
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        };

        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                const targetId = (link.getAttribute('href') || '').replace('#', '');
                if (targetId) {
                    setActiveLink(targetId);
                }
            });
        });

        const currentHash = window.location.hash.replace('#', '');
        if (currentHash) {
            setActiveLink(currentHash);
        } else {
            setActiveLink('hero');
        }

        if ('IntersectionObserver' in window && sections.length) {
            const observer = new IntersectionObserver(
                (entries) => {
                    const visibleEntry = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((left, right) => right.intersectionRatio - left.intersectionRatio)[0];

                    if (visibleEntry?.target?.id) {
                        setActiveLink(visibleEntry.target.id);
                    }
                },
                {
                    rootMargin: '-35% 0px -45% 0px',
                    threshold: [0.2, 0.45, 0.7]
                }
            );

            sections.forEach((section) => observer.observe(section));
        }

        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(contactForm);
                const data = Object.fromEntries(formData.entries());
                const submitBtn = contactForm.querySelector('button[type="submit"]');
                const successMsg = document.getElementById('contactSuccess');
                
                try {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Envoi en cours... <i class="fas fa-spinner fa-spin"></i>';
                    
                    const result = await api.request('POST', '/contact', data);
                    if (result.success) {
                        if (successMsg) {
                            successMsg.classList.remove('hidden');
                        }
                        contactForm.reset();
                        if (successMsg) {
                            setTimeout(() => successMsg.classList.add('hidden'), 5000);
                        }
                    } else {
                        alert(result.error || 'Erreur lors de l\'envoi du message');
                    }
                } catch (err) {
                    alert('Erreur: ' + err.message);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Envoyer le message';
                }
            };
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
                        
                        const role = UI.normalizeRole(this.currentUser.role);
                        const nextPage = role === 'member' ? 'member-dashboard' : 'dashboard';
                        
                        history.replaceState({ page: nextPage }, '', '/' + (nextPage === 'dashboard' ? '' : nextPage));
                        this.navigate(nextPage);
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
                    const isEdit = !!data.id;
                    const method = isEdit ? 'PUT' : 'POST';
                    const endpoint = isEdit ? `/members/${data.id}` : '/members';
                    
                    const result = await api.request(method, endpoint, data);
                    
                    if (result.success) {
                        const memberId = isEdit ? data.id : result.id;
                        
                        // Si une photo est fournie, l'uploader
                        if (photoFile && photoFile.size > 0) {
                            const photoFormData = new FormData();
                            photoFormData.append('photo', photoFile);
                            await api.request('POST', `/members/${memberId}/photo`, photoFormData);
                        }
                        
                        let msg = 'Membre enregistré avec succès !';
                        if (result.account) {
                            msg += `\n\nCompte membre créé :\nIdentifiant : ${result.account.username}\nMot de passe : ${result.account.password}`;
                        }
                        if (result.password_notification?.email) {
                            if (result.password_notification.sent) {
                                msg += `\n\nNotification mot de passe envoyee a : ${result.password_notification.email}`;
                            } else {
                                msg += `\n\nNotification mot de passe non envoyee a : ${result.password_notification.email}`;
                                if (result.password_notification.warning) {
                                    msg += `\nDetails : ${result.password_notification.warning}`;
                                }
                            }
                        }
                        alert(msg);
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

        // Gestion de la suppression d'un membre
        const tableBody = document.querySelector('.pro-table tbody');
        if (tableBody) {
            tableBody.onclick = async (e) => {
                const deleteBtn = e.target.closest('[data-delete-member]');
                if (deleteBtn) {
                    const memberId = deleteBtn.dataset.deleteMember;
                    const memberName = deleteBtn.dataset.memberName;
                    
                    if (confirm(`Êtes-vous sûr de vouloir supprimer le membre "${memberName}" ?\n\nCette action est irréversible et supprimera également son compte utilisateur associé.`)) {
                        try {
                            const result = await api.delete(`/members/${memberId}`);
                            if (result.success) {
                                alert('Membre supprimé avec succès.');
                                this.navigate('members');
                            }
                        } catch (error) {
                            alert('Erreur lors de la suppression : ' + error.message);
                        }
                    }
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
        const app = document.querySelector('#app');
        const homeEventForm = document.querySelector('#homeEventForm');
        const homeEventIndex = document.querySelector('#homeEventIndex');
        const homeEventsPayload = document.querySelector('#homeEventsPayload');
        const homeEventReset = document.querySelector('#homeEventReset');
        const homeEventSubmitLabel = document.querySelector('#homeEventSubmitLabel');
        const homeEventImageUpload = document.querySelector('#homeEventImageUpload');

        const readHomeEvents = () => {
            if (!homeEventsPayload || !homeEventsPayload.value) {
                return [];
            }

            try {
                const parsed = JSON.parse(decodeURIComponent(homeEventsPayload.value));
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                console.warn('Impossible de decoder les evenements admin:', error);
                return [];
            }
        };

        const writeHomeEvents = (events) => {
            if (!homeEventsPayload) return;
            homeEventsPayload.value = encodeURIComponent(JSON.stringify(events || []));
        };

        const resetHomeEventForm = () => {
            if (!homeEventForm) return;
            homeEventForm.reset();
            if (homeEventIndex) {
                homeEventIndex.value = '';
            }
            if (homeEventSubmitLabel) {
                homeEventSubmitLabel.textContent = 'Publier l evenement';
            }
        };

        const fillHomeEventForm = (event, index) => {
            if (!homeEventForm || !event) return;

            homeEventForm.elements.title.value = event.title || '';
            homeEventForm.elements.period.value = event.period || '';
            homeEventForm.elements.description.value = event.description || '';
            homeEventForm.elements.image_url.value = event.image_url || '';
            if (homeEventImageUpload) {
                homeEventImageUpload.value = '';
            }

            if (homeEventIndex) {
                homeEventIndex.value = String(index);
            }
            if (homeEventSubmitLabel) {
                homeEventSubmitLabel.textContent = 'Mettre a jour l evenement';
            }

            homeEventForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        if (homeEventReset) {
            homeEventReset.onclick = () => resetHomeEventForm();
        }

        if (homeEventForm) {
            homeEventForm.onsubmit = async (e) => {
                e.preventDefault();

                const formData = new FormData(homeEventForm);
                const title = String(formData.get('title') || '').trim();
                const period = String(formData.get('period') || '').trim();
                const description = String(formData.get('description') || '').trim();
                let image_url = String(formData.get('image_url') || '').trim();
                const imageFile = formData.get('image_file');

                if (!title || !period || !description) {
                    alert('Titre, periode et description sont obligatoires.');
                    return;
                }

                try {
                    if (imageFile instanceof File && imageFile.size > 0) {
                        if (homeEventSubmitLabel) {
                            homeEventSubmitLabel.textContent = 'Upload image en cours...';
                        }

                        const uploadResult = await api.uploadHomeEventImage(imageFile);
                        if (!uploadResult.success) {
                            alert(uploadResult.error || 'Erreur lors de l upload de l image');
                            return;
                        }

                        image_url =
                            uploadResult.data?.url ||
                            uploadResult.data?.path ||
                            image_url;
                    }
                } catch (error) {
                    alert(error.message || 'Erreur lors de l upload de l image');
                    if (homeEventSubmitLabel) {
                        homeEventSubmitLabel.textContent =
                            homeEventIndex && homeEventIndex.value !== ''
                                ? 'Mettre a jour l evenement'
                                : 'Publier l evenement';
                    }
                    return;
                }

                const currentEvents = readHomeEvents();
                const nextEvent = { title, period, description, image_url };
                const editIndex = homeEventIndex && homeEventIndex.value !== ''
                    ? Number(homeEventIndex.value)
                    : -1;

                let nextEvents = [...currentEvents];

                if (Number.isInteger(editIndex) && editIndex >= 0 && editIndex < nextEvents.length) {
                    nextEvents[editIndex] = nextEvent;
                } else {
                    nextEvents.unshift(nextEvent);
                }

                try {
                    if (homeEventSubmitLabel) {
                        homeEventSubmitLabel.textContent =
                            editIndex >= 0 ? 'Mise a jour en cours...' : 'Publication en cours...';
                    }

                    const result = await api.saveHomeEvents(nextEvents);
                    if (!result.success) {
                        alert(result.error || 'Erreur lors de la publication');
                        return;
                    }

                    writeHomeEvents(Array.isArray(result.data) ? result.data : nextEvents);
                    alert(editIndex >= 0 ? 'Evenement mis a jour.' : 'Evenement publie.');
                    this.navigate('settings');
                } catch (error) {
                    alert(error.message || 'Erreur lors de la publication');
                } finally {
                    if (homeEventSubmitLabel) {
                        homeEventSubmitLabel.textContent =
                            homeEventIndex && homeEventIndex.value !== ''
                                ? 'Mettre a jour l evenement'
                                : 'Publier l evenement';
                    }
                }
            };
        }

        if (app) {
            app.onclick = async (e) => {
                const editTrigger = e.target.closest('[data-edit-home-event]');
                if (editTrigger) {
                    e.preventDefault();
                    const index = Number(editTrigger.dataset.editHomeEvent);
                    const events = readHomeEvents();
                    fillHomeEventForm(events[index], index);
                    return;
                }

                const deleteTrigger = e.target.closest('[data-delete-home-event]');
                if (!deleteTrigger) return;

                e.preventDefault();

                const index = Number(deleteTrigger.dataset.deleteHomeEvent);
                const events = readHomeEvents();
                if (!Number.isInteger(index) || index < 0 || index >= events.length) {
                    return;
                }

                if (!confirm('Supprimer cet evenement de la page accueil ?')) {
                    return;
                }

                const nextEvents = events.filter((_, eventIndex) => eventIndex !== index);

                try {
                    const result = await api.saveHomeEvents(nextEvents);
                    if (!result.success) {
                        alert(result.error || 'Erreur lors de la suppression');
                        return;
                    }

                    writeHomeEvents(Array.isArray(result.data) ? result.data : nextEvents);
                    alert('Evenement supprime.');
                    this.navigate('settings');
                } catch (error) {
                    alert(error.message || 'Erreur lors de la suppression');
                }
            };
        }

        const profileForm = document.querySelector('#profileForm');
        if (profileForm) {
            profileForm.onsubmit = async (e) => {
                e.preventDefault();
                alert('Profil mis à jour (simulation)');
            };
        }

        const settingsForm = document.querySelector('#settingsForm');
        if (settingsForm) {
            settingsForm.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(settingsForm);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    const result = await api.request('POST', '/settings', data);
                    if (result.success) {
                        alert('Paramètres enregistrés avec succès !');
                    } else {
                        alert(result.error || 'Erreur lors de l\'enregistrement');
                    }
                } catch (error) {
                    alert('Erreur: ' + error.message);
                }
            };
        }
    }

    attachReportEvents() {
        const container = document.querySelector('#app');
        if (!container) return;

        container.onclick = async (e) => {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) return;

            e.preventDefault();

            const action = trigger.dataset.action || '';
            const reportType = trigger.dataset.reportType || '';
            const year = String(new Date().getFullYear());

            try {
                trigger.classList.add('pointer-events-none', 'opacity-70');

                switch (action) {
                    case 'export-pdf':
                        await api.exportPDF(reportType || 'balance', year);
                        break;
                    case 'export-csv':
                        await api.exportCSV(reportType || 'members', year);
                        break;
                    case 'export-json':
                        await api.exportJSON();
                        break;
                    case 'export-sql':
                        await api.exportSQL();
                        break;
                    default:
                        throw new Error(`Action de rapport inconnue: ${action}`);
                }
            } catch (error) {
                alert(error.message || 'Le telechargement a echoue.');
            } finally {
                trigger.classList.remove('pointer-events-none', 'opacity-70');
            }
        };
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

