/**
 * Reusable UI Components
 */

class UI {
    static getCurrentUser() {
        try {
            const user = JSON.parse(localStorage.getItem('user')) || { name: 'Invite', role: 'secretaire' };
            user.role = this.normalizeRole(user.role);
            return user;
        } catch (error) {
            return { name: 'Invite', role: 'secretaire' };
        }
    }

    static normalizeText(value = '') {
        return String(value)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    static normalizeRole(roleInput = 'secretaire') {
        let role = this.normalizeText(roleInput || 'secretaire');
        
        if (role.includes('adm')) return 'admin';
        if (role.includes('sorier') || role.startsWith('tr')) return 'tresorier';
        if (role.includes('ecretaire') || role.startsWith('sec')) return 'secretaire';
        
        return 'secretaire'; // Fallback
    }

    static roleMeta(role) {
        const normalized = this.normalizeRole(role);
        const meta = {
            admin: {
                label: 'Administrateur',
                tone: 'brand',
                copy: 'Vision globale, parametres et pilotage'
            },
            tresorier: {
                label: 'Tresorier',
                tone: 'emerald',
                copy: 'Suivi des flux, offrandes et depenses'
            },
            secretaire: {
                label: 'Secretaire',
                tone: 'amber',
                copy: 'Registre membres et suivi operationnel'
            }
        };

        return meta[normalized] || meta.secretaire;
    }

    static initials(...parts) {
        const cleaned = parts
            .flatMap((value) => String(value || '').split(/\s+/))
            .map((value) => value.trim())
            .filter(Boolean);

        if (!cleaned.length) {
            return 'ML';
        }

        return cleaned
            .slice(0, 2)
            .map((value) => value.charAt(0).toUpperCase())
            .join('');
    }

    static shell(activePage, content) {
        return `
            <div class="app-shell">
                ${this.sidebar(activePage)}
                <main class="app-main">
                    <div class="app-main-inner">
                        ${content}
                    </div>
                </main>
            </div>
        `;
    }

    static sidebar(activePage) {
        const user = this.getCurrentUser();
        const roleMeta = this.roleMeta(user.role);
        const links = [
            { id: 'dashboard', icon: 'fa-chart-line', label: 'Accueil', roles: ['admin', 'tresorier', 'secretaire'] },
            { id: 'members', icon: 'fa-users', label: 'Membres', roles: ['admin', 'secretaire'] },
            { id: 'finance', icon: 'fa-wallet', label: 'Caisse', roles: ['admin', 'tresorier'] },
            { id: 'expenses', icon: 'fa-receipt', label: 'Dépenses', roles: ['admin', 'tresorier'] },
            { id: 'reports', icon: 'fa-file-pdf', label: 'Rapports', roles: ['admin', 'tresorier'] },
            { id: 'audit-logs', icon: 'fa-clock-rotate-left', label: 'Activité', roles: ['admin'] },
            { id: 'settings', icon: 'fa-cog', label: 'Accès', roles: ['admin'] }
        ].filter((link) => link.roles.includes(this.normalizeRole(user.role)));

        return `
            <aside class="app-sidebar shadow-2xl">
                <div class="mb-4">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-church"></i>
                        </div>
                        <h1 class="text-xl font-black tracking-tight text-white uppercase">MALOTY</h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-8">
                    <span class="px-3 py-1 rounded-full bg-white/10 border border-white/10 text-[10px] font-bold uppercase tracking-wider text-brand-100 flex items-center gap-2">
                        ${roleMeta.label}
                    </span>
                </div>

                <nav class="app-nav flex-1 space-y-2 overflow-y-auto pr-2 custom-scrollbar">
                    ${links
                        .map(
                            (link) => `
                                <a href="#" data-page="${link.id}" class="group flex items-center gap-3 p-3.5 rounded-xl transition-all duration-200 border border-transparent ${
                                    activePage === link.id 
                                    ? 'bg-blue-600 text-white shadow-md' 
                                    : 'text-slate-400 hover:text-white hover:bg-white/5'
                                }">
                                    <span class="w-8 h-8 flex items-center justify-center rounded-lg ${activePage === link.id ? 'bg-white/20' : 'bg-white/5'}">
                                        <i class="fas ${link.icon} text-sm"></i>
                                    </span>
                                    <strong class="text-sm font-semibold tracking-tight">${link.label}</strong>
                                </a>
                            `
                        )
                        .join('')}
                </nav>

                <div class="mt-auto pt-6 border-t border-white/5 space-y-4">
                    <div class="flex items-center gap-4 px-2">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-slate-700 to-slate-800 border border-white/10 flex items-center justify-center text-white font-black text-lg">
                            ${this.initials(user.name)}
                        </div>
                        <div class="flex flex-col">
                            <p class="text-sm font-bold text-white leading-tight">${user.name || 'Utilisateur'}</p>
                            <p class="text-[10px] text-slate-400 font-medium">${roleMeta.label}</p>
                        </div>
                    </div>
                    <button id="logoutBtn" onclick="window.logout()" class="w-full flex items-center justify-center gap-3 p-4 rounded-2xl bg-rose-500/10 hover:bg-rose-500 border border-rose-500/20 hover:border-rose-500 text-rose-500 hover:text-white transition-all duration-300 text-sm font-bold shadow-lg shadow-rose-500/5" type="button">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </button>
                </div>
            </aside>
        `;
    }

