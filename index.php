<?php
require_once __DIR__ . '/includes/data.php';

date_default_timezone_set('Asia/Jakarta');

$schedules = loadData('schedules.json');
$equipment = loadData('equipment.json');
$purchases = loadData('purchases.json');
$maintenance = loadData('maintenance.json');
$plans = loadData('purchase_plans.json');

function findRecordById(array $collection, string $id): ?array
{
    foreach ($collection as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'save_schedule':
            $id = $_POST['id'] ?? '';
            $days = isset($_POST['days']) ? array_map('strtolower', (array) $_POST['days']) : [];
            $frequency = $_POST['frequency'] ?? 'daily';
            $existing = $id ? findRecordById($schedules, $id) : null;

            $schedule = [
                'id' => $id ?: uniqid('sch_'),
                'title' => trim($_POST['title'] ?? ''),
                'type' => $_POST['type'] ?? 'pemberian pakan',
                'time' => $_POST['time'] ?? '08:00',
                'frequency' => $frequency,
                'days' => $days,
                'interval_days' => isset($_POST['interval_days']) ? max(1, (int) $_POST['interval_days']) : null,
                'day_of_month' => isset($_POST['day_of_month']) ? max(1, min(31, (int) $_POST['day_of_month'])) : null,
                'start_date' => $_POST['start_date'] ?? ($existing['start_date'] ?? date('Y-m-d')),
                'notes' => trim($_POST['notes'] ?? ''),
                'created_at' => $existing['created_at'] ?? date('Y-m-d H:i'),
            ];

            upsertRecord($schedules, $schedule);
            saveData('schedules.json', $schedules);
            break;

        case 'delete_schedule':
            if (!empty($_POST['id'])) {
                deleteRecord($schedules, $_POST['id']);
                saveData('schedules.json', $schedules);
            }
            break;

        case 'save_equipment':
            $id = $_POST['id'] ?? '';
            $existing = $id ? findRecordById($equipment, $id) : null;

            $item = [
                'id' => $id ?: uniqid('eq_'),
                'item_type' => $_POST['item_type'] ?? 'filter',
                'brand' => trim($_POST['brand'] ?? ''),
                'quantity' => (float) ($_POST['quantity'] ?? 0),
                'price' => (float) ($_POST['price'] ?? 0),
                'notes' => trim($_POST['notes'] ?? ''),
                'recorded_at' => $existing['recorded_at'] ?? date('Y-m-d H:i'),
            ];

            upsertRecord($equipment, $item);
            saveData('equipment.json', $equipment);
            break;

        case 'delete_equipment':
            if (!empty($_POST['id'])) {
                deleteRecord($equipment, $_POST['id']);
                saveData('equipment.json', $equipment);
            }
            break;

        case 'save_purchase':
            $id = $_POST['id'] ?? '';
            $existing = $id ? findRecordById($purchases, $id) : null;

            $purchase = [
                'id' => $id ?: uniqid('pur_'),
                'category' => $_POST['category'] ?? 'ikan',
                'species' => trim($_POST['species'] ?? ''),
                'variant' => trim($_POST['variant'] ?? ''),
                'gender' => $_POST['gender'] ?? '',
                'quantity' => (int) ($_POST['quantity'] ?? 0),
                'price' => (float) ($_POST['price'] ?? 0),
                'notes' => trim($_POST['notes'] ?? ''),
                'recorded_at' => $existing['recorded_at'] ?? date('Y-m-d H:i'),
            ];

            if ($purchase['category'] !== 'ikan') {
                $purchase['gender'] = '';
            }

            upsertRecord($purchases, $purchase);
            saveData('purchases.json', $purchases);
            break;

        case 'delete_purchase':
            if (!empty($_POST['id'])) {
                deleteRecord($purchases, $_POST['id']);
                saveData('purchases.json', $purchases);
            }
            break;

        case 'save_maintenance':
            $id = $_POST['id'] ?? '';
            $existing = $id ? findRecordById($maintenance, $id) : null;

            $record = [
                'id' => $id ?: uniqid('mt_'),
                'status' => $_POST['status'] ?? 'mati',
                'species' => trim($_POST['species'] ?? ''),
                'variant' => trim($_POST['variant'] ?? ''),
                'gender' => $_POST['gender'] ?? '',
                'quantity' => (int) ($_POST['quantity'] ?? 0),
                'notes' => trim($_POST['notes'] ?? ''),
                'recorded_at' => $existing['recorded_at'] ?? date('Y-m-d H:i'),
            ];

            upsertRecord($maintenance, $record);
            saveData('maintenance.json', $maintenance);
            break;

        case 'delete_maintenance':
            if (!empty($_POST['id'])) {
                deleteRecord($maintenance, $_POST['id']);
                saveData('maintenance.json', $maintenance);
            }
            break;

        case 'save_plan':
            $id = $_POST['id'] ?? '';
            $existing = $id ? findRecordById($plans, $id) : null;

            $plan = [
                'id' => $id ?: uniqid('plan_'),
                'category' => $_POST['category'] ?? 'ikan',
                'name' => trim($_POST['name'] ?? ''),
                'variant' => trim($_POST['variant'] ?? ''),
                'gender' => $_POST['gender'] ?? '',
                'quantity' => (float) ($_POST['quantity'] ?? 0),
                'price' => (float) ($_POST['price'] ?? 0),
                'notes' => trim($_POST['notes'] ?? ''),
                'recorded_at' => $existing['recorded_at'] ?? date('Y-m-d H:i'),
            ];

            if ($plan['category'] !== 'ikan') {
                $plan['gender'] = '';
            }

            upsertRecord($plans, $plan);
            saveData('purchase_plans.json', $plans);
            break;

        case 'delete_plan':
            if (!empty($_POST['id'])) {
                deleteRecord($plans, $_POST['id']);
                saveData('purchase_plans.json', $plans);
            }
            break;

        case 'complete_plan':
            $planId = $_POST['id'] ?? '';
            $plan = $planId ? findRecordById($plans, $planId) : null;

            if ($plan) {
                deleteRecord($plans, $planId);
                saveData('purchase_plans.json', $plans);

                if (in_array($plan['category'], ['ikan', 'tanaman'], true)) {
                    $purchases[] = [
                        'id' => uniqid('pur_'),
                        'category' => $plan['category'],
                        'species' => $plan['name'],
                        'variant' => $plan['variant'],
                        'gender' => $plan['category'] === 'ikan' ? ($plan['gender'] ?? '') : '',
                        'quantity' => (int) $plan['quantity'],
                        'price' => (float) $plan['price'],
                        'notes' => $plan['notes'] ?? '',
                        'recorded_at' => date('Y-m-d H:i'),
                    ];

                    saveData('purchases.json', $purchases);
                } else {
                    $equipment[] = [
                        'id' => uniqid('eq_'),
                        'item_type' => $plan['category'],
                        'brand' => $plan['name'],
                        'quantity' => (float) $plan['quantity'],
                        'price' => (float) $plan['price'],
                        'notes' => $plan['notes'] ?? '',
                        'recorded_at' => date('Y-m-d H:i'),
                    ];

                    saveData('equipment.json', $equipment);
                }
            }
            break;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Metrics
$totalSchedules = count($schedules);
$totalEquipmentItems = count($equipment);
$totalPurchases = count($purchases);

$totalFishPurchased = 0;
foreach ($purchases as $purchase) {
    if ($purchase['category'] === 'ikan') {
        $totalFishPurchased += (int) $purchase['quantity'];
    }
}

$fishAdjustments = 0;
foreach ($maintenance as $record) {
    $multiplier = $record['status'] === 'mati' ? -1 : 1;
    $fishAdjustments += $multiplier * (int) $record['quantity'];
}

$currentFish = max(0, $totalFishPurchased + $fishAdjustments);

$totalExpense = 0;
foreach ($equipment as $item) {
    $totalExpense += (float) $item['price'];
}
foreach ($purchases as $purchase) {
    $totalExpense += (float) $purchase['price'];
}

$totalPlanBudget = 0;
foreach ($plans as $plan) {
    $totalPlanBudget += (float) $plan['price'];
}

$todayName = strtolower(date('l'));
$todayTasks = array_filter($schedules, function ($schedule) use ($todayName) {
    if ($schedule['frequency'] === 'daily') {
        return true;
    }

    if (in_array($schedule['frequency'], ['weekly', 'specific'], true)) {
        return in_array($todayName, $schedule['days'] ?? [], true);
    }

    if ($schedule['frequency'] === 'monthly') {
        return (int) date('j') === (int) ($schedule['day_of_month'] ?? 0);
    }

    if ($schedule['frequency'] === 'interval') {
        $start = $schedule['start_date'] ?? date('Y-m-d');
        $interval = (int) ($schedule['interval_days'] ?? 1);
        $startDate = new DateTime($start);
        $today = new DateTime();
        $diff = $startDate->diff($today);
        return $diff->days % max(1, $interval) === 0;
    }

    return false;
});

usort($todayTasks, fn($a, $b) => strcmp($a['time'], $b['time']));
$upcomingTasks = array_slice($todayTasks, 0, 5);

$recentActivities = [];
foreach ($equipment as $item) {
    $recentActivities[] = [
        'type' => 'Perlengkapan',
        'title' => $item['brand'] ?: ucfirst($item['item_type']),
        'timestamp' => $item['recorded_at'] ?? '',
    ];
}
foreach ($purchases as $item) {
    $recentActivities[] = [
        'type' => ucfirst($item['category']),
        'title' => $item['species'] ?: $item['variant'],
        'timestamp' => $item['recorded_at'] ?? '',
    ];
}
foreach ($maintenance as $item) {
    $recentActivities[] = [
        'type' => 'Pemeliharaan',
        'title' => ucfirst($item['status']) . ' ' . ($item['species'] ?: ''),
        'timestamp' => $item['recorded_at'] ?? '',
    ];
}

usort($recentActivities, function ($a, $b) {
    return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
});
$recentActivities = array_slice($recentActivities, 0, 6);

function formatCurrency(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyAquarium Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="gradient-bg bg-slate-100 text-slate-800 min-h-screen">
<div class="alert-toast hidden" id="alarmToast">
    <div class="toast-body card-glass rounded-xl shadow-xl p-4 text-slate-700">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-600">
                <span class="text-xl">⏰</span>
            </div>
            <div>
                <p class="font-semibold" id="alarmTitle"></p>
                <p class="text-sm text-slate-600" id="alarmDescription"></p>
            </div>
        </div>
    </div>
</div>
<header class="sticky top-0 backdrop-blur border-b border-white/40 z-40 bg-white/60">
    <nav class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-blue-500 text-white flex items-center justify-center font-semibold shadow-lg">MA</div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">MyAquarium</h1>
                <p class="text-sm text-slate-500 -mt-1">Kelola akuarium Anda dengan nyaman</p>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <a href="#dashboard" class="hover:text-blue-600 transition">Dashboard</a>
            <a href="#penjadwalan" class="hover:text-blue-600 transition">Penjadwalan</a>
            <a href="#perlengkapan" class="hover:text-blue-600 transition">Perlengkapan</a>
            <a href="#pembelian" class="hover:text-blue-600 transition">Pembelian</a>
            <a href="#pemeliharaan" class="hover:text-blue-600 transition">Pemeliharaan</a>
            <a href="#rencana" class="hover:text-blue-600 transition">Rencana</a>
            <a href="#keuangan" class="hover:text-blue-600 transition">Keuangan</a>
        </div>
    </nav>
</header>
<main class="max-w-7xl mx-auto px-6 py-12 space-y-16">
    <section id="dashboard" class="section-anchor">
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Total Jadwal 📅</p>
                <h2 class="text-3xl font-bold mt-2"><?= $totalSchedules; ?></h2>
                <p class="text-xs text-slate-500 mt-3">Penjadwalan otomatis dan pengingat pintar</p>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Perlengkapan 📦</p>
                <h2 class="text-3xl font-bold mt-2"><?= $totalEquipmentItems; ?></h2>
                <p class="text-xs text-slate-500 mt-3">Catatan perlengkapan dan kebutuhan</p>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Pembelian 🛒</p>
                <h2 class="text-3xl font-bold mt-2"><?= $totalPurchases; ?></h2>
                <p class="text-xs text-slate-500 mt-3">Riwayat pembelian ikan & tanaman</p>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Total Ikan Saat Ini ❤️</p>
                <h2 class="text-3xl font-bold mt-2"><?= $currentFish; ?></h2>
                <p class="text-xs text-slate-500 mt-3">Menghitung pembelian dan catatan pemeliharaan</p>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Total Pengeluaran Rp 📈</p>
                <h2 class="text-3xl font-bold mt-2"><?= formatCurrency($totalExpense); ?></h2>
                <p class="text-xs text-slate-500 mt-3">Akumulasi perlengkapan & pembelian</p>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <p class="text-sm text-slate-500">Tugas Mendatang ⏲️</p>
                <h2 class="text-3xl font-bold mt-2"><?= count($upcomingTasks); ?></h2>
                <p class="text-xs text-slate-500 mt-3">Jangan lewatkan aktivitas penting</p>
            </div>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 card-glass rounded-3xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold">Tips Perawatan Akuarium</h3>
                    <span class="tag-pill">Checklist Harian</span>
                </div>
                <ul class="space-y-4 text-sm text-slate-600">
                    <li class="flex gap-3"><span class="text-blue-500">•</span> Ganti air 1–2 minggu sekali (25–30%).</li>
                    <li class="flex gap-3"><span class="text-blue-500">•</span> Bersihkan media filter sebulan sekali.</li>
                    <li class="flex gap-3"><span class="text-blue-500">•</span> Berikan pakan 1–2× sehari, secukupnya.</li>
                    <li class="flex gap-3"><span class="text-blue-500">•</span> Perhatikan perilaku ikan untuk deteksi dini.</li>
                </ul>
            </div>
            <div class="card-glass rounded-3xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold">Aktivitas Terbaru</h3>
                    <span class="tag-pill">Terupdate</span>
                </div>
                <ul class="space-y-4 text-sm text-slate-600">
                    <?php if (empty($recentActivities)): ?>
                        <li class="text-slate-400">Belum ada aktivitas.</li>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <li>
                                <p class="font-medium text-slate-700"><?= htmlspecialchars($activity['title'] ?: 'Aktivitas'); ?></p>
                                <p class="text-xs text-slate-500 flex items-center gap-2">
                                    <span class="badge px-2 py-1 rounded-full text-xs font-semibold text-blue-600"><?= htmlspecialchars($activity['type']); ?></span>
                                    <span><?= htmlspecialchars(date('d M Y H:i', strtotime($activity['timestamp']))); ?></span>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php if ($upcomingTasks): ?>
            <div class="card-glass rounded-3xl p-8 shadow-xl mt-6">
                <h3 class="text-lg font-semibold mb-6">Agenda Hari Ini</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($upcomingTasks as $task): ?>
                        <div class="bg-white/70 rounded-2xl px-4 py-3 border border-white/60">
                            <p class="text-sm font-semibold text-slate-700 flex items-center justify-between">
                                <span><?= htmlspecialchars($task['title'] ?: ucfirst($task['type'])); ?></span>
                                <span class="text-blue-600 font-medium"><?= htmlspecialchars($task['time']); ?></span>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">Jenis: <?= htmlspecialchars(ucwords($task['type'])); ?> · Frekuensi: <?= htmlspecialchars(ucwords($task['frequency'])); ?></p>
                            <?php if (!empty($task['notes'])): ?>
                                <p class="text-xs text-slate-400 mt-1">Catatan: <?= htmlspecialchars($task['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section id="penjadwalan" class="section-anchor">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Penjadwalan & Pengingat</h2>
            <div class="relative">
                <input type="search" placeholder="Cari jadwal..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-500" data-filter-target="scheduleTable">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Tambah / Ubah Jadwal</h3>
                <form id="scheduleForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_schedule">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nama Jadwal</label>
                        <input type="text" name="title" required class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipe</label>
                            <select name="type" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pemberian pakan">Pemberian Pakan</option>
                                <option value="ganti air">Ganti Air</option>
                                <option value="cek filter">Cek Filter</option>
                                <option value="maintenance filter">Maintenance Filter</option>
                                <option value="maintenance total">Maintenance Total</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Waktu</label>
                            <input type="time" name="time" required class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Frekuensi</label>
                        <select name="frequency" data-frequency class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                            <option value="interval">Beberapa Hari Sekali</option>
                            <option value="specific">Hari Tertentu</option>
                        </select>
                    </div>
                    <div data-frequency-section="weekly" class="hidden">
                        <label class="block text-sm font-medium mb-2">Pilih Hari</label>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <?php
                            $days = ['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'];
                            foreach ($days as $key => $label): ?>
                                <label class="flex items-center gap-2 bg-white/60 rounded-xl px-3 py-2 border border-white/70">
                                    <input type="checkbox" name="days[]" value="<?= $key; ?>">
                                    <span><?= $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div data-frequency-section="specific" class="hidden">
                        <label class="block text-sm font-medium mb-2">Pilih Hari</label>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <?php foreach ($days as $key => $label): ?>
                                <label class="flex items-center gap-2 bg-white/60 rounded-xl px-3 py-2 border border-white/70">
                                    <input type="checkbox" name="days[]" value="<?= $key; ?>">
                                    <span><?= $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div data-frequency-section="monthly" class="hidden">
                        <label class="block text-sm font-medium mb-1">Tanggal dalam Bulan</label>
                        <input type="number" name="day_of_month" min="1" max="31" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 15">
                    </div>
                    <div data-frequency-section="interval" class="hidden">
                        <label class="block text-sm font-medium mb-1">Setiap ... hari</label>
                        <input type="number" name="interval_days" min="1" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 3">
                        <label class="block text-sm font-medium mt-3 mb-1">Mulai Tanggal</label>
                        <input type="date" name="start_date" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Catatan</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan keterangan penting..."></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" data-submit-label class="px-5 py-2 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">Simpan Jadwal</button>
                        <button type="reset" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-white/70">Bersihkan</button>
                    </div>
                </form>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl overflow-hidden">
                <div class="table-wrapper overflow-y-auto">
                    <table class="w-full text-sm" data-table id="scheduleTable">
                        <thead class="text-left text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="py-3">Nama</th>
                            <th>Jenis</th>
                            <th>Waktu</th>
                            <th>Frekuensi</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-white/60">
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada jadwal.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td class="py-3">
                                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($schedule['title'] ?: ucfirst($schedule['type'])); ?></p>
                                        <?php if (!empty($schedule['notes'])): ?>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($schedule['notes']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(ucwords($schedule['type'])); ?></td>
                                    <td><?= htmlspecialchars($schedule['time']); ?></td>
                                    <td><?= htmlspecialchars(ucwords($schedule['frequency'])); ?></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600" data-edit data-form="scheduleForm" data-record='<?= json_encode($schedule, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>Edit</button>
                                            <form method="post" onsubmit="return confirm('Hapus jadwal ini?');">
                                                <input type="hidden" name="action" value="delete_schedule">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($schedule['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="perlengkapan" class="section-anchor">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Catatan Perlengkapan & Kebutuhan</h2>
            <div class="relative">
                <input type="search" placeholder="Cari perlengkapan..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-500" data-filter-target="equipmentTable">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Tambah / Ubah Perlengkapan</h3>
                <form id="equipmentForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_equipment">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Jenis</label>
                        <select name="item_type" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="filter">Filter</option>
                            <option value="pakan">Pakan</option>
                            <option value="obat">Obat</option>
                            <option value="bakteri baik">Bakteri Baik</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Merek / Nama</label>
                        <input type="text" name="brand" required class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jumlah / Berat</label>
                            <input type="number" step="0.01" name="quantity" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Harga</label>
                            <input type="number" step="0.01" name="price" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" data-submit-label class="px-5 py-2 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">Simpan Perlengkapan</button>
                        <button type="reset" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-white/70">Bersihkan</button>
                    </div>
                </form>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl overflow-hidden">
                <div class="flex items-center justify-between mb-4 text-sm text-slate-500">
                    <span>Total Pengeluaran</span>
                    <span class="font-semibold text-blue-600"><?= formatCurrency($totalExpense); ?></span>
                </div>
                <div class="table-wrapper overflow-y-auto">
                    <table class="w-full text-sm" data-table id="equipmentTable">
                        <thead class="text-left text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="py-3">Merek</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-white/60">
                        <?php if (empty($equipment)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada data perlengkapan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td class="py-3">
                                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($item['brand']); ?></p>
                                        <p class="text-xs text-slate-500">Dicatat: <?= htmlspecialchars($item['recorded_at'] ?? ''); ?></p>
                                    </td>
                                    <td><?= htmlspecialchars(ucwords($item['item_type'])); ?></td>
                                    <td><?= htmlspecialchars($item['quantity']); ?></td>
                                    <td><?= formatCurrency((float) $item['price']); ?></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600" data-edit data-form="equipmentForm" data-record='<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>Edit</button>
                                            <form method="post" onsubmit="return confirm('Hapus data ini?');">
                                                <input type="hidden" name="action" value="delete_equipment">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="pembelian" class="section-anchor">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Catatan Pembelian Ikan & Tanaman</h2>
            <div class="relative">
                <input type="search" placeholder="Cari pembelian..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-500" data-filter-target="purchaseTable">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Tambah / Ubah Pembelian</h3>
                <form id="purchaseForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_purchase">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipe</label>
                        <select name="category" data-toggle-gender class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="ikan">Ikan</option>
                            <option value="tanaman">Tanaman</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Jenis</label>
                        <input type="text" name="species" required class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipe</label>
                        <input type="text" name="variant" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Gender</label>
                        <select name="gender" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-</option>
                            <option value="jantan">Jantan</option>
                            <option value="betina">Betina</option>
                            <option value="campuran">Campuran</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jumlah</label>
                            <input type="number" name="quantity" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Harga</label>
                            <input type="number" step="0.01" name="price" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" data-submit-label class="px-5 py-2 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">Simpan Pembelian</button>
                        <button type="reset" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-white/70">Bersihkan</button>
                    </div>
                </form>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl overflow-hidden">
                <div class="flex items-center justify-between mb-4 text-sm text-slate-500">
                    <span>Total Pengeluaran</span>
                    <span class="font-semibold text-blue-600"><?= formatCurrency($totalExpense); ?></span>
                </div>
                <div class="table-wrapper overflow-y-auto">
                    <table class="w-full text-sm" data-table id="purchaseTable">
                        <thead class="text-left text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="py-3">Jenis</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-white/60">
                        <?php if (empty($purchases)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada pembelian.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td class="py-3">
                                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($purchase['species']); ?></p>
                                        <p class="text-xs text-slate-500">Dicatat: <?= htmlspecialchars($purchase['recorded_at'] ?? ''); ?></p>
                                    </td>
                                    <td><?= htmlspecialchars($purchase['variant']); ?></td>
                                    <td><?= htmlspecialchars($purchase['quantity']); ?></td>
                                    <td><?= formatCurrency((float) $purchase['price']); ?></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600" data-edit data-form="purchaseForm" data-record='<?= json_encode($purchase, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>Edit</button>
                                            <form method="post" onsubmit="return confirm('Hapus data ini?');">
                                                <input type="hidden" name="action" value="delete_purchase">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($purchase['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="pemeliharaan" class="section-anchor">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Catatan Pemeliharaan Ikan</h2>
            <div class="relative">
                <input type="search" placeholder="Cari pemeliharaan..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-500" data-filter-target="maintenanceTable">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Tambah / Ubah Catatan</h3>
                <form id="maintenanceForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_maintenance">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Status Ikan</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="mati">Mati</option>
                            <option value="beranak">Beranak</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jenis</label>
                            <input type="text" name="species" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipe</label>
                            <input type="text" name="variant" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Gender</label>
                        <select name="gender" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-</option>
                            <option value="jantan">Jantan</option>
                            <option value="betina">Betina</option>
                            <option value="campuran">Campuran</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jumlah</label>
                            <input type="number" name="quantity" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Catatan</label>
                            <input type="text" name="notes" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" data-submit-label class="px-5 py-2 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">Simpan Catatan</button>
                        <button type="reset" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-white/70">Bersihkan</button>
                    </div>
                </form>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl overflow-hidden">
                <div class="table-wrapper overflow-y-auto">
                    <table class="w-full text-sm" data-table id="maintenanceTable">
                        <thead class="text-left text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="py-3">Status</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Waktu</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-white/60">
                        <?php if (empty($maintenance)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada catatan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($maintenance as $item): ?>
                                <tr>
                                    <td class="py-3 font-semibold text-slate-700"><?= htmlspecialchars(ucfirst($item['status'])); ?></td>
                                    <td><?= htmlspecialchars($item['species']); ?></td>
                                    <td><?= htmlspecialchars($item['quantity']); ?></td>
                                    <td><?= htmlspecialchars($item['recorded_at'] ?? ''); ?></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600" data-edit data-form="maintenanceForm" data-record='<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>Edit</button>
                                            <form method="post" onsubmit="return confirm('Hapus catatan ini?');">
                                                <input type="hidden" name="action" value="delete_maintenance">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="rencana" class="section-anchor">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Rencana Pembelian</h2>
            <div class="relative">
                <input type="search" placeholder="Cari rencana..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-white/80 focus:outline-none focus:ring-2 focus:ring-blue-500" data-filter-target="planTable">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            </div>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card-glass rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-semibold mb-4">Tambah / Ubah Rencana</h3>
                <form id="planForm" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_plan">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Jenis</label>
                        <select name="category" data-plan-category class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="ikan">Ikan</option>
                            <option value="tanaman">Tanaman</option>
                            <option value="filter">Filter</option>
                            <option value="pakan">Pakan</option>
                            <option value="obat">Obat</option>
                            <option value="bakteri baik">Bakteri Baik</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nama / Merek</label>
                        <input type="text" name="name" required class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipe / Varian</label>
                        <input type="text" name="variant" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div data-plan-gender>
                        <label class="block text-sm font-medium mb-1">Gender</label>
                        <select name="gender" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-</option>
                            <option value="jantan">Jantan</option>
                            <option value="betina">Betina</option>
                            <option value="campuran">Campuran</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jumlah / Berat</label>
                            <input type="number" step="0.01" name="quantity" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Estimasi Harga</label>
                            <input type="number" step="0.01" name="price" min="0" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" data-submit-label class="px-5 py-2 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">Simpan Rencana</button>
                        <button type="reset" class="px-4 py-2 rounded-full border border-slate-200 text-slate-600 hover:bg-white/70">Bersihkan</button>
                    </div>
                </form>
            </div>
            <div class="card-glass rounded-3xl p-6 shadow-xl overflow-hidden">
                <div class="flex items-center justify-between mb-4 text-sm text-slate-500">
                    <span>Total Estimasi</span>
                    <span class="font-semibold text-blue-600"><?= formatCurrency($totalPlanBudget); ?></span>
                </div>
                <div class="table-wrapper overflow-y-auto">
                    <table class="w-full text-sm" data-table id="planTable">
                        <thead class="text-left text-slate-500 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="py-3">Nama</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-white/60">
                        <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada rencana.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td class="py-3">
                                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($plan['name']); ?></p>
                                        <p class="text-xs text-slate-500">Dicatat: <?= htmlspecialchars($plan['recorded_at'] ?? ''); ?></p>
                                    </td>
                                    <td><?= htmlspecialchars(ucwords($plan['category'])); ?></td>
                                    <td><?= htmlspecialchars($plan['quantity']); ?></td>
                                    <td><?= formatCurrency((float) $plan['price']); ?></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600" data-edit data-form="planForm" data-record='<?= json_encode($plan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>Edit</button>
                                            <form method="post" class="inline-flex" onsubmit="return confirm('Hapus rencana ini?');">
                                                <input type="hidden" name="action" value="delete_plan">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($plan['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-500/10 text-rose-600">Hapus</button>
                                            </form>
                                            <form method="post" class="inline-flex" onsubmit="return confirm('Pindahkan ke catatan pembelian/perlengkapan?');">
                                                <input type="hidden" name="action" value="complete_plan">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($plan['id']); ?>">
                                                <button type="submit" class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600">Tandai Terbeli</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="keuangan" class="section-anchor">
        <div class="card-glass rounded-3xl p-8 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold">Ringkasan Keuangan</h2>
                <span class="tag-pill">Realtime</span>
            </div>
            <div class="grid md:grid-cols-3 gap-6 text-sm">
                <div class="bg-white/70 rounded-2xl p-5 border border-white/60">
                    <p class="text-slate-500">Perlengkapan</p>
                    <p class="text-2xl font-semibold text-slate-700"><?= formatCurrency(array_reduce($equipment, fn($carry, $item) => $carry + (float) $item['price'], 0)); ?></p>
                </div>
                <div class="bg-white/70 rounded-2xl p-5 border border-white/60">
                    <p class="text-slate-500">Pembelian Ikan & Tanaman</p>
                    <p class="text-2xl font-semibold text-slate-700"><?= formatCurrency(array_reduce($purchases, fn($carry, $item) => $carry + (float) $item['price'], 0)); ?></p>
                </div>
                <div class="bg-white/70 rounded-2xl p-5 border border-white/60">
                    <p class="text-slate-500">Rencana (Belum Realisasi)</p>
                    <p class="text-2xl font-semibold text-slate-700"><?= formatCurrency($totalPlanBudget); ?></p>
                </div>
            </div>
            <div class="mt-8 grid md:grid-cols-2 gap-6 text-sm">
                <div class="bg-blue-500/10 rounded-2xl p-5 border border-blue-500/20">
                    <p class="text-blue-600 font-semibold">Total Pengeluaran Aktual</p>
                    <p class="text-3xl font-bold text-blue-700 mt-2"><?= formatCurrency($totalExpense); ?></p>
                    <p class="text-xs text-blue-600/80 mt-1">Gabungan data perlengkapan dan pembelian aktual.</p>
                </div>
                <div class="bg-emerald-500/10 rounded-2xl p-5 border border-emerald-500/20">
                    <p class="text-emerald-600 font-semibold">Rencana Anggaran</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-2"><?= formatCurrency($totalPlanBudget); ?></p>
                    <p class="text-xs text-emerald-600/80 mt-1">Belum digabungkan ke pengeluaran aktual.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="max-w-7xl mx-auto px-6 pb-12 text-center text-xs text-slate-500">
    MyAquarium © <?= date('Y'); ?>. Dibuat dengan cinta untuk para aquascaper.
</footer>

<script>
    const scheduleData = <?= json_encode($schedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<script src="assets/app.js"></script>
</body>
</html>
