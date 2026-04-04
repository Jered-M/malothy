/**
 * API Client - Communique avec le backend
 * Backend: http://localhost:8000/api
 */

const API_BASE_URL = '/api';

class APIClient {
    constructor() {
        this.token = localStorage.getItem('token');
    }

    isPublicAuthEndpoint(endpoint) {
        const publicEndpoints = [
            '/auth/login',
            '/finance/public-members',
            '/finance/public-tithe',
            '/finance/public-offering'
        ];
        return publicEndpoints.includes(endpoint);
    }

    clearSession() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.token = null;

        if (window.app) {
            window.app.currentUser = null;
        }
    }

    persistSession(user, token) {
        this.token = token || null;

        if (token) {
            localStorage.setItem('token', token);
        } else {
            localStorage.removeItem('token');
        }

        if (user) {
            localStorage.setItem('user', JSON.stringify(user));
        } else {
            localStorage.removeItem('user');
        }
    }

    /**
     * Faire une requête HTTP
     */
    async request(method, endpoint, data = null, opts = {}) {
        const requestOptions = {
            method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        };
        const suppressAuthRedirect = opts.suppressAuthRedirect === true;

        if (this.token && !this.isPublicAuthEndpoint(endpoint)) {
            requestOptions.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (data && (method === 'POST' || method === 'PUT')) {
            if (data instanceof FormData) {
                requestOptions.body = data;
                delete requestOptions.headers['Content-Type'];
            } else {
                requestOptions.body = JSON.stringify(data);
            }
        }

        const response = await fetch(`${API_BASE_URL}${endpoint}`, requestOptions);
        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json')
            ? await response.json()
            : null;

        if (response.status === 401) {
            const message = result?.error || 'Non authentifié';

            if (!this.isPublicAuthEndpoint(endpoint) && !suppressAuthRedirect) {
                this.clearSession();

                if (window.app && window.app.currentPage !== 'login') {
                    window.app.navigate('login');
                }
            }

            throw new Error(message);
        }

        if (!response.ok) {
            throw new Error(result?.error || `Erreur API (${response.status})`);
        }

        return result;
    }

    // ============ AUTH ============
    async loginWith(email, password) {
        const result = await this.request('POST', '/auth/login', { email, password });
        this.persistSession(result.user, result.token);
        return result;
    }

    async logout() {
        try {
            return await this.request('POST', '/auth/logout');
        } finally {
            this.clearSession();
        }
    }

    async getProfile() {
        return await this.request('GET', '/auth/profile', null, { suppressAuthRedirect: true });
    }

    // ============ MEMBERS ============
    async getMembers() {
        return await this.request('GET', '/members');
    }

    async getMember(id) {
        return await this.request('GET', `/members/${id}`);
    }

    async createMember(data) {
        return await this.request('POST', '/members', data);
    }

    async updateMember(id, data) {
        return await this.request('PUT', `/members/${id}`, data);
    }

    async deleteMember(id) {
        return await this.request('DELETE', `/members/${id}`);
    }

    // ============ FINANCE ============
    async getTithes() {
        return await this.request('GET', '/finance/tithes');
    }

    async createTithe(data) {
        return await this.request('POST', '/finance/tithes', data);
    }

    async getOfferings() {
        return await this.request('GET', '/finance/offerings');
    }

    async createOffering(data) {
        return await this.request('POST', '/finance/offerings', data);
    }

    async getDashboard() {
        return await this.request('GET', '/dashboard');
    }

    // ============ PUBLIC CONTRIBUTIONS ============
    async getPublicMembers() {
        return await this.request('GET', '/finance/public-members');
    }

    async createPublicTithe(data) {
        return await this.request('POST', '/finance/public-tithe', data);
    }

    async createPublicOffering(data) {
        return await this.request('POST', '/finance/public-offering', data);
    }

    // ============ EXPENSES ============
    async getExpenses() {
        return await this.request('GET', '/expenses');
    }

    async createExpense(data) {
        return await this.request('POST', '/expenses', data);
    }

    async updateExpenseStatus(id, status) {
        return await this.request('PUT', `/expenses/${id}`, { status });
    }

    // ============ MEMBER PHOTOS ============
    async uploadMemberPhoto(memberId, file) {
        const formData = new FormData();
        formData.append('photo', file);
        return await this.request('POST', `/members/${memberId}/photo`, formData);
    }

    async getMemberPhotoUrl(memberId) {
        return `${API_BASE_URL}/members/${memberId}/photo`;
    }

    async deleteMemberPhoto(memberId) {
        return await this.request('DELETE', `/members/${memberId}/photo`);
    }

    // ============ REPORTS & EXPORTS ============
    async getBalanceSheet(year, month) {
        return await this.request('GET', `/report/balance-sheet?year=${year}&month=${month || 'all'}`);
    }

    async exportPDF(type, year, month) {
        let url = `/report/export-pdf?type=${type}&year=${year}`;
        if (month) url += `&month=${month}`;
        window.location.href = url;
    }

    async exportCSV(type, year) {
        let url = `/report/export-csv?type=${type}&year=${year}`;
        window.location.href = url;
    }

    async exportSQL() {
        window.location.href = `/report/export-sql`;
    }

    async exportJSON() {
        window.location.href = `/report/export-json`;
    }

    // ============ SHORTHANDS ============
    async get(endpoint) {
        return await this.request('GET', endpoint);
    }

    async post(endpoint, data) {
        return await this.request('POST', endpoint, data);
    }

    async put(endpoint, data) {
        return await this.request('PUT', endpoint, data);
    }

    async delete(endpoint) {
        return await this.request('DELETE', endpoint);
    }
}

// Singleton global
window.api = new APIClient();
