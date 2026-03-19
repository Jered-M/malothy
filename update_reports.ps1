$file = 'frontend/public/js/pages.js'
[string]$content = Get-Content $file -Raw -Encoding UTF8

# Regex pattern pour trouver la fonction reportsPage
$pattern = 'static async reportsPage\(\) \{[\s\S]*?(?=\n    static async offeringListPage)'

# Nouvelle fonction avec tous les boutons de téléchargement
$new_func = @'
static async reportsPage() {
        try {
            const dashboard = await api.getDashboard();
            const stats = dashboard.stats || {};
            const income = this.toNumber(stats.monthlyTithes) + this.toNumber(stats.monthlyOfferings);
            const expenses = this.toNumber(stats.monthlyExpenses);
            const balance = income - expenses;
            const year = new Date().getFullYear();

            return UI.shell(
                'reports',
                `
                    ${UI.pageHeader({
                        eyebrow: 'Rapports',
                        title: 'Centre d'export et de synthèse',
                        subtitle: 'Tous les formats PDF, CSV, SQL, JSON',
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

                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        ${this.reportCard({href: 'javascript:void(api.exportPDF("balance",' + year + '))', title: 'Bilan PDF', subtitle: 'Synthèse financière', icon: 'fa-file-pdf', tone: 'brand', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportCSV("members","all"))', title: 'Membres CSV', subtitle: 'Liste complète', icon: 'fa-file-csv', tone: 'emerald', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportPDF("tithes",' + year + '))', title: 'Dîmes PDF', subtitle: 'Rapport complet', icon: 'fa-file-pdf', tone: 'brand', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportCSV("tithes",' + year + '))', title: 'Dîmes CSV', subtitle: 'Tous les versements', icon: 'fa-file-csv', tone: 'emerald', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportJSON())', title: 'Sauvegarde JSON', subtitle: 'Backup de la base', icon: 'fa-database', tone: 'amber', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportSQL())', title: 'SQL Dump', subtitle: 'Script restauration', icon: 'fa-server', tone: 'slate', external: true, label: 'Télécharger'})}
                        ${this.reportCard({href: 'javascript:void(api.exportCSV("offerings",' + year + '))', title: 'Offrandes CSV', subtitle: 'Tous les collectes', icon: 'fa-file-csv', tone: 'amber', external: true, label: 'Télécharger'})}
                        ${this.reportCard({page: 'audit-logs', title: 'Traçabilité', subtitle: 'Logs d audit', icon: 'fa-shield-halved', tone: 'slate', label: 'Consulter'})}
                    </section>
                `
            );
        } catch (error) {
            return UI.error(error.message);
        }
    }
'@

# Remplacer
$new_content = [regex]::Replace($content, $pattern, $new_func, [System.Text.RegularExpressions.RegexOptions]::Singleline)

# Sauvegarder
$new_content | Set-Content $file -Encoding UTF8

Write-Host "✅ Fonction reportsPage mise à jour avec 8 boutons de téléchargement"
