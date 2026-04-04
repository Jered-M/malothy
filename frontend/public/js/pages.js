class Pages {
    static getCurrentUser() {
        return UI.getCurrentUser();
    }

    static today() {
        return new Date().toISOString().split('T')[0];
    }

    static toNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    static sum(items, key) {
        return items.reduce((total, item) => total + this.toNumber(item[key]), 0);
    }

    static average(items, key) {
        return items.length ? this.sum(items, key) / items.length : 0;
    }

    static formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'CDF',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(this.toNumber(amount));
    }

    static formatMoneyUSD(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(this.toNumber(amount));
    }

    static convertToUSD(amount) {
        const rate = window.APP_EXCHANGE_RATE_CDF_USD || 2800;
        return this.toNumber(amount) / rate;
    }

    static formatDate(value, options = { day: '2-digit', month: 'short', year: 'numeric' }) {
        if (!value) {
            return '--';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat('fr-FR', options).format(date);
    }

    static formatDateTime(value) {
        return this.formatDate(value, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    static formatLabel(value, fallback = '--') {
        if (!value) {
            return fallback;
        }

        return String(value)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    static fullName(item) {
        const name = [item?.first_name, item?.last_name].filter(Boolean).join(' ').trim();
        return name || item?.name || 'Information indisponible';
    }

    static topGroup(items, key) {
        const grouped = items.reduce((accumulator, item) => {
            const label = item[key] || 'Autre';
            accumulator[label] = (accumulator[label] || 0) + this.toNumber(item.amount);
            return accumulator;
        }, {});

        const [label, amount] = Object.entries(grouped).sort((left, right) => right[1] - left[1])[0] || [];
        return label ? { label, amount } : null;
    }

    static reportCard({ href = '#', page = '', title, subtitle, icon, tone = 'brand', external = false, label = 'Ouvrir' }) {
        const target = external ? 'target="_blank" rel="noopener noreferrer"' : '';
        const navigation = page ? `data-page="${page}"` : '';

        return `
            <a href="${href}" ${navigation} ${target} class="surface-panel action-card">
                <div class="action-card-head">
                    <span class="action-icon tone-${tone}">
                        <i class="fas ${icon}"></i>
                    </span>
                    ${UI.badge(external ? 'Export' : 'Interne', tone)}
                </div>
                <div>
                    <h3 class="action-title">${title}</h3>
                    <p class="action-subtitle">${subtitle}</p>
                </div>
                <span class="action-link">
                    ${label}
                    <i class="fas ${external ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right'}"></i>
                </span>
            </a>
        `;
    }

    static async loginPage() {
        const dateLabel = new Intl.DateTimeFormat('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date());

        return `
            <div class="min-h-screen grid lg:grid-cols-2 overflow-hidden bg-slate-50">
                <!-- Left: Content & Visual -->
                <section class="hidden lg:flex flex-col justify-between p-12 relative bg-slate-900 border-r border-white/5 overflow-hidden">
                    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&q=80')] bg-cover bg-center mix-blend-overlay opacity-40"></div>
                    <div class="absolute inset-0 bg-gradient-to-tr from-slate-950 via-slate-900/90 to-blue-900/50"></div>
                    
                    <div class="relative z-10 w-fit">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white text-xl">
                                <i class="fas fa-church"></i>
                            </div>
                            <h2 class="text-xl font-black text-white tracking-tight uppercase">MALOTY</h2>
                        </div>
                    </div>

                    <div class="relative z-10 max-w-xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-[10px] uppercase font-bold tracking-wider text-slate-300 mb-8">
                            Portail de gestion
                        </div>
                        <h1 class="text-4xl xl:text-5xl font-bold text-white leading-[0.9] mb-6">
                            Gestion d'église <span class="text-blue-400">simplifiée.</span>
                        </h1>
                        <p class="text-lg text-slate-300 leading-relaxed font-medium">
                            Pilotez vos finances et vos membres avec un outil clair et efficace.
                        </p>
                    </div>

                    <div class="relative z-10 grid grid-cols-3 gap-8">
                        <div>
                            <p class="text-3xl font-black text-white mb-1">100%</p>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Sécurisé</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black text-white mb-1">24/7</p>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Disponible</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black text-white mb-1">API</p>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Connectée</p>
                        </div>
                    </div>
                </section>

                <!-- Right: Form -->
                <section class="flex items-center justify-center p-6 lg:p-12 bg-white relative">
                    <div class="w-full max-w-md space-y-12">
                        <div class="lg:hidden">
                            <div class="flex items-center gap-4 mb-12">
                                <div class="w-12 h-12 rounded-xl bg-brand-600 flex items-center justify-center text-white text-xl shadow-xl shadow-brand-500/30">
                                    <i class="fas fa-church"></i>
                                </div>
                                <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase">MALOTY</h2>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-3xl font-bold text-slate-900 mb-2">Connectez-vous</h2>
                            <p class="text-slate-500 font-medium text-sm">Accédez à votre espace de travail</p>
                        </div>

                        <form id="loginForm" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[11px] uppercase font-black tracking-widest text-slate-400 px-1" for="email">Identifiant</label>
                                <div class="group relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors group-focus-within:text-brand-500">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input
                                        type="email"
                                        id="email"
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-3.5 pl-12 pr-4 text-slate-900 font-bold placeholder:text-slate-400 placeholder:font-medium focus:bg-white focus:border-brand-500 focus:outline-none transition-all duration-300"
                                        value="admin@maloty.com"
                                        placeholder="votre@email.com"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] uppercase font-black tracking-widest text-slate-400 px-1" for="password">Mot de passe</label>
                                <div class="group relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors group-focus-within:text-brand-500">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input
                                        type="password"
                                        id="password"
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-3.5 pl-12 pr-4 text-slate-900 font-bold placeholder:text-slate-400 placeholder:font-medium focus:bg-white focus:border-brand-500 focus:outline-none transition-all duration-300"
                                        value="admin123"
                                        placeholder="••••••••"
                                        required
                                    >
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-slate-900 hover:bg-brand-600 text-white font-black py-4 rounded-2xl shadow-2xl shadow-black/10 transition-all duration-300 active:scale-95 flex items-center justify-center gap-3">
                                <span>Accéder au dashboard</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>

                        <div class="text-center pt-4">
                            <p class="text-sm font-medium text-slate-500 mb-2">Vous êtes un membre ?</p>
                            <a href="/contribute" data-page="contribute" class="inline-flex items-center gap-2 text-brand-600 hover:text-brand-700 font-black transition-all hover:translate-x-1">
                                <span>Faire un don ou payer ma dîme</span>
                                <i class="fas fa-hand-holding-heart"></i>
                            </a>
                        </div>

                        <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100">
                            <div class="flex gap-4">
                                <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-black text-slate-900">Mode démonstration</p>
                                    <p class="text-xs text-slate-500 font-medium leading-relaxed">Utilisez les identifiants pré-remplis pour charger les données simulées.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        `;
    }

    static async dashboardPage() {
        try {
            const user = this.getCurrentUser();
            const data = await api.getDashboard();
            const stats = data.stats || {};
            const chartData = data.chartData || {};
            if (window.app) {
                window.app.dashboardChartData = chartData;
            }

            const totalIncome = this.toNumber(stats.monthlyTithes) + this.toNumber(stats.monthlyOfferings);
            const monthlyExpenses = this.toNumber(stats.monthlyExpenses);
            const balance = totalIncome - monthlyExpenses;
            const expenseRate = totalIncome ? Math.round((monthlyExpenses / totalIncome) * 100) : 0;
            const displayName = user.name ? user.name.split(' ')[0] : 'équipe';
            const activeMembers = stats.activeMembers ?? stats.totalMembers ?? 0;
            const totalMembers = stats.totalMembers ?? activeMembers;
            const dateLabel = new Intl.DateTimeFormat('fr-FR', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }).format(new Date());

            return UI.shell(
                'dashboard',
                `
                    ${UI.pageHeader({
                        title: 'Tableau de bord',
                        subtitle: `Mise à jour : ${dateLabel}`,
                        actions: `
                            <a href="#" data-page="reports" class="btn-secondary">
                                <i class="fas fa-file-pdf"></i>
                                <span>Rapports</span>
                            </a>
                            <a href="#" data-page="finance" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                <span>Nouvelle Entrée</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                        ${UI.statCard('Membres actifs', activeMembers, 'fa-users', 'brand', `Total: ${totalMembers}`)}
                        ${UI.statCard('Dîmes du mois', this.formatMoney(stats.monthlyTithes || 0), 'fa-hand-holding-heart', 'emerald')}
                        ${UI.statCard('Offrandes du mois', this.formatMoney(stats.monthlyOfferings || 0), 'fa-donate', 'brand')}
                        ${UI.statCard('Solde actuel', this.formatMoney(balance), 'fa-wallet', balance >= 0 ? 'emerald' : 'rose')}
                    </section>

                    <section class="flex flex-col gap-8">
                        <!-- Chart Block -->
                        <div class="surface-panel chart-card p-6 md:p-8">
                            <h2 class="text-xl font-bold text-slate-950 mb-6">Suivi des 6 derniers mois</h2>
                            <div class="h-[280px]">
                                <canvas id="financeChart"></canvas>
                            </div>
                            <div class="mt-8 grid gap-4 md:grid-cols-3">
                                <div class="summary-row">
                                    <strong>Recettes</strong>
                                    <b>${this.formatMoney(totalIncome)}</b>
                                    <span>${this.formatMoneyUSD(this.convertToUSD(totalIncome))}</span>
                                </div>
                                <div class="summary-row">
                                    <strong>Dépenses</strong>
                                    <b>${this.formatMoney(monthlyExpenses)}</b>
                                    <span>${this.formatMoneyUSD(this.convertToUSD(monthlyExpenses))}</span>
                                </div>
                                <div class="summary-row">
                                    <strong>Utilisation</strong>
                                    <b>${expenseRate}%</b>
                                    <span>${this.formatMoneyUSD(this.convertToUSD(balance))}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            ${user.role === 'admin' || user.role === 'secretaire' ? UI.actionCard('members', 'Membres', 'Gérer les fidèles.', 'fa-users', 'brand') : ''}
                            ${user.role === 'admin' || user.role === 'tresorier' ? UI.actionCard('finance', 'Caisse', 'Dîmes et offrandes.', 'fa-wallet', 'emerald') : ''}
                            ${user.role === 'admin' || user.role === 'tresorier' ? UI.actionCard('expenses', 'Dépenses', 'Suivi des sorties.', 'fa-receipt', 'rose') : ''}
                        </div>
                    </section>
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async membersPage() {
        try {
            const result = await api.getMembers();
            const members = result.data || [];
            const activeCount = members.filter((member) => UI.normalizeText(member.status) === 'actif').length;
            const departmentCount = new Set(members.map((member) => member.department).filter(Boolean)).size;
            const contactCount = members.filter((member) => member.phone || member.email).length;
            const departments = Array.from(
                new Set(members.map((member) => member.department).filter(Boolean))
            ).sort((a, b) => a.localeCompare(b, 'fr'));

            const emailCounts = members.reduce((acc, member) => {
                const key = (member.email || '').trim().toLowerCase();
                if (key) acc[key] = (acc[key] || 0) + 1;
                return acc;
            }, {});

            const phoneCounts = members.reduce((acc, member) => {
                const key = (member.phone || '').trim();
                if (key) acc[key] = (acc[key] || 0) + 1;
                return acc;
            }, {});

            const duplicateCount = members.filter((member) => {
                const emailKey = (member.email || '').trim().toLowerCase();
                const phoneKey = (member.phone || '').trim();
                return (emailKey && emailCounts[emailKey] > 1) || (phoneKey && phoneCounts[phoneKey] > 1);
            }).length;

            return UI.shell(
                'members',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Registre',
                        title: 'Base membres professionnelle',
                        subtitle: 'Un registre plus propre, plus lisible et mieux structuré pour les opérations administratives courantes.',
                        actions: `
                            <a href="#" data-page="members-form" class="btn-primary">
                                <i class="fas fa-user-plus"></i>
                                <span>Nouveau membre</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Total membres', members.length, 'fa-address-book', 'brand', 'Fiches enregistrées')}
                        ${UI.statCard('Statuts actifs', activeCount, 'fa-user-check', 'emerald', 'Comptes considérés actifs')}
                        ${UI.statCard('Départements', departmentCount || 0, 'fa-sitemap', 'amber', `${contactCount} fiches avec contact`)}
                    </section>

                    ${duplicateCount ? `
                        <section class="surface-panel border border-amber-200 bg-amber-50/60">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-amber-900">Doublons détectés</p>
                                    <p class="text-xs text-amber-800 font-medium">Certaines fiches partagent le même email ou téléphone. Corrigez-les avant la soutenance.</p>
                                </div>
                                ${UI.badge(`${duplicateCount} fiche(s)`, 'amber')}
                            </div>
                        </section>
                    ` : ''}

                    <section class="surface-panel p-6 md:p-8">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="field-label" for="membersSearch">Recherche</label>
                                <input id="membersSearch" type="text" class="pro-input" placeholder="Nom, email, téléphone, département">
                            </div>
                            <div>
                                <label class="field-label" for="membersDepartmentFilter">Département</label>
                                <select id="membersDepartmentFilter" class="pro-select">
                                    <option value="">Tous les départements</option>
                                    ${departments.map((dept) => `<option value="${dept}">${dept}</option>`).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="field-label" for="membersDateFilter">Date d'adhésion</label>
                                <input id="membersDateFilter" type="date" class="pro-input">
                            </div>
                            <div>
                                <label class="field-label" for="membersStatusFilter">Statut</label>
                                <select id="membersStatusFilter" class="pro-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                    <option value="suspendu">Suspendu</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    ${
                        members.length
                            ? `
                                <section class="surface-panel table-shell">
                                    <div class="table-header">
                                        <div>
                                            <h2 class="table-title">Liste des membres</h2>
                                            <p class="table-subtitle">Registre complet des membres et informations de contact.</p>
                                        </div>
                                        <span id="membersCountBadge" class="badge badge-brand">${members.length} enregistrements</span>
                                    </div>

                                    <div class="pro-table-wrap">
                                        <table class="pro-table">
                                            <thead>
                                                <tr>
                                                    <th>Membre</th>
                                                    <th>Département</th>
                                                    <th>Téléphone</th>
                                                    <th>Email</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${members
                                                    .map(
                                                        (member) => {
                                                            const emailKey = (member.email || '').trim().toLowerCase();
                                                            const phoneKey = (member.phone || '').trim();
                                                            const isDuplicate = (emailKey && emailCounts[emailKey] > 1) || (phoneKey && phoneCounts[phoneKey] > 1);
                                                            const departmentLabel = member.department || 'Général';
                                                            const duplicateBadge = isDuplicate ? UI.badge('Doublon', 'amber') : '';

                                                            return `
                                                            <tr data-member-row="1"
                                                                data-name="${this.fullName(member)}"
                                                                data-email="${member.email || ''}"
                                                                data-phone="${member.phone || ''}"
                                                                data-department="${departmentLabel}"
                                                                data-status="${member.status || 'actif'}"
                                                                data-join-date="${member.join_date || ''}">
                                                                <td>
                                                                    <div class="table-user">
                                                                        <div class="table-avatar">${UI.initials(member.first_name, member.last_name)}</div>
                                                                        <div>
                                                                            <p class="table-name">${this.fullName(member)}</p>
                                                                            <p class="table-muted">${member.email || 'Email non renseigné'}</p>
                                                                            ${duplicateBadge}
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>${UI.badge(departmentLabel, 'slate')}</td>
                                                                <td>
                                                                    <p class="table-name">${member.phone || 'Aucun téléphone'}</p>
                                                                </td>
                                                                <td>
                                                                    <p class="table-name">${member.email || 'Adresse email absente'}</p>
                                                                </td>
                                                                <td>${UI.statusBadge(member.status || 'actif')}</td>
                                                            </tr>
                                                        `;
                                                        }
                                                    )
                                                    .join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-users',
                                  'Aucun membre enregistré',
                                  'Commencez par créer la première fiche membre pour alimenter le registre et donner de la matière au dashboard.',
                                  `
                                      <a href="#" data-page="members-form" class="btn-primary">
                                          <i class="fas fa-user-plus"></i>
                                          <span>Créer une fiche membre</span>
                                      </a>
                                  `
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async memberFormPage() {
        return UI.shell(
            'members',
            `
                ${UI.pageHeader({
                    eyebrow: 'Création',
                    title: 'Ajouter un membre',
                    subtitle: 'Le formulaire a été enrichi pour mieux refléter les champs réellement attendus par l’API et offrir une expérience plus crédible.',
                    actions: `
                        <a href="#" data-page="members" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour au registre</span>
                        </a>
                    `
                })}

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.3fr,0.8fr]">
                    <div class="surface-panel p-6 md:p-8">
                        <form id="memberForm" class="member-form grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="field-label" for="first_name">Prénom</label>
                                <input id="first_name" name="first_name" type="text" class="pro-input" placeholder="Ex: Josué" required>
                            </div>
                            <div>
                                <label class="field-label" for="last_name">Nom</label>
                                <input id="last_name" name="last_name" type="text" class="pro-input" placeholder="Ex: Kabamba" required>
                            </div>
                            <div>
                                <label class="field-label" for="member_email">Email</label>
                                <input id="member_email" name="email" type="email" class="pro-input" placeholder="nom@exemple.com">
                            </div>
                            <div>
                                <label class="field-label" for="phone">Téléphone</label>
                                <input id="phone" name="phone" type="tel" class="pro-input" placeholder="+243 ..." required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label" for="address">Adresse</label>
                                <input id="address" name="address" type="text" class="pro-input" placeholder="Commune, quartier, avenue">
                            </div>
                            <div>
                                <label class="field-label" for="department">Département</label>
                                <input id="department" name="department" type="text" class="pro-input" placeholder="Chorale, jeunesse, accueil...">
                            </div>
                            <div>
                                <label class="field-label" for="join_date">Date d’adhésion</label>
                                <input id="join_date" name="join_date" type="date" class="pro-input" value="${this.today()}" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label" for="photo">Photo de profil (optionnel)</label>
                                <input id="photo" name="photo" type="file" class="pro-input" accept="image/jpeg,image/png,image/webp">
                                <p class="text-[11px] text-slate-500 mt-2">JPG, PNG ou WEBP. Max 5MB</p>
                            </div>
                            <div class="md:col-span-2">
                                <button type="submit" class="btn-primary w-full">
                                    <i class="fas fa-floppy-disk"></i>
                                    <span>Enregistrer le membre</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <aside class="surface-panel p-6 md:p-8">
                        <p class="page-eyebrow">Qualité des données</p>
                        <h2 class="text-2xl font-black text-slate-950">Conseils de saisie</h2>
                        <div class="summary-list mt-5">
                            <div class="summary-row">
                                <div>
                                    <strong>Coordonnées utiles</strong>
                                    <span>Le téléphone et la date d’adhésion sont obligatoires.</span>
                                </div>
                                <b>Requis</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Département</strong>
                                    <span>Précisez le service pour segmenter les rapports plus tard.</span>
                                </div>
                                <b>Optionnel</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Adresse</strong>
                                    <span>Ajoutez-la si vous souhaitez préparer un registre plus complet.</span>
                                </div>
                                <b>Conseillé</b>
                            </div>
                        </div>
                    </aside>
                </section>
            `
        );
    }

    static async financePage() {
        try {
            const [tithesResult, offeringsResult] = await Promise.all([api.getTithes(), api.getOfferings()]);
            const tithes = tithesResult.data || [];
            const offerings = offeringsResult.data || [];
            const totalTithes = this.sum(tithes, 'amount');
            const totalOfferings = this.sum(offerings, 'amount');
            const totalIncome = totalTithes + totalOfferings;
            const topOffering = this.topGroup(offerings, 'type');

            return UI.shell(
                'finance',
                `
                    ${UI.pageHeader({
                        title: 'Gestion des finances',
                        subtitle: 'Suivez les dîmes, les offrandes et les volumes de collecte.',
                        actions: `
                            <a href="#" data-page="reports" class="btn-secondary">
                                <i class="fas fa-file-export"></i>
                                <span>Préparer les exports</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Dîmes cumulées', this.formatMoney(totalTithes), 'fa-hand-holding-heart', 'emerald', `${tithes.length} enregistrement(s)`)}
                        ${UI.statCard('Offrandes cumulées', this.formatMoney(totalOfferings), 'fa-gift', 'brand', `${offerings.length} entrée(s)`)}
                        ${UI.statCard('Total consolidé', this.formatMoney(totalIncome), 'fa-coins', 'amber', topOffering ? `Type dominant : ${this.formatLabel(topOffering.label)}` : 'Aucune ventilation disponible')}
                    </section>

                    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            ${UI.actionCard('tithes', 'Registre des dîmes', 'Consultez l’historique, les montants et ouvrez le formulaire d’encaissement.', 'fa-hand-holding-heart', 'emerald', 'Collecte')}
                            ${UI.actionCard('offerings', 'Journal des offrandes', 'Suivez les types de collecte et mettez en forme les entrées de caisse.', 'fa-gift', 'brand', 'Suivi')}
                        </div>

                        <aside class="surface-panel p-6 md:p-8">
                            <p class="page-eyebrow">Lecture métier</p>
                            <h2 class="text-2xl font-black text-slate-950">Synthèse financière</h2>
                            <div class="summary-list mt-5">
                                <div class="summary-row">
                                    <div>
                                        <strong>Ticket moyen dîme</strong>
                                        <span>Montant moyen par enregistrement</span>
                                    </div>
                                    <b>${this.formatMoney(this.average(tithes, 'amount'))}</b>
                                </div>
                                <div class="summary-row">
                                    <div>
                                        <strong>Ticket moyen offrande</strong>
                                        <span>Montant moyen par collecte</span>
                                    </div>
                                    <b>${this.formatMoney(this.average(offerings, 'amount'))}</b>
                                </div>
                                <div class="summary-row">
                                    <div>
                                        <strong>Type dominant</strong>
                                        <span>Répartition actuelle des offrandes</span>
                                    </div>
                                    <b>${topOffering ? this.formatLabel(topOffering.label) : 'N/A'}</b>
                                </div>
                            </div>
                        </aside>
                    </section>
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async expensesPage() {
        try {
            const user = this.getCurrentUser();
            const result = await api.getExpenses();
            const expenses = result.data || [];
            const totalExpenses = this.sum(expenses, 'amount');
            const pendingCount = expenses.filter((expense) => UI.normalizeText(expense.status).includes('attente')).length;
            const approvedCount = expenses.filter((expense) => UI.normalizeText(expense.status).includes('approuve')).length;
            const rejectedCount = expenses.filter((expense) => UI.normalizeText(expense.status).includes('rejet')).length;

            return UI.shell(
                'expenses',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Décaissements',
                        title: 'Suivi professionnel des dépenses',
                        subtitle: 'La page affiche désormais l’activité réelle, les statuts et les pièces éventuelles au lieu d’un simple état vide.',
                        actions: `
                            <a href="#" data-page="expense-form" class="btn-rose">
                                <i class="fas fa-plus"></i>
                                <span>Nouvelle dépense</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                        ${UI.statCard('Montant total', this.formatMoney(totalExpenses), 'fa-wallet', 'rose', `${expenses.length} dépense(s) enregistrée(s)`)}
                        ${UI.statCard('En attente', pendingCount, 'fa-hourglass-half', 'amber', 'Demandes à examiner')}
                        ${UI.statCard('Approuvées', approvedCount, 'fa-circle-check', 'emerald', 'Sorties validées')}
                        ${UI.statCard('Rejetées', rejectedCount, 'fa-circle-xmark', 'rose', 'Sorties refusées')}
                    </section>

                    ${
                        expenses.length
                            ? `
                                <section class="surface-panel table-shell">
                                    <div class="table-header">
                                        <div>
                                            <h2 class="table-title">Journal des dépenses</h2>
                                            <p class="table-subtitle">Montants, catégories, statuts et justificatifs en un seul tableau.</p>
                                        </div>
                                        ${UI.badge(`${expenses.length} ligne(s)`, 'rose')}
                                    </div>

                                    <div class="pro-table-wrap">
                                        <table class="pro-table">
                                            <thead>
                                                <tr>
                                                    <th>Catégorie</th>
                                                    <th>Description</th>
                                                    <th>Date</th>
                                                    <th>Statut</th>
                                                    <th class="text-right">Montant</th>
                                                    ${user.role === 'admin' ? '<th class="text-right">Actions</th>' : ''}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${expenses
                                                    .map(
                                                        (expense) => `
                                                            <tr>
                                                                <td>${UI.badge(this.formatLabel(expense.category), 'slate')}</td>
                                                                <td>
                                                                    <p class="table-name">${expense.description || 'Sans description détaillée'}</p>
                                                                    <p class="table-muted">
                                                                        ${
                                                                            expense.document_path
                                                                                ? `<a class="text-brand-700 font-bold" href="${expense.document_path}" target="_blank" rel="noopener noreferrer">Voir le justificatif</a>`
                                                                                : 'Aucun justificatif joint'
                                                                        }
                                                                    </p>
                                                                </td>
                                                                <td>
                                                                    <p class="table-name">${this.formatDate(expense.expense_date)}</p>
                                                                    <p class="table-muted">${expense.recorded_at ? this.formatDateTime(expense.recorded_at) : 'Horodatage indisponible'}</p>
                                                                </td>
                                                                <td>${UI.statusBadge(expense.status || 'en attente')}</td>
                                                                <td class="text-right">
                                                                    <p class="table-name">${this.formatMoney(expense.amount)}</p>
                                                                </td>
                                                                ${
                                                                    user.role === 'admin' && (expense.status === 'en attente' || !expense.status)
                                                                        ? `
                                                                        <td class="text-right">
                                                                            <div class="flex justify-end gap-2">
                                                                                <button class="btn-icon btn-emerald approve-expense" data-id="${expense.id}" title="Approuver">
                                                                                    <i class="fas fa-check"></i>
                                                                                </button>
                                                                                <button class="btn-icon btn-rose reject-expense" data-id="${expense.id}" title="Rejeter">
                                                                                    <i class="fas fa-times"></i>
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                    `
                                                                        : user.role === 'admin' ? '<td></td>' : ''
                                                                }
                                                            </tr>
                                                        `
                                                    )
                                                    .join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-receipt',
                                  'Aucune dépense enregistrée',
                                  'Ajoutez une première sortie pour démarrer le suivi administratif et produire des rapports cohérents.',
                                  `
                                      <button id="addExpenseBtn" type="button" class="btn-rose">
                                          <i class="fas fa-plus"></i>
                                          <span>Créer la première dépense</span>
                                      </button>
                                  `
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async titheListPage() {
        try {
            const result = await api.getTithes();
            const tithes = result.data || [];
            const totalTithes = this.sum(tithes, 'amount');

            return UI.shell(
                'finance',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Dîmes',
                        title: 'Registre des contributions',
                        subtitle: 'Visualisez l’historique des dîmes avec une structure plus propre, plus lisible et directement exploitable.',
                        actions: `
                            <a href="#" data-page="finance" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                <span>Retour finances</span>
                            </a>
                            <a href="#" data-page="tithe-form" class="btn-emerald">
                                <i class="fas fa-plus"></i>
                                <span>Nouvelle dîme</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Montant cumulé', this.formatMoney(totalTithes), 'fa-sack-dollar', 'emerald', 'Total de la liste courante')}
                        ${UI.statCard('Entrées', tithes.length, 'fa-list-check', 'brand', 'Nombre d’enregistrements')}
                        ${UI.statCard('Ticket moyen', this.formatMoney(this.average(tithes, 'amount')), 'fa-chart-column', 'amber', 'Montant moyen par dîme')}
                    </section>

                    ${
                        tithes.length
                            ? `
                                <section class="surface-panel table-shell">
                                    <div class="table-header">
                                        <div>
                                            <h2 class="table-title">Historique des dîmes</h2>
                                            <p class="table-subtitle">Chaque ligne relie un fidèle, une date de versement et le montant saisi.</p>
                                        </div>
                                        ${UI.badge(`${tithes.length} mouvement(s)`, 'emerald')}
                                    </div>

                                    <div class="pro-table-wrap">
                                        <table class="pro-table">
                                            <thead>
                                                <tr>
                                                    <th>Fidèle</th>
                                                    <th>Observation</th>
                                                    <th>Date</th>
                                                    <th class="text-right">Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${tithes
                                                    .map(
                                                        (tithe) => `
                                                            <tr>
                                                                <td>
                                                                    <div class="table-user">
                                                                        <div class="table-avatar">${UI.initials(tithe.first_name, tithe.last_name)}</div>
                                                                        <div>
                                                                            <p class="table-name">${this.fullName(tithe)}</p>
                                                                            <p class="table-muted">Membre lié au registre</p>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <p class="table-name">${tithe.comment || 'Aucun commentaire'}</p>
                                                                    <p class="table-muted">Contribution enregistrée manuellement</p>
                                                                </td>
                                                                <td>${this.formatDate(tithe.tithe_date)}</td>
                                                                <td class="text-right">
                                                                    <p class="table-name text-emerald-700">${this.formatMoney(tithe.amount)}</p>
                                                                </td>
                                                            </tr>
                                                        `
                                                    )
                                                    .join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-hand-holding-heart',
                                  'Aucune dîme enregistrée',
                                  'Ajoutez une première contribution pour alimenter le registre et la synthèse financière.',
                                  `
                                      <a href="#" data-page="tithe-form" class="btn-emerald">
                                          <i class="fas fa-plus"></i>
                                          <span>Enregistrer une dîme</span>
                                      </a>
                                  `
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async titheFormPage(members = []) {
        if (!members.length) {
            return UI.shell(
                'finance',
                UI.emptyState(
                    'fa-users',
                    'Aucun membre disponible',
                    'Le formulaire de dîme a besoin d’un membre existant pour rattacher correctement la contribution.',
                    `
                        <a href="#" data-page="members-form" class="btn-primary">
                            <i class="fas fa-user-plus"></i>
                            <span>Créer un membre</span>
                        </a>
                    `
                )
            );
        }

        return UI.shell(
            'finance',
            `
                ${UI.pageHeader({
                    eyebrow: 'Encaissement',
                    title: 'Enregistrer une dîme',
                    subtitle: 'Le formulaire suit désormais les champs réellement attendus par l’API, avec une présentation plus nette.',
                    actions: `
                        <a href="#" data-page="tithes" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour au registre</span>
                        </a>
                    `
                })}

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                    <div class="surface-panel p-6 md:p-8">
                        <form id="titheForm" class="finance-form grid grid-cols-1 gap-6">
                            <div>
                                <label class="field-label" for="member_id">Membre</label>
                                <select id="member_id" name="member_id" class="pro-select" required>
                                    <option value="">Sélectionner un membre</option>
                                    ${members.map((member) => `<option value="${member.id}">${this.fullName(member)}</option>`).join('')}
                                </select>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="field-label" for="tithe_amount">Montant (CDF)</label>
                                    <input id="tithe_amount" name="amount" type="number" min="0" class="pro-input" placeholder="0" required>
                                </div>
                                <div>
                                    <label class="field-label" for="tithe_date">Date</label>
                                    <input id="tithe_date" name="tithe_date" type="date" class="pro-input" value="${this.today()}" required>
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="comment">Commentaire</label>
                                <textarea id="comment" name="comment" rows="4" class="pro-textarea" placeholder="Observation facultative sur la contribution"></textarea>
                            </div>
                            <button type="submit" class="btn-emerald w-full">
                                <i class="fas fa-check"></i>
                                <span>Confirmer l’encaissement</span>
                            </button>
                        </form>
                    </div>

                    <aside class="surface-panel p-6 md:p-8">
                        <p class="page-eyebrow">Bonnes pratiques</p>
                        <h2 class="text-2xl font-black text-slate-950">Saisie recommandée</h2>
                        <div class="summary-list mt-5">
                            <div class="summary-row">
                                <div>
                                    <strong>Association fidèle</strong>
                                    <span>Chaque dîme doit être rattachée à un membre existant.</span>
                                </div>
                                <b>Obligatoire</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Montant net</strong>
                                    <span>Saisissez la valeur réellement encaissée en CDF.</span>
                                </div>
                                <b>Précis</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Commentaire</strong>
                                    <span>Ajoutez une note si le versement nécessite un contexte particulier.</span>
                                </div>
                                <b>Facultatif</b>
                            </div>
                        </div>
                    </aside>
                </section>
            `
        );
    }

    static async reportsPage() {
        try {
            const dashboard = await api.getDashboard();
            const stats = dashboard.stats || {};
            const income = this.toNumber(stats.monthlyTithes) + this.toNumber(stats.monthlyOfferings);
            const expenses = this.toNumber(stats.monthlyExpenses);
            const balance = income - expenses;

            return UI.shell(
                'reports',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Rapports',
                        title: 'Centre d\'export et de synthèse',
                        subtitle: 'Téléchargez les extraits utiles et ouvrez rapidement les pages de contrôle pour garder une vision propre de l\'activité.',
                        actions: `
                            <a href="#" data-page="dashboard" class="btn-secondary">
                                <i class="fas fa-gauge"></i>
                                <span>Retour dashboard</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Recettes du mois', this.formatMoney(income), 'fa-coins', 'brand', 'Dîmes et offrandes cumulées')}
                        ${UI.statCard('Dépenses du mois', this.formatMoney(expenses), 'fa-file-invoice-dollar', 'rose', 'Sorties constatées')}
                        ${UI.statCard('Balance', this.formatMoney(balance), 'fa-scale-balanced', balance >= 0 ? 'emerald' : 'rose', balance >= 0 ? 'Excédent courant' : 'Déficit courant')}
                    </section>

                    <h2 class="text-2xl font-black text-slate-950 mt-8 mb-4">Téléchargements</h2>
                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        ${this.reportCard({
                            href: 'javascript:void(api.exportPDF("balance",' + new Date().getFullYear() + '))',
                            title: 'Bilan PDF',
                            subtitle: 'Synthèse financière',
                            icon: 'fa-file-pdf',
                            tone: 'brand',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportCSV("members","all"))',
                            title: 'Membres CSV',
                            subtitle: 'Liste complète',
                            icon: 'fa-file-csv',
                            tone: 'emerald',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportPDF("tithes",' + new Date().getFullYear() + '))',
                            title: 'Dîmes PDF',
                            subtitle: 'Rapport complet',
                            icon: 'fa-file-pdf',
                            tone: 'brand',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportCSV("tithes",' + new Date().getFullYear() + '))',
                            title: 'Dîmes CSV',
                            subtitle: 'Tous les versements',
                            icon: 'fa-file-csv',
                            tone: 'emerald',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportJSON())',
                            title: 'Sauvegarde JSON',
                            subtitle: 'Backup complet',
                            icon: 'fa-database',
                            tone: 'amber',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportSQL())',
                            title: 'SQL Dump',
                            subtitle: 'Script restauration',
                            icon: 'fa-server',
                            tone: 'slate',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            href: 'javascript:void(api.exportCSV("offerings",' + new Date().getFullYear() + '))',
                            title: 'Offrandes CSV',
                            subtitle: 'Tous les collectes',
                            icon: 'fa-file-csv',
                            tone: 'amber',
                            external: true,
                            label: 'Télécharger'
                        })}
                        ${this.reportCard({
                            page: 'audit-logs',
                            title: 'Traçabilité',
                            subtitle: 'Logs d\'audit',
                            icon: 'fa-shield-halved',
                            tone: 'slate',
                            label: 'Consulter'
                        })}
                    </section>
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async offeringListPage() {
        try {
            const result = await api.getOfferings();
            const offerings = result.data || [];
            const totalOfferings = this.sum(offerings, 'amount');
            const topType = this.topGroup(offerings, 'type');

            return UI.shell(
                'finance',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Offrandes',
                        title: 'Journal des collectes',
                        subtitle: 'Consultez les types d’offrandes, les descriptions et les montants dans une vue plus professionnelle.',
                        actions: `
                            <a href="#" data-page="finance" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                <span>Retour finances</span>
                            </a>
                            <a href="#" data-page="offering-form" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                <span>Nouvelle offrande</span>
                            </a>
                        `
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Montant cumulé', this.formatMoney(totalOfferings), 'fa-gift', 'brand', 'Total de la liste courante')}
                        ${UI.statCard('Entrées', offerings.length, 'fa-list-check', 'emerald', 'Collectes enregistrées')}
                        ${UI.statCard('Type dominant', topType ? this.formatLabel(topType.label) : 'N/A', 'fa-layer-group', 'amber', topType ? this.formatMoney(topType.amount) : 'Aucune ventilation')}
                    </section>

                    ${
                        offerings.length
                            ? `
                                <section class="surface-panel table-shell">
                                    <div class="table-header">
                                        <div>
                                            <h2 class="table-title">Historique des offrandes</h2>
                                            <p class="table-subtitle">Vue détaillée des collectes, utile pour la supervision et la préparation des rapports.</p>
                                        </div>
                                        ${UI.badge(`${offerings.length} collecte(s)`, 'brand')}
                                    </div>

                                    <div class="pro-table-wrap">
                                        <table class="pro-table">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Date</th>
                                                    <th class="text-right">Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${offerings
                                                    .map(
                                                        (offering) => `
                                                            <tr>
                                                                <td>${UI.badge(this.formatLabel(offering.type), 'brand')}</td>
                                                                <td>
                                                                    <p class="table-name">${offering.description || 'Collecte sans commentaire'}</p>
                                                                    <p class="table-muted">Source : ${this.formatLabel(offering.type)}</p>
                                                                </td>
                                                                <td>${this.formatDate(offering.offering_date)}</td>
                                                                <td class="text-right">
                                                                    <p class="table-name">${this.formatMoney(offering.amount)}</p>
                                                                </td>
                                                            </tr>
                                                        `
                                                    )
                                                    .join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-gift',
                                  'Aucune offrande enregistrée',
                                  'Ajoutez une première collecte pour enrichir la trésorerie et disposer d’un historique exploitable.',
                                  `
                                      <a href="#" data-page="offering-form" class="btn-primary">
                                          <i class="fas fa-plus"></i>
                                          <span>Créer une offrande</span>
                                      </a>
                                  `
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async offeringFormPage() {
        return UI.shell(
            'finance',
            `
                ${UI.pageHeader({
                    eyebrow: 'Collecte',
                    title: 'Enregistrer une offrande',
                    subtitle: 'Les types proposés ont été réalignés sur les valeurs réellement acceptées par le backend.',
                    actions: `
                        <a href="#" data-page="offerings" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour au journal</span>
                        </a>
                    `
                })}

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                    <div class="surface-panel p-6 md:p-8">
                        <form id="offeringForm" class="finance-form grid grid-cols-1 gap-6">
                            <div>
                                <label class="field-label" for="offering_type">Type d’offrande</label>
                                <select id="offering_type" name="type" class="pro-select" required>
                                    <option value="culte">Culte</option>
                                    <option value="evenement">Événement</option>
                                    <option value="mission">Mission</option>
                                    <option value="cotisation">Cotisation</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="field-label" for="offering_amount">Montant (CDF)</label>
                                    <input id="offering_amount" name="amount" type="number" min="0" class="pro-input" placeholder="0" required>
                                </div>
                                <div>
                                    <label class="field-label" for="offering_date">Date</label>
                                    <input id="offering_date" name="offering_date" type="date" class="pro-input" value="${this.today()}" required>
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="offering_description">Description</label>
                                <textarea id="offering_description" name="description" rows="4" class="pro-textarea" placeholder="Contexte de la collecte, événement ou remarque utile"></textarea>
                            </div>
                            <button type="submit" class="btn-primary w-full">
                                <i class="fas fa-floppy-disk"></i>
                                <span>Valider l’offrande</span>
                            </button>
                        </form>
                    </div>

                    <aside class="surface-panel p-6 md:p-8">
                        <p class="page-eyebrow">Alignement API</p>
                        <h2 class="text-2xl font-black text-slate-950">Types compatibles</h2>
                        <div class="summary-list mt-5">
                            <div class="summary-row">
                                <div>
                                    <strong>Culte</strong>
                                    <span>Collecte dominicale ou réunion courante.</span>
                                </div>
                                <b>culte</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Événement</strong>
                                    <span>Actions ponctuelles, conférences, levées spéciales.</span>
                                </div>
                                <b>evenement</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Cotisation</strong>
                                    <span>Contributions régulières ou campagnes internes.</span>
                                </div>
                                <b>cotisation</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Mission / autre</strong>
                                    <span>Formats complémentaires couverts par l’API.</span>
                                </div>
                                <b>mission / autre</b>
                            </div>
                        </div>
                    </aside>
                </section>
            `
        );
    }

    static async expenseFormPage() {
        return UI.shell(
            'expenses',
            `
                ${UI.pageHeader({
                    eyebrow: 'Décaissement',
                    title: 'Créer une dépense',
                    subtitle: 'Le formulaire inclut désormais des catégories cohérentes avec la documentation et un champ pour joindre un justificatif.',
                    actions: `
                        <a href="#" data-page="expenses" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour au journal</span>
                        </a>
                    `
                })}

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                    <div class="surface-panel p-6 md:p-8">
                        <form id="expenseForm" class="expense-form grid grid-cols-1 gap-6">
                            <div>
                                <label class="field-label" for="expense_category">Catégorie</label>
                                <select id="expense_category" name="category" class="pro-select" required>
                                    <option value="loyer">Loyer</option>
                                    <option value="salaire">Salaire</option>
                                    <option value="mission">Mission</option>
                                    <option value="entretien">Entretien</option>
                                    <option value="communion">Communion</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="field-label" for="expense_amount">Montant (CDF)</label>
                                    <input id="expense_amount" name="amount" type="number" min="0" class="pro-input" placeholder="0" required>
                                </div>
                                <div>
                                    <label class="field-label" for="expense_date">Date</label>
                                    <input id="expense_date" name="expense_date" type="date" class="pro-input" value="${this.today()}" required>
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="expense_description">Description</label>
                                <textarea id="expense_description" name="description" rows="4" class="pro-textarea" placeholder="Motif de la dépense, bénéficiaire, contexte" required></textarea>
                            </div>
                            <div>
                                <label class="field-label" for="document_path">Justificatif</label>
                                <input id="document_path" name="document_path" type="file" class="pro-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <p class="pro-helper">Optionnel, mais recommandé pour professionnaliser la traçabilité.</p>
                            </div>
                            <button type="submit" class="btn-rose w-full">
                                <i class="fas fa-file-circle-plus"></i>
                                <span>Enregistrer la dépense</span>
                            </button>
                        </form>
                    </div>

                    <aside class="surface-panel p-6 md:p-8">
                        <p class="page-eyebrow">Circuit</p>
                        <h2 class="text-2xl font-black text-slate-950">Statut automatique</h2>
                        <div class="summary-list mt-5">
                            <div class="summary-row">
                                <div>
                                    <strong>Création</strong>
                                    <span>Une nouvelle dépense démarre en statut “en attente”.</span>
                                </div>
                                <b>Workflow</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Justificatif</strong>
                                    <span>Le document facilite la revue administrative ultérieure.</span>
                                </div>
                                <b>Conseillé</b>
                            </div>
                            <div class="summary-row">
                                <div>
                                    <strong>Catégories</strong>
                                    <span>Les valeurs proposées suivent la structure métier documentée.</span>
                                </div>
                                <b>Aligné</b>
                            </div>
                        </div>
                    </aside>
                </section>
            `
        );
    }

    static async auditLogsPage() {
        try {
            const result = await api.request('GET', '/audit/logs');
            const logs = result.data || [];
            const pagination = result.pagination || {};

            return UI.shell(
                'audit-logs',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Audit',
                        title: 'Traçabilité des opérations',
                        subtitle: 'Historique des actions critiques, horodatage et utilisateur source dans une vue plus exploitable.',
                        actions: `${UI.badge(`${pagination.total || logs.length} log(s)`, 'amber')}`
                    })}

                    ${
                        logs.length
                            ? `
                                <section class="surface-panel table-shell">
                                    <div class="table-header">
                                        <div>
                                            <h2 class="table-title">Journal d’audit</h2>
                                            <p class="table-subtitle">Les 50 premières lignes affichées servent à la vérification rapide de l’activité système.</p>
                                        </div>
                                        ${UI.badge('Admin seulement', 'amber')}
                                    </div>

                                    <div class="pro-table-wrap">
                                        <table class="pro-table">
                                            <thead>
                                                <tr>
                                                    <th>Utilisateur</th>
                                                    <th>Action</th>
                                                    <th>Table</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${logs
                                                    .slice(0, 50)
                                                    .map(
                                                        (log) => `
                                                            <tr>
                                                                <td>
                                                                    <div class="table-user">
                                                                        <div class="table-avatar">${UI.initials(log.user_name || 'Système')}</div>
                                                                        <div>
                                                                            <p class="table-name">${log.user_name || 'Système'}</p>
                                                                            <p class="table-muted">${log.user_role || 'Rôle indisponible'}</p>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>${UI.badge(log.action || 'ACTION', 'brand')}</td>
                                                                <td>${UI.badge(log.table_name || 'N/A', 'slate')}</td>
                                                                <td>
                                                                    <p class="table-name">${this.formatDateTime(log.created_at)}</p>
                                                                </td>
                                                            </tr>
                                                        `
                                                    )
                                                    .join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-shield-halved',
                                  'Aucune trace d’audit',
                                  'Le journal ne retourne pas encore de ligne. Vérifiez si des opérations ont été enregistrées côté backend.'
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async settingsPage() {
        try {
            const result = await api.request('GET', '/users');
            const users = result.data || [];
            const admins = users.filter((user) => UI.normalizeRole(user.role) === 'admin').length;
            const treasurers = users.filter((user) => UI.normalizeRole(user.role) === 'tresorier').length;
            const secretaries = users.filter((user) => UI.normalizeRole(user.role) === 'secretaire').length;

            return UI.shell(
                'settings',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Paramètres',
                        title: 'Utilisateurs et accès',
                        subtitle: 'Le rendu met désormais mieux en avant les rôles et les comptes actifs, sans dépendre d’une mise en page basique.',
                        actions: `${UI.badge(`${users.length} utilisateur(s) actif(s)`, 'brand')}`
                    })}

                    <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        ${UI.statCard('Administrateurs', admins, 'fa-user-shield', 'brand', 'Accès total')}
                        ${UI.statCard('Trésoriers', treasurers, 'fa-wallet', 'emerald', 'Finance et dépenses')}
                        ${UI.statCard('Secrétaires', secretaries, 'fa-address-book', 'amber', 'Registre membres')}
                    </section>

                    ${
                        users.length
                            ? `
                                <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                                    ${users
                                        .map((user) => {
                                            const roleMeta = UI.roleMeta(user.role);
                                            return `
                                                <article class="surface-panel p-6">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div class="table-user">
                                                            <div class="table-avatar">${UI.initials(user.name)}</div>
                                                            <div>
                                                                <p class="table-name">${user.name}</p>
                                                                <p class="table-muted">${user.email}</p>
                                                            </div>
                                                        </div>
                                                        ${UI.badge(roleMeta.label, roleMeta.tone)}
                                                    </div>
                                                    <div class="summary-list mt-5">
                                                        <div class="summary-row">
                                                            <div>
                                                                <strong>Statut</strong>
                                                                <span>Compte utilisateur</span>
                                                            </div>
                                                            <b>${user.status || 'actif'}</b>
                                                        </div>
                                                        <div class="summary-row">
                                                            <div>
                                                                <strong>Dernière connexion</strong>
                                                                <span>Horodatage enregistré</span>
                                                            </div>
                                                            <b>${user.last_login ? this.formatDateTime(user.last_login) : 'Jamais'}</b>
                                                        </div>
                                                    </div>
                                                </article>
                                            `;
                                        })
                                        .join('')}
                                </section>
                            `
                            : UI.emptyState(
                                  'fa-users-gear',
                                  'Aucun utilisateur actif',
                                  'L’API ne remonte actuellement aucun compte utilisateur actif pour l’administration.'
                              )
                    }
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }

    static async memberContributionPage() {
        let members = [];
        try {
            const membersResult = await api.getPublicMembers();
            members = membersResult.data || [];
        } catch (error) {
            console.warn("Using mock members as fallback (database error):", error.message);
            members = [
                { id: 1, name: "Membre Démo 1" },
                { id: 2, name: "Membre Démo 2" },
                { id: 3, name: "Visiteur" }
            ];
        }

        try {
            return `
                <div class="min-h-screen bg-[#fcfdfe] flex items-center justify-center p-0 md:p-8 lg:p-12 selection:bg-brand-100 selection:text-brand-900">
                    <!-- Conteneur Principal Split-Screen -->
                    <div class="w-full max-w-[1200px] h-full md:h-auto min-h-[700px] bg-white md:rounded-[40px] shadow-[0_40px_100px_-20px_rgba(15,23,42,0.1)] overflow-hidden flex flex-col md:flex-row relative border border-slate-100/50">
                        
                        <!-- Côté Gauche: Visuel & Message -->
                        <div class="w-full md:w-[42%] bg-brand-600 relative overflow-hidden flex flex-col justify-end p-12 text-white">
                            <!-- Image de Fond -->
                            <img src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&q=80" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay scale-110 animate-pulse-slow">
                            
                            <!-- Overlays de Dégradé -->
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-900/90 via-brand-900/40 to-transparent"></div>
                            
                            <!-- Contenu flottant -->
                            <div class="relative z-10 space-y-6">
                                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20">
                                    <i class="fas fa-hand-holding-heart text-white/90"></i>
                                    <span class="text-[10px] font-black uppercase tracking-[2px]">Espace Sécurisé</span>
                                </div>
                                <h1 class="text-4xl lg:text-5xl font-black leading-[1.1] tracking-tighter">
                                    Soutenir <br>notre <span class="italic text-brand-100">vision</span>.
                                </h1>
                                <p class="text-brand-100/80 text-lg font-medium max-w-sm">
                                    Chaque geste compte. Votre générosité permet d'étendre nos missions et de servir notre communauté.
                                </p>
                                
                                <div class="pt-8 flex flex-col gap-4">
                                    <div class="flex items-center gap-4 group">
                                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center group-hover:bg-brand-400 transition-colors">
                                            <i class="fas fa-check text-xs text-brand-100"></i>
                                        </div>
                                        <span class="text-sm font-bold">Paiement 100% Sécurisé</span>
                                    </div>
                                    <div class="flex items-center gap-4 group">
                                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center group-hover:bg-brand-400 transition-colors">
                                            <i class="fas fa-shield-alt text-xs text-brand-100"></i>
                                        </div>
                                        <span class="text-sm font-bold">Don déductible d'impôts</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Côté Droit: Formulaire -->
                        <div class="flex-1 bg-white p-8 md:p-14 lg:p-16 overflow-y-auto">
                            <!-- Entête Form -->
                            <div class="flex items-center justify-between mb-12">
                                <a href="/" data-page="home" class="group flex items-center gap-2 text-slate-400 hover:text-brand-600 transition-all font-black text-xs uppercase tracking-widest">
                                    <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                                    <span>Retour</span>
                                </a>
                                <div class="text-right">
                                    <span class="block text-[10px] font-black text-slate-300 uppercase tracking-widest">Étape 1 de 1</span>
                                </div>
                            </div>

                            <div id="contributionSuccess" class="hidden mb-12 p-8 bg-emerald-50 border border-emerald-100 rounded-[30px] flex items-center gap-6 animate-in slide-in-from-top duration-700">
                                <div class="w-16 h-16 rounded-3xl bg-emerald-500 text-white flex items-center justify-center text-3xl shadow-xl shadow-emerald-200">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-emerald-900 font-black text-xl mb-1">Merci précieusement !</h3>
                                    <p class="text-emerald-700 font-medium">Votre contribution a été enregistrée. Dieu vous bénisse.</p>
                                </div>
                            </div>

                            <form id="publicContributionForm" class="space-y-10 max-w-xl mx-auto md:mx-0">
                                <!-- Type de Contribution (Toggle Premium) -->
                                <div class="space-y-4">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                                        Que souhaitez-vous faire ?
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <label class="contribution-card-premium relative group cursor-pointer">
                                            <input type="radio" name="contribution_type" value="tithe" class="peer hidden" checked>
                                            <div class="card-body-premium peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600 peer-checked:shadow-2xl peer-checked:shadow-brand-200">
                                                <i class="fas fa-church text-2xl mb-4 group-hover:scale-110 transition-transform"></i>
                                                <span class="block font-black text-xs uppercase">Dîme</span>
                                            </div>
                                        </label>
                                        <label class="contribution-card-premium relative group cursor-pointer">
                                            <input type="radio" name="contribution_type" value="offering" class="peer hidden">
                                            <div class="card-body-premium peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-2xl peer-checked:shadow-emerald-200">
                                                <i class="fas fa-heart text-2xl mb-4 group-hover:scale-110 transition-transform"></i>
                                                <span class="block font-black text-xs uppercase">Offrande</span>
                                            </div>
                                        </label>
                                        <label class="contribution-card-premium relative group cursor-pointer">
                                            <input type="radio" name="contribution_type" value="deposit" class="peer hidden">
                                            <div class="card-body-premium peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 peer-checked:shadow-2xl peer-checked:shadow-blue-200">
                                                <i class="fas fa-wallet text-2xl mb-4 group-hover:scale-110 transition-transform"></i>
                                                <span class="block font-black text-xs uppercase">Dépôt</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Informations Donateur -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div id="memberSelectRow" class="space-y-3">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 px-1">Sélectionnez votre nom</label>
                                        <div class="group relative">
                                            <i class="fas fa-user-circle absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-brand-500 transition-colors z-10"></i>
                                            <select name="member_id" class="premium-input appearance-none cursor-pointer" required>
                                                <option value="">-- Choisir mon nom --</option>
                                                ${members
                                                    .map(
                                                        (m) => `
                                                    <option value="${m.id}">${m.name}</option>
                                                `
                                                    )
                                                    .join('')}
                                                <option value="new">Autre (Saisir mon nom)</option>
                                            </select>
                                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                        </div>
                                    </div>

                                    <div id="manualNameRow" class="space-y-3 hidden animate-in slide-in-from-top duration-300">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 px-1">Votre Nom Complet</label>
                                        <div class="group relative">
                                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-brand-500 transition-colors"></i>
                                            <input type="text" name="member_name" class="premium-input" placeholder="Ex: Jean Mukendi">
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 px-1">Date du don</label>
                                        <div class="group relative">
                                            <i class="fas fa-calendar absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-brand-500 transition-colors"></i>
                                            <input type="date" name="date" class="premium-input" value="${this.today()}" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Offrande (Masqué par défaut) -->
                                <div id="offeringTypeRow" class="space-y-3 hidden animate-in slide-in-from-left duration-300">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 px-1">Catégorie d'offrande</label>
                                    <div class="group relative">
                                        <i class="fas fa-tag absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-emerald-500 transition-colors"></i>
                                        <select name="type" class="premium-input appearance-none cursor-pointer">
                                            <option value="culte">Culte de louange</option>
                                            <option value="evenement">Événement Spécial</option>
                                            <option value="mission">Soutien aux Missions</option>
                                            <option value="cotisation">Cotisation</option>
                                            <option value="autre">Action de grâce</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                    </div>
                                </div>

                                <!-- Montant (L'élément central) -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between px-1">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-slate-400">Montant de la contribution</label>
                                        
                                        <!-- Sélecteur de Devise Premium -->
                                        <div class="currency-toggle-container bg-slate-100 p-1 rounded-2xl flex items-center gap-1 border border-slate-200/50">
                                            <label class="currency-option">
                                                <input type="radio" name="currency" value="CDF" class="hidden" checked>
                                                <span class="currency-label">CDF</span>
                                            </label>
                                            <label class="currency-option">
                                                <input type="radio" name="currency" value="USD" class="hidden">
                                                <span class="currency-label">USD</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="relative group">
                                        <div class="absolute left-6 top-1/2 -translate-y-1/2 flex items-center gap-2 pointer-events-none">
                                            <span id="currencyPrefix" class="text-brand-600 font-black text-2xl transition-all">FC</span>
                                            <div class="w-px h-8 bg-slate-100 group-focus-within:bg-brand-200 mx-2"></div>
                                        </div>
                                        <input 
                                            type="number" 
                                            name="amount" 
                                            class="w-full bg-slate-50 border-2 border-slate-50 rounded-[24px] py-10 pl-24 pr-8 text-4xl font-black text-slate-900 group-focus-within:bg-white group-focus-within:border-brand-500 group-focus-within:ring-8 group-focus-within:ring-brand-500/5 transition-all outline-none placeholder:text-slate-200"
                                            placeholder="000" 
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Commentaire -->
                                <div class="space-y-3">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-slate-400 px-1">Note de bénédiction (Optionnel)</label>
                                    <textarea name="comment" class="premium-input min-h-[120px] pt-4 resize-none" placeholder="Partagez un message ou une intention..."></textarea>
                                </div>

                                <!-- Bouton Submit -->
                                <div class="pt-6 space-y-6">
                                    <button type="submit" class="cta-button w-full bg-slate-950 text-white rounded-[24px] py-6 font-black text-lg flex items-center justify-center gap-4 shadow-2xl shadow-slate-200 hover:-translate-y-1 active:scale-[0.98] transition-all">
                                        <span>Confirmer & Payer</span>
                                        <i class="fas fa-paper-plane text-brand-400"></i>
                                    </button>
                                    
                                    <!-- Badges de Paiement -->
                                    <div class="flex flex-col items-center gap-4 pt-2">
                                        <div class="flex items-center gap-6 opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
                                            <span class="text-[10px] font-black tracking-widest text-slate-400">MOBILE MONEY</span>
                                            <div class="flex items-center gap-4">
                                                <i class="fab fa-cc-visa text-xl"></i>
                                                <i class="fab fa-cc-mastercard text-xl"></i>
                                                <span class="font-black text-xs">M-PESA / AIRTEL / ORANGE</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-full">
                                            <i class="fas fa-lock text-[10px] text-emerald-500"></i>
                                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Secured via <span class="text-slate-900">MaishaPay</span></span>
                                        </div>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&display=swap');
                    
                    #app { font-family: 'Montserrat', sans-serif; }

                    .premium-input {
                        width: 100%;
                        background-color: #f8fafc;
                        border: 2px solid #f8fafc;
                        border-radius: 20px;
                        padding: 18px 24px 18px 60px;
                        font-weight: 700;
                        color: #0f172a;
                        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
                        font-size: 0.95rem;
                        outline: none;
                    }
                    .premium-input:focus {
                        background-color: white;
                        border-color: #0c87eb;
                        box-shadow: 0 10px 25px -5px rgba(12, 135, 235, 0.1);
                    }
                    .premium-input::placeholder { color: #cbd5e1; }

                    .card-body-premium {
                        padding: 24px;
                        background-color: #f8fafc;
                        border: 2px solid #f1f5f9;
                        border-radius: 28px;
                        transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
                        color: #64748b;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                    }
                    .contribution-card-premium:hover .card-body-premium {
                        border-color: #cbd5e1;
                        background-color: #f1f5f9;
                        transform: translateY(-4px);
                    }

                    .currency-toggle-container {
                        width: auto;
                        display: flex;
                        background: #f1f5f9;
                    }
                    .currency-option {
                        cursor: pointer;
                    }
                    .currency-label {
                        display: block;
                        padding: 8px 16px;
                        border-radius: 12px;
                        font-size: 10px;
                        font-weight: 900;
                        color: #94a3b8;
                        transition: all 0.3s;
                        text-transform: uppercase;
                    }
                    .currency-option input:checked + .currency-label {
                        background: white;
                        color: #0c87eb;
                        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                    }

                    .animate-pulse-slow {
                        animation: pulse-slow 12s infinite ease-in-out;
                    }
                    @keyframes pulse-slow {
                        0%, 100% { transform: scale(1); }
                        50% { transform: scale(1.08); }
                    }

                    .cta-button {
                        background: radial-gradient(circle at top left, #1e293b, #020617);
                    }
                </style>
            `;
        } catch (error) {
            console.error("Critical render error in contribution page:", error);
            return UI.error("Erreur de rendu de la page. " + error.message);
        }
    }

    static async homePage() {
        return `
            <div class="min-h-screen bg-white">
                <!-- Navigation Fixe -->
                <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-100 px-6 py-3">
                    <div class="max-w-7xl mx-auto flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white text-base shadow-lg shadow-brand-200">
                                <i class="fas fa-church"></i>
                            </div>
                            <span class="text-xl font-black text-slate-950 tracking-tighter uppercase">MALOTY</span>
                        </div>
                        
                        <!-- Menu Desktop -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="#hero" class="nav-link active">Accueil</a>
                            <a href="#services" class="nav-link">Ce que nous faisons</a>
                            <a href="#events" class="nav-link">Événements</a>
                            <a href="#contact" class="nav-link">Contact</a>
                            <a href="/contribute" data-page="contribute" class="btn-primary-sm bg-emerald-600 hover:bg-emerald-700">
                                <i class="fas fa-hand-holding-heart mr-2"></i>Offrande
                            </a>
                            <a href="/login" data-page="login" class="text-slate-400 hover:text-slate-800 transition-colors">
                                <i class="fas fa-user-circle text-2xl"></i>
                            </a>
                        </div>

                        <!-- Menu Mobile Toggle -->
                        <button class="md:hidden text-slate-900 text-2xl">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </nav>

                <!-- Section Hero -->
                <section id="hero" class="pt-32 pb-20 px-6 bg-slate-50 relative overflow-hidden">
                    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div class="space-y-8 animate-in slide-in-from-left duration-1000">
                            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-50 text-brand-600 text-[11px] font-black uppercase tracking-widest border border-brand-100">
                                Bienvenue dans notre communauté
                            </div>
                            <h1 class="text-6xl md:text-7xl font-black text-slate-950 leading-[0.95] tracking-tight">
                                Venez tels que <span class="text-brand-600 italic">vous êtes.</span>
                            </h1>
                            <p class="text-xl text-slate-600 font-medium leading-relaxed max-w-lg">
                                Une église moderne, vivante et ouverte à tous, au service de notre Dieu et de notre communauté locale pour une foi partagée.
                            </p>
                            <div class="flex flex-wrap gap-4 pt-4">
                                <a href="/contribute" data-page="contribute" class="btn-primary py-4 px-8 text-lg bg-emerald-600 hover:bg-emerald-700 shadow-xl shadow-emerald-100">
                                    <span>Participer à l'offrande</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <a href="#events" class="btn-secondary py-4 px-8 text-lg border-2">
                                    <span>Nos événements</span>
                                </a>
                            </div>
                        </div>
                        <div class="relative animate-in zoom-in duration-1000 delay-300">
                            <div class="aspect-[4/3] rounded-4xl bg-brand-600 overflow-hidden shadow-3xl transform rotate-2">
                                <img src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&q=80" class="w-full h-full object-cover opacity-80 mix-blend-overlay">
                            </div>
                            <div class="absolute -bottom-6 -left-6 bg-white p-6 rounded-3xl shadow-xl space-y-1 border border-slate-50">
                                <p class="text-3xl font-black text-brand-600">8:30 <span class="text-base text-slate-400 font-bold">AM</span></p>
                                <p class="font-bold text-slate-900 uppercase tracking-widest text-[10px]">Culte du Dimanche</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section Services -->
                <section id="services" class="py-24 px-6">
                    <div class="max-w-7xl mx-auto text-center mb-16 space-y-4">
                        <p class="text-brand-600 font-black uppercase tracking-widest text-xs">Notre Mission</p>
                        <h2 class="text-4xl md:text-5xl font-black text-slate-950 tracking-tight">Ce que nous faisons</h2>
                    </div>
                    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                        ${this.serviceCard('EnseignementBiblique', 'Études Bibliques', 'Des moments de formation et de partage pour approfondir sa connaissance de la Parole.', 'fa-book-open', 'brand')}
                        ${this.serviceCard('CommunionFraternelle', 'Vie de Communauté', 'Des groupes de prière et de communion pour se soutenir mutuellement.', 'fa-people-group', 'emerald')}
                        ${this.serviceCard('ActionsSociales', 'Mission & Social', 'Des actions concrètes pour aider les plus démunis et partager l\'amour de Dieu.', 'fa-handshake-angle', 'amber')}
                    </div>
                </section>

                <!-- Section Événements -->
                <section id="events" class="py-24 px-6 bg-slate-950 text-white rounded-[4rem] mx-4">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
                            <div class="space-y-4">
                                <p class="text-blue-400 font-black uppercase tracking-widest text-xs">Agenda</p>
                                <h2 class="text-4xl md:text-5xl font-black tracking-tight">Nos événements à venir</h2>
                            </div>
                            <a href="#" class="text-blue-400 hover:text-blue-300 font-bold transition-all flex items-center gap-2">
                                Voir tout le calendrier <i class="fas fa-calendar-alt"></i>
                            </a>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            ${this.eventCard('Conférence de Prière', '25 AVRIL 2026', 'Une journée entière dédiée à l\'intercession et à la louange.', 'https://images.unsplash.com/photo-1510563800743-aed236490d08?auto=format&fit=crop&q=80')}
                            ${this.eventCard('Retraite des Jeunes', '12 MAI 2026', 'Weekend de ressourcement spirituel pour la nouvelle génération.', 'https://images.unsplash.com/photo-1523580494863-6f30312248f5?auto=format&fit=crop&q=80')}
                        </div>
                    </div>
                </section>

                <!-- Section Contribution CTA -->
                <section class="py-32 px-6">
                    <div class="max-w-5xl mx-auto surface-panel p-12 md:p-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-brand-600 text-white rounded-5xl text-center shadow-3xl shadow-brand-100">
                        <div class="max-w-2xl mx-auto space-y-8">
                            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-3xl mx-auto">
                                <i class="fas fa-donate"></i>
                            </div>
                            <h2 class="text-4xl md:text-5xl font-black tracking-tight leading-none">
                                Soutenez notre mission par vos dons.
                            </h2>
                            <p class="text-lg text-brand-100 font-medium">
                                Chaque contribution nous aide à maintenir nos cultes, à soutenir les nécessiteux et à proclamer l'Évangile.
                            </p>
                            <a href="/contribute" data-page="contribute" class="btn-primary py-5 px-10 text-xl bg-white text-brand-600 hover:bg-slate-50 shadow-2xl">
                                <i class="fas fa-heart text-emerald-500 mr-2"></i>Payer ma dîme ou Offrande
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Section Contact -->
                <section id="contact" class="py-24 px-6 bg-slate-50">
                    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16">
                        <div class="space-y-12">
                            <div class="space-y-4">
                                <p class="text-brand-600 font-black uppercase tracking-widest text-xs">Contact</p>
                                <h2 class="text-4xl md:text-5xl font-black text-slate-950 tracking-tight">Restons connectés</h2>
                            </div>
                            <div class="space-y-6">
                                <div class="flex items-center gap-6 group">
                                    <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-xl text-slate-400 group-hover:bg-brand-600 group-hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-location-dot"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Adresse</p>
                                        <p class="text-lg text-slate-900 font-bold">123 Avenue de l'Espoir, Kinshasa</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 group">
                                    <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-xl text-slate-400 group-hover:bg-brand-600 group-hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Téléphone</p>
                                        <p class="text-lg text-slate-900 font-bold">+243 812 345 678</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 group">
                                    <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-xl text-slate-400 group-hover:bg-brand-600 group-hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Email</p>
                                        <p class="text-lg text-slate-900 font-bold">contact@maloty-church.org</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white p-10 rounded-4xl shadow-xl border border-slate-100">
                            <form class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <input type="text" class="pro-input py-4 bg-slate-50 border-none" placeholder="Prénom">
                                    <input type="text" class="pro-input py-4 bg-slate-50 border-none" placeholder="Nom">
                                </div>
                                <input type="email" class="pro-input py-4 bg-slate-50 border-none" placeholder="Email">
                                <textarea class="pro-textarea bg-slate-50 border-none" rows="4" placeholder="Votre message..."></textarea>
                                <button type="button" class="btn-primary w-full py-5 text-lg shadow-xl shadow-brand-100">Envoyer le message</button>
                            </form>
                        </div>
                    </div>
                </section>

                <footer class="py-12 bg-white border-t border-slate-100 text-center">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-slate-600">
                            <i class="fas fa-church"></i>
                        </div>
                        <span class="text-lg font-black text-slate-900 tracking-tighter uppercase">MALOTY</span>
                    </div>
                    <p class="text-slate-400 font-medium">&copy; 2026 MALOTY Gestion Digitale. Tous droits réservés.</p>
                </footer>

                <style>
                    .nav-link {
                        font-size: 0.9rem;
                        font-weight: 800;
                        color: #64748b;
                        text-transform: uppercase;
                        letter-spacing: 0.1em;
                        transition: all 0.3s;
                    }
                    .nav-link:hover, .nav-link.active {
                        color: #0c87eb;
                    }
                    .service-card {
                        padding: 3rem 2rem;
                        border-radius: 3rem;
                        background: white;
                        border: 1px solid #f1f5f9;
                        transition: all 0.4s;
                    }
                    .service-card:hover {
                        transform: translateY(-10px);
                        box-shadow: 0 30px 60px -12px rgba(15, 23, 42, 0.08);
                        border-color: transparent;
                    }
                    html {
                        scroll-behavior: smooth;
                    }
                    .btn-primary-sm {
                        padding: 0.6rem 1.25rem;
                        border-radius: 1rem;
                        color: white;
                        font-weight: 800;
                        text-transform: uppercase;
                        font-size: 0.75rem;
                        letter-spacing: 0.05em;
                        transition: all 0.3s;
                    }
                    .rounded-5xl { border-radius: 4rem; }
                    .rounded-4xl { border-radius: 3rem; }
                </style>
            </div>
        `;
    }

    static serviceCard(id, title, desc, icon, tone) {
        return `
            <div class="service-card text-center group">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-${tone}-50 text-${tone}-600 flex items-center justify-center text-2xl mb-8 group-hover:bg-${tone}-600 group-hover:text-white transition-all duration-500">
                    <i class="fas ${icon}"></i>
                </div>
                <h3 class="text-xl font-black text-slate-950 mb-4">${title}</h3>
                <p class="text-slate-500 font-medium leading-relaxed text-sm">${desc}</p>
            </div>
        `;
    }

    static eventCard(title, date, desc, img) {
        return `
            <div class="relative h-80 rounded-4xl overflow-hidden group">
                <img src="${img}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-8 space-y-2">
                    <p class="text-blue-400 font-black uppercase tracking-[0.2em] text-[10px]">${date}</p>
                    <h3 class="text-2xl font-black">${title}</h3>
                    <p class="text-slate-300 text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-500 max-w-sm">
                        ${desc}
                    </p>
                </div>
                <div class="absolute top-6 right-6">
                    <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white border border-white/20">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
            </div>
        `;
    }
}


