const STORAGE_KEY = 'myaquarium_state_v1';

let state;

const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

document.addEventListener('DOMContentLoaded', () => {
    state = loadState();

    initSubmitButtons();
    initFormReset();
    initActionButtons();
    initTableFilters();
    initScheduleFrequency();
    initGenderToggle();
    initPlanGenderToggle();
    initFormHandlers();

    renderApp();
    initAlarmChecker();
});

function loadState() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return createEmptyState();
        }

        const parsed = JSON.parse(raw);
        return {
            schedules: Array.isArray(parsed.schedules) ? parsed.schedules : [],
            equipment: Array.isArray(parsed.equipment) ? parsed.equipment : [],
            purchases: Array.isArray(parsed.purchases) ? parsed.purchases : [],
            maintenance: Array.isArray(parsed.maintenance) ? parsed.maintenance : [],
            plans: Array.isArray(parsed.plans) ? parsed.plans : [],
        };
    } catch (error) {
        console.warn('Tidak dapat memuat data dari localStorage', error);
        return createEmptyState();
    }
}

function createEmptyState() {
    return {
        schedules: [],
        equipment: [],
        purchases: [],
        maintenance: [],
        plans: [],
    };
}

function saveState() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (error) {
        console.warn('Tidak dapat menyimpan data ke localStorage', error);
    }
}

function commitState() {
    saveState();
    renderApp();
}

function initFormHandlers() {
    const scheduleForm = document.getElementById('scheduleForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', handleScheduleSubmit);
    }

    const equipmentForm = document.getElementById('equipmentForm');
    if (equipmentForm) {
        equipmentForm.addEventListener('submit', handleEquipmentSubmit);
    }

    const purchaseForm = document.getElementById('purchaseForm');
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', handlePurchaseSubmit);
    }

    const maintenanceForm = document.getElementById('maintenanceForm');
    if (maintenanceForm) {
        maintenanceForm.addEventListener('submit', handleMaintenanceSubmit);
    }

    const planForm = document.getElementById('planForm');
    if (planForm) {
        planForm.addEventListener('submit', handlePlanSubmit);
    }
}

function handleScheduleSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const id = (formData.get('id') || '').trim();
    const existing = id ? state.schedules.find((item) => item.id === id) : null;

    const schedule = {
        id: id || generateId('sch'),
        title: (formData.get('title') || '').trim(),
        type: formData.get('type') || 'pemberian pakan',
        time: formData.get('time') || '08:00',
        frequency: formData.get('frequency') || 'daily',
        days: Array.from(new Set(formData.getAll('days[]').map((day) => String(day).toLowerCase()))),
        interval_days: null,
        day_of_month: null,
        start_date: formData.get('start_date') || existing?.start_date || todayDate(),
        notes: (formData.get('notes') || '').trim(),
        created_at: existing?.created_at || currentTimestamp(),
    };

    const intervalInput = formData.get('interval_days');
    if (intervalInput) {
        const parsed = Math.max(1, parseInt(intervalInput, 10) || 1);
        schedule.interval_days = parsed;
    }

    const dayOfMonthInput = formData.get('day_of_month');
    if (dayOfMonthInput) {
        const parsed = Math.min(31, Math.max(1, parseInt(dayOfMonthInput, 10) || 1));
        schedule.day_of_month = parsed;
    }

    if (!['weekly', 'specific'].includes(schedule.frequency)) {
        schedule.days = [];
    }

    if (schedule.frequency !== 'monthly') {
        schedule.day_of_month = null;
    }

    if (schedule.frequency !== 'interval') {
        schedule.interval_days = null;
    } else {
        schedule.start_date = schedule.start_date || todayDate();
    }

    upsertRecord('schedules', schedule);

    form.reset();
    resetFormState(form);
    commitState();
}

function handleEquipmentSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const id = (formData.get('id') || '').trim();
    const existing = id ? state.equipment.find((item) => item.id === id) : null;

    const record = {
        id: id || generateId('eq'),
        item_type: formData.get('item_type') || 'filter',
        brand: (formData.get('brand') || '').trim(),
        quantity: parseNumber(formData.get('quantity')),
        price: parseNumber(formData.get('price')),
        notes: (formData.get('notes') || '').trim(),
        recorded_at: existing?.recorded_at || currentTimestamp(),
    };

    upsertRecord('equipment', record);

    form.reset();
    resetFormState(form);
    commitState();
}

function handlePurchaseSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const id = (formData.get('id') || '').trim();
    const existing = id ? state.purchases.find((item) => item.id === id) : null;

    const record = {
        id: id || generateId('pur'),
        category: formData.get('category') || 'ikan',
        species: (formData.get('species') || '').trim(),
        variant: (formData.get('variant') || '').trim(),
        gender: (formData.get('gender') || '').trim(),
        quantity: parseNumber(formData.get('quantity')),
        price: parseNumber(formData.get('price')),
        notes: (formData.get('notes') || '').trim(),
        recorded_at: existing?.recorded_at || currentTimestamp(),
    };

    if (record.category !== 'ikan') {
        record.gender = '';
    }

    upsertRecord('purchases', record);

    form.reset();
    resetFormState(form);
    commitState();
}

function handleMaintenanceSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const id = (formData.get('id') || '').trim();
    const existing = id ? state.maintenance.find((item) => item.id === id) : null;

    const record = {
        id: id || generateId('mt'),
        status: formData.get('status') || 'mati',
        species: (formData.get('species') || '').trim(),
        variant: (formData.get('variant') || '').trim(),
        gender: (formData.get('gender') || '').trim(),
        quantity: parseNumber(formData.get('quantity')),
        notes: (formData.get('notes') || '').trim(),
        recorded_at: existing?.recorded_at || currentTimestamp(),
    };

    upsertRecord('maintenance', record);

    form.reset();
    resetFormState(form);
    commitState();
}

function handlePlanSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const id = (formData.get('id') || '').trim();
    const existing = id ? state.plans.find((item) => item.id === id) : null;

    const record = {
        id: id || generateId('plan'),
        category: formData.get('category') || 'ikan',
        name: (formData.get('name') || '').trim(),
        variant: (formData.get('variant') || '').trim(),
        gender: (formData.get('gender') || '').trim(),
        quantity: parseNumber(formData.get('quantity')),
        price: parseNumber(formData.get('price')),
        notes: (formData.get('notes') || '').trim(),
        recorded_at: existing?.recorded_at || currentTimestamp(),
    };

    if (record.category !== 'ikan') {
        record.gender = '';
    }

    upsertRecord('plans', record);

    form.reset();
    resetFormState(form);
    commitState();
}

function upsertRecord(collection, record) {
    const list = state[collection];
    if (!Array.isArray(list)) {
        return;
    }

    const index = list.findIndex((item) => item.id === record.id);
    if (index >= 0) {
        list[index] = record;
    } else {
        list.push(record);
    }
}

function deleteRecord(collection, id) {
    const list = state[collection];
    if (!Array.isArray(list)) {
        return;
    }

    state[collection] = list.filter((item) => item.id !== id);
    commitState();
}

function completePlan(id) {
    const index = state.plans.findIndex((plan) => plan.id === id);
    if (index === -1) {
        return;
    }

    const [plan] = state.plans.splice(index, 1);
    if (!plan) {
        commitState();
        return;
    }

    if (['ikan', 'tanaman'].includes(plan.category)) {
        state.purchases.push({
            id: generateId('pur'),
            category: plan.category,
            species: plan.name || '',
            variant: plan.variant || '',
            gender: plan.category === 'ikan' ? (plan.gender || '') : '',
            quantity: parseNumber(plan.quantity),
            price: parseNumber(plan.price),
            notes: plan.notes || '',
            recorded_at: currentTimestamp(),
        });
    } else {
        state.equipment.push({
            id: generateId('eq'),
            item_type: plan.category || 'lainnya',
            brand: plan.name || '',
            quantity: parseNumber(plan.quantity),
            price: parseNumber(plan.price),
            notes: plan.notes || '',
            recorded_at: currentTimestamp(),
        });
    }

    commitState();
}

function renderApp() {
    const metrics = computeMetrics();

    updateStats(metrics);
    renderAgenda(metrics.upcomingTasks);
    renderRecentActivities(metrics.recentActivities);
    renderSchedulesTable(state.schedules);
    renderEquipmentTable(state.equipment);
    renderPurchasesTable(state.purchases);
    renderMaintenanceTable(state.maintenance);
    renderPlansTable(state.plans);
    renderFinanceSummary(metrics);
    updateCurrentYear();

    window.scheduleData = state.schedules.map((item) => ({ ...item }));
}

