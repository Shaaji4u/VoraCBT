export class Timer {
    constructor(durationSeconds, displayElementId, warningThresholdSeconds = 300, onTimeUpCallback = null) {
        this.duration = durationSeconds;
        this.remaining = durationSeconds;
        this.displayElement = document.getElementById(displayElementId);
        this.warningThreshold = warningThresholdSeconds;
        this.timerInterval = null;
        this.onTimeUp = onTimeUpCallback;
        this.isWarning = false;
    }

    start() {
        if (this.timerInterval) return;

        this.updateDisplay();
        this.timerInterval = setInterval(() => {
            this.remaining--;
            this.updateDisplay();

            if (this.remaining <= this.warningThreshold && !this.isWarning) {
                this.displayElement.classList.add('text-danger', 'animate-pulse');
                this.isWarning = true;
                // Trigger warning event if needed
                const event = new CustomEvent('timerWarning', { detail: { remaining: this.remaining } });
                document.dispatchEvent(event);
            }

            if (this.remaining <= 0) {
                this.stop();
                if (this.onTimeUp) this.onTimeUp();

                const event = new CustomEvent('timerExpired');
                document.dispatchEvent(event);
            }
        }, 1000);
    }

    stop() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    updateDisplay() {
        if (!this.displayElement) return;

        const hours = Math.floor(this.remaining / 3600);
        const minutes = Math.floor((this.remaining % 3600) / 60);
        const seconds = this.remaining % 60;

        const formatted = [
            hours.toString().padStart(2, '0'),
            minutes.toString().padStart(2, '0'),
            seconds.toString().padStart(2, '0')
        ].join(':');

        this.displayElement.textContent = formatted;
    }

    sync(serverRemaining) {
        // Only update if difference is significant (> 2 seconds) to avoid jitter
        if (Math.abs(this.remaining - serverRemaining) > 2) {
            this.remaining = serverRemaining;
            this.updateDisplay();
        }
    }
}
