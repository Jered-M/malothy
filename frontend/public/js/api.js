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
    getAuthHeaders(endpoint, headers = {}) {
        const requestHeaders = { ...headers };

        if (this.token && !this.isPublicAuthEndpoint(endpoint)) {
            requestHeaders['Authorization'] = `Bearer ${this.token}`;
        }

        return requestHeaders;
    }

    extractFilename(contentDisposition = '') {
        if (!contentDisposition) {
            return '';
        }

        const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utfMatch && utfMatch[1]) {
            try {
                return decodeURIComponent(utfMatch[1]);
            } catch (error) {
                return utfMatch[1];
            }
        }

        const plainMatch = contentDisposition.match(/filename=\"?([^\";]+)\"?/i);
        return plainMatch ? plainMatch[1] : '';
    }

    async download(endpoint) {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, {
            method: 'GET',
            headers: this.getAuthHeaders(endpoint),
            credentials: 'include'
        });

        if (!response.ok) {
            let message = `Erreur API (${response.status})`;
            const text = await response.text();

            if (text) {
                try {
                    const result = JSON.parse(text);
                    message = result?.error || message;
                } catch (error) {
                    message = text;
                }
            }

            if (response.status === 401) {
                this.clearSession();

                if (window.app && window.app.currentPage !== 'login') {
                    window.app.navigate('login');
                }
            }

            throw new Error(message);
        }

        const blob = await response.blob();
        const filename =
            this.extractFilename(response.headers.get('Content-Disposition')) || 'telechargement';
        const objectUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = objectUrl;
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(() => window.URL.revokeObjectURL(objectUrl), 1000);

        return { success: true, filename };
    }

    async request(method, endpoint, data = null, opts = {}) {
        const requestOptions = {
            method,
            headers: this.getAuthHeaders(endpoint, {
                'Content-Type': 'application/json',
            }),
            credentials: 'include'
        };
        const suppressAuthRedirect = opts.suppressAuthRedirect === true;

        if (data && (method === 'POST' || method === 'PUT')) {
            if (data instanceof FormData) {
                requestOptions.body = data;
                delete requestOptions.headers['Content-Type'];
            } else {
                requestOptions.body = JSON.stringify(data);
            }
        }

        const response = await fetch(`${API_BASE_URL}${endpoint}`, requestOptions);
        
        let result = null;
        const text = await response.text();
        if (text) {
            try {
                result = JSON.parse(text);
            } catch (err) {
                // Not JSON, ignore or log
                console.warn(`Response from ${endpoint} is not valid JSON:`, text);
            }
        }

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

    async getMemberDashboard() {
        return await this.request('GET', '/dashboard/member');
    }

    async getHomeEvents() {
        return await this.request('GET', '/settings/home-events');
    }

    async saveHomeEvents(events) {
        return await this.request('POST', '/settings/home-events', { events });
    }

    async uploadHomeEventImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        return await this.request('POST', '/settings/home-event-image', formData);
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
        let endpoint = `/report/export-pdf?type=${encodeURIComponent(type)}&year=${encodeURIComponent(year)}`;
        if (month) endpoint += `&month=${encodeURIComponent(month)}`;
        return await this.download(endpoint);
    }

    async exportCSV(type, year) {
        const endpoint = `/report/export-csv?type=${encodeURIComponent(type)}&year=${encodeURIComponent(year)}`;
        return await this.download(endpoint);
    }

    async exportSQL() {
        return await this.download('/report/export-sql');
    }

    async exportJSON() {
        return await this.download('/report/export-json');
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