function computeMetrics() {
    const totalSchedules = state.schedules.length;
    const totalEquipment = state.equipment.length;
    const totalPurchases = state.purchases.length;

    const totalFishPurchased = state.purchases.reduce((sum, purchase) => {
        return purchase?.category === 'ikan' ? sum + parseNumber(purchase.quantity) : sum;
    }, 0);

    const fishAdjustments = state.maintenance.reduce((sum, record) => {
        const qty = parseNumber(record.quantity);
        if (!qty) {
            return sum;
        }
        const status = (record.status || '').toLowerCase();
        return status === 'mati' ? sum - qty : sum + qty;
    }, 0);

    const currentFish = Math.max(0, Math.round(totalFishPurchased + fishAdjustments));

    const equipmentExpense = state.equipment.reduce((sum, item) => sum + parseNumber(item.price), 0);
    const purchaseExpense = state.purchases.reduce((sum, item) => sum + parseNumber(item.price), 0);
    const totalExpense = equipmentExpense + purchaseExpense;
    const planBudget = state.plans.reduce((sum, plan) => sum + parseNumber(plan.price), 0);

    const upcomingTasks = computeUpcomingTasks(state.schedules);
    const recentActivities = computeRecentActivities();

    return {
        totalSchedules,
        totalEquipment,
        totalPurchases,
        currentFish,
        equipmentExpense,
        purchaseExpense,
        totalExpense,
        planBudget,
        upcomingTasks,
        recentActivities,
    };
}

function updateStats(metrics) {
    setTextForAll('[data-stat="totalSchedules"]', metrics.totalSchedules);
    setTextForAll('[data-stat="totalEquipment"]', metrics.totalEquipment);
    setTextForAll('[data-stat="totalPurchases"]', metrics.totalPurchases);
    setTextForAll('[data-stat="currentFish"]', metrics.currentFish);
    setTextForAll('[data-stat="totalExpense"]', formatCurrency(metrics.totalExpense));
    setTextForAll('[data-stat="upcomingTasks"]', metrics.upcomingTasks.length);
}

function renderAgenda(upcomingTasks) {
    const card = document.querySelector('[data-agenda-card]');
    const container = card ? card.querySelector('[data-upcoming-container]') : null;

    if (!card || !container) {
        return;
    }

    container.innerHTML = '';

    if (!upcomingTasks.length) {
        card.classList.add('hidden');
        return;
    }

    card.classList.remove('hidden');

    upcomingTasks.forEach((task) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'bg-white/70 rounded-2xl px-4 py-3 border border-white/60';

        const titleRow = document.createElement('p');
        titleRow.className = 'text-sm font-semibold text-slate-700 flex items-center justify-between';

        const titleSpan = document.createElement('span');
        titleSpan.textContent = task.title || capitalizeWords(task.type || '');
        titleRow.appendChild(titleSpan);

        const timeSpan = document.createElement('span');
        timeSpan.className = 'text-blue-600 font-medium';
        timeSpan.textContent = task.time || '-';
        titleRow.appendChild(timeSpan);

        const meta = document.createElement('p');
        meta.className = 'text-xs text-slate-500 mt-1';
        meta.textContent = `Jenis: ${capitalizeWords(task.type || '')} · Frekuensi: ${capitalizeWords(task.frequency || '')}`;

        wrapper.appendChild(titleRow);
        wrapper.appendChild(meta);

        if (task.notes) {
            const notes = document.createElement('p');
            notes.className = 'text-xs text-slate-400 mt-1';
            notes.textContent = `Catatan: ${task.notes}`;
            wrapper.appendChild(notes);
        }

        container.appendChild(wrapper);
    });
}

function renderRecentActivities(activities) {
    const list = document.querySelector('[data-recent-list]');
    if (!list) {
        return;
    }

    list.innerHTML = '';

    if (!activities.length) {
        const item = document.createElement('li');
        item.className = 'text-slate-400';
        item.textContent = 'Belum ada aktivitas.';
        list.appendChild(item);
        return;
    }

    activities.forEach((activity) => {
        const item = document.createElement('li');

        const title = document.createElement('p');
        title.className = 'font-medium text-slate-700';
        title.textContent = activity.title || 'Aktivitas';

        const meta = document.createElement('p');
        meta.className = 'text-xs text-slate-500 flex items-center gap-2';

        const badge = document.createElement('span');
        badge.className = 'badge px-2 py-1 rounded-full text-xs font-semibold text-blue-600';
        badge.textContent = activity.type || 'Aktivitas';

        const timestamp = document.createElement('span');
        timestamp.textContent = formatDateTime(activity.timestamp);

        meta.appendChild(badge);
        meta.appendChild(timestamp);

        item.appendChild(title);
        item.appendChild(meta);

        list.appendChild(item);
    });
}

