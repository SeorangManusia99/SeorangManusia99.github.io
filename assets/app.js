document.addEventListener('DOMContentLoaded', () => {
    initSubmitButtons();
    initEditButtons();
    initTableFilters();
    initScheduleFrequency();
    initGenderToggle();
    initPlanGenderToggle();
    initFormReset();
    initAlarmChecker();
});

function initSubmitButtons() {
    document.querySelectorAll('button[data-submit-label]').forEach((button) => {
        button.dataset.defaultLabel = button.textContent.trim();
    });
}

function initFormReset() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('reset', () => {
            window.setTimeout(() => {
                resetFormState(form);
            }, 0);
        });
    });
}

function resetFormState(form) {
    const idField = form.querySelector('input[name="id"]');
    if (idField) {
        idField.value = '';
    }

    const submitButton = form.querySelector('[data-submit-label]');
    if (submitButton) {
        submitButton.textContent = submitButton.dataset.defaultLabel || submitButton.textContent;
    }

    form.classList.remove('ring-2', 'ring-blue-300');
}

function initEditButtons() {
    document.querySelectorAll('[data-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const formId = button.dataset.form;
            const raw = button.dataset.record;
            if (!formId || !raw) return;

            const form = document.getElementById(formId);
            if (!form) return;

            try {
                const record = JSON.parse(raw);
                populateForm(form, record);
            } catch (error) {
                console.error('Gagal memuat data', error);
            }
        });
    });
}

function populateForm(form, record) {
    form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.checked = false;
    });

    Object.entries(record).forEach(([key, value]) => {
        if (key === 'id') {
            const idField = form.querySelector('input[name="id"]');
            if (idField) {
                idField.value = value;
            }
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((val) => {
                form.querySelectorAll(`input[name="${key}[]"][value="${val}"]`).forEach((checkbox) => {
                    checkbox.checked = true;
                });
            });
            return;
        }

        const field = form.querySelector(`[name="${key}"]`);
        if (!field) return;

        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
        } else if (field.tagName === 'SELECT') {
            field.value = value ?? '';
            field.dispatchEvent(new Event('change'));
        } else {
            field.value = value ?? '';
        }
    });

    const submitButton = form.querySelector('[data-submit-label]');
    if (submitButton) {
        const base = submitButton.dataset.defaultLabel || submitButton.textContent;
        submitButton.textContent = base.replace('Simpan', 'Perbarui');
    }

    form.classList.add('ring-2', 'ring-blue-300');
}

function initTableFilters() {
    document.querySelectorAll('[data-filter-target]').forEach((input) => {
        const targetId = input.dataset.filterTarget;
        const table = document.getElementById(targetId);
        if (!table) return;

        input.addEventListener('input', () => {
            const term = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });
}

function initScheduleFrequency() {
    document.querySelectorAll('select[data-frequency]').forEach((select) => {
        const form = select.closest('form');
        const sections = form.querySelectorAll('[data-frequency-section]');

        const toggleSections = () => {
            const value = select.value;
            sections.forEach((section) => {
                section.classList.toggle('hidden', section.dataset.frequencySection !== value);
            });
        };

        select.addEventListener('change', toggleSections);
        toggleSections();
    });
}

function initGenderToggle() {
    document.querySelectorAll('select[data-toggle-gender]').forEach((select) => {
        const form = select.closest('form');
        const genderField = form.querySelector('select[name="gender"]');

        const toggleGender = () => {
            if (!genderField) return;
            const isFish = select.value === 'ikan';
            genderField.disabled = !isFish;
            if (!isFish) {
                genderField.value = '';
            }
        };

        select.addEventListener('change', toggleGender);
        toggleGender();
    });
}

function initPlanGenderToggle() {
    document.querySelectorAll('select[data-plan-category]').forEach((select) => {
        const form = select.closest('form');
        const genderWrapper = form.querySelector('[data-plan-gender]');
        const genderSelect = genderWrapper ? genderWrapper.querySelector('select[name="gender"]') : null;

        const toggle = () => {
            const show = select.value === 'ikan';
            if (genderWrapper) {
                genderWrapper.classList.toggle('hidden', !show);
            }
            if (!show && genderSelect) {
                genderSelect.value = '';
            }
        };

        select.addEventListener('change', toggle);
        toggle();
    });
}

function initAlarmChecker() {
    if (!Array.isArray(window.scheduleData)) {
        return;
    }

    const toast = document.getElementById('alarmToast');
    const titleEl = document.getElementById('alarmTitle');
    const descEl = document.getElementById('alarmDescription');
    let toastTimer = null;

    const storageKey = 'myaquarium_alarm_log';

    const loadTriggered = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            console.warn('Tidak dapat membaca localStorage', error);
            return {};
        }
    };

    const saveTriggered = (data) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(data));
        } catch (error) {
            console.warn('Tidak dapat menyimpan localStorage', error);
        }
    };

    const showToast = (title, description) => {
        if (!toast) return;
        titleEl.textContent = title;
        descEl.textContent = description;
        toast.classList.remove('hidden');
        if (toastTimer) {
            clearTimeout(toastTimer);
        }
        toastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 6000);
    };

    const shouldTriggerToday = (schedule, today) => {
        const dayName = today.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
        const dayOfMonth = today.getDate();

        switch (schedule.frequency) {
            case 'daily':
                return true;
            case 'weekly':
            case 'specific':
                return Array.isArray(schedule.days) && schedule.days.includes(dayName);
            case 'monthly':
                return Number(schedule.day_of_month || 0) === dayOfMonth;
            case 'interval':
                const baseDate = schedule.start_date || schedule.created_at;
                const interval = Number(schedule.interval_days || 1);
                if (!baseDate || !interval) return false;
                const start = new Date(baseDate);
                if (Number.isNaN(start.getTime())) return false;
                const diff = Math.floor((today - start) / (1000 * 60 * 60 * 24));
                return diff >= 0 && diff % interval === 0;
            default:
                return false;
        }
    };

    const checkAlarms = () => {
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();
        const dateKey = now.toISOString().slice(0, 10);
        const triggered = loadTriggered();

        window.scheduleData.forEach((schedule) => {
            if (!schedule.time || !shouldTriggerToday(schedule, now)) {
                return;
            }

            const [hour, minute] = schedule.time.split(':').map(Number);
            if (Number.isNaN(hour) || Number.isNaN(minute)) {
                return;
            }

            const scheduleMinutes = hour * 60 + minute;
            if (scheduleMinutes !== currentMinutes) {
                return;
            }

            const key = `${schedule.id}-${dateKey}-${schedule.time}`;
            if (triggered[key]) {
                return;
            }

            const title = schedule.title || schedule.type || 'Pengingat Jadwal';
            const desc = schedule.notes || `Saatnya melakukan ${schedule.type || 'aktivitas'}.`;
            showToast(title, desc);
            triggered[key] = true;
            saveTriggered(triggered);
        });
    };

    checkAlarms();
    setInterval(checkAlarms, 60000);
}
