import { ApiClient } from '../utils/ApiClient.js';

class QuestionManager {
    constructor() {
        this.api = new ApiClient();
        this.attachListeners();
        console.log('Question Manager initialized');
    }

    attachListeners() {
        // Search Input
        const searchInput = document.querySelector('input[placeholder*="Search"]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => this.handleSearch(e.target.value), 300));
        }

        // Create Button
        const createBtn = document.querySelector('button.btn-primary'); // Assuming the main CTA is primary
        if (createBtn && createBtn.textContent.includes('Create Question')) {
            createBtn.addEventListener('click', () => {
                // Redirect to a create page (e.g. MCQ by default or a chooser)
                window.location.href = '/teacher/questions/create-mcq';
            });
        }
    }

    handleSearch(query) {
        console.log('Searching for:', query);
        // Here we would call the API to filter the table
        // this.api.get('/teacher/questions', { q: query }).then(this.renderTable);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => new QuestionManager());
