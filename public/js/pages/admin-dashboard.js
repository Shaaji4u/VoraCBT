import { ApiClient } from '../utils/ApiClient.js';

class AdminDashboard {
    constructor() {
        this.api = new ApiClient();
        this.tableBody = document.getElementById('session-table-body');
        this.status = document.getElementById('dashboard-status');
        this.refreshButton = document.getElementById('btn-refresh-sessions');
    }

    async init() {
        if (!this.tableBody) {
            return;
        }

        this.refreshButton?.addEventListener('click', () => this.loadSessions());
        await this.loadSessions();
    }

    async loadSessions() {
        try {
            this.setStatus('Loading live session feed…', 'info');
            const payload = await this.api.get('/admin/logs?limit=20');
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            this.renderRows(rows);

            if (rows.length === 0) {
                this.setStatus('No recent proctoring/admin events found for the selected window.', 'secondary');
            } else {
                this.setStatus(`Loaded ${rows.length} recent event(s).`, 'success');
            }
        } catch (error) {
            this.renderRows([]);
            this.setStatus('Unable to load session feed. Verify admin login/token and try again.', 'danger');
        }
    }

    renderRows(rows) {
        if (rows.length === 0) {
            this.tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">No live session events available.</td>
                </tr>
            `;
            return;
        }

        this.tableBody.innerHTML = rows.map((row) => {
            const userId = this.escape(row.user_id ?? 'unknown');
            const source = this.escape(row.event_source ?? 'system');
            const exam = this.escape(row.exam_template_id ?? '-');
            const createdAt = this.escape(row.created_at ?? '-');
            return `
                <tr>
                    <td>${userId}</td>
                    <td><span class="badge text-bg-secondary">${source}</span></td>
                    <td>${exam}</td>
                    <td>${createdAt}</td>
                    <td><button class="btn btn-sm btn-outline-primary" type="button" disabled>Session Log</button></td>
                </tr>
            `;
        }).join('');
    }

    setStatus(message, type = 'secondary') {
        if (!this.status) {
            return;
        }

        this.status.className = `alert alert-${type} border mt-3 mb-0`;
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

document.addEventListener('DOMContentLoaded', () => new AdminDashboard().init());
