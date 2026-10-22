document.addEventListener('DOMContentLoaded', () => {
    const roleRadios   = document.querySelectorAll('input[name="role"]');
    const companyField = document.getElementById('companyField');
    const schoolField  = document.getElementById('schoolField');
    const studentField = document.getElementById('studentField');

    function updateRoleFields() {
        const selected = document.querySelector('input[name="role"]:checked');
        if (!selected) return;
        const role = selected.value;

        // Show/hide based on role
        if (companyField) companyField.style.display = (role === 'company') ? 'block' : 'none';
        if (schoolField)  schoolField.style.display  = (role === 'school')  ? 'block' : 'none';
        if (studentField) studentField.style.display = (role === 'student') ? 'block' : 'none';
    }

    roleRadios.forEach(r => r.addEventListener('change', updateRoleFields));
    if (roleRadios.length) updateRoleFields();

    // USN: numeric only
    const usnInput = document.getElementById('student_no');
    if (usnInput) {
        usnInput.addEventListener('input', () => {
            usnInput.value = usnInput.value.replace(/\D/g, '').slice(0, 11);
        });
    }
});