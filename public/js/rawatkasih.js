
// Tampilkan / sembunyikan kata sandi
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Tutup' : 'Lihat';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
});

// Panel tambah template tugas
const drawer = document.getElementById('template-drawer');
const openDrawerButton = document.getElementById('open-template-drawer');
const closeDrawerButtons = document.querySelectorAll('[data-close-drawer]');

if (drawer && openDrawerButton) {
    openDrawerButton.addEventListener('click', () => {
        drawer.hidden = false;
    });

    closeDrawerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            drawer.hidden = true;
        });
    });

    drawer.addEventListener('click', (event) => {
        if (event.target === drawer) {
            drawer.hidden = true;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !drawer.hidden) {
            drawer.hidden = true;
        }
    });
}


// Pilihan kategori tugas
const categoryButtons = document.querySelectorAll('[data-category]');
const categoryInput = document.getElementById('task-category');

categoryButtons.forEach((button) => {
    button.addEventListener('click', () => {
        categoryButtons.forEach((item) => item.classList.remove('is-selected'));
        button.classList.add('is-selected');

        if (categoryInput) {
            categoryInput.value = button.dataset.category;
        }
    });
});

// Pilihan frekuensi pengulangan
const repeatButtons = document.querySelectorAll('[data-repeat]');
const repeatInput = document.getElementById('task-repeat');
const dayContainer = document.getElementById('task-days');

repeatButtons.forEach((button) => {
    button.addEventListener('click', () => {
        repeatButtons.forEach((item) => item.classList.remove('is-selected'));
        button.classList.add('is-selected');

        if (repeatInput) {
            repeatInput.value = button.dataset.repeat;
        }

        if (dayContainer) {
            dayContainer.hidden = button.dataset.repeat === 'daily';
        }
    });
});

// Pilihan hari (bisa lebih dari satu)
const dayButtons = document.querySelectorAll('[data-day]');

dayButtons.forEach((button) => {
    button.addEventListener('click', () => {
        button.classList.toggle('is-selected');
    });
});

// Validasi dan pengiriman form tambah template
const templateForm = document.getElementById('template-form');

if (templateForm) {
    templateForm.addEventListener('submit', (event) => {
        templateForm.querySelectorAll('[data-validation-error]').forEach((error) => {
            error.remove();
        });

        let isValid = true;

        const showError = (container, message) => {
            const error = document.createElement('p');
            error.className = 'adm-form-error';
            error.dataset.validationError = 'true';
            error.textContent = message;
            container.appendChild(error);
            isValid = false;
        };

        // Validasi kategori
        if (!categoryInput.value) {
            showError(
                templateForm.querySelector('.adm-category-options').parentElement,
                'Pilih kategori tugas terlebih dahulu.'
            );
        }

        // Ambil hari yang dipilih
        const selectedDays = document.querySelectorAll('[data-day].is-selected');

        // Validasi hari untuk pengulangan mingguan
        if (repeatInput.value === 'weekly' && selectedDays.length === 0) {
            showError(
                dayContainer.parentElement,
                'Pilih minimal satu hari.'
            );
        }

        if (!isValid) {
            event.preventDefault();
            return;
        }

        // Hapus input hari yang dibuat sebelumnya
        templateForm.querySelectorAll('[data-generated-day]').forEach((input) => {
            input.remove();
        });

        // Kirim setiap hari yang dipilih ke Laravel
        if (repeatInput.value === 'weekly') {
            selectedDays.forEach((button) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'days_of_week[]';
                input.value = button.dataset.day;
                input.dataset.generatedDay = 'true';
                templateForm.appendChild(input);
            });
        }
    });
}

// Menu tindakan template tugas
document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();

        const menu = button.parentElement.querySelector('[data-action-menu]');
        const isOpening = menu.hidden;

        document.querySelectorAll('[data-action-menu]').forEach((item) => {
            item.hidden = true;
        });

        document.querySelectorAll('[data-action-menu-toggle]').forEach((item) => {
            item.setAttribute('aria-expanded', 'false');
        });

        menu.hidden = !isOpening;
        button.setAttribute('aria-expanded', String(isOpening));
    });
});

// Submenu ubah status template
document.querySelectorAll('[data-status-menu-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();

        const submenu = button.parentElement.querySelector('[data-status-menu]');
        const isOpening = submenu.hidden;

        submenu.hidden = !isOpening;
        button.setAttribute('aria-expanded', String(isOpening));
    });
});

// Tutup menu jika klik di luar
document.addEventListener('click', () => {
    document.querySelectorAll('[data-action-menu]').forEach((menu) => {
        menu.hidden = true;
    });

    document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', 'false');
    });

    document.querySelectorAll('[data-status-menu]').forEach((submenu) => {
        submenu.hidden = true;
    });

    document.querySelectorAll('[data-status-menu-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', 'false');
    });
});

// Tutup menu dengan tombol Escape
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('[data-action-menu], [data-status-menu]').forEach((menu) => {
            menu.hidden = true;
        });

        document.querySelectorAll('[data-action-menu-toggle], [data-status-menu-toggle]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
    }
});

