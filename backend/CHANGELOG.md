# Changelog - MALOTY

Toutes les modifications importantes du projet sont documentées ici.

## [1.0.0] - 2026-03-17

### 🎉 Version Initiale

#### Ajouté

- ✅ Architecture MVC complète
- ✅ Authentification avec sessions PHP
- ✅ 3 rôles d'utilisateurs (Admin, Trésorier, Secrétaire)
- ✅ Dashboard avec statistiques et graphiques (Chart.js)
- ✅ Gestion complète des membres (CRUD)
- ✅ Gestion des dîmes
- ✅ Gestion des offrandes
- ✅ Gestion des dépenses avec workflow d'approbation
- ✅ Système de journalisation (audit logs)
- ✅ Interface responsive avec Tailwind CSS
- ✅ Upload de fichiers (photos, justificatifs)
- ✅ Filtrage et recherche avancés
- ✅ Formatage des devises et dates

#### Fonctionnalités de Sécurité

- ✅ Mots de passe sécurisés (bcrypt)
- ✅ Protection XSS (htmlspecialchars)
- ✅ Protection SQL injection (PDO prepared statements)
- ✅ Validation et sanitisation des entrées
- ✅ Contrôle d'accès basé sur les rôles (RBAC)
- ✅ Headers de sécurité HTTP

#### Documentation

- ✅ README.md complet
- ✅ INSTALL.md avec guide détaillé
- ✅ API.md avec documentation des routes
- ✅ Code source bien commenté

## [1.1.0] - À venir

### Prévisions

#### Backend

- [ ] API REST (JSON)
- [ ] Système de notifications par email
- [ ] Export PDF des rapports (TCPDF)
- [ ] Sauvegardes automatiques
- [ ] Système de cache (Redis)

#### Frontend

- [ ] Application mobile (Flutter/React Native)
- [ ] Progressive Web App (PWA)
- [ ] Mode sombre
- [ ] Internationalisation (i18n)
- [ ] Dashboard personnalisable

#### Fonctionnalités Métier

- [ ] Réconciliation bancaire
- [ ] Budgets et prévisions
- [ ] Gestion d'événements
- [ ] Suivi des présences
- [ ] Gestion des frais de scolarité
- [ ] Programme de dons réguliers

#### DevOps

- [ ] Docker & Docker Compose
- [ ] CI/CD (GitHub Actions, GitLab CI)
- [ ] Tests unitaires (PHPUnit)
- [ ] Tests d'intégration
- [ ] Monitoring et logs centralisés

## Notes de Mise à Jour

### De 0.X vers 1.0

1. Créer la base de données avec `database.sql`
2. Configurerles fichiers `.env.php`
3. Définir les permissions sur `/uploads`
4. Vérifier la version PHP (7.4+ requis)

## Plan de Route (Roadmap)

### Phase 1 (Mars 2026) ✅

Base fonctionnelle de gestion administrative et financière

### Phase 2 (Avril-Mai 2026) 🔄

API REST et mobilité

### Phase 3 (Juin-Juillet 2026) 📱

Application mobile

### Phase 4 (Août-Septembre 2026) 📊

Rapports avancés et analytics

### Phase 5 (Octobre 2026+) 🚀

Déploiement en production et support

## Support et Contribution

Pour rapporter un bug ou proposer une amélioration :

1. Vérifier que le problème n'existe pas déjà
2. Donner autant de détails que possible
3. Inclure les étapes pour reproduire
4. Fournir des informations système

## Historique des Versions

| Version | Date       | Notes               |
| ------- | ---------- | ------------------- |
| 1.0.0   | 2026-03-17 | 🎉 Version initiale |
| 0.9.0   | 2026-03-10 | Beta testing        |
| 0.1.0   | 2026-01-01 | Prototype           |

---

**MALOTY - Gestion d'Église** | © 2026
