<?php
// admin/products.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable strict internal error reporting for MySQLi drivers
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
include '../db.php';

// 🛡️ Security Gatekeeper
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Helper function to extract or parse checkbox arrays safely
function parseConcerns($post_concerns) {
    if (is_array($post_concerns)) {
        return implode(', ', array_map('trim', $post_concerns));
    }
    return null;
}

/**
 * Universal Dynamic Asset Pipeline Upload Handler Engine
 * @param string $brand Selected brand parameter string
 * @param int|string $productId Concrete unique numerical row ID sequence
 */
function handleCustomImageUpload($brand, $productId) {
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Permitted extensions validation matrix
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            
            // Normalize and clean brand strings to construct clean subfolder names
            $cleanBrandFolder = !empty($brand) ? trim($brand) : 'Generic';
            $uploadFileDir = '../images/' . $cleanBrandFolder . '/';
            
            // Enforce unified rule: Name the image using the clean Product ID string
            $newFileName = $productId . '.' . $fileExtension;
            
            // Automatically build target directory structures if missing on the disk environment
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Returns the brand subfolder prefixed route to database (e.g. "Cetaphil/12.jpg")
                return $cleanBrandFolder . '/' . $newFileName;
            }
        }
    }
    return null;
}

// 📥 ACTION: Create New Product 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'create') {
    $name = trim($_POST['name']);
    $brand = isset($_POST['brand']) && !empty(trim($_POST['brand'])) ? trim($_POST['brand']) : 'Generic';
    $skin_suitability = isset($_POST['skin_suitability']) && !empty(trim($_POST['skin_suitability'])) ? trim($_POST['skin_suitability']) : 'All';
    $category = trim($_POST['category']); 
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $sold_quantity = intval($_POST['sold_quantity']);

    // Parse checkboxes array strictly into a flat string for the description column
    $description = isset($_POST['concerns']) ? parseConcerns($_POST['concerns']) : 'General Skincare';

    if (!empty($name) && !empty($category)) {
        // Step 1: Temporarily reserve database slot to get auto-increment entity ID 
        $temp_image = "";
        
        // 8 columns total -> sssssdis format signature mapping
        $stmt = $conn->prepare("INSERT INTO products (name, brand, skin_suitability, description, category, price, stock_quantity, sold_quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdiis", $name, $brand, $skin_suitability, $description, $category, $price, $stock_quantity, $sold_quantity, $temp_image);
        
        if ($stmt->execute()) {
            $newId = $conn->insert_id; // Capture new unique database identity value
            
            // Step 2: Route file pipeline using the newly obtained ID matching the target brand folder
            $finalImageName = handleCustomImageUpload($brand, $newId);
            
            if (!empty($finalImageName)) {
                // Step 3: Flash patch the correct structural image file location back to the record row
                $updateStmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
                $updateStmt->bind_param("si", $finalImageName, $newId);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            $message = "Product successfully created under #RD-" . $newId . " and stored inside " . htmlspecialchars($brand) . " subfolder.";
        } else {
            $error = "System error updating database logs.";
        }
        $stmt->close();
    } else {
        $error = "Please fill out all required form variables.";
    }
}