// Mode edit template tugas
document.querySelectorAll('[data-edit-template]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!drawer || !templateForm) return;

        const titleInput = document.getElementById('task-name');
        const residentInput = document.getElementById('elder');
        const timeInput = document.getElementById('task-time');
        const instructionsInput = document.getElementById('task-instructions');
        const drawerTitle = document.getElementById('template-drawer-title');
        const submitButton = document.getElementById('template-submit-button');

        // Isi form dengan data template yang dipilih
        residentInput.value = button.dataset.resident || '';
        titleInput.value = button.dataset.title || '';
        categoryInput.value = button.dataset.category || '';
        timeInput.value = button.dataset.time || '';
        instructionsInput.value = button.dataset.instructions || '';

        // Pilih kategori
        categoryButtons.forEach((item) => {
            item.classList.toggle(
                'is-selected',
                item.dataset.category === button.dataset.category
            );
        });

        // Ambil data hari
        let selectedDays = [];

        try {
            selectedDays = JSON.parse(button.dataset.days || '[]');
        } catch (error) {
            selectedDays = [];
        }

        if (!Array.isArray(selectedDays)) {
            selectedDays = [];
        }

        // Atur frekuensi
        const frequency = button.dataset.frequency || 'daily';
        repeatInput.value = frequency;

        repeatButtons.forEach((item) => {
            item.classList.toggle(
                'is-selected',
                item.dataset.repeat === frequency
            );
        });

        dayContainer.hidden = frequency === 'daily';

        // Pilih hari yang sudah tersimpan
        dayButtons.forEach((item) => {
            item.classList.toggle(
                'is-selected',
                selectedDays.map(String).includes(item.dataset.day)
            );
        });

        // Ubah form menjadi mode update
        templateForm.action = button.dataset.updateUrl;

        let methodInput = templateForm.querySelector('input[name="_method"]');

        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            templateForm.appendChild(methodInput);
        }

        methodInput.value = 'PATCH';

        // Ubah judul dan tombol simpan
        drawerTitle.textContent = 'Edit template';
        submitButton.textContent = 'Simpan perubahan';

        drawer.hidden = false;
    });
});

// Kembalikan form ke mode tambah saat tombol Tambah diklik
if (openDrawerButton && templateForm) {
    openDrawerButton.addEventListener('click', () => {
        templateForm.reset();

        templateForm.action = templateForm.dataset.storeAction;

        const methodInput = templateForm.querySelector('input[name="_method"]');
        if (methodInput) {
            methodInput.remove();
        }

        categoryButtons.forEach((item) => {
            item.classList.remove('is-selected');
        });

        repeatButtons.forEach((item) => {
            item.classList.toggle('is-selected', item.dataset.repeat === 'daily');
        });

        dayButtons.forEach((item) => {
            item.classList.remove('is-selected');
        });

        dayContainer.hidden = true;

        document.getElementById('template-drawer-title').textContent = 'Tambah template';
        document.getElementById('template-submit-button').textContent = 'Simpan data';
    });
}


// =========================================================
// Caregiver — Hari Ini: tabs, filters, and status dialog
// =========================================================

