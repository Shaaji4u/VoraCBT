import { ApiClient } from '../utils/ApiClient.js';
import { Charts } from '../components/Charts.js';

class AdminDashboard {
    constructor() {
        this.api = new ApiClient();
    }

    async init() {
        console.log('Admin Dashboard initialized');
        try {
            // Fetch data (mock)
            // const data = await this.api.get('/admin/analytics/dashboard');
            const data = this.getMockData();

            // Render Charts
            // Check if chart element exists
            const ctx = document.getElementById('performanceChart');
            if (ctx) {
                Charts.initBarChart('performanceChart', data.labels, data.performance, 'Average Score');
            } else {
                console.warn('performanceChart canvas not found');
            }

        } catch (e) {
            console.error('Dashboard error:', e);
        }
    }

    getMockData() {
        return {
            performance: [70, 82, 65, 88, 75, 92],
            labels: ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3']
        };
    }
}

document.addEventListener('DOMContentLoaded', () => new AdminDashboard().init());