function renderSchedulesTable(items) {
    const tbody = document.querySelector('[data-table-body="schedules"]');
    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.appendChild(createEmptyRow(5, 'Belum ada jadwal.'));
        return;
    }

    items.forEach((schedule) => {
        const tr = document.createElement('tr');

        const nameTd = document.createElement('td');
        nameTd.className = 'py-3';
        const title = document.createElement('p');
        title.className = 'font-semibold text-slate-700';
        title.textContent = schedule.title || capitalizeWords(schedule.type || '');
        nameTd.appendChild(title);
        if (schedule.notes) {
            const notes = document.createElement('p');
            notes.className = 'text-xs text-slate-500';
            notes.textContent = schedule.notes;
            nameTd.appendChild(notes);
        }
        tr.appendChild(nameTd);

        const typeTd = document.createElement('td');
        typeTd.textContent = capitalizeWords(schedule.type || '');
        tr.appendChild(typeTd);

        const timeTd = document.createElement('td');
        timeTd.textContent = schedule.time || '-';
        tr.appendChild(timeTd);

        const freqTd = document.createElement('td');
        freqTd.textContent = capitalizeWords(schedule.frequency || '');
        tr.appendChild(freqTd);

        const actionTd = document.createElement('td');
        actionTd.className = 'text-right';
        const actionWrapper = document.createElement('div');
        actionWrapper.className = 'flex justify-end gap-2';

        const editButton = createActionButton('Edit', 'px-3 py-1 rounded-full bg-blue-500/10 text-blue-600', {
            action: 'edit',
            form: 'scheduleForm',
            record: JSON.stringify(schedule),
        });

        const deleteButton = createActionButton('Hapus', 'px-3 py-1 rounded-full bg-rose-500/10 text-rose-600', {
            action: 'delete',
            collection: 'schedules',
            id: schedule.id || '',
            confirm: 'Hapus jadwal ini?',
        });

        actionWrapper.appendChild(editButton);
        actionWrapper.appendChild(deleteButton);
        actionTd.appendChild(actionWrapper);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });
}

function renderEquipmentTable(items) {
    const tbody = document.querySelector('[data-table-body="equipment"]');
    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.appendChild(createEmptyRow(5, 'Belum ada data perlengkapan.'));
        return;
    }

    items.forEach((item) => {
        const tr = document.createElement('tr');

        const brandTd = document.createElement('td');
        brandTd.className = 'py-3';
        const brand = document.createElement('p');
        brand.className = 'font-semibold text-slate-700';
        brand.textContent = item.brand || 'Perlengkapan';
        brandTd.appendChild(brand);
        if (item.recorded_at) {
            const recorded = document.createElement('p');
            recorded.className = 'text-xs text-slate-500';
            recorded.textContent = `Dicatat: ${formatDateTime(item.recorded_at)}`;
            brandTd.appendChild(recorded);
        }
        tr.appendChild(brandTd);

        const typeTd = document.createElement('td');
        typeTd.textContent = capitalizeWords(item.item_type || '');
        tr.appendChild(typeTd);

        const quantityTd = document.createElement('td');
        quantityTd.textContent = parseNumber(item.quantity);
        tr.appendChild(quantityTd);

        const priceTd = document.createElement('td');
        priceTd.textContent = formatCurrency(parseNumber(item.price));
        tr.appendChild(priceTd);

        const actionTd = document.createElement('td');
        actionTd.className = 'text-right';
        const actionWrapper = document.createElement('div');
        actionWrapper.className = 'flex justify-end gap-2';

        const editButton = createActionButton('Edit', 'px-3 py-1 rounded-full bg-blue-500/10 text-blue-600', {
            action: 'edit',
            form: 'equipmentForm',
            record: JSON.stringify(item),
        });

        const deleteButton = createActionButton('Hapus', 'px-3 py-1 rounded-full bg-rose-500/10 text-rose-600', {
            action: 'delete',
            collection: 'equipment',
            id: item.id || '',
            confirm: 'Hapus data ini?',
        });

        actionWrapper.appendChild(editButton);
        actionWrapper.appendChild(deleteButton);
        actionTd.appendChild(actionWrapper);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });
}

