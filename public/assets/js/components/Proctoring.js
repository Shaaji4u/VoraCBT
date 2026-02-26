export class Proctoring {
    constructor(examId, apiClient) {
        this.examId = examId;
        this.apiClient = apiClient;
        this.warnings = 0;
        this.isActive = false;
    }

    start() {
        if (this.isActive) return;
        this.isActive = true;

        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        window.addEventListener('blur', this.handleBlur.bind(this));
        document.addEventListener('fullscreenchange', this.handleFullScreenChange.bind(this));

        // Prevent copy/paste/contextmenu
        document.addEventListener('copy', this.handleCopy.bind(this));
        document.addEventListener('paste', this.handlePaste.bind(this));
        document.addEventListener('contextmenu', this.handleContextMenu.bind(this));

        console.log('Proctoring started.');
    }

    stop() {
        this.isActive = false;
        // Remove listeners if needed, but usually we just stop logging
    }

    handleVisibilityChange() {
        if (!this.isActive) return;
        if (document.hidden) {
            this.logEvent('tab_switch', 'User switched tabs or minimized window.');
        } else {
            this.showWarning('Please stay on the exam tab. Navigate away again and the exam may be terminated.');
        }
    }

    handleBlur() {
        if (!this.isActive) return;
        // Blur can fire on click outside sometimes, so use with caution
        // this.logEvent('focus_lost', 'Window lost focus.');
    }

    handleFullScreenChange() {
        if (!this.isActive) return;
        if (!document.fullscreenElement) {
            this.logEvent('fullscreen_exit', 'User exited full screen.');
            this.showWarning('Please return to full screen mode immediately.');
        }
    }

    handleCopy(e) {
        if (!this.isActive) return;
        e.preventDefault();
        this.logEvent('copy_attempt', 'User attempted to copy content.');
        this.showToast('Copying is disabled during the exam.');
    }

    handlePaste(e) {
        if (!this.isActive) return;
        e.preventDefault();
        this.logEvent('paste_attempt', 'User attempted to paste content.');
        this.showToast('Pasting is disabled during the exam.');
    }

    handleContextMenu(e) {
        if (!this.isActive) return;
        e.preventDefault();
    }

    async logEvent(type, description) {
        console.warn(`Proctoring Event: ${type} - ${description}`);
        try {
            await this.apiClient.post(`/exams/${this.examId}/proctoring-events`, {
                type,
                description,
                timestamp: new Date().toISOString()
            });
        } catch (error) {
            // Silently fail to avoid disrupting user
            console.error('Failed to log proctoring event', error);
        }
    }

    showWarning(message) {
        this.pushNotice(`Warning: ${message}`, 'warning');
    }

    showToast(message) {
        this.pushNotice(message, 'info');
    }

    pushNotice(message, level = 'info') {
        let tray = document.getElementById('proctoring-notice-tray');
        if (!tray) {
            tray = document.createElement('div');
            tray.id = 'proctoring-notice-tray';
            tray.className = 'position-fixed top-0 end-0 p-3';
            tray.style.zIndex = '1080';
            document.body.appendChild(tray);
        }

        const item = document.createElement('div');
        item.className = `alert alert-${level === 'warning' ? 'warning' : 'secondary'} shadow-sm mb-2`;
        item.setAttribute('role', 'status');
        item.textContent = message;
        tray.appendChild(item);

        window.setTimeout(() => {
            item.remove();
            if (tray.children.length === 0) {
                tray.remove();
            }
        }, 3000);
    }
}
