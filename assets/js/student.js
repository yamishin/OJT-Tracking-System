document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Toggle
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

    // Progress Bars Animation
    document.querySelectorAll('.pb-fill').forEach(bar => {
        const target = bar.dataset.width || '0%';
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = target; }, 250);
    });

    // Confirm actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

        // ---------- Automated Attendance Computation ----------
    const timeIn = document.getElementById('time_in');
    const timeOut = document.getElementById('time_out');
    const hoursDisplay = document.getElementById('total_hours_display');
    const statusDisplay = document.getElementById('status_display');

    function computeHours() {
        if (!timeIn || !timeOut || !hoursDisplay) return;
        
        if (!timeIn.value || !timeOut.value) {
            hoursDisplay.value = '0.00';
            if (statusDisplay) statusDisplay.value = 'Waiting for Time Out...';
            return;
        }

        const [h1, m1] = timeIn.value.split(':').map(Number);
        const [h2, m2] = timeOut.value.split(':').map(Number);
        
        let start = h1 * 60 + m1;
        let end = h2 * 60 + m2;
        
        // Handle overnight (past midnight)
        if (end < start) end += 24 * 60;
        
        let rawHours = (end - start) / 60;
        let finalHours = rawHours;
        let status = '';
        let lunchDeducted = 0;

        // Rule 1: 8 hours or more (Whole Day) -> Deduct 1 hour lunch
        if (rawHours >= 8) {
            finalHours = rawHours - 1;
            lunchDeducted = 1;
        } 
        // Rule 2: Exactly 4 hours (Half Day)
        else if (rawHours >= 3.5 && rawHours < 4.5) {
            finalHours = rawHours;
            status = 'Half Day';
        } 
        // Rule 3: Under 8 hours (Undertime)
        else if (rawHours < 8) {
            finalHours = rawHours;
            status = 'Undertime';
        }

        // Rule 4: Overtime Checks (Based on 7:00 PM / 19:00)
        // Check if time out is past 19:00
        if (h2 >= 19) {
            const allowedMaxMinutes = 19 * 60; // 7:00 PM in minutes
            if (end > allowedMaxMinutes) {
                status = 'Exceeded Overtime';
            } else {
                status = 'Overtime';
            }
        } else if (rawHours >= 8 && h2 < 19) {
             // If they hit whole day but left before 7 PM
             status = 'Whole Day';
        }

        // Update the UI
        hoursDisplay.value = finalHours.toFixed(2) + (lunchDeducted ? ' (Lunch Deducted)' : '');
        if (statusDisplay) {
            statusDisplay.value = status;
            
            // Color code the status visually
            statusDisplay.style.fontWeight = 'bold';
            if (status === 'Whole Day') statusDisplay.style.color = 'var(--success)';
            else if (status === 'Half Day') statusDisplay.style.color = 'var(--light-blue-dark)';
            else if (status === 'Undertime') statusDisplay.style.color = 'var(--warning)';
            else if (status === 'Overtime') statusDisplay.style.color = '#F97316'; // Orange
            else if (status === 'Exceeded Overtime') statusDisplay.style.color = 'var(--red)';
        }
    }

    if (timeIn && timeOut) {
        timeIn.addEventListener('change', computeHours);
        timeOut.addEventListener('change', computeHours);
    }
});