(function () {
    const residentTabs = document.querySelectorAll('[data-resident-tab]');
    const residentPanels = document.querySelectorAll('[data-resident-panel]');

    // Pilih lansia
    residentTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const residentId = tab.dataset.residentTab;

            residentTabs.forEach((item) => {
                const isActive = item === tab;

                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });

            residentPanels.forEach((panel) => {
                panel.hidden = panel.dataset.residentPanel !== residentId;
            });
        });
    });

    // Filter tugas berdasarkan status
    residentPanels.forEach((panel) => {
        const filterButtons = panel.querySelectorAll('[data-task-filter]');
        const taskRows = panel.querySelectorAll('[data-task-row]');
        const filterEmpty = panel.querySelector('.rk-stitch-filter-empty');
        const baseEmpty = panel.querySelector('.rk-stitch-empty');

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const wasActive = button.classList.contains('is-active');
                const selectedStatus = wasActive
                    ? null
                    : button.dataset.taskFilter;

                filterButtons.forEach((item) => {
                    item.classList.remove('is-active');
                    item.setAttribute('aria-pressed', 'false');
                });

                if (selectedStatus !== null) {
                    button.classList.add('is-active');
                    button.setAttribute('aria-pressed', 'true');
                }

                let visibleCount = 0;

                taskRows.forEach((row) => {
                    const isVisible = selectedStatus === null
                        || row.dataset.taskStatus === selectedStatus;

                    row.hidden = !isVisible;

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (baseEmpty) {
                    baseEmpty.hidden = selectedStatus !== null;
                }

                if (filterEmpty) {
                    filterEmpty.hidden = selectedStatus === null
                        || visibleCount > 0;
                }
            });
        });
    });

    // Elemen dialog
    const dialog = document.getElementById('care-task-dialog');

    if (!dialog) {
        return;
    }

    const dialogForm = document.getElementById('care-task-update-form');
    const dialogTitle = document.getElementById('care-task-dialog-title');
    const dialogContext = document.getElementById('care-task-dialog-context');
    const statusField = document.getElementById('care-task-dialog-status-field');
    const statusSelect = document.getElementById('care-task-dialog-status');
    const statusHidden = document.getElementById('care-task-dialog-status-hidden');
    const reasonsContainer = document.getElementById('care-task-dialog-reasons');
    const textareaContainer = document.getElementById('care-task-dialog-textarea');
    const reasonTextarea = document.getElementById('care-task-dialog-reason');
    const reasonError = document.getElementById('care-task-dialog-error');

    const openButtons = document.querySelectorAll('[data-care-open-dialog]');
    const closeButtons = dialog.querySelectorAll('[data-care-close-dialog]');
    const reasonButtons = dialog.querySelectorAll('[data-care-reason]');
    const otherReasonButton = dialog.querySelector('[data-care-reason-other]');

    let previousFocus = null;

    function clearReasonSelection() {
        reasonButtons.forEach((button) => {
            button.classList.remove('is-selected');
        });

        if (otherReasonButton) {
            otherReasonButton.classList.remove('is-selected');
        }
    }

    function selectReasonButton(value) {
        clearReasonSelection();

        const matchingButton = Array.from(reasonButtons).find((button) => {
            return button.dataset.careReason === value;
        });

        if (matchingButton) {
            matchingButton.classList.add('is-selected');
        } else if (value && otherReasonButton) {
            otherReasonButton.classList.add('is-selected');
        }
    }

    function updateDialogStatus(status) {
        statusHidden.value = status;

        const requiresReason = status === 'postponed' || status === 'missed';

        reasonsContainer.hidden = !requiresReason;
        textareaContainer.hidden = !requiresReason;

        if (!requiresReason) {
            reasonTextarea.value = '';
            clearReasonSelection();
            dialogTitle.textContent = 'Ubah status tugas';
        } else if (status === 'postponed') {
            dialogTitle.textContent = 'Kenapa ditunda?';
            reasonTextarea.placeholder = 'Tuliskan alasan penundaan di sini...';
        } else {
            dialogTitle.textContent = 'Kenapa terlewat?';
            reasonTextarea.placeholder = 'Tuliskan alasan tugas terlewat di sini...';
        }

        reasonError.hidden = true;
    }

    function openDialog(button) {
        previousFocus = button;

        dialogForm.reset();
        clearReasonSelection();

        dialogForm.action = button.dataset.updateUrl;

        const mode = button.dataset.dialogMode;
        const targetStatus = button.dataset.targetStatus;

        const isEditMode = mode === 'edit';

        statusField.hidden = !isEditMode;
        statusSelect.disabled = !isEditMode;

        statusSelect.value = targetStatus;
        statusHidden.value = targetStatus;

        dialogContext.textContent =
            `${button.dataset.taskTitle}, ${button.dataset.residentName}`;

        if (isEditMode) {
            const currentReason = button.dataset.currentReason || '';

            updateDialogStatus(targetStatus);
            reasonTextarea.value = currentReason;

            if (targetStatus === 'postponed' || targetStatus === 'missed') {
                selectReasonButton(currentReason);
            }
        } else {
            reasonTextarea.value = '';
            updateDialogStatus(targetStatus);
        }

        dialog.hidden = false;

        const firstControl = isEditMode
            ? statusSelect
            : dialog.querySelector('[data-care-reason]');

        if (firstControl) {
            firstControl.focus();
        }
    }

    function closeDialog() {
        dialog.hidden = true;

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    // Buka dialog dari tombol Tunda, Lewat, atau Ubah
    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openDialog(button);
        });
    });

    // Koreksi status
    statusSelect.addEventListener('change', () => {
        updateDialogStatus(statusSelect.value);
    });

    // Pilihan alasan
    reasonButtons.forEach((button) => {
        button.addEventListener('click', () => {
            reasonTextarea.value = button.dataset.careReason;
            selectReasonButton(button.dataset.careReason);
            reasonError.hidden = true;
        });
    });

    // Pilihan alasan lainnya
    if (otherReasonButton) {
        otherReasonButton.addEventListener('click', () => {
            clearReasonSelection();
            otherReasonButton.classList.add('is-selected');
            reasonTextarea.focus();
            reasonError.hidden = true;
        });
    }

    // Hilangkan pesan kesalahan saat pengguna mulai mengetik
    reasonTextarea.addEventListener('input', () => {
        if (reasonTextarea.value.trim() !== '') {
            reasonError.hidden = true;
        }
    });

    // Tutup dialog
    closeButtons.forEach((button) => {
        button.addEventListener('click', closeDialog);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) {
            closeDialog();
        }
    });

    // Validasi alasan sebelum mengirim status
    dialogForm.addEventListener('submit', (event) => {
        const status = statusHidden.value;
        const reason = reasonTextarea.value.trim();

        if (
            (status === 'postponed' || status === 'missed')
            && reason === ''
        ) {
            event.preventDefault();

            reasonError.hidden = false;
            reasonTextarea.focus();

            return;
        }

        if (status === 'completed') {
            reasonTextarea.value = '';
        }
    });
})();