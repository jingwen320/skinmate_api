<?php
// admin/index.php
session_start();

// 🛡️ Gatekeeper: Bounce back to login if session is empty
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle dynamic logout actions
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <title>SkinMate Admin - Overview</title>
    <!-- Bring in head configuration and menus from shared file -->
    <?php include 'sidebar.php'; ?>
</head>
<body class="bg-surface text-on-surface antialiased">

    <!-- Main Content Canvas -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <section class="mb-10">
            <!-- <h2 class="text-3xl font-extrabold text-on-background tracking-tight">Overview Dashboard</h2> -->
            <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Overview Dashboard</h2>
            <p class="text-on-surface-variant mt-1">System summary and current operational health metrics.</p>
        </section>

        <!-- Bento Grid Metrics -->
        <div class="grid grid-cols-12 gap-6 mb-8">
            <!-- Metric 1: Total App Users -->
            <div class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-primary">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">group</span>
                    </div>
                </div>
                <p class="text-on-surface-variant text-sm mt-4 font-medium">Total Registered Users</p>
                <h3 id="stat-users" class="text-3xl font-extrabold text-on-surface mt-1">...</h3>
            </div>

            <!-- Metric 2: Total Scans -->
            <div class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-secondary">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-full bg-secondary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">auto_awesome_motion</span>
                    </div>
                </div>
                <p class="text-on-surface-variant text-sm mt-4 font-medium">Total Scans Run</p>
                <h3 id="stat-scans" class="text-3xl font-extrabold text-on-surface mt-1">...</h3>
            </div>

            <!-- Metric 3: Health Score -->
            <div class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-tertiary">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-full bg-tertiary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-tertiary" style="font-variation-settings: 'FILL' 1;">favorite</span>
                    </div>
                </div>
                <p class="text-on-surface-variant text-sm mt-4 font-medium">Global Health Avg</p>
                <h3 id="stat-health" class="text-3xl font-extrabold text-on-surface mt-1">...</h3>
            </div>

            <!-- Distribution Visualizing Area -->
            <div class="col-span-12 bg-surface-container-lowest rounded-xl p-8 shadow-sm">
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-on-surface">Condition Metrics Breakdown</h4>
                    <p class="text-xs text-on-surface-variant uppercase tracking-widest mt-1">Model Analysis Averages (%)</p>
                </div>
                <div class="h-64 flex items-end justify-between gap-4 border-b border-stone-100 pb-2">
                    <div class="flex-1 bg-stone-100 rounded-t-lg relative h-full flex flex-col justify-end items-center">
                        <div id="bar-acne" class="w-full bg-primary rounded-t-lg transition-all duration-500 h-[0%]"></div>
                        <span class="absolute bottom-2 text-xs font-bold text-stone-700">Acne</span>
                    </div>
                    <div class="flex-1 bg-stone-100 rounded-t-lg relative h-full flex flex-col justify-end items-center">
                        <div id="bar-spots" class="w-full bg-secondary rounded-t-lg transition-all duration-500 h-[0%]"></div>
                        <span class="absolute bottom-2 text-xs font-bold text-stone-700">Dark Spots</span>
                    </div>
                    <div class="flex-1 bg-stone-100 rounded-t-lg relative h-full flex flex-col justify-end items-center">
                        <div id="bar-pores" class="w-full bg-tertiary rounded-t-lg transition-all duration-500 h-[0%]"></div>
                        <span class="absolute bottom-2 text-xs font-bold text-stone-700">Pores</span>
                    </div>
                    <div class="flex-1 bg-stone-100 rounded-t-lg relative h-full flex flex-col justify-end items-center">
                        <div id="bar-redness" class="w-full bg-orange-400 rounded-t-lg transition-all duration-500 h-[0%]"></div>
                        <span class="absolute bottom-2 text-xs font-bold text-stone-700">Redness</span>
                    </div>
                    <div class="flex-1 bg-stone-100 rounded-t-lg relative h-full flex flex-col justify-end items-center">
                        <div id="bar-wrinkles" class="w-full bg-amber-700 rounded-t-lg transition-all duration-500 h-[0%]"></div>
                        <span class="absolute bottom-2 text-xs font-bold text-stone-700">Wrinkles</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Wire JavaScript integration controller here -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            fetch('get_analytics.php')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('stat-users').innerText = data.summary.total_users;
                        document.getElementById('stat-scans').innerText = data.summary.total_scans;
                        document.getElementById('stat-health').innerText = data.summary.avg_health_score + "%";

                        document.getElementById('bar-acne').style.height = data.conditions.Acne + "%";
                        document.getElementById('bar-spots').style.height = data.conditions['Dark Spots'] + "%";
                        document.getElementById('bar-pores').style.height = data.conditions.Pores + "%";
                        document.getElementById('bar-redness').style.height = data.conditions.Redness + "%";
                        document.getElementById('bar-wrinkles').style.height = data.conditions.Wrinkles + "%";
                    }
                }).catch(e => console.error("Metrics engine error:", e));
        });
    </script>

    <?php include 'footer.php'; ?>

</body>
</html>