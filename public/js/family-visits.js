// Progressive enhancement: forms, links, CSRF and server validation also work without JavaScript.
const visitForm = document.querySelector('[data-visit-form]');
if (visitForm) {
    const startTime = visitForm.elements.start_time;
    const endTime = visitForm.elements.end_time;
    const timeHint = document.getElementById('visit-time-hint');
    const timeMessage = 'Waktu selesai harus setelah waktu mulai pada hari yang sama.';

    const updateSummary = () => {
        const date = visitForm.elements.visit_date.value;
        const start = startTime.value;
        const end = endTime.value;
        const invalidOrder = Boolean(start && end && endTime.valueAsNumber <= startTime.valueAsNumber);
        endTime.setCustomValidity(invalidOrder ? timeMessage : '');
        endTime.setAttribute('aria-invalid', String(invalidOrder));
        timeHint.classList.toggle('adm-form-error', invalidOrder);
        timeHint.classList.toggle('fv-form-hint', !invalidOrder);
        document.querySelector('[data-summary-date]').textContent = date
            ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${date}T12:00:00`))
            : 'Pilih tanggal kunjungan';
        let summaryTime = 'Waktu belum dipilih';
        if (invalidOrder) {
            summaryTime = 'Rentang waktu belum valid';
        } else if (start && end) {
            summaryTime = `${start.replace(':', '.')}–${end.replace(':', '.')} WIB`;
        }
        document.querySelector('[data-summary-time]').textContent = summaryTime;
        document.querySelector('[data-summary-visitors]').textContent = `${visitForm.elements.visitor_count.value || '0'} pengunjung`;
        document.querySelector('[data-summary-name]').textContent = visitForm.elements.primary_visitor_name.value || 'Nama belum diisi';
    };
    visitForm.addEventListener('input', updateSummary);
    updateSummary();
}
const decision = document.getElementById('decision');
if (decision) {
    const updateRequired = () => { document.getElementById('review_note').required = decision.value === 'rejected'; };
    decision.addEventListener('change', updateRequired);
    updateRequired();
}
document.querySelectorAll('form[data-confirm], form[data-visit-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.dataset.submitting = 'true'; }
    });
});
window.addEventListener('pageshow', () => document.querySelectorAll('[data-submitting]').forEach((button) => { button.disabled = false; }));
