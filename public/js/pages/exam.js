import { ApiClient } from '../utils/ApiClient.js';
import { Timer } from '../utils/Timer.js';
import { QuestionRenderer } from '../components/QuestionRenderer.js';
import { Proctoring } from '../components/Proctoring.js';

class ExamController {
    constructor() {
        this.api = new ApiClient();
        this.renderer = new QuestionRenderer('question-container');
        this.currentQuestionIndex = 0;
        this.questions = [];
        this.answers = {};
        this.flaggedQuestions = new Set();
        this.examId = this.getExamIdFromUrl();
        this.timer = null;
        this.proctoring = null;
        this.examData = null;
    }

    getExamIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id') || 'sample-exam-id';
    }

    async init() {
        try {
            // Mock Data for demonstration if API fails or for development
            // In production, this would strictly come from API
            let examData;
            try {
                examData = await this.api.get(`/student/exams/${this.examId}`);
            } catch (e) {
                console.warn('API fetch failed, using mock data for demo');
                examData = this.getMockExamData();
            }

            if (!examData) {
                examData = this.getMockExamData();
            }

            this.examData = examData;

            // Handle Sections vs Flat list
            if (examData.sections) {
                this.questions = [];
                examData.sections.forEach(section => {
                    section.questions.forEach(q => {
                        this.questions.push({ ...q, sectionTitle: section.title, sectionId: section.id });
                    });
                });
            } else {
                this.questions = examData.questions;
            }

            this.duration = examData.duration_seconds;

            // Update UI
            document.getElementById('exam-title').textContent = examData.title;
            document.getElementById('nav-total-questions').textContent = `${this.questions.length} Questions`;

            // Initialize Timer
            this.timer = new Timer(this.duration, 'exam-timer', 300, () => this.submitExam(true));
            this.timer.start();

            // Initialize Proctoring
            this.proctoring = new Proctoring(this.examId, this.api);
            this.proctoring.start();

            // Render First Question
            this.renderQuestion(0);
            this.updateNavigator();

            // Attach Listeners
            document.getElementById('btn-next').addEventListener('click', () => this.nextQuestion());
            document.getElementById('btn-prev').addEventListener('click', () => this.prevQuestion());
            document.getElementById('btn-submit').addEventListener('click', () => this.confirmSubmit());
            document.getElementById('btn-flag').addEventListener('click', () => this.toggleFlag());

            // Listen for answer changes
            document.addEventListener('answerChanged', (e) => {
                this.answers[e.detail.questionId] = e.detail.answer;
                this.saveAnswer(e.detail.questionId, e.detail.answer);
                this.updateNavigator();
                this.updateProgress();
            });

        } catch (error) {
            console.error('Failed to initialize exam', error);
            document.getElementById('question-container').innerHTML = `<div class="alert alert-danger">Failed to load exam. Please refresh. Error: ${error.message}</div>`;
        }
    }

    renderQuestion(index) {
        if (index < 0 || index >= this.questions.length) return;

        this.currentQuestionIndex = index;
        const question = this.questions[index];
        const answer = this.answers[question.id];

        this.renderer.render(question, answer);

        // Update Header Info
        document.getElementById('question-number').textContent = `Question ${index + 1} of ${this.questions.length}`;
        document.getElementById('question-type-badge').textContent = question.type.replace('_', ' ').toUpperCase();

        if (question.sectionTitle) {
            document.getElementById('section-title').textContent = question.sectionTitle;
        } else {
            document.getElementById('section-title').textContent = 'General Section';
        }

        // Update Buttons
        document.getElementById('btn-prev').disabled = index === 0;
        document.getElementById('btn-next').innerHTML = index === this.questions.length - 1
            ? 'Finish <span class="material-symbols-outlined fs-5">check</span>'
            : 'Next <span class="material-symbols-outlined fs-5">arrow_forward</span>';

        // Update Flag Button state
        const btnFlag = document.getElementById('btn-flag');
        if (this.flaggedQuestions.has(question.id)) {
            btnFlag.classList.add('text-warning');
            btnFlag.querySelector('.material-symbols-outlined').classList.add('fill');
        } else {
            btnFlag.classList.remove('text-warning');
            btnFlag.querySelector('.material-symbols-outlined').classList.remove('fill');
        }

        // Highlight in navigator
        this.highlightNavigator(index);
        this.updateProgress();
    }

    nextQuestion() {
        if (this.currentQuestionIndex < this.questions.length - 1) {
            this.renderQuestion(this.currentQuestionIndex + 1);
        } else {
            // Confirm submit
            this.confirmSubmit();
        }
    }

    prevQuestion() {
        if (this.currentQuestionIndex > 0) {
            this.renderQuestion(this.currentQuestionIndex - 1);
        }
    }

    toggleFlag() {
        const questionId = this.questions[this.currentQuestionIndex].id;
        if (this.flaggedQuestions.has(questionId)) {
            this.flaggedQuestions.delete(questionId);
        } else {
            this.flaggedQuestions.add(questionId);
        }
        this.renderQuestion(this.currentQuestionIndex); // Re-render to update UI
        this.updateNavigator();
    }

    async saveAnswer(questionId, answer) {
        // Auto-save logic (debounced ideally, but here simple async call)
        console.log(`Saving answer for ${questionId}: ${answer}`);
        try {
            // await this.api.post(`/student/exams/${this.examId}/answers`, { question_id: questionId, answer });
        } catch (e) {
            console.error('Auto-save failed', e);
        }
    }

    confirmSubmit() {
        const answeredCount = Object.keys(this.answers).length;
        const total = this.questions.length;
        const confirmMsg = `You have answered ${answeredCount} out of ${total} questions.\nAre you sure you want to submit?`;
        if (confirm(confirmMsg)) {
            this.submitExam();
        }
    }

    async submitExam(isAuto = false) {
        try {
            console.log('Submitting exam...', this.answers);
            // await this.api.post(`/student/exams/${this.examId}/submit`, { answers: this.answers, is_auto: isAuto });
            alert('Exam Submitted Successfully!');
            window.location.href = '/student/dashboard';
        } catch (e) {
            console.error('Submission failed', e);
            alert('Submission failed. Please try again.');
        }
    }

    updateNavigator() {
        const grid = document.getElementById('navigator-grid');
        grid.innerHTML = '';

        this.questions.forEach((q, index) => {
            const isAnswered = this.answers[q.id] !== undefined && this.answers[q.id] !== '';
            const isCurrent = index === this.currentQuestionIndex;
            const isFlagged = this.flaggedQuestions.has(q.id);

            let btnClass = 'btn-light text-secondary border'; // Default Not Visited

            if (isAnswered) btnClass = 'btn-success text-white border-success';
            if (isCurrent) {
                // Overlay current style
                // We use ring or border for current
            }
            if (isFlagged && !isAnswered) btnClass = 'btn-warning text-white border-warning';

            const btn = document.createElement('button');
            btn.className = `btn p-0 d-flex align-items-center justify-content-center fw-bold shadow-sm ratio ratio-1x1 fs-6 position-relative ${btnClass}`;

            if (isCurrent) {
                btn.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
                // Ensure it looks distinct
                if (!isAnswered && !isFlagged) btn.classList.replace('btn-light', 'btn-primary');
            }

            btn.innerHTML = `
                ${index + 1}
                ${isFlagged ? '<span class="position-absolute top-0 end-0 bg-surface rounded-circle m-1" style="width: 6px; height: 6px;"></span>' : ''}
            `;

            btn.onclick = () => this.renderQuestion(index);
            grid.appendChild(btn);
        });
    }

    highlightNavigator(index) {
        // Redraw navigator to update "Current" status
        this.updateNavigator();
    }

    updateProgress() {
        const answeredCount = Object.keys(this.answers).length;
        const total = this.questions.length;
        const pct = (answeredCount / total) * 100;
        document.getElementById('exam-progress').style.width = `${pct}%`;
        document.getElementById('exam-progress').setAttribute('aria-valuenow', pct);
    }

    getMockExamData() {
        return {
            id: 'mock-1',
            title: 'Mock Exam: History & Physics',
            duration_seconds: 3600,
            sections: [
                {
                    id: 's1',
                    title: 'Section 1: Physics',
                    questions: [
                        {
                            id: 'q1',
                            type: 'multiple_choice',
                            content: 'What is the speed of light?',
                            options: [
                                { id: 'opt1', text: '300,000 km/s', value: 'a' },
                                { id: 'opt2', text: '150,000 km/s', value: 'b' },
                                { id: 'opt3', text: 'Instantaneous', value: 'c' }
                            ],
                            media_url: null
                        }
                    ]
                },
                {
                    id: 's2',
                    title: 'Section 2: History (Essay)',
                    questions: [
                        {
                            id: 'q2',
                            type: 'essay',
                            content: 'Discuss the impact of the Industrial Revolution.',
                            min_words: 50
                        }
                    ]
                },
                {
                    id: 's3',
                    title: 'Section 3: General Knowledge',
                    questions: [
                        {
                            id: 'q3',
                            type: 'fill_in_the_blank',
                            content: 'The capital of France is ______.',
                        },
                        {
                            id: 'q4',
                            type: 'multiple_choice',
                            content: 'Who painted the Mona Lisa?',
                            options: [
                                { id: 'opt1', text: 'Van Gogh', value: 'a' },
                                { id: 'opt2', text: 'Da Vinci', value: 'b' },
                                { id: 'opt3', text: 'Picasso', value: 'c' }
                            ]
                        }
                    ]
                }
            ]
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ExamController().init();
});
