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
    <style>
        @keyframes floatDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-float-down {
            animation: floatDown 0.5s ease-out forwards;
        }

        /* Card Flip Styles */
        .perspective-1000 {
            perspective: 1000px;
        }
        .transform-style-3d {
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
        }
        .rotate-y-180 {
            transform: rotateY(180deg);
        }
        .backface-hidden {
            backface-visibility: hidden;
        }
        .front-card {
            z-index: 2;
        }
        .back-card {
            transform: rotateY(180deg);
        }
    </style>
    <?php include 'sidebar.php'; ?>
</head>
<body class="bg-surface text-on-surface antialiased">

    <!-- Main Content Canvas -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <section class="mb-10">
            <!-- <h2 class="text-3xl font-extrabold text-on-background tracking-tight">Overview Dashboard</h2> -->
            <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Overview Dashboard</h2>
            <p class="text-on-surface-variant mt-1">System Summary</p>
        </section>

        <!-- Bento Grid Metrics -->
        <div class="grid grid-cols-12 gap-6 mb-8">
            <!-- <div class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-primary">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">payments</span>
                    </div>
                    <p class="text-on-surface-variant text-sm font-medium">Total Sales</p>
                </div>
                <h3 id="stat-sales" class="text-3xl font-extrabold text-on-surface mt-1">RM 0.00</h3>
            </div> -->

            <!-- <div class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-3xl p-6 shadow-sm border border-stone-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                    </div>
                    <p class="text-stone-500 text-xs font-bold uppercase tracking-widest">Next Month Forecast</p>
                </div>
                <h3 id="stat-forecast" class="text-3xl font-extrabold text-stone-900 mt-1">RM 0.00</h3>
                <p class="text-xs text-stone-400 mt-2">Predicted based on historical trend</p>
            </div> -->

            <div class="col-span-12 md:col-span-4 perspective-1000 cursor-pointer" onclick="toggleCardFlip(this)">
                <div id="salesCardInner" class="relative w-full h-full transform-style-3d">  
                    <div class="front-card bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-primary relative w-full h-full backface-hidden hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">payments</span>
                                </div>
                                <p class="text-on-surface-variant text-sm font-medium">Total Sales</p>
                            </div>
                            <!-- Visual cue telling user it's clickable -->
                            <span class="text-[10px] font-bold text-primary bg-primary/10 px-2 py-1 rounded-full flex items-center gap-1 animate-pulse">
                                <span class="material-symbols-outlined text-xs">touch_app</span> Flip for Forecast
                            </span>
                        </div>
                        <h3 id="stat-sales" class="text-3xl font-extrabold text-on-surface mt-1">RM 0.00</h3>
                        <!-- <p class="text-[11px] text-stone-400 mt-2">Click card to see next month's projection</p> -->
                    </div>

                    <div class="back-card absolute inset-0 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-primary backface-hidden flex flex-col justify-between hover:shadow-md">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                                    </div>
                                    <!-- <p class="text-stone-500 text-xs font-bold uppercase tracking-widest">Next Month Forecast</p> -->
                                    <p class="text-on-surface-variant text-sm font-medium">Next Month Forecast</p>
                                </div>
                                <span class="text-[10px] font-bold text-stone-400 bg-stone-100 px-2 py-1 rounded-full">
                                    ⟳ Flip back
                                </span>
                            </div>
                            <h3 id="stat-forecast" class="text-3xl font-extrabold text-stone-900 mt-1">RM 0.00</h3>
                        </div>
                        <!-- <p class="text-xs text-stone-400">Predicted based on historical trend</p> -->
                    </div>
                </div>
            </div>

            <a href="users.php" class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-primary hover:shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">group</span>
                    </div>
                    <p class="text-on-surface-variant text-sm font-medium">Total Registered Users</p>
                </div>
                <h3 id="stat-users" class="text-3xl font-extrabold text-on-surface mt-1">0</h3>
            </a>

            <a href="orders.php" class="col-span-12 md:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm border-b-4 border-secondary hover:shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">pending_actions</span>
                    </div>
                    <p class="text-on-surface-variant text-sm font-medium">Pending Orders</p>
                </div>
                <h3 id="stat-pending" class="text-3xl font-extrabold text-on-surface mt-1">0</h3>
            </a>
        </div>
            
        <div class="grid grid-cols-12 gap-6">
            <!-- Distribution Visualizing Area -->
            <div class="col-span-12 lg:col-span-8 bg-surface-container-lowest rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-bold text-on-surface">Revenue Analytics</h4>
                    <!-- <div class="flex items-center gap-2">
                        <button onclick="changeWeek(-1)" class="text-stone-400 hover:text-primary"><</button>
                        <span id="weekLabel" class="text-xs font-bold text-stone-600 uppercase">This Week</span>
                        <button onclick="changeWeek(1)" class="text-stone-400 hover:text-primary disabled:opacity-30" id="nextWeekBtn" disabled>></button>
                    </div> -->
                    <div id="weekSelector" class="flex items-center gap-2">
                        <button onclick="changeWeek(-1)" class="text-stone-400 hover:text-primary"><</button>
                        <span id="weekLabel" class="text-xs font-bold text-stone-600 uppercase">This Week</span>
                        <button onclick="changeWeek(1)" class="text-stone-400 hover:text-primary disabled:opacity-30" id="nextWeekBtn" disabled>></button>
                    </div>
                    <div class="flex gap-1 bg-stone-100 p-1 rounded-lg w-fit">
                        <button id="weekBtn" onclick="updateChart('weekly')" 
                            class="px-4 py-1.5 rounded-md font-bold text-sm transition-all duration-300 bg-white shadow-sm text-primary">
                            Weekly
                        </button>
                        <button id="monthBtn" onclick="updateChart('monthly')" 
                            class="px-4 py-1.5 rounded-md font-bold text-sm transition-all duration-300 text-primary shadow-sm">
                            Monthly
                        </button>
                    </div>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="revenueChart" class="block"></canvas>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4 bg-surface-container-lowest rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-bold text-on-surface">Top 3 Best-Selling</h4>
                    <select id="leaderboard-period" onchange="loadLeaderboard(this.value)" 
                            class="bg-stone-100 text-xs font-bold text-stone-600 rounded-lg px-2 py-1 border-none focus:ring-0 cursor-pointer w-32">
                        <option value="monthly">Last 30 Days</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                <div id="leaderboard-list" class="space-y-4"></div>
            </div>
        </div>
    </main>

    <!-- Wire JavaScript integration controller here -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let myChart;
        let analyticsData;

        let currentWeekOffset = 0;

        document.addEventListener("DOMContentLoaded", () => {
            fetch('backend/get_analytics.php') 
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update metrics
                        document.getElementById('stat-sales').innerText = "RM " + data.metrics.total_sales;
                        document.getElementById('stat-users').innerText = data.metrics.active_users;
                        document.getElementById('stat-pending').innerText = data.metrics.pending_orders;

                        const forecastEl = document.getElementById('stat-forecast');
                        if (forecastEl) {
                            forecastEl.innerText = "RM " + data.metrics.forecast_sales;
                        }

                        // Render Leaderboard
                        // const leaderboardList = document.getElementById('leaderboard-list');
                        // leaderboardList.innerHTML = `
                        //     <div class="flex justify-between text-xs font-bold text-stone-400 uppercase mb-2 px-2">
                        //         <span>Product</span>
                        //         <span>Units Sold</span>
                        //     </div>
                        // `;

                        // data.leaderboard.forEach((item, index) => {
                        //     leaderboardList.innerHTML += `
                        //         <div class="flex items-center justify-between p-2 hover:bg-stone-50 rounded-lg transition">
                        //             <div class="flex items-center gap-3">
                        //                 <span class="w-7 h-7 flex items-center justify-center bg-primary-container text-primary rounded-full text-xs font-bold">
                        //                     ${index + 1}
                        //                 </span>
                        //                 <span class="font-medium text-stone-700 truncate max-w-[150px]">
                        //                     ${item.product_name}
                        //                 </span>
                        //             </div>
                        //             <span class="font-bold text-on-surface">${item.total_sold}</span>
                        //         </div>
                        //     `;
                        // });

                        // Store chart data
                        analyticsData = data.charts; 
                        
                        // Initial render using the correct key 'weekly'
                        updateChart('weekly'); 
                    }
                }).catch(e => console.error("Dashboard error:", e));

            loadLeaderboard('monthly');
        });

        function changeWeek(direction) {
            currentWeekOffset += direction;
            document.getElementById('nextWeekBtn').disabled = (currentWeekOffset === 0);
            
            // Fetch updated data
            fetch(`backend/get_analytics.php?week_offset=${currentWeekOffset}`)
                .then(r => r.json())
                .then(data => {
                    analyticsData.weekly = data.charts.weekly;
                    updateChart('weekly');
                    updateWeekLabel();
                });
        }

        function updateWeekLabel() {
            const label = document.getElementById('weekLabel');
            // if (currentWeekOffset === 0) label.innerText = "This Week";
            // else if (currentWeekOffset === 1) label.innerText = "Last Week";
            // else label.innerText = `${currentWeekOffset} weeks ago`;
            if (currentWeekOffset === 0) {
                label.innerText = "This Week";
            } else {
                // Use Math.abs to remove the negative sign
                const weeks = Math.abs(currentWeekOffset);
                label.innerText = weeks === 1 ? "1 Week Ago" : `${weeks} Weeks Ago`;
            }
        }

        function loadLeaderboard(period = 'monthly') {
            const list = document.getElementById('leaderboard-list');
            
            // 1. Clear existing content so it doesn't duplicate
            list.innerHTML = ''; 

            // 2. Fetch the new data
            fetch(`backend/get_analytics.php?period=${period}`)
                .then(r => r.json())
                .then(data => {
                    // 3. Handle empty case
                    if (!data.leaderboard || data.leaderboard.length === 0) {
                        list.innerHTML = '<p class="text-center text-stone-400 text-sm p-4 italic">No sales recorded for this period.</p>';
                        return;
                    }

                    // 4. Inject the new items
                    data.leaderboard.forEach((item, index) => {
                        const delay = `${index * 0.15}s`;
                        list.innerHTML += `
                            <div class="animate-float-down opacity-0" style="animation-delay: ${delay};">
                                <div class="flex items-center gap-4 p-3 bg-white border border-stone-100 rounded-xl shadow-sm 
                                            transition-all duration-300 ease-out 
                                            hover:scale-[1.02] hover:shadow-lg hover:border-primary hover:cursor-pointer">
                                    
                                    <img src="../images/${item.image}" 
                                        alt="${item.product_name}" 
                                        onerror="this.src='../images/placeholder.jpg'"
                                        class="w-14 h-14 rounded-lg object-cover bg-stone-100 transition-transform duration-300">
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest">${item.brand}</p>
                                        <h5 class="font-bold text-stone-800 text-sm truncate">${item.product_name}</h5>
                                        <div class="flex gap-3 text-[11px] font-medium mt-0.5">
                                            <span class="text-stone-500">${item.total_sold} units sold</span>
                                            <span class="text-rose-500 font-bold italic">${item.refund_count} refunds</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-50 font-bold text-primary text-xs">
                                        #${index + 1}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                })
                .catch(error => {
                    console.error("Error loading leaderboard:", error);
                    list.innerHTML = '<p class="text-center text-red-400 text-sm p-4">Failed to load data.</p>';
                });
        }

        function updateChart(view) {
            // Toggle active button
            const weekBtn = document.getElementById("weekBtn");
            const monthBtn = document.getElementById("monthBtn");
            const weekSelector = document.getElementById("weekSelector");

            const activeClass = ["bg-white", "shadow-sm", "text-on-surface"];
            const inactiveClass = ["text-stone-500", "hover:text-on-surface"];

            [weekBtn, monthBtn].forEach(btn => btn.classList.remove(...activeClass, ...inactiveClass));

            if (view === "weekly") {
                weekSelector.classList.remove("hidden");
                weekBtn.classList.add(...activeClass);
                monthBtn.classList.add(...inactiveClass);
            } else {
                weekSelector.classList.add("hidden");
                monthBtn.classList.add(...activeClass);
                weekBtn.classList.add(...inactiveClass);
            }

            const data = analyticsData[view];
            if (!data) return;

            const rawValues = data.map(item => Number(item.total));
            const displayValues = rawValues.map(v => Math.max(0, v));

            // Format weekday labels
            const parseLocalDate = (dateString) => {
                const [year, month, day] = dateString.split("-").map(Number);
                return new Date(year, month - 1, day);
            };

            const formatDateToDay = (dateString) => {
                return parseLocalDate(dateString).toLocaleDateString("en-US", {
                    weekday: "short",
                    month: "short",
                    day: "numeric"
                });
            };

            // Calculate dynamic Y-axis
            const values = data.map(item => Number(item.total));
            const maxValue = Math.max(...values);

            // Minimum axis height RM100 if no revenue
            const yMax = maxValue === 0
                ? 100
                : Math.ceil(maxValue * 1.2 / 50) * 50;

            // Destroy previous chart
            const ctx = document.getElementById("revenueChart").getContext("2d");

            if (myChart) {
                myChart.destroy();
                // myChart.data.labels = labels;
                // myChart.data.datasets = datasets;
                // myChart.options = options;
                // myChart.update();
                // return;
            }

            myChart = new Chart(ctx, {
                type: view === "weekly" ? "bar" : "line",

                data: {
                    labels:
                        view === "weekly"
                            ? data.map(item => formatDateToDay(item.date))
                            : data.map(item => {
                                const date = new Date(item.month + "-01");
                                return date.toLocaleDateString("en-US", {
                                    month: "short",
                                    year: 'numeric'
                                });
                            }),

                    datasets: [{
                        label: "Revenue (RM)",
                        // data: values,
                        data: displayValues,

                        // borderColor: "#fe9d7f",

                        // borderColor: view === "weekly"
                        //     ? data.map(item => (parseFloat(item.total) === 0 ? "#e5e7eb" : "#fe9d7f"))
                        //     : "#fe9d7f",

                        borderColor: view === "weekly"
                            ? data.map(item => {
                                const val = parseFloat(item.total);
                                if (val < 0) return "#fb7185"; // Loss color
                                if (val === 0) return "#e5e7eb"; // No activity color
                                return "#fe9d7f"; // Revenue color
                            })
                            : "#fe9d7f",
                        borderWidth: 2,

                        minBarLength: 4,

                        // backgroundColor:
                        //     view === "weekly"
                        //         ? "#fe9d7f"
                        //         : "rgba(249,115,22,0.2)",

                        // backgroundColor: view === "weekly" 
                        //     ? data.map(item => (parseFloat(item.total) === 0 ? "#e5e7eb" : "#fe9d7f")) 
                        //     : "rgba(249,115,22,0.2)",

                        backgroundColor: view === "weekly" 
                            ? rawValues.map(v => (v < 0 ? "#fb7185" : (v === 0 ? "#e5e7eb" : "#fe9d7f"))) 
                            : "rgba(249,115,22,0.2)",

                        borderRadius: view === "weekly"
                            ? {
                                topLeft: 12,
                                topRight: 12,
                                bottomLeft: 0,
                                bottomRight: 0
                            }
                            : 0,

                        borderSkipped: false,

                        fill: view === "monthly",

                        tension: view === "monthly" ? 0.4 : 0,

                        pointRadius: view === "monthly" ? 5 : 0,

                        pointHoverRadius: view === "monthly" ? 7 : 0
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: yMax,

                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            },

                            ticks: {
                                callback: function(value) {
                                    return "RM " + Number(value).toLocaleString("en-MY", {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let val = rawValues[context.dataIndex]; 
                                    return val < 0 
                                        ? `Net Loss: RM ${Math.abs(val).toFixed(2)}` 
                                        : `Revenue: RM ${val.toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function toggleCardFlip(element) {
            const innerCard = element.querySelector('#salesCardInner');
            innerCard.classList.toggle('rotate-y-180');
        }
    </script>

    <?php include 'footer.php'; ?>

</body>
</html>