document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 900 && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 2. Progress Bars Animation
    document.querySelectorAll('.pb-fill').forEach(bar => {
        const target = bar.dataset.width || '0%';
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = target; }, 250);
    });

    // 3. Confirm actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // 4. Automated Attendance Computation
    const timeIn = document.getElementById('time_in');
    const timeOut = document.getElementById('time_out');
    const hoursDisplay = document.getElementById('total_hours_display');
    const statusDisplay = document.getElementById('status_display');
    const punctualityDisplay = document.getElementById('punctuality_display');

    function computeHours() {
        if (!timeIn || !timeOut || !hoursDisplay) return;

        // If either time is empty, reset the displays
        if (!timeIn.value || !timeOut.value) {
            hoursDisplay.value = '0.00';
            if (statusDisplay) statusDisplay.value = 'Waiting for Time Out...';
            if (punctualityDisplay) punctualityDisplay.value = '—';
            return;
        }

        // Parse inputs into hours and minutes
        const [h1, m1] = timeIn.value.split(':').map(Number);
        const [h2, m2] = timeOut.value.split(':').map(Number);

        // --- PUNCTUALITY LOGIC ---
        // Standard is 8:00 AM (8 * 60 = 480 minutes)
        const standardStart = 8 * 60; 
        const startInMinutes = h1 * 60 + m1;

        let punctuality = '';
        if (startInMinutes < standardStart) {
            punctuality = 'Early';
        } else if (startInMinutes === standardStart) {
            punctuality = 'On Time';
        } else {
            punctuality = 'Late';
        }

        if (punctualityDisplay) {
            punctualityDisplay.value = punctuality;
            punctualityDisplay.style.fontWeight = 'bold';
            if (punctuality === 'Early') punctualityDisplay.style.color = 'var(--success)';
            else if (punctuality === 'On Time') punctualityDisplay.style.color = 'var(--light-blue-dark)';
            else if (punctuality === 'Late') punctualityDisplay.style.color = 'var(--red)';
        }

        // --- HOURS LOGIC ---
        let start = h1 * 60 + m1;
        let end = h2 * 60 + m2;

        let isExceeded = false;
        const cutoffTime = 19 * 60; // 7:00 PM in minutes

        // Cap at 7:00 PM
        if (h2 >= 19 && end > cutoffTime) {
            end = cutoffTime; 
            isExceeded = true;
        }

        // Handle overnight just in case
        if (end < start && !isExceeded) end += 24 * 60;

        // Calculate RAW hours and minutes
        const diffMinutes = end - start;
        const rawHours = diffMinutes / 60;

        let finalHours = rawHours;
        let status = '';
        let lunchDeducted = 0;

        // Deduct 1 hour if 8+ hours
        if (rawHours >= 8) {
            finalHours = rawHours - 1;
            lunchDeducted = 1;
        } else if (rawHours >= 3.5 && rawHours < 4.5) {
            status = 'Half Day';
        } else {
            status = 'Undertime';
        }

        // Status Labels
        if (isExceeded) {
            status = 'Exceeded Overtime';
        } else if (h2 >= 19 && end === cutoffTime) {
            status = 'Overtime';
        } else if (rawHours >= 8) {
            status = 'Whole Day';
        }

        // Update UI for Hours
        let displayStr = finalHours.toFixed(2);
        if (lunchDeducted) displayStr += ' (Lunch Deducted)';
        hoursDisplay.value = displayStr;

        // Update UI for Status
        if (statusDisplay) {
            statusDisplay.value = status;
            statusDisplay.style.fontWeight = 'bold';
            if (status === 'Whole Day') statusDisplay.style.color = 'var(--success)';
            else if (status === 'Half Day') statusDisplay.style.color = 'var(--light-blue-dark)';
            else if (status === 'Undertime') statusDisplay.style.color = 'var(--warning)';
            else if (status === 'Overtime') statusDisplay.style.color = '#F97316';
            else if (status === 'Exceeded Overtime') statusDisplay.style.color = 'var(--red)';
        }
    }

    // Attach the event listeners so it calculates live as they type
    if (timeIn && timeOut) {
        timeIn.addEventListener('change', computeHours);
        timeOut.addEventListener('change', computeHours);
        timeIn.addEventListener('input', computeHours);
        timeOut.addEventListener('input', computeHours);
    }
});