function renderPurchasesTable(items) {
    const tbody = document.querySelector('[data-table-body="purchases"]');
    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.appendChild(createEmptyRow(5, 'Belum ada data pembelian.'));
        return;
    }

    items.forEach((item) => {
        const tr = document.createElement('tr');

        const speciesTd = document.createElement('td');
        speciesTd.className = 'py-3';
        const species = document.createElement('p');
        species.className = 'font-semibold text-slate-700';
        species.textContent = item.species || 'Pembelian';
        speciesTd.appendChild(species);
        if (item.recorded_at) {
            const recorded = document.createElement('p');
            recorded.className = 'text-xs text-slate-500';
            recorded.textContent = `Dicatat: ${formatDateTime(item.recorded_at)}`;
            speciesTd.appendChild(recorded);
        }
        tr.appendChild(speciesTd);

        const variantTd = document.createElement('td');
        variantTd.textContent = item.variant || '-';
        tr.appendChild(variantTd);

        const quantityTd = document.createElement('td');
        quantityTd.textContent = parseNumber(item.quantity);
        tr.appendChild(quantityTd);

        const priceTd = document.createElement('td');
        priceTd.textContent = formatCurrency(parseNumber(item.price));
        tr.appendChild(priceTd);

        const actionTd = document.createElement('td');
        actionTd.className = 'text-right';
        const actionWrapper = document.createElement('div');
        actionWrapper.className = 'flex justify-end gap-2';

        const editButton = createActionButton('Edit', 'px-3 py-1 rounded-full bg-blue-500/10 text-blue-600', {
            action: 'edit',
            form: 'purchaseForm',
            record: JSON.stringify(item),
        });

        const deleteButton = createActionButton('Hapus', 'px-3 py-1 rounded-full bg-rose-500/10 text-rose-600', {
            action: 'delete',
            collection: 'purchases',
            id: item.id || '',
            confirm: 'Hapus data ini?',
        });

        actionWrapper.appendChild(editButton);
        actionWrapper.appendChild(deleteButton);
        actionTd.appendChild(actionWrapper);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });
}

function renderMaintenanceTable(items) {
    const tbody = document.querySelector('[data-table-body="maintenance"]');
    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.appendChild(createEmptyRow(5, 'Belum ada catatan.'));
        return;
    }

    items.forEach((item) => {
        const tr = document.createElement('tr');

        const statusTd = document.createElement('td');
        statusTd.className = 'py-3 font-semibold text-slate-700';
        statusTd.textContent = capitalizeWords(item.status || '');
        tr.appendChild(statusTd);

        const speciesTd = document.createElement('td');
        speciesTd.textContent = item.species || '-';
        tr.appendChild(speciesTd);

        const quantityTd = document.createElement('td');
        quantityTd.textContent = parseNumber(item.quantity);
        tr.appendChild(quantityTd);

        const timeTd = document.createElement('td');
        timeTd.textContent = formatDateTime(item.recorded_at);
        tr.appendChild(timeTd);

        const actionTd = document.createElement('td');
        actionTd.className = 'text-right';
        const actionWrapper = document.createElement('div');
        actionWrapper.className = 'flex justify-end gap-2';

        const editButton = createActionButton('Edit', 'px-3 py-1 rounded-full bg-blue-500/10 text-blue-600', {
            action: 'edit',
            form: 'maintenanceForm',
            record: JSON.stringify(item),
        });

        const deleteButton = createActionButton('Hapus', 'px-3 py-1 rounded-full bg-rose-500/10 text-rose-600', {
            action: 'delete',
            collection: 'maintenance',
            id: item.id || '',
            confirm: 'Hapus catatan ini?',
        });

        actionWrapper.appendChild(editButton);
        actionWrapper.appendChild(deleteButton);
        actionTd.appendChild(actionWrapper);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });
}

