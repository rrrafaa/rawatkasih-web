// Progressive enhancement: forms, links, CSRF and server validation also work without JavaScript.
const visitForm = document.querySelector('[data-visit-form]');
if (visitForm) {
    const updateSummary = () => {
        const date = visitForm.elements.visit_date.value;
        const start = visitForm.elements.start_time.value;
        const end = visitForm.elements.end_time.value;
        document.querySelector('[data-summary-date]').textContent = date
            ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${date}T12:00:00`))
            : 'Pilih tanggal kunjungan';
        document.querySelector('[data-summary-time]').textContent = start && end ? `${start.replace(':', '.')}–${end.replace(':', '.')} WIB` : 'Waktu belum dipilih';
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
