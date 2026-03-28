/**
 * API Client - Communique avec le backend
 * Backend: http://localhost:8000/api
 */

const API_BASE_URL = '/api';

class APIClient {
    constructor() {
        this.token = localStorage.getItem('token');
    }

    /**
     * Faire une requête HTTP
     */
    async request(method, endpoint, data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        };

        if (this.token) {
            options.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (data && (method === 'POST' || method === 'PUT')) {
            if (data instanceof FormData) {
                options.body = data;
                // Delete Content-Type to let fetch set the boundary automatically
                delete options.headers['Content-Type'];
            } else {
                options.body = JSON.stringify(data);
            }
        }

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
            
            if (response.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                if (window.app) {
                    window.app.currentUser = null;
                    window.app.navigate('login');
                }
                throw new Error('Non authentifié');
            }

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erreur API');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // ============ AUTH ============
    async loginWith(email, password) {
        const result = await this.request('POST', '/auth/login', { email, password });
        this.token = result.token;
        localStorage.setItem('token', this.token);
        localStorage.setItem('user', JSON.stringify(result.user));
        return result;
    }

    async logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.token = null;
        return await this.request('POST', '/auth/logout');
    }

    async getProfile() {
        return await this.request('GET', '/auth/profile');
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