function renderPlansTable(items) {
    const tbody = document.querySelector('[data-table-body="plans"]');
    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';

    if (!items.length) {
        tbody.appendChild(createEmptyRow(5, 'Belum ada rencana.'));
        return;
    }

    items.forEach((item) => {
        const tr = document.createElement('tr');

        const nameTd = document.createElement('td');
        nameTd.className = 'py-3';
        const name = document.createElement('p');
        name.className = 'font-semibold text-slate-700';
        name.textContent = item.name || 'Rencana';
        nameTd.appendChild(name);
        if (item.recorded_at) {
            const recorded = document.createElement('p');
            recorded.className = 'text-xs text-slate-500';
            recorded.textContent = `Dicatat: ${formatDateTime(item.recorded_at)}`;
            nameTd.appendChild(recorded);
        }
        tr.appendChild(nameTd);

        const categoryTd = document.createElement('td');
        categoryTd.textContent = capitalizeWords(item.category || '');
        tr.appendChild(categoryTd);

        const quantityTd = document.createElement('td');
        quantityTd.textContent = parseNumber(item.quantity);
        tr.appendChild(quantityTd);

        const priceTd = document.createElement('td');
        priceTd.textContent = formatCurrency(parseNumber(item.price));
        tr.appendChild(priceTd);

        const actionTd = document.createElement('td');
        actionTd.className = 'text-right';
        const actionWrapper = document.createElement('div');
        actionWrapper.className = 'flex justify-end gap-2';

        const editButton = createActionButton('Edit', 'px-3 py-1 rounded-full bg-blue-500/10 text-blue-600', {
            action: 'edit',
            form: 'planForm',
            record: JSON.stringify(item),
        });

        const deleteButton = createActionButton('Hapus', 'px-3 py-1 rounded-full bg-rose-500/10 text-rose-600', {
            action: 'delete',
            collection: 'plans',
            id: item.id || '',
            confirm: 'Hapus rencana ini?',
        });

        const completeButton = createActionButton('Tandai Terbeli', 'px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600', {
            action: 'complete-plan',
            id: item.id || '',
            confirm: 'Pindahkan ke catatan pembelian/perlengkapan?',
        });

        actionWrapper.appendChild(editButton);
        actionWrapper.appendChild(deleteButton);
        actionWrapper.appendChild(completeButton);
        actionTd.appendChild(actionWrapper);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });
}

function renderFinanceSummary(metrics) {
    setTextForAll('[data-summary="equipmentTotal"]', formatCurrency(metrics.totalExpense));
    setTextForAll('[data-summary="purchaseTotal"]', formatCurrency(metrics.totalExpense));
    setTextForAll('[data-summary="planTotal"]', formatCurrency(metrics.planBudget));

    setTextForAll('[data-finance="equipmentActual"]', formatCurrency(metrics.equipmentExpense));
    setTextForAll('[data-finance="purchaseActual"]', formatCurrency(metrics.purchaseExpense));
    setTextForAll('[data-finance="planBudget"]', formatCurrency(metrics.planBudget));
    setTextForAll('[data-finance="totalExpense"]', formatCurrency(metrics.totalExpense));
    setTextForAll('[data-finance="planTotal"]', formatCurrency(metrics.planBudget));
}

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

    form.querySelectorAll('[data-frequency], [data-toggle-gender], [data-plan-category]').forEach((select) => {
        select.dispatchEvent(new Event('change'));
    });
}