// 💾 ACTION: Update Existing Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update') {
    $id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    
    $brand = isset($_POST['brand']) && !empty(trim($_POST['brand'])) ? trim($_POST['brand']) : 'Generic';
    $skin_suitability = isset($_POST['skin_suitability']) && !empty(trim($_POST['skin_suitability'])) ? trim($_POST['skin_suitability']) : 'All';
    
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $existing_image = !empty(trim($_POST['existing_image'])) ? trim($_POST['existing_image']) : null;

    // Process file upload dynamically routing to brand folder named after product ID
    $uploaded_image = handleCustomImageUpload($brand, $id);
    $image = (!empty($uploaded_image)) ? $uploaded_image : $existing_image;

    // Parse checkboxes array strictly into a flat string for the description column
    $description = isset($_POST['concerns']) ? parseConcerns($_POST['concerns']) : 'General Skincare';

    if ($id > 0 && !empty($name)) {
        // 9 columns total -> sssssdisi format signature mapping
        $stmt = $conn->prepare("UPDATE products SET name = ?, brand = ?, skin_suitability = ?, description = ?, category = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ?");
        $stmt->bind_param("sssssdisi", $name, $brand, $skin_suitability, $description, $category, $price, $stock_quantity, $image, $id);
        
        if ($stmt->execute()) {
            $message = "Product metrics successfully updated.";
        } else {
            $error = "Failed to update product database entity.";
        }
        $stmt->close();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $filename = "Product_Inventory_Report_" . date('Y-m-d') . ".xls";
    
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF";

    $threshold_query = "SELECT DISTINCT sold_quantity FROM products ORDER BY sold_quantity DESC LIMIT 1 OFFSET 2";
    $threshold_res = $conn->query($threshold_query);
    $top_3_threshold = 0;
    
    if ($threshold_res && $threshold_res->num_rows > 0) {
        $top_3_threshold = (int)$threshold_res->fetch_assoc()['sold_quantity'];
    } else {
        // Fallback: if there are fewer than 3 distinct items, find the absolute highest sold quantity
        $fallback_query = "SELECT MAX(sold_quantity) as max_sold FROM products";
        $fallback_res = $conn->query($fallback_query);
        if ($fallback_res) {
            $top_3_threshold = (int)$fallback_res->fetch_assoc()['max_sold'];
        }
    }

    $export_query = "SELECT id, name, brand, skin_suitability, description, category, price, stock_quantity, sold_quantity, created_at FROM products ORDER BY id ASC";
    $export_result = $conn->query($export_query);

    echo '
    <head>
        <style>
            .bg-danger-light { background-color: #FFC7CE; color: #9C0006; font-weight: bold; } /* Soft red highlight */
            .bg-success-light { background-color: #C6EFCE; color: #006100; font-weight: bold; } /* Soft green highlight */
        </style>
    </head>
    <body>
    ';

    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<thead>';
    echo '  <tr>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Product ID</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Product Name</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Brand</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Skin Suitability</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Target Concerns</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Category</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Price (RM)</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Stock Balance</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Sold Count</th>';
    echo '    <th style="background-color: #91462e; color: white; font-weight: bold;">Date Added</th>';
    echo '  </tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $export_result->fetch_assoc()) {
        $stock = (int)$row['stock_quantity'];
        $sold = (int)$row['sold_quantity'];

        $stock_class = ($stock < 10) ? ' class="bg-danger-light"' : '';
        
        $sold_class = ($sold >= $top_3_threshold && $sold > 0) ? ' class="bg-success-light"' : '';

        echo '<tr>';
        // Force strings to preserve leading hashes safely without string drops
        echo '  <td style="vnd.ms-excel.numberformat:@">#RD-' . $row['id'] . '</td>';
        echo '  <td>' . htmlspecialchars($row['name']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['brand']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['skin_suitability']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['description']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['category']) . '</td>';
        echo '  <td>' . number_format($row['price'], 2) . '</td>';
        echo '  <td' . $stock_class . '>' . $stock . '</td>';
        echo '  <td' . $sold_class . '>' . $sold . '</td>';
        echo '  <td>' . $row['created_at'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';

    exit;
}

// 🔒 INACTIVATION ENGINE
if (isset($_GET['inactivate_id'])) {
    $inactivate_id = intval($_GET['inactivate_id']);
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = 0 WHERE id = ?");
    $stmt->bind_param("i", $inactivate_id);
    if ($stmt->execute()) {
        $message = "Product flagged as inactive. Availability has been set to 0.";
    } else {
        $error = "Unable to adjust inventory availability state.";
    }
    $stmt->close();
}

// Live statistics calculations
$total_products = 0;
if ($count_res = $conn->query("SELECT COUNT(*) as total FROM products")) {
    $total_products = $count_res->fetch_assoc()['total'];
}

$low_stock_count = 0;
if ($low_res = $conn->query("SELECT COUNT(*) as low_total FROM products WHERE stock_quantity > 0 AND stock_quantity < 10")) {
    $low_stock_count = $low_res->fetch_assoc()['low_total'];
}

// $query = "SELECT id, name, brand, skin_suitability, description, category, price, stock_quantity, image, created_at FROM products ORDER BY id DESC";
$query = "SELECT id, name, brand, skin_suitability, description, category, price, stock_quantity, sold_quantity, image, created_at FROM products ORDER BY id ASC";
$products_result = $conn->query($query);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Product Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-fixed": "#fed07f",
                        "background": "#f7f6f3",
                        "surface-bright": "#f7f6f3",
                        "error-dim": "#9f0519",
                        "outline": "#767775",
                        "inverse-primary": "#fe9d7f",
                        "tertiary-dim": "#694a02",
                        "tertiary": "#77560f",
                        "primary-fixed": "#fe9d7f",
                        "secondary": "#7d4d5f",
                        "on-tertiary-fixed": "#4b3400",
                        "surface-variant": "#ddddd9",
                        "primary": "#91462e",
                        "inverse-surface": "#0d0f0d",
                        "primary-dim": "#823a23",
                        "on-tertiary-container": "#634500",
                        "on-surface-variant": "#5b5c5a",
                        "on-error": "#ffefee",
                        "on-primary-container": "#5f200b",
                        "surface-container-high": "#e3e3df",
                        "surface": "#f7f6f3",
                        "primary-container": "#fe9d7f",
                        "on-primary-fixed": "#3e0c00",
                        "on-secondary-fixed-variant": "#714355",
                        "on-background": "#2e2f2d",
                        "on-primary-fixed-variant": "#6a2913",
                        "surface-container-highest": "#ddddd9",
                        "secondary-fixed-dim": "#efb3c8",
                        "on-primary": "#ffefeb",
                        "tertiary-fixed-dim": "#efc373",
                        "error": "#b31b25",
                        "tertiary-container": "#fed07f",
                        "quaternary-container": "#fec1d6",
                        "secondary-fixed": "#fec1d6",
                        "surface-dim": "#d4d5d1",
                        "on-tertiary-fixed-variant": "#6e4f07",
                        "error-container": "#fb5151",
                        "inverse-on-surface": "#9d9d9a",
                        "primary-fixed-dim": "#ee9073",
                        "secondary-container": "#fec1d6",
                        "on-secondary-fixed": "#502738",
                        "surface-tint": "#91462e",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-container": "#663a4b",
                        "on-secondary": "#ffeff2",
                        "on-error-container": "#570008",
                        "outline-variant": "#adadab",
                        "surface-container-low": "#f1f1ee",
                        "on-tertiary": "#fff1de",
                        "secondary-dim": "#6f4253",
                        "on-surface": "#2e2f2d",
                        "surface-container": "#e8e8e5"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Manrope"],
                        "label": ["Manrope"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { font-family: 'Manrope', sans-serif; background-color: #f7f6f3; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="text-on-background bg-background overflow-x-hidden">
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <section>
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Product Inventory</h2>
                    <p class="text-on-surface-variant mt-1">Manage Skincare Catalog & Stock Levels</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="openCreateModal()" class="px-6 py-3 bg-primary text-on-primary text-sm font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/20 transition-all duration-200 hover:opacity-95 hover:shadow-xl hover:shadow-primary/30 active:scale-95">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Add New Product
                    </button>
                    <a href="products.php?action=export_excel" class="px-6 py-3 bg-secondary-container text-on-secondary-container text-sm font-bold rounded-full flex items-center gap-2 shadow-lg shadow-secondary-container/20 transition-all duration-200 hover:opacity-90 hover:shadow-xl hover:shadow-secondary-container/30 no-underline inline-flex items-center active:scale-95">
                        <span class="material-symbols-outlined text-lg">download</span>
                        Export to Excel
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl font-semibold">✨ <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-2xl font-semibold">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-4 gap-6 mb-10">
                <div class="col-span-1 bg-surface-container-lowest p-6 rounded-3xl shadow-sm border border-stone-100">
                    <p class="text-stone-500 text-xs font-bold uppercase tracking-widest mb-1">Total Products</p>
                    <h3 class="text-3xl font-extrabold text-orange-900"><?php echo $total_products; ?></h3>
                </div>
                <div class="col-span-1 bg-surface-container-lowest p-6 rounded-3xl shadow-sm border border-stone-100">
                    <p class="text-stone-500 text-xs font-bold uppercase tracking-widest mb-1">Active Categories</p>
                    <h3 class="text-3xl font-extrabold text-orange-900">5 Types</h3>
                </div>
                <div class="col-span-2 bg-primary-container/10 p-6 rounded-3xl border border-primary-container/20 flex justify-between items-center">
                    <div>
                        <p class="text-primary text-xs font-bold uppercase tracking-widest mb-1">Stock Alert</p>
                        <h3 class="text-2xl font-extrabold text-orange-950"><?php echo $low_stock_count; ?> Items Low Stock</h3>
                    </div>
                </div>
            </div>

            <!-- SkinMate Core Search and Optimization Filtering Matrix Dashboard Interface -->
            <div class="w-full bg-white border border-stone-200 rounded-3xl p-6 mb-6 shadow-sm flex flex-col gap-4">
                
                <!-- Top Row: General Text and Categorization Filtering Matrices -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- 1. Global Descriptive Keyword Matching -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Search Catalog</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3 text-stone-400 text-xl">search</span>
                            <input type="text" id="filter_search_input" oninput="executeDataFilterPipeline()" placeholder="Search description, brand, or SKU..." class="w-full pl-10 pr-4 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                        </div>
                    </div>

                    <!-- 2. Brand Selector Matrix -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Brand Group</label>
                        <select id="filter_brand_select" onchange="executeDataFilterPipeline()" class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                            <option value="ALL">All Brands</option>
                            <!-- PHP script can dynamically generate these values or hardcode your main selections -->
                            <option value="Cetaphil">Cetaphil</option>
                            <option value="Skintific">Skintific</option>
                            <option value="Glad2Glow">Glad2Glow</option>  
                            <option value="Hada Labo">Hada Labo</option>    
                            <option value="Bio-essence">Bio-essence</option>
                        </select>
                    </div>

                    <!-- 3. Target Formulation Category -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Product Category</label>
                        <select id="filter_category_select" onchange="executeDataFilterPipeline()" class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                            <option value="ALL">All Categories</option>
                            <option value="Cleanser">Cleanser</option>
                            <option value="Toner">Toner</option>
                            <option value="Serum">Serum</option>
                            <option value="Moisturizer">Moisturizer</option>
                            <option value="Acne Treatment">Acne Treatment</option>
                        </select>
                    </div>

                    <!-- 4. Clinical Skin Suitability Condition -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Skin Suitability</label>
                        <select id="filter_suitability_select" onchange="executeDataFilterPipeline()" class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                            <option value="ALL">All Skin Types</option>
                            <option value="Normal">Normal</option>
                            <option value="Oily">Oily</option>
                            <option value="Dry">Dry</option>
                            <option value="Combination">Combination</option>
                        </select>
                    </div>

                    <div class="col-span-1 flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Stock Status</label>
                        <select id="filter_status_select" onchange="executeDataFilterPipeline()" class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                            <option value="ALL">Show All Items</option>
                            <option value="LOW_STOCK">Low Stock (< 10)</option>
                            <option value="INACTIVE">Inactivated (0)</option>
                            <option value="IN_STOCK">Healthy Stock (10+)</option>
                        </select>
                    </div>
                </div>

                <!-- Bottom Row: Numerical Metrics Pipelines (Price & Inventory Stock Tracks) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-dashed border-stone-100">
                    <!-- Max Price Ceiling Track -->
                    <div class="flex flex-col gap-1.5 colspan-2">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Maximum Price Budget</label>
                            <span id="price_range_badge" class="text-xs font-extrabold text-primary bg-primary-container px-2 py-0.5 rounded-md">RM 500</span>
                        </div>
                        <input type="range" id="filter_price_range" min="0" max="250" value="250" oninput="updatePriceIndicatorBadge(this.value); executeDataFilterPipeline();" class="w-full h-1.5 bg-stone-100 rounded-lg appearance-none cursor-pointer accent-primary">
                    </div>

                    <!-- Min Stock Inventory Threshold Track -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Minimum Inventory Stock Level</label>
                            <span id="stock_range_badge" class="text-xs font-extrabold text-stone-600 bg-stone-100 px-2 py-0.5 rounded-md">0 units</span>
                        </div>
                        <input type="range" id="filter_stock_range" min="0" max="250" value="0" oninput="updateStockIndicatorBadge(this.value); executeDataFilterPipeline();" class="w-full h-1.5 bg-stone-100 rounded-lg appearance-none cursor-pointer accent-stone-600">
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-[2rem] shadow-sm border border-stone-100 overflow-hidden">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <thead>
                            <tr class="bg-surface-container-low/50">
                                <th onclick="toggleTableSort(0, 'int')" class="cursor-pointer select-none px-8 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Product ID
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(1, 'str')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Product Details
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(2, 'str')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Skin Suitability
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(3, 'str')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Target Concerns
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(4, 'str')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Category
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(5, 'float')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Price (RM)
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(6, 'int')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Stock Status
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th onclick="toggleTableSort(7, 'int')" class="cursor-pointer select-none px-6 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 hover:text-primary transition-colors group/th">
                                    <div class="flex items-center gap-1">
                                        Sold Status
                                        <span class="sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-colors">unfold_more</span>
                                    </div>
                                </th>
                                
                                <th class="px-8 py-5 text-[11px] font-bold uppercase tracking-[0.15em] text-stone-500 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody" class="divide-y divide-stone-100">
                            <?php if ($products_result && $products_result->num_rows > 0): ?>
                                <?php while ($prod = $products_result->fetch_assoc()): 
                                    $is_active = ($prod['stock_quantity'] > 0);

                                    $id_string = 'id-' . $prod['id'] . ' #rd-' . $prod['id'];
                                    
                                    // Build a clean, lowercase text blob for client-side search indexing
                                    $search_blob = strtolower($id_string . ' ' . ($prod['description'] ?? '') . ' ' . ($prod['name'] ?? '') . ' ' . ($prod['brand'] ?? ''));
                                ?>
                                    <tr class="product-data-row-item hover:bg-surface-container-low transition-colors group <?php echo !$is_active ? 'bg-stone-50/60 opacity-60' : ''; ?>"
                                        data-brand="<?php echo htmlspecialchars($prod['brand'] ?? 'Generic'); ?>"
                                        data-suitability="<?php echo htmlspecialchars($prod['skin_suitability'] ?? 'All Skin Types'); ?>"
                                        data-description="<?php echo htmlspecialchars($search_blob); ?>"
                                        data-category="<?php echo htmlspecialchars($prod['category'] ?? ''); ?>"
                                        data-price="<?php echo floatval($prod['price']); ?>"
                                        data-stock="<?php echo intval($prod['stock_quantity']); ?>"
                                        data-sold="<?php echo intval($prod['sold_quantity']); ?>">
                                        
                                        <td class="px-8 py-6 text-sm font-medium text-stone-400">#RD-<?php echo $prod['id']; ?></td>
                                        <td class="px-6 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-14 h-14 rounded-2xl bg-stone-100 flex items-center justify-center overflow-hidden border border-stone-200 shrink-0">
                                                    <?php if (!empty($prod['image'])): ?>
                                                        <img src="../images/<?php echo htmlspecialchars($prod['image']); ?>" class="w-full h-full object-cover" alt="">
                                                    <?php else: ?>
                                                        <span class="material-symbols-outlined text-stone-400">shampoo</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest"><?php echo htmlspecialchars($prod['brand'] ?? 'Generic'); ?></p>
                                                    <p class="font-bold text-on-surface text-sm"><?php echo htmlspecialchars($prod['name']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6">
                                            <span class="bg-tertiary-container text-on-tertiary-container text-[10px] font-extrabold px-3 py-1 rounded-full uppercase">
                                                <?php echo htmlspecialchars($prod['skin_suitability'] ?? 'All'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-6">
                                            <div class="flex flex-col gap-1.5" title="<?php echo htmlspecialchars($prod['description'] ?? ''); ?>">
                                                <?php 
                                                $description = $prod['description'] ?? 'General Skincare';
                                                if (!empty(trim($description))):
                                                    $tags = explode(',', $description);
                                                    foreach ($tags as $tag):
                                                        $clean_tag = trim($tag);
                                                        if ($clean_tag !== ''): 
                                                ?>
                                                            <div class="flex items-center gap-2 text-xs font-medium text-stone-600 whitespace-nowrap">
                                                                <span class="w-1 h-1 rounded-full bg-primary shrink-0"></span>
                                                                <?php echo htmlspecialchars($clean_tag); ?>
                                                            </div>
                                                <?php 
                                                        endif;
                                                    endforeach;
                                                else: 
                                                ?>
                                                    <span class="text-xs text-stone-400 font-medium">General Skincare</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6">
                                            <span class="bg-quaternary-container text-on-tertiary-container text-[10px] font-extrabold px-3 py-1 rounded-full uppercase">
                                                <?php echo htmlspecialchars($prod['category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-6 text-sm font-bold text-on-surface"><?php echo number_format($prod['price'], 2); ?></td>
                                        <td class="px-6 py-6">
                                            <div class="flex items-center gap-2 truncate">
                                                <?php if ($is_active): ?>
                                                    <div class="w-1.5 h-1.5 rounded-full <?php echo $prod['stock_quantity'] < 10 ? 'bg-orange-500' : 'bg-emerald-500'; ?>"></div>
                                                    <span class="text-sm font-medium <?php echo $prod['stock_quantity'] < 10 ? 'text-orange-600 font-bold' : ''; ?>">
                                                        <?php echo $prod['stock_quantity']; ?> In Stock
                                                    </span>
                                                <?php else: ?>
                                                    <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                                    <span class="text-sm font-bold text-red-600">Not Available</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6 text-sm text-on-surface"><?php echo $prod['sold_quantity']; ?> Sold</td>
                                        <td class="px-8 py-6 text-right space-x-1">
                                            <button onclick='captureScrollAndOpenEdit(<?php echo json_encode($prod); ?>)' class="p-2 text-stone-400 hover:text-primary hover:bg-primary-container/20 rounded-lg transition-all" title="Edit Parameters">
                                                <span class="material-symbols-outlined">edit_note</span>
                                            </button>
                                            <?php if ($is_active): ?>
                                                <a href="products.php?inactivate_id=<?php echo $prod['id']; ?>" onclick="captureScrollPosition(); return confirm('Flag this formulation item as inactive / unlisted to customers?');" class="p-2 text-stone-400 hover:text-error hover:bg-error/10 rounded-lg inline-block transition-all" title="Inactivate Product">
                                                    <span class="material-symbols-outlined">block</span>
                                                </a>
                                            <?php else: ?>
                                                <button class="p-2 text-stone-200 cursor-not-allowed" disabled>
                                                    <span class="material-symbols-outlined">block</span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center p-12 text-sm text-stone-400 font-medium">No skin formulation records present.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div id="productModal" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-10 hidden">
            <div class="bg-surface w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden flex h-full max-h-[890px]">
                
                <div class="flex-1 p-12 overflow-y-auto">
                    <div class="mb-8">
                        <h2 id="modalTitle" class="text-2xl font-extrabold text-orange-900">Add New Product</h2>
                        <p id="modalSubtitle" class="text-stone-500 text-sm mt-1">Enter details to create a new SKU in the inventory.</p>
                    </div>
                    
                    <form action="products.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="product_id" id="form_product_id">
                        <input type="hidden" name="action_type" id="form_action_type" value="create">
                        <input type="hidden" name="sold_quantity" id="form_sold_quantity" value="0">
                        <input type="hidden" name="existing_image" id="form_existing_image">

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Product ID</label>
                                <input id="form_display_id" class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm font-mono text-stone-400" readonly type="text" value="#RD-NEW"/>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Brand *</label>
                                <select name="brand" id="form_brand" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all appearance-none">
                                    <option value="Cetaphil">Cetaphil</option>
                                    <option value="Skintific">Skintific</option>
                                    <option value="Glad2Glow">Glad2Glow</option>
                                    <option value="Hada Labo">Hada Labo</option>
                                    <option value="Bio-essence">Bio-essence</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Product Name *</label>
                            <input name="name" id="form_name" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all" placeholder="Product Name" type="text"/>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Skin Suitability *</label>
                            <select name="skin_suitability" id="form_skin_suitability" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all appearance-none">
                                <option value="Normal">Normal</option>
                                <option value="Oily">Oily</option>
                                <option value="Dry">Dry</option>
                                <option value="Combination">Combination</option>
                                <option value="All">All</option>
                            </select> 
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1 block mb-2">Target Skin Concerns</label>
                            <div class="grid grid-cols-3 gap-3 bg-surface-container-low p-4 rounded-2xl border border-stone-200/40">
                                <?php 
                                $concerns_list = ['Acne', 'Dark Spots', 'Pigmentation', 'Pores', 'Redness', 'Wrinkles'];
                                foreach ($concerns_list as $concern): 
                                    $safe_id = strtolower(str_replace(' ', '_', $concern));
                                ?>
                                    <label class="flex items-center gap-3 cursor-pointer p-1 group">
                                        <input type="checkbox" name="concerns[]" value="<?php echo $concern; ?>" id="concern_<?php echo $safe_id; ?>" class="rounded text-primary focus:ring-primary/20 border-stone-300 bg-white w-5 h-5 transition-all">
                                        <span class="text-sm font-medium text-stone-700 group-hover:text-primary transition-colors"><?php echo $concern; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Category *</label>
                                <select name="category" id="form_category" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all appearance-none">
                                    <option value="Cleanser">Cleanser</option>
                                    <option value="Toner">Toner</option>
                                    <option value="Serum">Serum</option>
                                    <option value="Moisturizer">Moisturizer</option>
                                    <option value="Acne Treatment">Acne Treatment</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Base Price (RM) *</label>
                                <input name="price" id="form_price" step="0.01" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all" placeholder="0.00" type="number"/>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Stock Quantity *</label>
                            <input name="stock_quantity" id="form_stock_quantity" required class="w-full bg-surface-container-low border-none rounded-2xl py-3.5 px-5 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all" placeholder="0" type="number"/>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-stone-500 ml-1">Product Media Asset Upload</label>
                            <input name="image_file" id="form_image_file" class="w-full bg-surface-container-low border-none rounded-2xl py-3 px-4 text-sm focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-on-primary hover:file:opacity-90" type="file" accept="image/jpeg,image/png,image/webp,image/avif"/>
                            <p id="form_image_preview_text" class="text-xs text-stone-400 font-medium ml-1 hidden"></p>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button onclick="closeModal()" class="flex-1 bg-surface-container-highest text-on-surface py-4 rounded-full font-bold transition-all hover:bg-stone-200" type="button">Cancel</button>
                            <button id="formSubmitButton" class="flex-1 bg-primary text-on-primary py-4 rounded-full font-bold shadow-lg shadow-primary/20 transition-all hover:brightness-110 active:scale-95" type="submit">Save Product</button>
                        </div>
                    </form>
                </div>

                <!-- <div class="w-80 bg-stone-100/50 p-12 border-l border-stone-200 flex flex-col items-center justify-center text-center">
                    <div class="w-full aspect-square bg-surface-container-lowest rounded-[2rem] border-2 border-dashed border-stone-300 flex flex-col items-center justify-center p-6 cursor-pointer hover:border-primary transition-all">
                        <div class="w-16 h-16 bg-primary-container rounded-full flex items-center justify-center text-primary mb-4">
                            <span class="material-symbols-outlined text-3xl">upload_file</span>
                        </div>
                        <p class="text-sm font-bold text-on-surface">Asset Pipeline</p>
                        <p class="text-[10px] text-stone-400 mt-2 uppercase tracking-tighter">Images write into brand-specific subfolders named after Product IDs</p>
                    </div>
                </div> -->

                <div class="w-80 bg-stone-100/50 p-12 border-l border-stone-200 flex flex-col items-center justify-center text-center">
                    <div class="w-full aspect-square bg-surface-container-lowest rounded-[2rem] border-2 border-dashed border-stone-300 flex flex-col items-center justify-center p-4 overflow-hidden group/preview relative transition-all">
                        
                        <div id="preview_placeholder" class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 bg-primary-container rounded-full flex items-center justify-center text-primary mb-4">
                                <span class="material-symbols-outlined text-3xl">upload_file</span>
                            </div>
                            <p class="text-sm font-bold text-on-surface">Asset Pipeline</p>
                            <p class="text-[10px] text-stone-400 mt-2 uppercase tracking-tight">
                                Images write into brand-specific subfolders named after Product IDs
                            </p>
                        </div>

                        <img id="preview_image_element" src="" class="hidden w-full h-full object-cover rounded-2xl absolute inset-0 transition-opacity duration-300" alt="Product Formulation Preview">
                        
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        const targetModal = document.getElementById('productModal');
        const standardConcerns = ['Acne', 'Dark Spots', 'Pigmentation', 'Pores', 'Redness', 'Wrinkles'];
        
        function clearAllCheckboxes() {
            standardConcerns.forEach(concern => {
                const safeId = 'concern_' + concern.toLowerCase().replace(' ', '_');
                const checkbox = document.getElementById(safeId);
                if (checkbox) checkbox.checked = false;
            });
        }

        function openCreateModal() {
            document.getElementById('form_product_id').value = '';
            document.getElementById('form_action_type').value = 'create';
            document.getElementById('form_existing_image').value = '';
            document.getElementById('form_display_id').value = '#RD-NEW';
            document.getElementById('form_brand').value = 'Cetaphil';
            document.getElementById('form_name').value = '';
            document.getElementById('form_skin_suitability').value = 'All';
            document.getElementById('form_category').value = 'Cleanser';
            document.getElementById('form_price').value = '';
            document.getElementById('form_stock_quantity').value = '';
            document.getElementById('form_sold_quantity').value = '';
            document.getElementById('form_image_file').value = '';
            
            const previewText = document.getElementById('form_image_preview_text');
            previewText.classList.add('hidden');
            previewText.innerText = '';
            
            clearAllCheckboxes();

            document.getElementById('preview_image_element').classList.add('hidden');
            document.getElementById('preview_image_element').src = '';
            document.getElementById('preview_placeholder').classList.remove('hidden');

            document.getElementById('modalTitle').innerText = "Add New Product";
            document.getElementById('modalSubtitle').innerText = "Enter details to create a new SKU in the inventory.";
            document.getElementById('formSubmitButton').innerText = "Save Product";
            
            targetModal.classList.remove('hidden');
        }

        function openEditModal(prod) {
            clearAllCheckboxes();
            document.getElementById('form_image_file').value = '';
            
            document.getElementById('form_product_id').value = prod.id;
            document.getElementById('form_action_type').value = 'update';
            document.getElementById('form_display_id').value = '#RD-' + prod.id;
            document.getElementById('form_brand').value = prod.brand || 'Cetaphil';
            document.getElementById('form_name').value = prod.name;
            document.getElementById('form_skin_suitability').value = prod.skin_suitability || 'All';
            document.getElementById('form_category').value = prod.category;
            document.getElementById('form_price').value = prod.price;
            document.getElementById('form_stock_quantity').value = prod.stock_quantity;
            
            document.getElementById('form_existing_image').value = prod.image || '';
            const previewText = document.getElementById('form_image_preview_text');
            if (prod.image) {
                previewText.innerText = "Current Active Asset File: " + prod.image;
                previewText.classList.remove('hidden');
            } else {
                previewText.classList.add('hidden');
            }

            // Map concerns cleanly from the description column string directly to the checkboxes
            if (prod.description) {
                const activeConcerns = prod.description.split(', ');
                activeConcerns.forEach(concern => {
                    const safeId = 'concern_' + concern.trim().toLowerCase().replace(' ', '_');
                    const checkbox = document.getElementById(safeId);
                    if (checkbox) checkbox.checked = true;
                });
            }

            const imgPreview = document.getElementById('preview_image_element');
            const placeholder = document.getElementById('preview_placeholder');

            if (prod.image) {
                // Construct path stepping back out of admin directory to find root image assets
                imgPreview.src = "../images/" + prod.image;
                imgPreview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                imgPreview.classList.add('hidden');
                imgPreview.src = '';
                placeholder.classList.remove('hidden');
            }

            document.getElementById('modalTitle').innerText = "Modify Product Parameters";
            document.getElementById('modalSubtitle').innerText = "Adjust formula specifications, values, and inventory status flags.";
            document.getElementById('formSubmitButton').innerText = "Apply Updates";

            targetModal.classList.remove('hidden');
        }

        function closeModal() {
            targetModal.classList.add('hidden');
        }

        /**
         * Synchronizes UI layout indicators with real-time numeric slide inputs
         */
        function updatePriceIndicatorBadge(value) {
            document.getElementById('price_range_badge').textContent = 'RM ' + parseFloat(value).toFixed(0);
        }

        function updateStockIndicatorBadge(value) {
            document.getElementById('stock_range_badge').textContent = value + ' units';
        }

        function executeDataFilterPipeline() {
            const searchInput = document.getElementById('filter_search_input').value.toLowerCase().trim();
            // Split by comma or space using Regex
            const keywords = searchInput.split(/[ ,]+/).filter(k => k.length > 0);
            
            // const searchKeyword  = document.getElementById('filter_search_input').value.toLowerCase().trim();
            const selectedBrand  = document.getElementById('filter_brand_select').value;
            const selectCategory = document.getElementById('filter_category_select').value;
            const selectSuit     = document.getElementById('filter_suitability_select').value;
            const maxAllowedPrice = parseFloat(document.getElementById('filter_price_range').value);
            const minRequiredStock = parseInt(document.getElementById('filter_stock_range').value, 10);
            const selectStatus = document.getElementById('filter_status_select').value;

            const dataRows = document.querySelectorAll('.product-data-row-item');

            dataRows.forEach(row => {
                const itemBrand       = row.getAttribute('data-brand');
                const itemCategory    = row.getAttribute('data-category');
                const itemSuitability = row.getAttribute('data-suitability');
                const itemDescription = row.getAttribute('data-description');
                const itemPrice       = parseFloat(row.getAttribute('data-price'));
                const itemStock       = parseInt(row.getAttribute('data-stock'), 10);

                // const matchSearch = searchKeyword === "" || itemDescription.includes(searchKeyword) || itemBrand.toLowerCase().includes(searchKeyword);
                const matchSearch = keywords.length === 0 || keywords.some(k => 
                    itemDescription.toLowerCase().includes(k) || 
                    itemBrand.toLowerCase().includes(k)
                );
                const matchBrand  = selectedBrand === "ALL" || itemBrand === selectedBrand;
                const matchCat    = selectCategory === "ALL" || itemCategory === selectCategory;
                const matchSuit   = selectSuit === "ALL" || itemSuitability.includes(selectSuit) || itemSuitability === "All Skin Types";
                
                const matchPrice  = itemPrice <= maxAllowedPrice;
                const matchStock  = itemStock >= minRequiredStock;

                let matchStatus = true;
                if (selectStatus === "LOW_STOCK") {
                    matchStatus = itemStock > 0 && itemStock < 10;
                } else if (selectStatus === "INACTIVE") {
                    matchStatus = itemStock === 0;
                } else if (selectStatus === "IN_STOCK") {
                    matchStatus = itemStock >= 10;
                }

                if (matchSearch && matchBrand && matchCat && matchSuit && matchPrice && matchStock && matchStatus) {
                    row.style.display = ""; 

                    if (itemStock === 0) {
                        row.style.opacity = "0.6"; 
                    } else {
                        row.style.opacity = "1";   
                    }
                } else {
                    row.style.display = "none"; 
                }
            });
        }

        function captureScrollPosition() {
            localStorage.setItem('inventory_scroll_y', window.scrollY);
        }

        function captureScrollAndOpenEdit(productData) {
            captureScrollPosition();
            openEditModal(productData); 
        }

        window.addEventListener('DOMContentLoaded', () => {
            const savedScrollY = localStorage.getItem('inventory_scroll_y');
            if (savedScrollY !== null) {
                window.scrollTo(0, parseInt(savedScrollY, 10));
                localStorage.removeItem('inventory_scroll_y'); 
            }
        });

        const adminForm = document.querySelector('form'); 
        if (adminForm) {
            adminForm.addEventListener('submit', captureScrollPosition);
        }


        let currentSortColumn = -1;
        let isAscending = true;

        function toggleTableSort(columnIndex, dataType) {
            const tableBody = document.getElementById('inventoryTableBody');
            const rows = Array.from(tableBody.querySelectorAll('.product-data-row-item'));
            
            if (currentSortColumn === columnIndex) {
                isAscending = !isAscending; 
            } else {
                isAscending = true;
                currentSortColumn = columnIndex;
            }

            rows.sort((rowA, rowB) => {
                let cellA = rowA.children[columnIndex];
                let cellB = rowB.children[columnIndex];
                
                let valA, valB;

                if (columnIndex === 5) { // Price
                    valA = parseFloat(rowA.getAttribute('data-price')) || 0;
                    valB = parseFloat(rowB.getAttribute('data-price')) || 0;
                } else if (columnIndex === 6) { // Stock
                    valA = parseInt(rowA.getAttribute('data-stock'), 10) || 0;
                    valB = parseInt(rowB.getAttribute('data-stock'), 10) || 0;
                } else if (columnIndex === 7) { // Sold
                    valA = parseInt(rowA.getAttribute('data-sold'), 10) || 0;
                    valB = parseInt(rowB.getAttribute('data-sold'), 10) || 0;
                } else {
                    valA = cellA.textContent.trim().toLowerCase();
                    valB = cellB.textContent.trim().toLowerCase();
                }

                if (dataType === 'int' && columnIndex === 0) { // Strip text prefixes from product numbers
                    valA = parseInt(valA.replace('#rd-', ''), 10) || 0;
                    valB = parseInt(valB.replace('#rd-', ''), 10) || 0;
                }

                if (valA < valB) return isAscending ? -1 : 1;
                if (valA > valB) return isAscending ? 1 : -1;
                return 0;
            });

            rows.forEach(row => tableBody.appendChild(row));
            updateSortIndicatorIcons(columnIndex);
        }

        function updateSortIndicatorIcons(activeColumnIndex) {
            const icons = document.querySelectorAll('th .sort-icon');
            
            icons.forEach((icon, idx) => {
                if (idx === activeColumnIndex) {
                    icon.innerHTML = isAscending ? 'arrow_upward' : 'arrow_downward';
                    icon.className = "sort-icon material-symbols-outlined text-sm text-primary font-bold transition-all";
                } else {
                    icon.innerHTML = 'unfold_more';
                    icon.className = "sort-icon material-symbols-outlined text-sm text-stone-300 group-hover/th:text-stone-400 transition-all";
                }
            });
        }
    </script>
</body>
</html>