    static pageHeader({ eyebrow = '', title = '', subtitle = '', actions = '' }) {
        return `
            <section class="surface-panel page-hero">
                <div>
                    ${eyebrow ? `<p class="page-eyebrow">${eyebrow}</p>` : ''}
                    <h1 class="page-title">${title}</h1>
                    ${subtitle ? `<p class="page-subtitle">${subtitle}</p>` : ''}
                </div>
                ${actions ? `<div class="page-hero-actions">${actions}</div>` : ''}
            </section>
        `;
    }

    static statCard(title, value, icon, tone = 'brand', note = '') {
        return `
            <article class="surface-panel metric-card">
                <div class="metric-card-top">
                    <span class="metric-icon tone-${tone}">
                        <i class="fas ${icon}"></i>
                    </span>
                    ${this.badge(title, tone)}
                </div>
                <div>
                    <p class="metric-label">${title}</p>
                    <h3 class="metric-value">${value}</h3>
                    ${note ? `<p class="metric-note">${note}</p>` : ''}
                </div>
            </article>
        `;
    }

    static actionCard(page, title, subtitle, icon, tone = 'brand', meta = '') {
        return `
            <a href="#" data-page="${page}" class="surface-panel action-card group hover:scale-[1.02] active:scale-[0.98]">
                <div class="action-card-head mb-4">
                    <span class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:rotate-12 tone-${tone}">
                        <i class="fas ${icon}"></i>
                    </span>
                    ${meta ? this.badge(meta, tone) : ''}
                </div>
                <div class="space-y-2">
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">${title}</h3>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">${subtitle}</p>
                </div>
                <div class="mt-8 flex items-center gap-2 text-brand-600 font-bold text-xs uppercase tracking-widest group-hover:gap-4 transition-all">
                    <span>Ouvrir l'espace</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        `;
    }

    static emptyState(icon, title, message, action = '') {
        return `
            <section class="surface-panel empty-state">
                <div class="empty-state-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <h3>${title}</h3>
                <p>${message}</p>
                ${action ? `<div class="empty-state-action">${action}</div>` : ''}
            </section>
        `;
    }

    static badge(text, tone = 'slate') {
        return `<span class="badge badge-${tone}">${text}</span>`;
    }

    static statusBadge(status = '') {
        const normalized = this.normalizeText(status);

        if (normalized.includes('actif') || normalized.includes('approuve')) {
            return this.badge(status, 'emerald');
        }

        if (normalized.includes('attente') || normalized.includes('suspendu')) {
            return this.badge(status, 'amber');
        }

        if (normalized.includes('inactif') || normalized.includes('rejete')) {
            return this.badge(status, 'rose');
        }

        return this.badge(status || 'N/A', 'slate');
    }

    static spinner() {
        return '<div class="spinner-shell"></div>';
    }

    static error(message) {
        return `
            <section class="error-panel">
                <div class="text-3xl">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <h3>Une erreur bloque le rendu</h3>
                <p>${message}</p>
            </section>
        `;
    }
}