function initActionButtons() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const action = button.dataset.action;
        if (action === 'edit') {
            const formId = button.dataset.form;
            const rawRecord = button.dataset.record;
            if (!formId || !rawRecord) {
                return;
            }

            const form = document.getElementById(formId);
            if (!form) {
                return;
            }

            try {
                const record = JSON.parse(rawRecord);
                populateForm(form, record);
            } catch (error) {
                console.error('Gagal memuat data', error);
            }
            return;
        }

        if (action === 'delete') {
            const collection = button.dataset.collection;
            const id = button.dataset.id;
            if (!collection || !id) {
                return;
            }

            const confirmMessage = button.dataset.confirm || 'Hapus data ini?';
            if (window.confirm(confirmMessage)) {
                deleteRecord(collection, id);
            }
            return;
        }

        if (action === 'complete-plan') {
            const id = button.dataset.id;
            if (!id) {
                return;
            }

            const confirmMessage = button.dataset.confirm || 'Pindahkan ke catatan pembelian/perlengkapan?';
            if (window.confirm(confirmMessage)) {
                completePlan(id);
            }
        }
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
        if (!field) {
            return;
        }

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
        if (!table) {
            return;
        }

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
        const sections = form ? form.querySelectorAll('[data-frequency-section]') : [];

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
        const genderField = form ? form.querySelector('select[name="gender"]') : null;

        const toggleGender = () => {
            if (!genderField) {
                return;
            }
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
        const genderWrapper = form ? form.querySelector('[data-plan-gender]') : null;
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
        if (!toast) {
            return;
        }
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

    const checkAlarms = () => {
        const schedules = Array.isArray(window.scheduleData) ? window.scheduleData : [];
        if (!schedules.length) {
            return;
        }

        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();
        const dateKey = now.toISOString().slice(0, 10);
        const triggered = loadTriggered();

        schedules.forEach((schedule) => {
            if (!schedule.time || !scheduleOccursToday(schedule, now)) {
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

function scheduleOccursToday(schedule, referenceDate = new Date()) {
    if (!schedule) {
        return false;
    }

    const frequency = schedule.frequency || 'daily';
    const days = Array.isArray(schedule.days) ? schedule.days.map((day) => String(day).toLowerCase()) : [];
    const dayName = referenceDate.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();

    switch (frequency) {
        case 'daily':
            return true;
        case 'weekly':
        case 'specific':
            return days.includes(dayName);
        case 'monthly':
            return Number(schedule.day_of_month || 0) === referenceDate.getDate();
        case 'interval': {
            const base = schedule.start_date || schedule.created_at;
            const interval = Math.max(1, parseInt(schedule.interval_days, 10) || 1);
            if (!base) {
                return false;
            }
            const startDate = parseDateOnly(base);
            if (!startDate) {
                return false;
            }
            const today = startOfDay(referenceDate);
            const diff = Math.floor((today - startDate) / (1000 * 60 * 60 * 24));
            return diff >= 0 && diff % interval === 0;
        }
        default:
            return false;
    }
}

function computeUpcomingTasks(schedules) {
    const now = new Date();
    return schedules
        .filter((schedule) => scheduleOccursToday(schedule, now))
        .sort((a, b) => (a.time || '').localeCompare(b.time || ''))
        .slice(0, 5);
}

function computeRecentActivities() {
    const activities = [];

    state.equipment.forEach((item) => {
        activities.push({
            type: 'Perlengkapan',
            title: item.brand || capitalizeWords(item.item_type || ''),
            timestamp: item.recorded_at || '',
        });
    });

    state.purchases.forEach((item) => {
        activities.push({
            type: capitalizeWords(item.category || 'Pembelian'),
            title: item.species || item.variant || 'Pembelian',
            timestamp: item.recorded_at || '',
        });
    });

    state.maintenance.forEach((item) => {
        const status = capitalizeWords(item.status || 'Pemeliharaan');
        const title = [status, item.species || ''].join(' ').trim();
        activities.push({
            type: 'Pemeliharaan',
            title,
            timestamp: item.recorded_at || '',
        });
    });

    return activities
        .sort((a, b) => (parseTimestamp(b.timestamp)?.getTime() || 0) - (parseTimestamp(a.timestamp)?.getTime() || 0))
        .slice(0, 6);
}

function createEmptyRow(colspan, message) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = colspan;
    td.className = 'py-4 text-center text-slate-400';
    td.textContent = message;
    tr.appendChild(td);
    return tr;
}

function createActionButton(label, className, dataset) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.textContent = label;
    Object.entries(dataset || {}).forEach(([key, value]) => {
        button.dataset[key] = value;
    });
    return button;
}

function setTextForAll(selector, value) {
    document.querySelectorAll(selector).forEach((element) => {
        element.textContent = value;
    });
}

function updateCurrentYear() {
    const year = new Date().getFullYear();
    document.querySelectorAll('[data-current-year]').forEach((element) => {
        element.textContent = year;
    });
}

function generateId(prefix) {
    return `${prefix}_${Math.random().toString(36).slice(2, 8)}${Date.now().toString(36)}`;
}

function parseNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
}

function formatCurrency(amount) {
    return currencyFormatter.format(parseNumber(amount));
}

function capitalizeWords(text) {
    return (text || '').toString().replace(/\b\w/g, (char) => char.toUpperCase());
}

function currentTimestamp() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

function todayDate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function parseTimestamp(value) {
    if (!value) {
        return null;
    }
    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const date = new Date(normalized);
    return Number.isNaN(date.getTime()) ? null : date;
}

function parseDateOnly(value) {
    const date = parseTimestamp(value);
    if (!date) {
        return null;
    }
    return startOfDay(date);
}

function startOfDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function formatDateTime(value) {
    const date = parseTimestamp(value);
    if (!date) {
        return value || '-';
    }
    const datePart = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const timePart = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
    return `${datePart} ${timePart}`;
}
