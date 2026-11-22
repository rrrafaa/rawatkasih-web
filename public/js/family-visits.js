// Gunakan kontrol bawaan jika JavaScript dinonaktifkan.
const visitForm = document.querySelector('[data-visit-form]');
if (visitForm) {
    const { visit_date: date, start_time: start, end_time: end, visitor_count: count,
        primary_visitor_name: name, note } = visitForm.elements;
    const fields = [date, start, end, count, name, note];
    const dateTrigger = document.getElementById('visit-date-trigger');
    const dateText = document.getElementById('visit-date-value');
    const calendar = document.getElementById('visit-calendar');
    const monthTitle = calendar.querySelector('[data-calendar-title]');
    const days = calendar.querySelector('[data-calendar-days]');
    const previous = calendar.querySelector('[data-calendar-month="-1"]');
    const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'UTC' });
    const monthFormat = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    const minimumDate = new Date(date.min);
    const touched = new Set(fields.filter((field) => field.getAttribute('aria-invalid') === 'true'));
    let displayedMonth;

    const renderCalendar = () => {
        const year = displayedMonth.getUTCFullYear();
        const month = displayedMonth.getUTCMonth();
        const offset = (displayedMonth.getUTCDay() + 6) % 7;
        const lastDay = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
        monthTitle.textContent = monthFormat.format(displayedMonth);
        previous.disabled = displayedMonth <= new Date(Date.UTC(minimumDate.getUTCFullYear(), minimumDate.getUTCMonth(), 1));
        days.replaceChildren();
        for (let index = 0; index < offset; index++) days.append(document.createElement('span'));
        for (let day = 1; day <= lastDay; day++) {
            const current = new Date(Date.UTC(year, month, day));
            const value = current.toISOString().slice(0, 10);
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = day;
            button.dataset.date = value;
            button.disabled = current < minimumDate;
            button.setAttribute('aria-label', dateFormat.format(current));
            button.setAttribute('aria-pressed', String(value === date.value));
            days.append(button);
        }
    };

    const closeCalendar = (restoreFocus = false) => {
        calendar.hidden = true;
        dateTrigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) dateTrigger.focus();
    };

    dateTrigger.addEventListener('click', () => {
        if (!calendar.hidden) {
            closeCalendar();
            return;
        }
        const selected = new Date(date.value || date.min);
        displayedMonth = new Date(Date.UTC(selected.getUTCFullYear(), selected.getUTCMonth(), 1));
        renderCalendar();
        calendar.hidden = false;
        dateTrigger.setAttribute('aria-expanded', 'true');
    });

    calendar.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;
        if (button.dataset.calendarMonth) {
            displayedMonth.setUTCMonth(displayedMonth.getUTCMonth() + Number(button.dataset.calendarMonth));
            renderCalendar();
        } else {
            date.value = button.dataset.date;
            touched.add(date);
            date.dispatchEvent(new Event('input', { bubbles: true }));
            closeCalendar(true);
        }
    });

    const datePicker = dateTrigger.parentElement;
    document.addEventListener('click', (event) => {
        if (!datePicker.contains(event.target)) closeCalendar();
    });
    datePicker.addEventListener('focusout', (event) => {
        if (!datePicker.contains(event.relatedTarget)) closeCalendar();
    });
    datePicker.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !calendar.hidden) {
            event.preventDefault();
            closeCalendar(true);
        }
    });

    const validationMessage = (field) => {
        if (field === date) {
            if (!date.value) return 'Pilih tanggal kunjungan.';
            if (date.validity.rangeUnderflow) return 'Pilih tanggal hari ini atau setelahnya.';
            if (start.value && Date.parse(`${date.value}T${start.value}:00+07:00`) <= Date.now()) {
                return 'Tanggal dan waktu kunjungan harus setelah waktu sekarang (WIB).';
            }
        }
        if (field === start && !start.value) return 'Pilih waktu mulai.';
        if (field === end) {
            if (!end.value) return 'Pilih waktu selesai.';
            if (start.value && end.value <= start.value) return 'Waktu selesai harus setelah waktu mulai.';
        }
        if (field === count) {
            if (count.validity.badInput) return 'Masukkan jumlah pengunjung sebagai bilangan bulat.';
            if (!count.value) return 'Isi jumlah pengunjung.';
            if (!count.validity.valid) return 'Masukkan jumlah pengunjung antara 1–20 orang.';
        }
        if (field === name) {
            if (!name.value.trim()) return 'Isi nama pengunjung utama.';
            if (name.value.trim().length < 2) return 'Nama pengunjung minimal 2 karakter.';
        }
        if (!field.validity.valid) return field.validationMessage;
        return '';
    };

    const showError = (field, message) => {
        const control = field === date ? dateTrigger : field;
        control.setAttribute('aria-invalid', String(Boolean(message)));
        document.getElementById(field.name + '-error').textContent = message;
    };

    const updateSummary = () => {
        const selectedDate = date.value ? dateFormat.format(new Date(date.value)) : '';
        dateText.textContent = selectedDate || 'Pilih tanggal';
        document.querySelector('[data-summary-date]').textContent = selectedDate || 'Pilih tanggal kunjungan';
        document.querySelector('[data-summary-time]').textContent = start.value && end.value && end.value > start.value
            ? `${start.value.replace(':', '.')}–${end.value.replace(':', '.')} WIB`
            : 'Pilih rentang waktu kunjungan';
        document.querySelector('[data-summary-visitors]').textContent = `${count.value || '0'} pengunjung`;
        document.querySelector('[data-summary-name]').textContent = name.value.trim() || 'Nama belum diisi';
    };

    visitForm.addEventListener('input', (event) => {
        const field = event.target;
        if (!fields.includes(field)) return;
        if (touched.has(field)) showError(field, validationMessage(field));
        if (field === start && date.value) showError(date, validationMessage(date));
        if ((field === start || field === end) && end.value) showError(end, validationMessage(end));
        updateSummary();
    });
    visitForm.addEventListener('focusout', (event) => {
        if (!fields.includes(event.target)) return;
        if (!fields.includes(event.relatedTarget) && event.relatedTarget !== dateTrigger) return;
        touched.add(event.target);
        showError(event.target, validationMessage(event.target));
    });
    visitForm.addEventListener('submit', (event) => {
        let firstInvalid;
        for (const field of fields) {
            touched.add(field);
            const message = validationMessage(field);
            showError(field, message);
            if (message) firstInvalid ??= field;
        }
        if (firstInvalid) {
            event.preventDefault();
            (firstInvalid === date ? dateTrigger : firstInvalid).focus();
        }
    });

    visitForm.noValidate = true;
    dateTrigger.setAttribute('aria-invalid', String(touched.has(date)));
    date.hidden = true;
    dateTrigger.hidden = false;
    document.getElementById('visit-date-label').htmlFor = dateTrigger.id;
    updateSummary();
}
const decision = document.getElementById('decision');
if (decision) {
    const reviewForm = decision.form;
    const dialog = document.getElementById('review-confirmation');
    let confirmed = false;
    const updateRequired = () => { document.getElementById('review_note').required = decision.value === 'rejected'; };
    decision.addEventListener('change', updateRequired);
    updateRequired();
    if (dialog && typeof dialog.showModal === 'function') {
        const title = document.getElementById('review-confirmation-title');
        const confirm = dialog.querySelector('[value="confirm"]');
        reviewForm.addEventListener('submit', (event) => {
            if (event.defaultPrevented || confirmed) return;
            event.preventDefault();
            const reject = decision.value === 'rejected';
            title.textContent = reject ? 'Tolak kunjungan ini?' : 'Setujui kunjungan ini?';
            confirm.textContent = reject ? 'Tolak kunjungan' : 'Setujui kunjungan';
            confirm.classList.toggle('is-rejection', reject);
            dialog.returnValue = '';
            dialog.showModal();
        });
        dialog.addEventListener('close', () => {
            if (dialog.returnValue !== 'confirm') return;
            confirmed = true;
            reviewForm.requestSubmit();
            confirmed = false;
        });
    }
}
document.querySelectorAll('form[data-confirm], form[data-visit-form], form[data-review-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.dataset.submitting = 'true'; }
    });
});
window.addEventListener('pageshow', () => document.querySelectorAll('[data-submitting]').forEach((button) => { button.disabled = false; }));
