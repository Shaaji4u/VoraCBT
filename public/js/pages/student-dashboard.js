import { ApiClient } from '../utils/ApiClient.js';

class StudentDashboard {
    constructor() {
        this.api = new ApiClient();
        this.userId = localStorage.getItem('user_id');
        this.status = document.getElementById('student-dashboard-status');
        this.inProgress = document.getElementById('in-progress-list');
        this.upcoming = document.getElementById('upcoming-list');
        this.history = document.getElementById('history-list');
    }

    async init() {
        if (!this.userId) {
            this.setStatus('Sign in required: missing user_id in localStorage.', 'warning');
            this.renderEmpty();
            return;
        }

        await this.load();
    }

    async load() {
        try {
            this.setStatus('Loading your exam dashboard…', 'info');
            const payload = await this.api.get(`/student/dashboard/overview?user_id=${encodeURIComponent(this.userId)}`);
            const data = payload?.data ?? {};
            this.renderInProgress(Array.isArray(data.in_progress) ? data.in_progress : []);
            this.renderUpcoming(Array.isArray(data.upcoming) ? data.upcoming : []);
            this.renderHistory(Array.isArray(data.history) ? data.history : []);
            this.setStatus('Dashboard updated.', 'success');
        } catch (error) {
            this.setStatus('Unable to load dashboard right now.', 'danger');
            this.renderEmpty();
        }
    }

    renderInProgress(items) {
        if (!items.length) {
            this.inProgress.innerHTML = '<div class="text-secondary">No in-progress exam sessions.</div>';
            return;
        }

        this.inProgress.innerHTML = items.map((item) => `
            <a class="list-group-item list-group-item-action" href="/student/exam?id=${encodeURIComponent(item.id)}">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${this.escape(item.title ?? 'Untitled exam')}</h6>
                    <small class="text-warning text-uppercase">${this.escape(item.status ?? 'in_progress')}</small>
                </div>
                <small class="text-secondary">${this.escape(item.subject ?? 'General')}</small>
            </a>
        `).join('');
    }

    renderUpcoming(items) {
        if (!items.length) {
            this.upcoming.innerHTML = '<div class="text-secondary">No upcoming exams published.</div>';
            return;
        }

        this.upcoming.innerHTML = items.map((item) => `
            <div class="col">
                <div class="card border h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-1">${this.escape(item.title ?? 'Untitled exam')}</h6>
                        <p class="small text-secondary mb-2">${this.escape(item.subject ?? 'General')}</p>
                        <p class="small mb-0">Duration: ${this.escape(item.duration_minutes ?? '-')} mins</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderHistory(items) {
        if (!items.length) {
            this.history.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-3">No completed exams yet.</td></tr>';
            return;
        }

        this.history.innerHTML = items.map((item) => `
            <tr>
                <td>${this.escape(item.subject ?? 'General')}</td>
                <td>${this.escape(item.title ?? 'Untitled exam')}</td>
                <td>${this.escape(item.end_time ?? '-')}</td>
                <td>${this.escape(item.score ?? '-')}</td>
            </tr>
        `).join('');
    }

    renderEmpty() {
        this.renderInProgress([]);
        this.renderUpcoming([]);
        this.renderHistory([]);
    }

    setStatus(message, type = 'secondary') {
        if (!this.status) return;
        this.status.className = `alert alert-${type}`;
        this.status.textContent = message;
    }

    escape(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

document.addEventListener('DOMContentLoaded', () => new StudentDashboard().init());
