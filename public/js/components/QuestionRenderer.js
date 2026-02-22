export class QuestionRenderer {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
    }

    render(question, answer = null) {
        if (!question) {
            this.container.innerHTML = '<div class="alert alert-warning">No question loaded.</div>';
            return;
        }

        let html = `
            <div class="card border rounded-4 shadow-sm overflow-hidden bg-surface" data-question-id="${question.id}">
        `;

        // Image/Media if present
        if (question.media_url) {
            html += `
                <div class="ratio ratio-21x9 bg-cover bg-center position-relative" style="background-image: url('${question.media_url}'); max-height: 240px;">
                    ${question.media_caption ? `<div class="position-absolute bottom-0 start-0 p-4 text-white w-100 bg-gradient-to-t-black"><h5 class="fw-bold mb-0 text-shadow">${question.media_caption}</h5></div>` : ''}
                </div>
            `;
        }

        html += `<div class="card-body p-4 p-md-5">`;

        // Passage content if passage-based
        if (question.passage) {
            html += `
                <div class="alert alert-light border mb-4 p-4 typography">
                    ${question.passage}
                </div>
            `;
        }

        // Question Text
        html += `
            <div class="mb-4 typography">
                <h4 class="fw-bold text-body mb-3">${question.content}</h4>
                ${question.instructions ? `<p class="text-secondary lh-lg fs-6">${question.instructions}</p>` : ''}
            </div>
        `;

        // Answer Input Area
        html += this.renderInputArea(question, answer);

        html += `</div></div>`; // Close card-body and card

        this.container.innerHTML = html;
        this.attachListeners(question.id);
    }

    renderInputArea(question, answer) {
        switch (question.type) {
            case 'multiple_choice':
            case 'mcq':
                return this.renderMCQ(question, answer);
            case 'essay':
                return this.renderEssay(question, answer);
            case 'fill_in_the_blank':
                return this.renderFillInBlank(question, answer);
            case 'numerical':
                return this.renderNumerical(question, answer);
            case 'matching':
                return this.renderMatching(question, answer);
            default:
                return `<div class="alert alert-danger">Unsupported question type: ${question.type}</div>`;
        }
    }

    renderMCQ(question, answer) {
        let optionsHtml = '<div class="d-flex flex-column gap-3">';
        question.options.forEach((opt, index) => {
            const isChecked = answer === opt.id || answer === opt.value; // Handle both id or value based on API
            const inputId = `opt_${question.id}_${index}`;
            optionsHtml += `
                <div class="form-check custom-radio-card p-0">
                    <input class="form-check-input d-none" type="radio" name="question_${question.id}" id="${inputId}" value="${opt.value}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label d-flex align-items-center p-3 border rounded-3 cursor-pointer hover-border-primary transition-all w-100" for="${inputId}">
                        <span class="d-flex align-items-center justify-content-center border rounded-circle me-3 flex-shrink-0" style="width: 24px; height: 24px;"></span>
                        <span class="text-body">${opt.text}</span>
                    </label>
                </div>
            `;
        });
        optionsHtml += '</div>';
        return optionsHtml;
    }

    renderEssay(question, answer) {
        return `
            <div class="border rounded-3 overflow-hidden bg-body mt-4 focus-within-ring-primary transition-all">
                <textarea class="form-control border-0 shadow-none p-3 fs-6 lh-base bg-surface" rows="10" placeholder="Type your answer here..." name="question_${question.id}">${answer || ''}</textarea>
                <div class="d-flex justify-content-between px-3 py-2 bg-surface border-top">
                    <span class="small text-secondary">Min words: ${question.min_words || 0}</span>
                    <span class="small fw-medium text-body" id="word-count-${question.id}">Word Count: 0</span>
                </div>
            </div>
        `;
    }

    renderFillInBlank(question, answer) {
        // Assuming question content has placeholders like {1}, {2} or just one input
        // For simplicity, let's assume standard single input or list of inputs
        return `
            <div class="mt-3">
                <input type="text" class="form-control form-control-lg bg-body-secondary" name="question_${question.id}" value="${answer || ''}" placeholder="Type your answer...">
            </div>
        `;
    }

    renderNumerical(question, answer) {
        return `
            <div class="mt-3">
                <input type="number" class="form-control form-control-lg bg-body-secondary" name="question_${question.id}" value="${answer || ''}" placeholder="Enter number...">
            </div>
        `;
    }

    renderMatching(question, answer) {
        // Placeholder for matching UI (drag and drop or dropdowns)
        return `<div class="alert alert-info">Matching question UI to be implemented.</div>`;
    }

    attachListeners(questionId) {
        // Dispatch custom event when answer changes
        const inputs = this.container.querySelectorAll(`[name="question_${questionId}"]`);
        inputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const event = new CustomEvent('answerChanged', {
                    detail: {
                        questionId: questionId,
                        answer: e.target.value
                    }
                });
                document.dispatchEvent(event);
            });

            // For essay word count
            if (input.tagName === 'TEXTAREA') {
                input.addEventListener('input', (e) => {
                    const count = e.target.value.trim().split(/\s+/).filter(w => w.length > 0).length;
                    const counter = document.getElementById(`word-count-${questionId}`);
                    if (counter) counter.textContent = `Word Count: ${count}`;

                    // Also trigger answer changed for auto-save debounce
                    const event = new CustomEvent('answerChanged', {
                        detail: {
                            questionId: questionId,
                            answer: e.target.value
                        }
                    });
                    document.dispatchEvent(event);
                });
            }
        });
    }
}
