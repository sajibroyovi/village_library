<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Family Information Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="assets/js/app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        // Use a prefix for Tailwind to avoid conflicts with existing dashboard styles
        tailwind.config = {
            prefix: 'tw-',
            important: true,
            theme: {
                extend: {
                    keyframes: {
                        pan: {
                            '0%': { transform: 'scale(1.05) translate(0, 0)' },
                            '100%': { transform: 'scale(1.1) translate(-2%, -2%)' }
                        },
                        'fade-in-up': {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        }
                    },
                    animation: {
                        pan: 'pan 20s ease-in-out infinite alternate',
                        'fade-in-up': 'fade-in-up 1s ease-out forwards'
                    }
                }
            }
        }
    </script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Family Portal</h2>
            <nav class="sidebar-nav">
                <a href="#" id="nav-home" class="nav-item active" onclick="switchMainView('home')">🏠 Village Portfolio</a>
                <a href="#" id="nav-analytics" class="nav-item" onclick="switchMainView('analytics')">📊 Analytics</a>
                <a href="#" id="nav-map" class="nav-item" onclick="switchMainView('map')">🗺️ Village Map</a>
                <a href="#" id="nav-newsfeed" class="nav-item" onclick="switchMainView('newsfeed')">📰 Village Newsfeed</a>
                <a href="#" id="nav-families" class="nav-item" onclick="switchMainView('families')">Families Directory</a>
                <div style="margin: 1rem 0; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <h4 style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem; padding: 0 1rem;">Data Export</h4>
                    <a href="#" class="nav-item" onclick="exportToExcel()">📊 Export to Excel</a>
                    <a href="#" class="nav-item" onclick="printReport()">📄 Print Report (PDF)</a>
                </div>
                <?php if ($role === 'super_admin'): ?>
                <a href="#" id="nav-users" class="nav-item" onclick="switchMainView('users')">Manage Users</a>
                <a href="#" id="nav-approvals" class="nav-item" onclick="switchMainView('approvals')">
                    🔔 Approval Queue
                    <span class="notification-badge" id="pendingBadge" style="display:none;">0</span>
                </a>
                <a href="#" id="nav-bin" class="nav-item" onclick="switchMainView('bin')">📊 Recycle Bin</a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
                    <h1 style="margin:0; font-size: 1.8rem; font-weight: 600;">Welcome, <?php echo htmlspecialchars($username); ?></h1>
                </div>
                <div class="user-profile" style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="search-container" style="flex: 1; max-width: 400px; position: relative;">
                        <input type="text" id="globalSearch" class="form-control" autocomplete="off" placeholder="🔍 Search Family, Name, Mobile, Area..." oninput="handleGlobalSearch(this.value)" style="margin-bottom: 0; padding-left: 2.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                        <div id="searchDropdown" class="search-results-dropdown"></div>
                    </div>
                    
                    <div class="notification-wrapper" onclick="toggleNotifications()">
                        <span class="notif-bell">🔔</span>
                        <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                        <div class="notif-dropdown" id="notif-dropdown">
                            <div class="notif-header">
                                <strong>Notifications</strong>
                                <button class="btn-sm btn-outline" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;" onclick="markNotificationsRead(event)">Clear All</button>
                            </div>
                            <div id="notif-list">
                                <div style="padding: 1rem; text-align: center; color: var(--text-muted);">No new notifications</div>
                            </div>
                        </div>
                    </div>

                    <span class="role-badge"><?php echo htmlspecialchars($role); ?></span>
                    <button class="btn-outline" onclick="logout()">Logout</button>
                </div>
            </header>

            <div id="alertBox"></div>

            <!-- Home Portfolio View -->
            <div id="homePortfolioWrapper">
                <div id="homePortfolioView" class="tw-relative tw-w-full tw-min-h-[calc(100vh-80px)] tw-rounded-2xl tw-overflow-hidden tw-shadow-2xl tw-flex tw-flex-col tw-items-center tw-justify-center tw-mb-8">
                <!-- Background Image & Overlay -->
                <div class="tw-absolute tw-inset-0 tw-z-0">
                    <img src="assets/images/hero_bg.png" class="tw-w-full tw-h-full tw-object-cover tw-animate-pan" alt="Village Hero" />
                    <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-b tw-from-gray-900/80 tw-via-gray-900/50 tw-to-gray-900/90"></div>
                </div>

                <!-- Glassmorphism Content Box -->
                <div class="tw-relative tw-z-10 tw-w-full tw-max-w-4xl tw-p-8 md:tw-p-12 tw-mx-4 tw-bg-white/10 tw-backdrop-blur-md tw-border tw-border-white/20 tw-rounded-3xl tw-text-center tw-shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] tw-animate-fade-in-up">
                    <h2 class="tw-text-4xl md:tw-text-6xl tw-font-black tw-text-white tw-mb-6 tw-tracking-tight tw-drop-shadow-lg" style="font-family: serif;">The Heritage of Shidhlajury</h2>
                    
                    <div class="tw-my-8 tw-relative">
                        <span class="tw-absolute -tw-top-6 -tw-left-4 tw-text-6xl tw-text-white/20 tw-font-serif">"</span>
                        <p class="tw-text-xl md:tw-text-2xl tw-text-indigo-50 tw-font-light tw-italic tw-leading-relaxed tw-drop-shadow-md">A people without the knowledge of their past history, origin and culture is like a tree without roots.</p>
                        <span class="tw-absolute -tw-bottom-8 -tw-right-4 tw-text-6xl tw-text-white/20 tw-font-serif">"</span>
                    </div>

                    <div class="tw-flex tw-justify-center tw-gap-8 tw-mb-10 stats-row">
                        <div class="tw-text-center">
                            <div class="tw-text-4xl tw-font-bold tw-text-indigo-300 tw-drop-shadow" id="statFamiliesCounter">...</div>
                            <div class="tw-text-sm tw-text-indigo-100 tw-uppercase tw-tracking-wider tw-mt-1">Registered Families</div>
                        </div>
                        <div class="tw-text-center">
                            <div class="tw-text-4xl tw-font-bold tw-text-indigo-300 tw-drop-shadow" id="statMembersCounter">...</div>
                            <div class="tw-text-sm tw-text-indigo-100 tw-uppercase tw-tracking-wider tw-mt-1">Total Ancestors</div>
                        </div>
                    </div>

                    <button onclick="switchMainView('families')" class="tw-group tw-relative tw-inline-flex tw-items-center tw-justify-center tw-px-8 tw-py-4 tw-font-bold tw-text-white tw-transition-all tw-duration-300 tw-bg-indigo-600 tw-rounded-full hover:tw-bg-indigo-500 tw-shadow-[0_0_20px_rgba(79,70,229,0.5)] hover:tw-shadow-[0_0_30px_rgba(79,70,229,0.8)] hover:tw-scale-105 active:tw-scale-95 tw-text-lg">
                        <span class="tw-absolute tw-inset-0 tw-w-full tw-h-full -tw-mt-1 tw-rounded-full tw-opacity-30 tw-bg-gradient-to-b tw-from-transparent tw-via-transparent tw-to-black"></span>
                        <span class="tw-relative">Explore The Village Library &rarr;</span>
                    </button>
                </div>
                </div>
            </div>

            <!-- Families Directory Header + Filters Section -->
            <div id="familiesDirectoryHeader" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.6rem; font-weight: 700;">🏘️ Families Directory</h2>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">Browse all registered households in Shidhlajury.</p>
                    </div>
                    <?php if ($role === 'super_admin' || $role === 'admin'): ?>
                    <button class="btn-primary" onclick="openAddFamilyModal()" style="width: auto; padding: 0.65rem 1.4rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                        <span style="font-size: 1.1rem;">+</span> Add New Family
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Filters Section -->
            <div class="filter-container" style="display: none;">
                <div class="filter-group">
                    <label>📍 Area:</label>
                    <select id="filterArea" class="filter-control" onchange="renderFamilies(document.getElementById('globalSearch').value)">
                        <option value="">All Areas</option>
                        <option value="purbo para">Purbo Para</option>
                        <option value="uttor para">Uttor Para</option>
                        <option value="dokhin para">Dokhin Para</option>
                        <option value="roy bari">Roy Bari</option>
                        <option value="boshu para">Boshu Para</option>
                        <option value="babu para">Babu Para</option>
                        <option value="porchim para">Porchim Para</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>💰 Finance:</label>
                    <select id="filterFinance" class="filter-control" onchange="renderFamilies(document.getElementById('globalSearch').value)">
                        <option value="">All Classes</option>
                        <option value="lower class">Lower Class</option>
                        <option value="middle class">Middle Class</option>
                        <option value="uper middle class">Upper Middle Class</option>
                        <option value="rich">Rich</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>🩸 Blood:</label>
                    <select id="filterBlood" class="filter-control" onchange="renderFamilies(document.getElementById('globalSearch').value)">
                        <option value="">All Groups</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <button class="btn-sm btn-outline" onclick="resetFilters()" style="margin-left: auto;">Reset Filters</button>
            </div>

            <!-- Recycle Bin View -->
            <div id="recycleBinView" style="display:none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3>📊 Recycle Bin</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Soft-deleted records can be restored or permanently removed here.</p>
                </div>

                <div class="user-table-container" style="margin-bottom: 2.5rem;">
                    <div style="padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-color); font-weight: 600;">🗑️ Deleted Families</div>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Deleted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="binFamiliesTable"></tbody>
                    </table>
                </div>

                <div class="user-table-container">
                    <div style="padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-color); font-weight: 600;">🗑️ Deleted Members</div>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Family ID</th>
                                <th>Deleted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="binMembersTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics View -->
            <div id="analyticsView" style="display: none;" class="tw-animate-fade-in-up">
                <div class="tw-mb-8">
                    <h2 class="tw-text-3xl tw-font-bold tw-text-indigo-900 tw-mb-2 tw-drop-shadow-sm">Village Demographics & Analytics</h2>
                    <p class="tw-text-indigo-600">Real-time sociological data distribution across Shidhlajury.</p>
                </div>
                
                <!-- KPI Cards -->
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <div class="tw-text-sm tw-text-indigo-400 tw-uppercase tw-tracking-wider tw-font-bold">Total Households</div>
                        <div class="tw-text-3xl tw-font-black tw-text-indigo-900 tw-mt-2" id="kpiHouseholds">-</div>
                    </div>
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <div class="tw-text-sm tw-text-indigo-400 tw-uppercase tw-tracking-wider tw-font-bold">Total Population</div>
                        <div class="tw-text-3xl tw-font-black tw-text-indigo-900 tw-mt-2" id="kpiPopulation">-</div>
                    </div>
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <div class="tw-text-sm tw-text-indigo-400 tw-uppercase tw-tracking-wider tw-font-bold">Avg Family Size</div>
                        <div class="tw-text-3xl tw-font-black tw-text-indigo-900 tw-mt-2" id="kpiAvgSize">-</div>
                    </div>
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <div class="tw-text-sm tw-text-indigo-400 tw-uppercase tw-tracking-wider tw-font-bold">Employed Members</div>
                        <div class="tw-text-3xl tw-font-black tw-text-indigo-900 tw-mt-2" id="kpiEmployed">-</div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-8 tw-mb-10">
                    <!-- Area Chart -->
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <h3 class="tw-text-lg tw-font-bold tw-text-indigo-900 tw-mb-4">Population by Area</h3>
                        <div class="tw-relative tw-h-64">
                            <canvas id="chartArea"></canvas>
                        </div>
                    </div>
                    <!-- Financial Chart -->
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <h3 class="tw-text-lg tw-font-bold tw-text-indigo-900 tw-mb-4">Household Financial Status</h3>
                        <div class="tw-relative tw-h-64 tw-flex tw-items-center tw-justify-center">
                            <canvas id="chartFinance"></canvas>
                        </div>
                    </div>
                    <!-- Blood Group Chart -->
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <h3 class="tw-text-lg tw-font-bold tw-text-indigo-900 tw-mb-4">Blood Group Distribution</h3>
                        <div class="tw-relative tw-h-64 tw-flex tw-items-center tw-justify-center">
                            <canvas id="chartBlood"></canvas>
                        </div>
                    </div>
                    <!-- Education Chart -->
                    <div class="tw-bg-white tw-border tw-border-indigo-100 tw-shadow-sm tw-rounded-xl tw-p-6">
                        <h3 class="tw-text-lg tw-font-bold tw-text-indigo-900 tw-mb-4">Education Levels</h3>
                        <div class="tw-relative tw-h-64">
                            <canvas id="chartEdu"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Village Map View -->
            <div id="mapView" style="display: none;">
                <div class="tw-mb-6 tw-flex tw-items-center tw-justify-between">
                    <div>
                        <h2 class="tw-text-3xl tw-font-bold tw-text-indigo-900 tw-mb-1">🗺️ Interactive Village Map</h2>
                        <p class="tw-text-indigo-500 tw-text-sm">Explore all registered households mapped across Shidhlajury.</p>
                    </div>
                    <div class="tw-flex tw-gap-3">
                        <span class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-indigo-50 tw-text-indigo-700 tw-text-xs tw-font-bold tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-indigo-200">🏠 Household with Location</span>
                        <span class="tw-inline-flex tw-items-center tw-gap-1 tw-bg-amber-50 tw-text-amber-700 tw-text-xs tw-font-bold tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-amber-200">📍 Area Cluster</span>
                    </div>
                </div>
                <div id="villageMap" style="height: 620px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 30px rgba(79,70,229,0.15); border: 1px solid rgba(79,70,229,0.15);"></div>
                <div class="tw-mt-4 tw-text-xs tw-text-indigo-400 tw-text-center">Pins represent registered households. Click any pin to view family details.</div>
            </div>

            <!-- Village Newsfeed View -->
            <div id="newsfeedView" style="display: none;">
                <div class="tw-mb-8">
                    <h2 class="tw-text-3xl tw-font-bold tw-text-indigo-900 tw-mb-1">📰 Village Newsfeed</h2>
                    <p class="tw-text-indigo-500 tw-text-sm">A living timeline of the Shidhlajury family registry.</p>
                </div>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-8">
                    <!-- Timeline Feed -->
                    <div class="tw-col-span-2">
                        <div id="newsfeedTimeline" class="tw-flex tw-flex-col tw-gap-0">
                            <div class="tw-text-indigo-400 tw-text-center tw-py-8">Loading events...</div>
                        </div>
                    </div>
                    <!-- Stats Sidebar -->
                    <div class="tw-flex tw-flex-col tw-gap-4">
                        <div class="tw-bg-gradient-to-br tw-from-indigo-600 tw-to-purple-600 tw-rounded-2xl tw-p-6 tw-text-white tw-shadow-lg">
                            <div class="tw-text-sm tw-uppercase tw-tracking-widest tw-font-bold tw-opacity-80 tw-mb-4">Village at a Glance</div>
                            <div class="tw-flex tw-flex-col tw-gap-3">
                                <div class="tw-flex tw-justify-between tw-items-center"><span class="tw-opacity-80">Total Families</span><span class="tw-font-black tw-text-xl" id="nf-total-fam">-</span></div>
                                <div class="tw-border-t tw-border-white/20"></div>
                                <div class="tw-flex tw-justify-between tw-items-center"><span class="tw-opacity-80">Total People</span><span class="tw-font-black tw-text-xl" id="nf-total-mem">-</span></div>
                                <div class="tw-border-t tw-border-white/20"></div>
                                <div class="tw-flex tw-justify-between tw-items-center"><span class="tw-opacity-80">Newest Household</span><span class="tw-font-black tw-text-sm tw-text-right tw-max-w-[120px]" id="nf-newest">-</span></div>
                            </div>
                        </div>
                        <div class="tw-bg-white tw-border tw-border-indigo-100 tw-rounded-2xl tw-p-5 tw-shadow-sm">
                            <div class="tw-text-xs tw-font-bold tw-text-indigo-400 tw-uppercase tw-tracking-widest tw-mb-4">Areas in Registry</div>
                            <div id="nf-areas" class="tw-flex tw-flex-col tw-gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management View (Hidden by default) -->
            <div id="userManagementView" style="display: none;">
                <div class="user-management-actions">
                    <button class="btn-primary" onclick="openAddUserModal()" style="width: auto; padding: 0.6rem 1.5rem;">+ Add New User</button>
                </div>
                <div class="user-table-container">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <!-- Users will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approval Queue View (super_admin only, hidden by default) -->
            <?php if ($role === 'super_admin'): ?>
            <div id="approvalQueueView" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="margin:0;">🔔 Pending Approvals</h2>
                    <button class="btn-outline" onclick="loadPendingActions()" style="padding: 0.5rem 1rem;">↻ Refresh</button>
                </div>
                <div class="user-table-container">
                    <table class="user-table" id="approvalTable">
                        <thead>
                            <tr>
                                <th>Submitted By</th>
                                <th>Action</th>
                                <th>Target ID</th>
                                <th>Date</th>
                                <th style="min-width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approvalTableBody">
                            <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="data-grid" id="familiesGrid" style="display: none;">
                <!-- Families will be populated here -->
                <div style="color:var(--text-muted);">Loading families...</div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div class="modal-backdrop" id="addFamilyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Family</h3>
                <button class="close-btn" onclick="closeModal('addFamilyModal')">&times;</button>
            </div>
            <form id="addFamilyForm" style="max-height: 70vh; overflow-y: auto; padding-right: 1rem;">
                <input type="hidden" id="family_action" value="add_family">
                <input type="hidden" id="family_edit_id" value="">
                <input type="hidden" id="family_pending_id">
                <div class="form-group">
                    <label>House Owner Name</label>
                    <input type="text" id="owner_name" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Owner's Father Name</label>
                        <input type="text" id="owner_father_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Owner's Mother Name</label>
                        <input type="text" id="owner_mother_name" class="form-control">
                    </div>
                </div>

                <div style="background: rgba(129, 140, 248, 0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px dashed var(--primary); margin-top: 0.5rem;">
                    <label style="color: var(--primary); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; display: block; margin-bottom: 0.8rem;">🌳 Family Lineage (Household Origin)</label>
                    <div class="form-group">
                        <label style="font-size: 0.85rem;">Origin Family (Which household did they come from?)</label>
                        <select id="parent_family_id" class="form-control">
                            <option value="">🏠 No Parent Family (New Root Household)</option>
                        </select>
                    </div>
                    <div class="form-group" id="origin_member_group" style="display: none;">
                        <label style="font-size: 0.85rem;">Specifically, which member was this owner?</label>
                        <select id="origin_member_id" class="form-control">
                            <option value="">Select Member</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>House No</label>
                    <input type="text" id="house_no" class="form-control">
                </div>
                <div class="form-group">
                    <label>Area of House</label>
                    <select id="area" class="form-control" required>
                        <option value="purbo para">Purbo Para</option>
                        <option value="uttor para">Uttor Para</option>
                        <option value="dokhin para">Dokhin Para</option>
                        <option value="roy bari">Roy Bari</option>
                        <option value="boshu para">Boshu Para</option>
                        <option value="babu para">Babu Para</option>
                        <option value="porchim para">Porchim Para</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_area_group" style="display: none;">
                    <label>Specify Other Area</label>
                    <input type="text" id="other_area_name" class="form-control" placeholder="Type area name">
                </div>
                <div class="form-group">
                    <label>House Owner Mobile</label>
                    <input type="text" id="owner_mobile" class="form-control">
                </div>
                <div class="form-group">
                    <label>Type of House</label>
                    <select id="type_of_house" class="form-control" required>
                        <option value="building">Building</option>
                        <option value="semi building">Semi Building</option>
                        <option value="teen sheed">Teen Sheed</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_house_type_group" style="display: none;">
                    <label>Specify House Type</label>
                    <input type="text" id="other_house_type" class="form-control">
                </div>
                <div class="form-group">
                    <label>Financial Condition</label>
                    <select id="financial_condition" class="form-control" required>
                        <option value="lower class">Lower Class</option>
                        <option value="middle class">Middle Class</option>
                        <option value="uper middle class">Upper Middle Class</option>
                        <option value="rich">Rich</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_finance_group" style="display: none;">
                    <label>Specify Financial Condition</label>
                    <input type="text" id="other_finance" class="form-control">
                </div>
                <div class="form-group">
                    <label>Google Map Location</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="google_map_location" class="form-control" placeholder="Link or coordinates" style="flex: 1;">
                        <button type="button" class="btn-outline" onclick="detectLocation()" style="white-space: nowrap; font-size: 0.8rem; padding: 0.3rem 0.6rem;">📍 Detect Location</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Land Amount</label>
                    <input type="text" id="land" class="form-control" placeholder="e.g. 5 decimals">
                </div>
                <div class="form-group">
                    <label>Members of House (Summary)</label>
                    <input type="text" id="members_of_house" class="form-control" placeholder="e.g. 5 members">
                </div>
                <div class="form-group">
                    <label>Temple Details</label>
                    <textarea id="temple_details" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Photo</label>
                    <input type="file" id="family_photo" class="form-control" accept="image/*">
                    <div id="family_photo_preview" style="margin-top: 0.5rem; display: none;">
                        <img src="" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Save Family</button>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="addMemberModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Family Member</h3>
                <button class="close-btn" onclick="closeModal('addMemberModal')">&times;</button>
            </div>
            <form id="addMemberForm" style="max-height: 70vh; overflow-y: auto; padding-right: 1rem;">
                <input type="hidden" id="member_action" value="add_member">
                <input type="hidden" id="member_edit_id" value="">
                <input type="hidden" id="member_pending_id">
                <input type="hidden" id="member_family_id">
                <div class="form-group">
                    <label>Member Name</label>
                    <input type="text" id="member_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nick Name</label>
                    <input type="text" id="nick_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select id="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Relation to Owner</label>
                    <select id="member_relation" class="form-control" required>
                        <option value="Self (Owner)">Self (Owner)</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Child">Child</option>
                        <option value="Parent">Parent</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Niece">Niece</option>
                        <option value="Grand Child">Grand Child</option>
                        <option value="Son-in-law">Son-in-law</option>
                        <option value="Daughter-in-law">Daughter-in-law</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group" id="other_relation_group" style="display: none;">
                    <label>Specify Other Relation</label>
                    <input type="text" id="other_relation" class="form-control">
                </div>
                <div class="form-group" id="parent_member_group" style="display: none;">
                    <label>Father's / Parent's Name (Registered Member)</label>
                    <select id="parent_member_id" class="form-control">
                        <option value="">Select Parent</option>
                    </select>
                </div>
                <div class="form-group" id="sibling_member_group" style="display: none;">
                    <label>Select Sibling (Registered Member)</label>
                    <select id="sibling_member_id" class="form-control">
                        <option value="">Select Sibling</option>
                    </select>
                </div>
                <div class="form-group" id="spouse_member_group" style="display: none;">
                    <label>Select Spouse (Registered Member)</label>
                    <select id="spouse_member_id" class="form-control">
                        <option value="">Select Spouse</option>
                    </select>
                </div>
                <div class="form-group" id="child_type_group" style="display: none;">
                    <label>Child Type</label>
                    <select id="child_type" class="form-control">
                        <option value="">Select Type / Position</option>
                        <option value="1st Child">1st Child (Elder)</option>
                        <option value="2nd Child">2nd Child</option>
                        <option value="3rd Child">3rd Child</option>
                        <option value="4th Child">4th Child</option>
                        <option value="5th Child">5th Child</option>
                        <option value="Younger">Youngest Child</option>
                        <option value="Middle">Middle Child</option>
                        <option value="Only Child">Only Child</option>
                        <option value="Other">Other Position</option>
                    </select>
                </div>
                <div class="form-group" id="other_child_type_group" style="display: none;">
                    <label>Specify Position</label>
                    <input type="text" id="other_child_type" class="form-control" placeholder="e.g. 6th Child">
                </div>
                <div class="form-group" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label>Date type</label>
                        <select id="dob_dod_type" class="form-control" required>
                            <option value="DOB">Birth (DOB)</option>
                            <option value="DOD">Death (DOD)</option>
                        </select>
                    </div>
                    <div style="flex: 2;">
                        <label>Date Value</label>
                        <input type="text" id="dob_dod" class="form-control" placeholder="e.g. 1990">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" id="member_mobile" class="form-control">
                </div>
                <div class="form-group">
                    <label>Blood Group</label>
                    <select id="blood_group" class="form-control">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_blood_group" style="display: none;">
                    <label>Specify Blood Group</label>
                    <input type="text" id="other_blood" class="form-control">
                </div>
                <div class="form-group">
                    <label>Education (BD Standard)</label>
                    <select id="education" class="form-control">
                        <option value="">Select Qualification</option>
                        <option value="PSC">PSC (Primary)</option>
                        <option value="JSC">JSC (Junior)</option>
                        <option value="SSC">SSC (Secondary)</option>
                        <option value="HSC">HSC (Higher Secondary)</option>
                        <option value="Diploma">Diploma</option>
                        <option value="Honours">Honours / Bachelor</option>
                        <option value="Masters">Masters</option>
                        <option value="Doctorate">Doctorate (PhD)</option>
                        <option value="MBBS">MBBS / Medical</option>
                        <option value="Vocational">Vocational</option>
                        <option value="Primary">Below PSC</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_edu_group" style="display: none;">
                    <label>Specify Education</label>
                    <input type="text" id="other_edu" class="form-control">
                </div>
                <div class="form-group">
                    <label>Job Status</label>
                    <select id="member_job" class="form-control">
                        <option value="">Select Status</option>
                        <option value="Student">Student</option>
                        <option value="Service (Govt)">Service (Govt)</option>
                        <option value="Service (Private)">Service (Private)</option>
                        <option value="Business">Business</option>
                        <option value="Housewife">Housewife</option>
                        <option value="Freelancer">Freelancer</option>
                        <option value="Farmer">Farmer</option>
                        <option value="Unemployed">Unemployed</option>
                        <option value="Retired">Retired</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_job_group" style="display: none;">
                    <label>Specify Job Status</label>
                    <input type="text" id="other_job" class="form-control">
                </div>
                <div class="form-group">
                    <label>Job Details</label>
                    <textarea id="job_details" class="form-control" rows="2" placeholder="e.g. Software Engineer at Google"></textarea>
                </div>
                <div class="form-group">
                    <label>Marital Status</label>
                    <select id="member_marital" class="form-control">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="other_marital_group" style="display: none;">
                    <label>Specify Marital Status</label>
                    <input type="text" id="other_marital" class="form-control">
                </div>
                <div id="dynamic_marriage_fields" style="display: none; padding-left: 1rem; border-left: 2px solid var(--primary); margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Spouse Name</label>
                        <input type="text" id="spouse_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Date of Marriage</label>
                        <input type="text" id="date_of_marriage" class="form-control" placeholder="e.g. 2015">
                    </div>
                    <div class="form-group">
                        <label>In-law's Village</label>
                        <input type="text" id="in_laws_village" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>In-law's Father Name</label>
                        <input type="text" id="in_laws_father_name" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Others / Observations</label>
                    <textarea id="others" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Member Photo</label>
                    <input type="file" id="member_photo" class="form-control" accept="image/*">
                    <div id="member_photo_preview" style="margin-top: 0.5rem; display: none;">
                        <img src="" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Save Member</button>
            </form>
        </div>
    </div>

    <!-- View Members Modal -->
    <div class="modal-backdrop" id="viewMembersModal">
        <div class="modal-content" style="max-width: 900px; width: 95%;">
            <div class="modal-header" style="align-items: center;">
                <h3 id="viewMembersTitle" style="margin: 0;">Family Members</h3>
                <div style="margin-left: 2rem; display: flex; background: var(--input-bg); border-radius: 20px; padding: 0.3rem;">
                    <button id="btnListView" class="btn-sm btn-primary" onclick="switchMemberView('list')" style="border-radius: 18px; padding: 0.3rem 1rem;">List View</button>
                    <button id="btnTreeView" class="btn-sm" onclick="switchMemberView('tree')" style="background:transparent; border-radius: 18px; padding: 0.3rem 1rem; color: var(--text-muted);">Hierarchy Tree</button>
                </div>
                <button onclick="exportToPDF()" class="btn-sm" style="margin-left: 1rem; background: #ef4444; color: white; border-radius: 18px; padding: 0.3rem 1rem; display: flex; align-items: center; gap: 0.5rem; border:none; cursor:pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export PDF
                </button>
                <button class="close-btn" onclick="closeModal('viewMembersModal')">&times;</button>
            </div>
            <div id="viewMembersContent" style="max-height: 75vh; overflow-y: auto; padding: 1rem;">
                <!-- Detailed member info will be injected here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="addUserModal" class="modal-backdrop">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form id="addUserForm">
                <input type="hidden" id="user_action" value="add_user">
                <input type="hidden" id="user_edit_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="user_username" class="form-control" required>
                </div>
                <div class="form-group" id="pass_group">
                    <label id="pass_label">Password</label>
                    <input type="password" id="user_password" class="form-control">
                    <small id="pass_hint" style="color: var(--text-muted); display: none;">Leave blank to keep current password</small>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="user_role" class="form-control">
                        <option value="user">User (View Only)</option>
                        <option value="admin">Admin (Add/Edit Records)</option>
                        <option value="super_admin">Super Admin (All Access)</option>
                    </select>
                </div>
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detailed Pending Review Modal (Super Admin) -->
    <div class="modal-backdrop" id="pendingDetailsModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>🔍 Review Submission Details</h3>
                <button class="close-btn" onclick="closeModal('pendingDetailsModal')">&times;</button>
            </div>
            <div id="pendingDetailsContent" class="detail-modal-body">
                <!-- Details will be injected here -->
            </div>
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button id="detailApproveBtn" class="btn-primary" style="flex: 1;">✅ Approve Request</button>
                <button id="detailRejectBtn" class="btn-outline" style="flex: 1; border-color: var(--secondary); color: var(--secondary);">❌ Reject Request</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const userRole = '<?php echo $role; ?>';
        let globalFamiliesData = [];
        let globalPendingData = []; // To store current user's pending actions
        let currentViewFamilyId = null;
        let currentMemberView = 'list';

        async function logout() {
            localStorage.removeItem('shidhlajury_last_view');
            await authCall('logout');
            window.location.href = 'index.php';
        }

        function openAddFamilyModal() {
            document.getElementById('family_action').value = 'add_family';
            document.getElementById('addFamilyForm').reset();
            document.getElementById('other_area_group').style.display = 'none';
            document.getElementById('other_house_type_group').style.display = 'none';
            document.getElementById('other_finance_group').style.display = 'none';
            document.getElementById('origin_member_group').style.display = 'none';
            populateParentFamilies();
            document.querySelector('#addFamilyModal h3').textContent = 'Add New Family';
            document.getElementById('addFamilyModal').classList.add('active');
        }

        function populateParentFamilies(selectedId = '', excludeId = null) {
            const select = document.getElementById('parent_family_id');
            if (!select) return;
            let html = '<option value="">🏠 No Parent Family (New Root Household)</option>';
            
            // Live Families
            globalFamiliesData.forEach(f => {
                if (f.id != excludeId) {
                    html += `<option value="${f.id}" ${f.id == selectedId ? 'selected' : ''}>${f.house_owner_name} (${f.area})</option>`;
                }
            });

            // Pending Families (Current Admin's additions)
            if (userRole === 'admin' && globalPendingData) {
                globalPendingData.forEach(p => {
                    if (p.action_type === 'add_family' && p.status === 'pending') {
                        const payload = JSON.parse(p.payload);
                        const id = `pending_${p.id}`;
                        html += `<option value="${id}" ${id == selectedId ? 'selected' : ''} style="font-style: italic; color: #818cf8;">${payload.owner_name} 🕒 (Pending Approval)</option>`;
                    }
                });
            }
            select.innerHTML = html;
        }

        function populateOriginMembers(familyId, selectedMemberId = '') {
            const group = document.getElementById('origin_member_group');
            const select = document.getElementById('origin_member_id');
            if (!familyId) { group.style.display = 'none'; return; }
            
            let html = '<option value="">Select Member</option>';
            let found = false;

            // Case 1: Existing Live Family
            const liveFamily = globalFamiliesData.find(f => f.id == familyId);
            if (liveFamily) {
                found = true;
                if (liveFamily.members) {
                    liveFamily.members.forEach(m => {
                        html += `<option value="${m.id}" ${m.id == selectedMemberId ? 'selected' : ''}>${m.name} (${m.relation_to_owner})</option>`;
                    });
                }
            }

            // Case 2: Pending Family (Admin's addition)
            if (!liveFamily && familyId.toString().startsWith('pending_')) {
                const actionId = familyId.toString().replace('pending_', '');
                const action = globalPendingData.find(a => a.id == actionId);
                if (action) {
                    found = true;
                    const payload = JSON.parse(action.payload);
                    const ownerId = `pending_owner_${action.id}`;
                    html += `<option value="${ownerId}" ${ownerId == selectedMemberId ? 'selected' : ''} style="font-style: italic;">${payload.owner_name} (🕒 Initial Owner)</option>`;
                }
            }

            // Case 3: Also check for pending ADD_MEMBER actions for this family
            if (userRole === 'admin' && globalPendingData) {
                globalPendingData.forEach(p => {
                    if (p.action_type === 'add_member' && p.status === 'pending') {
                        const payload = JSON.parse(p.payload);
                        // Convert both to string for robust comparison (handles 'pending_' prefix)
                        if (payload.family_id.toString() === familyId.toString()) {
                            found = true;
                            const mid = `pending_${p.id}`;
                            html += `<option value="${mid}" ${mid == selectedMemberId ? 'selected' : ''} style="font-size:0.8rem; color:#818cf8;">${payload.name} 🕒 (Pending Member)</option>`;
                        }
                    }
                });
            }

            if (found) {
                select.innerHTML = html;
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        }

        // Universal Toggle Helper
        function toggleOther(selectId, groupId, otherInputId) {
            const select = document.getElementById(selectId);
            const group = document.getElementById(groupId);
            const val = select.value.toLowerCase();
            if(val === 'others' || val === 'other') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                if(otherInputId) document.getElementById(otherInputId).value = '';
            }
        }

        document.getElementById('area').addEventListener('change', () => toggleOther('area', 'other_area_group', 'other_area_name'));
        document.getElementById('type_of_house').addEventListener('change', () => toggleOther('type_of_house', 'other_house_type_group', 'other_house_type'));
        document.getElementById('financial_condition').addEventListener('change', () => toggleOther('financial_condition', 'other_finance_group', 'other_finance'));
        document.getElementById('parent_family_id').addEventListener('change', (e) => populateOriginMembers(e.target.value));
        
        document.getElementById('blood_group').addEventListener('change', () => toggleOther('blood_group', 'other_blood_group', 'other_blood'));
        document.getElementById('education').addEventListener('change', () => toggleOther('education', 'other_edu_group', 'other_edu'));
        document.getElementById('member_job').addEventListener('change', () => toggleOther('member_job', 'other_job_group', 'other_job'));
        document.getElementById('member_marital').addEventListener('change', () => toggleOther('member_marital', 'other_marital_group', 'other_marital'));
        document.getElementById('member_relation').addEventListener('change', () => toggleOther('member_relation', 'other_relation_group', 'other_relation'));
        document.getElementById('child_type').addEventListener('change', () => toggleOther('child_type', 'other_child_type_group', 'other_child_type'));

        function openEditFamilyModal(id) {
            let family;
            let pendingId = null;
            
            if (id.toString().startsWith('pending_')) {
                pendingId = id.toString().replace('pending_', '');
                const action = globalPendingData.find(a => a.id == pendingId);
                if (!action) return;
                const payload = JSON.parse(action.payload);
                family = {
                    house_owner_name: payload.owner_name,
                    owner_father_name: payload.owner_father_name,
                    owner_mother_name: payload.owner_mother_name,
                    owner_mobile: payload.owner_mobile,
                    house_no: payload.house_no,
                    google_map_location: payload.google_map_location,
                    area: payload.area,
                    type_of_house: payload.type_of_house,
                    financial_condition: payload.financial_condition,
                    land: payload.land,
                    members_of_house: payload.members_of_house,
                    temple_details: payload.temple_details,
                    parent_family_id: payload.parent_family_id,
                    origin_member_id: payload.origin_member_id
                };
            } else {
                family = globalFamiliesData.find(f => f.id == id);
            }

            if(!family) return;
            
            document.getElementById('family_action').value = pendingId ? 'update_pending_action' : 'edit_family';
            document.getElementById('family_edit_id').value = pendingId ? '' : id;
            document.getElementById('family_pending_id').value = pendingId || '';
            document.querySelector('#addFamilyModal h3').textContent = pendingId ? '✏️ Edit Pending Request' : 'Edit Family';
            
            document.getElementById('owner_name').value = family.house_owner_name || '';
            document.getElementById('owner_father_name').value = family.owner_father_name || '';
            document.getElementById('owner_mother_name').value = family.owner_mother_name || '';
            document.getElementById('owner_mobile').value = family.owner_mobile || '';
            document.getElementById('house_no').value = family.house_no || '';
            document.getElementById('google_map_location').value = family.google_map_location || '';

            
            const standardAreas = ['purbo para', 'uttor para', 'dokhin para', 'roy bari', 'boshu para', 'babu para', 'porchim para'];
            if (standardAreas.includes(family.area)) {
                document.getElementById('area').value = family.area;
                document.getElementById('other_area_group').style.display = 'none';
            } else {
                document.getElementById('area').value = 'others';
                document.getElementById('other_area_name').value = family.area || '';
                document.getElementById('other_area_group').style.display = 'block';
            }

            const standardTypes = ['building', 'semi building', 'teen sheed'];
            if (standardTypes.includes(family.type_of_house)) {
                document.getElementById('type_of_house').value = family.type_of_house;
                document.getElementById('other_house_type_group').style.display = 'none';
            } else {
                document.getElementById('type_of_house').value = 'others';
                document.getElementById('other_house_type').value = family.type_of_house || '';
                document.getElementById('other_house_type_group').style.display = 'block';
            }

            const standardFinance = ['lower class', 'middle class', 'uper middle class', 'rich'];
            if (standardFinance.includes(family.financial_condition)) {
                document.getElementById('financial_condition').value = family.financial_condition;
                document.getElementById('other_finance_group').style.display = 'none';
            } else {
                document.getElementById('financial_condition').value = 'others';
                document.getElementById('other_finance').value = family.financial_condition || '';
                document.getElementById('other_finance_group').style.display = 'block';
            }

            document.getElementById('google_map_location').value = family.google_map_location || '';
            document.getElementById('land').value = family.land || '';
            document.getElementById('members_of_house').value = family.members_of_house || '';
            document.getElementById('temple_details').value = family.temple_details || '';
            
            // Lineage Pre-filling
            populateParentFamilies(family.parent_family_id || '', family.id);
            if (family.parent_family_id) {
                populateOriginMembers(family.parent_family_id, family.origin_member_id || '');
            } else {
                document.getElementById('origin_member_group').style.display = 'none';
            }

            if (family.photo_path) {
                const preview = document.getElementById('family_photo_preview');
                preview.querySelector('img').src = family.photo_path;
                preview.style.display = 'block';
            } else {
                document.getElementById('family_photo_preview').style.display = 'none';
            }

            document.getElementById('addFamilyModal').classList.add('active');
        }
        
        function openAddMemberModal(familyId) {
            document.getElementById('member_action').value = 'add_member';
            document.getElementById('addMemberForm').reset();
            document.getElementById('dynamic_marriage_fields').style.display = 'none';
            document.getElementById('child_type_group').style.display = 'none';
            document.getElementById('parent_member_group').style.display = 'none';
            document.getElementById('other_blood_group').style.display = 'none';
            document.getElementById('other_edu_group').style.display = 'none';
            document.getElementById('other_job_group').style.display = 'none';
            document.getElementById('other_marital_group').style.display = 'none';
            document.getElementById('other_child_type_group').style.display = 'none';
            document.querySelector('#addMemberModal h3').textContent = 'Add Family Member';
            document.getElementById('member_family_id').value = familyId;
            
            populateParentDropdown(familyId);
            
            document.getElementById('addMemberModal').classList.add('active');
        }

        function populateParentDropdown(familyId, excludeMemberId = null) {
            const parentSelect = document.getElementById('parent_member_id');
            const spouseSelect = document.getElementById('spouse_member_id');
            const siblingSelect = document.getElementById('sibling_member_id');
            
            const options = '<option value="">Select Member</option>';
            parentSelect.innerHTML = options;
            spouseSelect.innerHTML = options;
            siblingSelect.innerHTML = options;

            let members = [];

            // 1. Live Members from globalFamiliesData
            const liveFamily = globalFamiliesData.find(f => f.id == familyId);
            if (liveFamily && liveFamily.members) {
                liveFamily.members.forEach(m => {
                    members.push({
                        id: m.id,
                        name: m.name,
                        nick_name: m.nick_name,
                        relation: m.relation_to_owner,
                        is_pending: false
                    });
                });
            }

            // 2. Pending Members from globalPendingData (add_member)
            if (userRole === 'admin' && globalPendingData) {
                globalPendingData.forEach(p => {
                    if (p.action_type === 'add_member' && p.status === 'pending') {
                        const payload = JSON.parse(p.payload);
                        if (payload.family_id == familyId) {
                            members.push({
                                id: `pending_mem_${p.id}`,
                                name: payload.name,
                                nick_name: payload.nick_name,
                                relation: payload.relation_to_owner,
                                is_pending: true
                            });
                        }
                    }
                });
            }

            // 3. Special Case: If Family is Pending, add its Initial Owner
            if (familyId.toString().startsWith('pending_')) {
                const actionId = familyId.toString().replace('pending_', '');
                const action = globalPendingData.find(a => a.id == actionId);
                if (action && action.action_type === 'add_family') {
                    const payload = JSON.parse(action.payload);
                    members.push({
                        id: `pending_owner_${action.id}`,
                        name: payload.owner_name,
                        nick_name: '',
                        relation: 'Self (Owner)',
                        is_pending: true
                    });
                }
            }

            // Populate the Dropdowns
            members.forEach(m => {
                if (m.id != excludeMemberId) {
                    const option = document.createElement('option');
                    option.value = m.id;
                    const label = `${m.name} ${m.nick_name ? `(${m.nick_name})` : ''} - ${m.relation}`;
                    option.textContent = m.is_pending ? `${label} 🕒 (Pending)` : label;
                    if (m.is_pending) {
                        option.style.fontStyle = 'italic';
                        option.style.color = '#818cf8';
                    }
                    
                    parentSelect.appendChild(option.cloneNode(true));
                    spouseSelect.appendChild(option.cloneNode(true));
                    siblingSelect.appendChild(option);
                }
            });
        }

        function openEditMemberModal(familyId, memberId) {
            let member;
            let pendingId = null;
            
            if (memberId.toString().startsWith('pending_mem_')) {
                pendingId = memberId.toString().replace('pending_mem_', '');
                const action = globalPendingData.find(a => a.id == pendingId);
                if (!action) return;
                const payload = JSON.parse(action.payload);
                member = {
                    name: payload.name,
                    nick_name: payload.nick_name,
                    gender: payload.gender,
                    relation_to_owner: payload.relation_to_owner,
                    dob_dod_type: payload.dob_dod_type,
                    dob_dod: payload.dob_dod,
                    mobile_number: payload.mobile_number,
                    blood_group: payload.blood_group,
                    education: payload.education,
                    job_status: payload.job_status,
                    job_details: payload.job_details,
                    marital_status: payload.marital_status,
                    spouse_name: payload.spouse_name,
                    date_of_marriage: payload.date_of_marriage,
                    in_laws_village: payload.in_laws_village,
                    in_laws_father_name: payload.in_laws_father_name,
                    others: payload.others,
                    child_type: payload.child_type,
                    parent_member_id: payload.parent_member_id,
                    spouse_member_id: payload.spouse_member_id
                };
            } else {
                console.log("Editing live member of family:", familyId);
                const family = globalFamiliesData.find(f => f.id == familyId || f.id == parseInt(familyId));
                if (family && family.members) {
                    member = family.members.find(m => m.id == memberId || m.id == parseInt(memberId));
                }
            }

            if(!member) {
                console.error("Member not found for editing", {familyId, memberId});
                return;
            }

            document.getElementById('member_action').value = pendingId ? 'update_pending_action' : 'edit_member';
            document.getElementById('member_edit_id').value = pendingId ? '' : memberId;
            document.getElementById('member_pending_id').value = pendingId || '';
            document.getElementById('member_family_id').value = familyId;
            document.querySelector('#addMemberModal h3').textContent = pendingId ? '✏️ Edit Pending Member' : 'Edit Family Member';

            document.getElementById('member_name').value = member.name || '';
            document.getElementById('nick_name').value = member.nick_name || '';
            document.getElementById('gender').value = member.gender || '';
            
            const standardRelations = ['Self (Owner)', 'Spouse', 'Child', 'Parent', 'Sibling', 'Niece', 'Grand Child'];
            if (standardRelations.includes(member.relation_to_owner)) {
                document.getElementById('member_relation').value = member.relation_to_owner;
                document.getElementById('other_relation_group').style.display = 'none';
            } else {
                document.getElementById('member_relation').value = 'Other';
                document.getElementById('other_relation').value = member.relation_to_owner || '';
                document.getElementById('other_relation_group').style.display = 'block';
            }

            document.getElementById('dob_dod_type').value = member.dob_dod_type || 'DOB';
            document.getElementById('dob_dod').value = member.dob_dod || '';
            document.getElementById('member_mobile').value = member.mobile_number || '';
            
            // Blood Group Logic
            const standardBlood = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (standardBlood.includes(member.blood_group)) {
                document.getElementById('blood_group').value = member.blood_group;
                document.getElementById('other_blood_group').style.display = 'none';
            } else {
                document.getElementById('blood_group').value = member.blood_group ? 'Others' : '';
                document.getElementById('other_blood').value = member.blood_group || '';
                if(member.blood_group) document.getElementById('other_blood_group').style.display = 'block';
            }

            // Education Logic
            const standardEdu = ['PSC', 'JSC', 'SSC', 'HSC', 'Diploma', 'Honours', 'Masters', 'Doctorate', 'MBBS', 'Vocational', 'Primary'];
            if (standardEdu.includes(member.education)) {
                document.getElementById('education').value = member.education;
                document.getElementById('other_edu_group').style.display = 'none';
            } else {
                document.getElementById('education').value = member.education ? 'Others' : '';
                document.getElementById('other_edu').value = member.education || '';
                if(member.education) document.getElementById('other_edu_group').style.display = 'block';
            }

            // Job Logic
            const standardJob = ['Student', 'Service (Govt)', 'Service (Private)', 'Business', 'Housewife', 'Freelancer', 'Farmer', 'Unemployed', 'Retired'];
            if (standardJob.includes(member.job_status)) {
                document.getElementById('member_job').value = member.job_status;
                document.getElementById('other_job_group').style.display = 'none';
            } else {
                document.getElementById('member_job').value = member.job_status ? 'Others' : '';
                document.getElementById('other_job').value = member.job_status || '';
                if(member.job_status) document.getElementById('other_job_group').style.display = 'block';
            }

            document.getElementById('job_details').value = member.job_details || '';
            
            // Marital Logic
            const standardMarital = ['Single', 'Married', 'Divorced', 'Widowed'];
            if (standardMarital.includes(member.marital_status)) {
                document.getElementById('member_marital').value = member.marital_status;
                document.getElementById('other_marital_group').style.display = 'none';
            } else {
                document.getElementById('member_marital').value = member.marital_status ? 'Others' : '';
                document.getElementById('other_marital').value = member.marital_status || '';
                if(member.marital_status) document.getElementById('other_marital_group').style.display = 'block';
            }

            document.getElementById('spouse_name').value = member.spouse_name || '';
            document.getElementById('date_of_marriage').value = member.date_of_marriage || '';
            document.getElementById('in_laws_village').value = member.in_laws_village || '';
            document.getElementById('in_laws_father_name').value = member.in_laws_father_name || '';
            document.getElementById('others').value = member.others || '';
            
            if (member.photo_path) {
                const preview = document.getElementById('member_photo_preview');
                preview.querySelector('img').src = member.photo_path;
                preview.style.display = 'block';
            } else {
                document.getElementById('member_photo_preview').style.display = 'none';
            }
            
            // Child Type Logic
            const standardChildPositions = ['1st Child', '2nd Child', '3rd Child', '4th Child', '5th Child', 'Younger', 'Middle', 'Only Child'];
            if (standardChildPositions.includes(member.child_type)) {
                document.getElementById('child_type').value = member.child_type;
                document.getElementById('other_child_type_group').style.display = 'none';
            } else {
                document.getElementById('child_type').value = member.child_type ? 'Other' : '';
                document.getElementById('other_child_type').value = member.child_type || '';
                if(member.child_type) document.getElementById('other_child_type_group').style.display = 'block';
            }

            populateParentDropdown(familyId, memberId);
            document.getElementById('parent_member_id').value = member.parent_member_id || '';

            if(member.marital_status === 'Married') {
                document.getElementById('dynamic_marriage_fields').style.display = 'block';
            } else {
                document.getElementById('dynamic_marriage_fields').style.display = 'none';
            }

            if(member.relation_to_owner === 'Child') {
                document.getElementById('child_type_group').style.display = 'block';
            } else {
                document.getElementById('child_type_group').style.display = 'none';
            }

            // Show parent dropdown for Self (Owner), Child, Niece, Grand Child, or Other
            const needsParent = ['Self (Owner)', 'Child', 'Niece', 'Grand Child', 'Other'];
            if(needsParent.includes(member.relation_to_owner)) {
                document.getElementById('parent_member_group').style.display = 'block';
            } else {
                document.getElementById('parent_member_group').style.display = 'none';
            }

            // Sibling logic in Edit mode
            if (member.relation_to_owner === 'Sibling') {
                document.getElementById('sibling_member_group').style.display = 'block';
                // Try to find a co-sibling sharing same parent to suggest in dropdown
                const familyData = globalFamiliesData.find(f => f.id == familyId);
                if (familyData && familyData.members && member.parent_member_id) {
                    const coSibling = familyData.members.find(m => m.id != memberId && m.parent_member_id == member.parent_member_id);
                    if (coSibling) document.getElementById('sibling_member_id').value = coSibling.id;
                }
            } else {
                document.getElementById('sibling_member_group').style.display = 'none';
            }

            // Show spouse dropdown for Spouse, Son-in-law, or Daughter-in-law
            const needsSpouse = ['Spouse', 'Son-in-law', 'Daughter-in-law'];
            if(needsSpouse.includes(member.relation_to_owner)) {
                document.getElementById('spouse_member_group').style.display = 'block';
            } else {
                document.getElementById('spouse_member_group').style.display = 'none';
            }

            populateParentDropdown(familyId, memberId);
            document.getElementById('parent_member_id').value = member.parent_member_id || '';
            document.getElementById('spouse_member_id').value = member.spouse_member_id || '';

            document.getElementById('addMemberModal').classList.add('active');
        }

        function openViewMembersModal(familyId) {
            currentViewFamilyId = familyId;
            const family = globalFamiliesData.find(f => f.id == familyId);
            if(!family) return;

            document.getElementById('viewMembersTitle').textContent = `${family.house_owner_name}'s Family Directory`;
            switchMemberView('list'); // Default to list view
            document.getElementById('viewMembersModal').classList.add('active');
        }

        function switchMemberView(view) {
            currentMemberView = view;
            const btnList = document.getElementById('btnListView');
            const btnTree = document.getElementById('btnTreeView');
            const modalContent = document.querySelector('#viewMembersModal .modal-content');

            if (view === 'list') {
                modalContent.style.width = '95%';
                modalContent.style.maxWidth = '900px';
                btnList.classList.add('btn-primary');
                btnList.style.background = 'var(--primary)';
                btnList.style.color = 'white';
                btnTree.classList.remove('btn-primary');
                btnTree.style.background = 'transparent';
                btnTree.style.color = 'var(--text-muted)';
                renderMemberList();
            } else {
                modalContent.style.width = 'fit-content';
                modalContent.style.minWidth = '900px';
                modalContent.style.maxWidth = '95vw';
                btnTree.classList.add('btn-primary');
                btnTree.style.background = 'var(--primary)';
                btnTree.style.color = 'white';
                btnList.classList.remove('btn-primary');
                btnList.style.background = 'transparent';
                btnList.style.color = 'var(--text-muted)';
                renderFamilyTree();
            }
        }

        function renderMemberList() {
            const family = globalFamiliesData.find(f => f.id == currentViewFamilyId);
            const content = document.getElementById('viewMembersContent');
            if (!content) return;
            
            let allMembers = [];
            
            // 1. Approved Members
            if (family && family.members) {
                family.members.forEach(m => {
                    allMembers.push({ ...m, is_pending: false });
                });
            }

            // 2. Pending Members (Current Admin's additions)
            if (userRole === 'admin' && globalPendingData) {
                globalPendingData.forEach(p => {
                    if (p.action_type === 'add_member' && p.status === 'pending') {
                        const payload = JSON.parse(p.payload);
                        if (payload.family_id == currentViewFamilyId) {
                            allMembers.push({
                                id: `pending_mem_${p.id}`,
                                name: payload.name,
                                nick_name: payload.nick_name,
                                gender: payload.gender,
                                relation_to_owner: payload.relation_to_owner,
                                parent_member_id: payload.parent_member_id,
                                spouse_member_id: payload.spouse_member_id,
                                photo_path: payload.photo_path || '',
                                blood_group: payload.blood_group,
                                education: payload.education,
                                member_job: payload.member_job,
                                job_details: payload.job_details,
                                member_marital: payload.member_marital,
                                spouse_name: payload.spouse_name,
                                dob_dod: payload.dob_dod,
                                dob_dod_type: payload.dob_dod_type,
                                is_pending: true
                            });
                        }
                    }
                });
            }

            if (allMembers.length === 0) {
                content.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted);">No members found in this household yet.</div>';
                return;
            }

            let html = '';
            allMembers.forEach((m) => {
                let parentNameHtml = '';
                if (m.parent_member_id) {
                    const parent = allMembers.find(p => p.id == m.parent_member_id);
                    if (parent) {
                        parentNameHtml = `<div style="font-size: 0.85rem; color: #818cf8; margin-top: 0.2rem;">👨‍👦 Parent: ${parent.name}</div>`;
                    }
                }

                // Deep Relationship Intelligence
                let relationsHtml = '';
                const householdChildren = allMembers.filter(c => c.parent_member_id == m.id);
                const householdSpouse = m.spouse_member_id ? allMembers.find(sm => sm.id == m.spouse_member_id) : null;
                const householdSiblings = m.parent_member_id ? allMembers.filter(s => s.parent_member_id == m.parent_member_id && s.id != m.id) : [];

                const grandchildren = [];
                const inLaws = [];
                householdChildren.forEach(child => {
                    const gc = allMembers.filter(c => c.parent_member_id == child.id);
                    grandchildren.push(...gc);
                    const spouse = child.spouse_member_id ? allMembers.find(sm => sm.id == child.spouse_member_id) : null;
                    if (spouse) inLaws.push({ name: spouse.name, type: child.gender === 'Male' ? 'Daughter-in-law' : 'Son-in-law' });
                });

                if (householdChildren.length > 0 || householdSpouse || householdSiblings.length > 0 || grandchildren.length > 0 || inLaws.length > 0) {
                    relationsHtml = `
                    <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        ${householdSpouse ? `<span style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">💍 Spouse: ${householdSpouse.name}</span>` : ''}
                        ${householdChildren.map(c => `<span style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">👶 Child: ${c.name}</span>`).join('')}
                        ${householdSiblings.map(s => `<span style="background: rgba(129, 140, 248, 0.1); color: #818cf8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🤝 Sibling: ${s.name}</span>`).join('')}
                        ${grandchildren.map(g => `<span style="background: rgba(245, 158, 11, 0.1); color: #fbbf24; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🧑‍🍼 Grandchild: ${g.name}</span>`).join('')}
                        ${inLaws.map(il => `<span style="background: rgba(168, 85, 247, 0.1); color: #c084fc; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🏠 ${il.type}: ${il.name}</span>`).join('')}
                    </div>`;
                }

                const memberPhoto = m.photo_path ? `<img src="${m.photo_path}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid ${m.is_pending ? '#818cf8' : 'var(--primary)'};">` : `
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: ${m.is_pending ? 'rgba(129, 140, 248, 0.3)' : 'rgba(129, 140, 248, 0.2)'}; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #818cf8; border: 1px solid rgba(129, 140, 248, 0.3);">
                        ${m.name.charAt(0)}
                    </div>
                `;

                const pendingBadge = m.is_pending ? `<span class="result-badge member" style="margin-left: 0.5rem; font-size: 0.65rem; padding: 2px 8px; border-radius: 99px; background: rgba(129, 140, 248, 0.2); color: #818cf8; text-transform: uppercase;">🕒 Pending Approval</span>` : '';
                const cardStyle = m.is_pending ? 'border: 1px dashed #818cf8; background: rgba(129, 140, 248, 0.05);' : 'border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05);';

                html += `
                <div class="member-detail-card" style="${cardStyle} border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.8rem;">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            ${memberPhoto}
                            <div>
                                <h4 style="margin: 0; font-size: 1.2rem; color: var(--primary);">${m.name} ${m.nick_name ? `<span style="color: var(--text-muted); font-size: 0.9rem;">(${m.nick_name})</span>` : ''} ${pendingBadge}</h4>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    👤 ${m.relation_to_owner} ${m.child_type ? `• ${m.child_type}` : ''}
                                </div>
                                ${parentNameHtml}
                                ${relationsHtml}
                            </div>
                        </div>
                        ${(userRole === 'admin' || userRole === 'super_admin') ? `
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn-sm btn-outline" onclick="closeModal('viewMembersModal'); openEditMemberModal('${currentViewFamilyId}', '${m.id}')">Edit Profile</button>
                                <button class="btn-sm btn-outline btn-danger" onclick="deleteMember('${m.id}')">Delete</button>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                        ${m.blood_group ? `<div>🩸 <strong>Blood Group:</strong> <span style="color: #ef4444; font-weight: bold;">${m.blood_group}</span></div>` : ''}
                        ${m.dob_dod ? `<div>📅 <strong>${m.dob_dod_type || 'Born'}:</strong> ${m.dob_dod}</div>` : ''}
                        ${m.mobile_number ? `<div>📱 <strong>Mobile:</strong> ${m.mobile_number}</div>` : ''}
                        ${m.education ? `<div>🎓 <strong>Education:</strong> ${m.education}</div>` : ''}
                        ${m.marital_status ? `<div>💍 <strong>Status:</strong> ${m.marital_status}</div>` : ''}
                        ${m.job_status ? `<div>💼 <strong>Job Status:</strong> ${m.job_status}</div>` : ''}
                    </div>
                </div>
                `;
            });
            content.innerHTML = html;
        }


        // Member rendering logic completed above.


        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const family = globalFamiliesData.find(f => f.id == currentViewFamilyId);
            const fileName = `${family.house_owner_name.replace(/\s+/g, '_')}_Family_${currentMemberView}.pdf`;
            
            showToast("Generating PDF... please wait.");

            if (currentMemberView === 'list') {
                const doc = new jsPDF();
                doc.setFontSize(18);
                doc.text(`${family.house_owner_name}'s Family Directory`, 14, 20);
                doc.setFontSize(11);
                doc.setTextColor(100);
                doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 14, 28);
                
                let yPos = 40;
                family.members.forEach((m, index) => {
                    if (yPos > 270) { doc.addPage(); yPos = 20; }
                    
                    doc.setFontSize(12);
                    doc.setTextColor(0);
                    doc.text(`${index + 1}. ${m.name}`, 14, yPos);
                    doc.setFontSize(10);
                    doc.setTextColor(80);
                    doc.text(`Relation: ${m.relation_to_owner} | Mobile: ${m.mobile_number || 'N/A'}`, 20, yPos + 6);
                    
                    // Parent Link
                    if (m.parent_member_id) {
                        const parent = family.members.find(p => p.id == m.parent_member_id);
                        if (parent) {
                            doc.setTextColor(120);
                            doc.text(`Father/Parent: ${parent.name}`, 20, yPos + 11);
                            yPos += 5;
                        }
                    }
                    
                    yPos += 15;
                });
                
                doc.save(fileName);
                showToast("PDF Downloaded successfully!");
            } else {
                // Tree Export using html2canvas
                const treeElement = document.getElementById('premium-tree-mount');
                
                // Temporarily expand padding for capture
                const originalPadding = treeElement.style.padding;
                const originalOverflow = treeElement.style.overflow;
                treeElement.style.padding = '40px';
                treeElement.style.overflow = 'visible';
                treeElement.style.width = 'fit-content';

                try {
                    const canvas = await html2canvas(treeElement, {
                        backgroundColor: '#0f172a', // Match theme background
                        scale: 1.5, // Higher quality
                        useCORS: true,
                        logging: false
                    });

                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({
                        orientation: canvas.width > canvas.height ? 'l' : 'p',
                        unit: 'px',
                        format: [canvas.width, canvas.height]
                    });

                    pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                    pdf.save(fileName);
                    showToast("Family Tree PDF Downloaded!");
                } catch (err) {
                    console.error("PDF Export Error:", err);
                    showToast("Error generating tree PDF. Please try again.");
                } finally {
                    // Restore original styles
                    treeElement.style.padding = originalPadding;
                    treeElement.style.overflow = originalOverflow;
                    treeElement.style.width = '';
                }
            }
        }

        function renderFamilyTree() {
            const currentFamily = globalFamiliesData.find(f => f.id == currentViewFamilyId);
            const content = document.getElementById('viewMembersContent');
            if (!content) return;

            // Combine Live and Pending Members for Tree Tracing
            let allVillageMembers = globalFamiliesData.flatMap(f => f.members || []);
            if (userRole === 'admin' && globalPendingData) {
                globalPendingData.forEach(p => {
                    if (p.action_type === 'add_member' && p.status === 'pending') {
                        const payload = JSON.parse(p.payload);
                        allVillageMembers.push({
                            id: `pending_mem_${p.id}`,
                            name: payload.name,
                            family_id: payload.family_id,
                            parent_member_id: payload.parent_member_id,
                            relation_to_owner: payload.relation_to_owner,
                            is_pending: true
                        });
                    }
                });
            }

            const owner = allVillageMembers.find(m => m.family_id == currentViewFamilyId && m.relation_to_owner === 'Self (Owner)');
            if (!owner) {
                content.innerHTML = '<div style="color:var(--text-muted); text-align: center; padding: 3rem;">House Owner not found.</div>';
                return;
            }

            let absoluteRoot = owner;
            let iterations = 0;
            while (iterations < 50) { 
                let parent = allVillageMembers.find(m => m.id == absoluteRoot.parent_member_id);
                
                if (!parent) {
                    const memFamily = globalFamiliesData.find(f => f.id == absoluteRoot.family_id);
                    if (memFamily && memFamily.origin_member_id) {
                        parent = allVillageMembers.find(m => m.id == memFamily.origin_member_id);
                    }
                }

                if (parent) {
                    absoluteRoot = parent;
                } else {
                    break;
                }
                iterations++;
            }


            // Phase 2: Trigger React Rendering
            content.innerHTML = `
                <div class="tw-text-center tw-mb-4 tw-text-indigo-400 tw-text-sm tw-opacity-70">
                    💡 <span class="tw-font-bold">Tip:</span> Click and drag anywhere on the tree to scroll
                </div>
                <div id="premium-tree-mount" class="premium-tree-container tw-cursor-grab active:tw-cursor-grabbing tw-overflow-hidden tw-whitespace-nowrap tw-p-20 tw-min-h-[500px]"></div>
            `;
            
            const mount = document.getElementById('premium-tree-mount');
            // ... (Drag to scroll logic stays same)
            let isDown = false;
            let startX, startY, scrollLeft, scrollTop;

            mount.addEventListener('mousedown', (e) => {
                isDown = true;
                mount.classList.add('tw-cursor-grabbing');
                startX = e.pageX - mount.offsetLeft;
                startY = e.pageY - mount.offsetTop;
                scrollLeft = mount.scrollLeft;
                scrollTop = mount.scrollTop;
            });
            mount.addEventListener('mouseleave', () => { isDown = false; mount.classList.remove('tw-cursor-grabbing'); });
            mount.addEventListener('mouseup', () => { isDown = false; mount.classList.remove('tw-cursor-grabbing'); });
            mount.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - mount.offsetLeft;
                const y = e.pageY - mount.offsetTop;
                const walkX = (x - startX) * 2;
                const walkY = (y - startY) * 2;
                mount.scrollLeft = scrollLeft - walkX;
                mount.scrollTop = scrollTop - walkY;
            });

            // This global function is defined in the Babel script below
            if (window.renderReactTree) {
                window.renderReactTree(absoluteRoot, allVillageMembers, currentViewFamilyId);
            }
        }

        // Legacy Tree Node rendering removed in favor of React Engine
        function renderTreeNode(member, allMembers, renderedIds) {
            return '';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        document.getElementById('member_relation').addEventListener('change', function() {
            if (this.value === 'Child') {
                document.getElementById('child_type_group').style.display = 'block';
            } else {
                document.getElementById('child_type_group').style.display = 'none';
                document.getElementById('child_type').value = '';
            }

            // Show parent dropdown for Self (Owner), Child, Niece, Grand Child, or Other
            const needsParent = ['Self (Owner)', 'Child', 'Niece', 'Grand Child', 'Other'];
            if (needsParent.includes(this.value)) {
                document.getElementById('parent_member_group').style.display = 'block';
            } else {
                document.getElementById('parent_member_group').style.display = 'none';
                document.getElementById('parent_member_id').value = '';
            }

            // Show spouse dropdown for Spouse, Son-in-law, or Daughter-in-law
            const needsSpouse = ['Spouse', 'Son-in-law', 'Daughter-in-law'];
            if (needsSpouse.includes(this.value)) {
                document.getElementById('spouse_member_group').style.display = 'block';
            } else {
                document.getElementById('spouse_member_group').style.display = 'none';
                document.getElementById('spouse_member_id').value = '';
            }

            // Sibling logic
            if (this.value === 'Sibling') {
                document.getElementById('sibling_member_group').style.display = 'block';
            } else {
                document.getElementById('sibling_member_group').style.display = 'none';
                document.getElementById('sibling_member_id').value = '';
            }
        });

        document.getElementById('member_marital').addEventListener('change', function() {
            if (this.value === 'Married') {
                document.getElementById('dynamic_marriage_fields').style.display = 'block';
            } else {
                document.getElementById('dynamic_marriage_fields').style.display = 'none';
                document.getElementById('spouse_name').value = '';
                document.getElementById('date_of_marriage').value = '';
                document.getElementById('in_laws_village').value = '';
                document.getElementById('in_laws_father_name').value = '';
            }
        });

        document.getElementById('addFamilyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = document.getElementById('family_action').value;
            const formData = new FormData();
            
            let finalArea = document.getElementById('area').value;
            if (finalArea === 'others') finalArea = document.getElementById('other_area_name').value;

            let finalType = document.getElementById('type_of_house').value;
            if (finalType === 'others') finalType = document.getElementById('other_house_type').value;

            let finalFinance = document.getElementById('financial_condition').value;
            if (finalFinance === 'others') finalFinance = document.getElementById('other_finance').value;

            formData.append('id', document.getElementById('family_edit_id').value);
            formData.append('pending_id', document.getElementById('family_pending_id').value);
            formData.append('owner_name', document.getElementById('owner_name').value);
            formData.append('owner_father_name', document.getElementById('owner_father_name').value);
            formData.append('owner_mother_name', document.getElementById('owner_mother_name').value);
            formData.append('owner_mobile', document.getElementById('owner_mobile').value);
            formData.append('house_no', document.getElementById('house_no').value);
            formData.append('google_map_location', document.getElementById('google_map_location').value);
            formData.append('area', finalArea);
            formData.append('type_of_house', finalType);
            formData.append('financial_condition', finalFinance);
            formData.append('land', document.getElementById('land').value);
            formData.append('members_of_house', document.getElementById('members_of_house').value);
            formData.append('temple_details', document.getElementById('temple_details').value);
            formData.append('parent_family_id', document.getElementById('parent_family_id').value);
            formData.append('origin_member_id', document.getElementById('origin_member_id').value);
            
            const fileInput = document.getElementById('family_photo');
            if (fileInput.files[0]) formData.append('photo', fileInput.files[0]);

            const res = await apiCall(action, formData);
            if (res.status === 'success' || res.status === 'pending') {
                closeModal('addFamilyModal');
                document.getElementById('addFamilyForm').reset();
                document.getElementById('family_photo_preview').style.display = 'none';
                document.getElementById('origin_member_group').style.display = 'none';
                loadFamilies();
                if (res.status === 'pending') {
                    const msg = action === 'update_pending_action'
                        ? '✏️ Changes saved and sent to Super Admin for approval.'
                        : '✅ Family request submitted! Awaiting Super Admin approval.';
                    showToast(msg, 'info');
                } else {
                    showToast(res.message || '✅ Family saved successfully!', 'success');
                }
            } else {
                showToast('❌ ' + (res.message || 'Something went wrong.'), 'error');
            }
        });

        document.getElementById('addMemberForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = document.getElementById('member_action').value;
            const formData = new FormData();

            let finalBlood = document.getElementById('blood_group').value;
            if (finalBlood === 'Others') finalBlood = document.getElementById('other_blood').value;

            let finalEdu = document.getElementById('education').value;
            if (finalEdu === 'Others') finalEdu = document.getElementById('other_edu').value;

            let finalJobStatus = document.getElementById('member_job').value;
            if (finalJobStatus === 'Others') finalJobStatus = document.getElementById('other_job').value;

            let finalMarital = document.getElementById('member_marital').value;
            if (finalMarital === 'Others') finalMarital = document.getElementById('other_marital').value;

            formData.append('id', document.getElementById('member_edit_id').value);
            formData.append('pending_id', document.getElementById('member_pending_id').value);
            formData.append('family_id', document.getElementById('member_family_id').value);
            formData.append('name', document.getElementById('member_name').value);
            formData.append('relation_to_owner', (document.getElementById('member_relation').value === 'Other') ? document.getElementById('other_relation').value : document.getElementById('member_relation').value);
            formData.append('mobile_number', document.getElementById('member_mobile').value);
            formData.append('job_status', finalJobStatus);
            formData.append('marital_status', finalMarital);
            formData.append('nick_name', document.getElementById('nick_name').value);
            formData.append('gender', document.getElementById('gender').value);
            formData.append('dob_dod_type', document.getElementById('dob_dod_type').value);
            formData.append('dob_dod', document.getElementById('dob_dod').value);
            formData.append('education', finalEdu);
            formData.append('job_details', document.getElementById('job_details').value);
            formData.append('spouse_name', document.getElementById('spouse_name').value);
            formData.append('date_of_marriage', document.getElementById('date_of_marriage').value);
            formData.append('in_laws_village', document.getElementById('in_laws_village').value);
            formData.append('in_laws_father_name', document.getElementById('in_laws_father_name').value);
            formData.append('others', document.getElementById('others').value);
            formData.append('blood_group', finalBlood);
            formData.append('child_type', (document.getElementById('child_type').value === 'Other') ? document.getElementById('other_child_type').value : document.getElementById('child_type').value);
            formData.append('parent_member_id', document.getElementById('parent_member_id').value);
            formData.append('spouse_member_id', document.getElementById('spouse_member_id').value);
            
            // Handle Sibling inheritance
            const relation = document.getElementById('member_relation').value;
            const siblingId = document.getElementById('sibling_member_id').value;
            if (relation === 'Sibling' && siblingId) {
                let parentId = null;
                const familyId = document.getElementById('member_family_id').value;
                const family = globalFamiliesData.find(f => f.id == familyId);
                if (family && family.members) {
                    const sibling = family.members.find(m => m.id == siblingId);
                    if (sibling) parentId = sibling.parent_member_id;
                }
                // Check if sibling is in pending data
                if (!parentId && siblingId.toString().startsWith('pending_mem_')) {
                    const pId = siblingId.toString().replace('pending_mem_', '');
                    const action = globalPendingData.find(a => a.id == pId);
                    if (action) {
                        const payload = JSON.parse(action.payload);
                        parentId = payload.parent_member_id;
                    }
                }
                if (parentId) formData.set('parent_member_id', parentId);
            }
            
            const fileInput = document.getElementById('member_photo');
            if (fileInput.files[0]) formData.append('photo', fileInput.files[0]);

            const res = await apiCall(action, formData);
            if (res.status === 'success' || res.status === 'pending') {
                closeModal('addMemberModal');
                document.getElementById('addMemberForm').reset();
                document.getElementById('member_photo_preview').style.display = 'none';
                document.getElementById('sibling_member_group').style.display = 'none';
                loadFamilies();
                if (res.status === 'pending') {
                    const msg = action === 'update_pending_action'
                        ? '✏️ Member changes saved and sent for approval.'
                        : '✅ Member request submitted! Awaiting Super Admin approval.';
                    showToast(msg, 'info');
                } else {
                    showToast(res.message || '✅ Member saved successfully!', 'success');
                }
            } else {
                alert(res.message);
            }
        });

        async function deleteFamily(id) {
            if(!confirm("Are you sure you want to delete this family?")) return;
            const res = await apiCall('delete_family', { id });
            if(res.status === 'success' || res.status === 'pending') {
                if (res.status === 'pending') {
                    alert("✅ Delete Request Submitted\n\nYour delete request has been sent for approval.");
                } else {
                    loadFamilies();
                    showToast(res.message);
                }
            } else {
                alert(res.message);
            }
        }

        async function deleteMember(id) {
            if(!confirm("Are you sure you want to delete this family member?")) return;
            const res = await apiCall('delete_member', { id });
            if(res.status === 'success' || res.status === 'pending') {
                if (res.status === 'pending') {
                    alert("✅ Delete Request Submitted\n\nYour delete request for this member has been sent for approval.");
                } else {
                    loadFamilies();
                    showToast(res.message);
                }
            } else {
                alert(res.message);
            }
        }

        function detectLocation() {
            const btn = event.currentTarget;
            const input = document.getElementById('google_map_location');
            
            if (!navigator.geolocation) {
                alert("Geolocation is not supported by your browser.");
                return;
            }

            btn.disabled = true;
            btn.textContent = "Detecting...";

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const name = document.getElementById('owner_name').value || 'Owner';
                    const houseNo = document.getElementById('house_no').value || 'N/A';
                    const label = `House ${houseNo}: ${name}`;
                    const url = `https://www.google.com/maps?q=${lat},${lng}(${encodeURIComponent(label)})`;
                    input.value = url;
                    btn.disabled = false;
                    btn.textContent = "📍 Detect Location";
                },
                (error) => {
                    btn.disabled = false;
                    btn.textContent = "📍 Detect Location";
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            alert("User denied the request for Geolocation.");
                            break;
                        case error.POSITION_UNAVAILABLE:
                            alert("Location information is unavailable.");
                            break;
                        case error.TIMEOUT:
                            alert("The request to get user location timed out.");
                            break;
                        case error.UNKNOWN_ERROR:
                            alert("An unknown error occurred.");
                            break;
                    }
                }
            );
        }

        document.getElementById('globalSearch').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            renderFamilies(query);
        });

        async function loadFamilies() {
            const res = await apiCall('get_families');
            if(res.status === 'success') {
                globalFamiliesData = res.families;
                globalPendingData = res.pending || [];
                const searchVal = document.getElementById('globalSearch')?.value || '';
                renderFamilies(searchVal);
                
                // Update Home Portfolio Stats
                const statFam = document.getElementById('statFamiliesCounter');
                const statMem = document.getElementById('statMembersCounter');
                if (statFam) statFam.innerText = globalFamiliesData.length;
                if (statMem) {
                    const allMems = globalFamiliesData.flatMap(f => f.members || []).length;
                    statMem.innerText = allMems;
                }
            } else {
                document.getElementById('familiesGrid').innerHTML = `<div style="color:var(--secondary); text-align:center; padding:2rem;">${res.message}</div>`;
            }
        }

        function renderFamilies(searchQuery = '') {
            const grid = document.getElementById('familiesGrid');
            if (!grid) return;

            let familiesToRender = JSON.parse(JSON.stringify(globalFamiliesData));
            
            // Integrate Pending Actions for Admin
            if (userRole === 'admin' && globalPendingData.length > 0) {
                globalPendingData.forEach(p => {
                    let payload = {};
                    try { payload = JSON.parse(p.payload); } catch(e) { return; }
                    
                    if (p.action_type === 'add_family') {
                        // Create pseudo-family

                        const fakeFamily = {
                            id: 'pending_' + p.id,
                            house_owner_name: payload.owner_name,
                            area: payload.area,
                            house_no: payload.house_no,
                            financial_condition: payload.financial_condition,
                            photo_path: p.photo_path || null,
                            members: [],
                            is_pending: true,
                            pending_type: 'Addition'
                        };
                        familiesToRender.unshift(fakeFamily);
                    } else if (p.action_type === 'edit_family') {
                        const target = familiesToRender.find(f => f.id == p.target_id);
                        if (target) { target.is_pending = true; target.pending_type = 'Update'; }
                    } else if (p.action_type === 'delete_family') {
                        const target = familiesToRender.find(f => f.id == p.target_id);
                        if (target) { target.is_pending = true; target.pending_type = 'Deletion'; }
                    } else if (p.action_type === 'add_member') {
                        const target = familiesToRender.find(f => f.id == payload.family_id);
                        if (target) {
                            if (!target.members) target.members = [];
                            target.members.push({
                                id: 'pending_mem_' + p.id,
                                name: payload.name,
                                relation_to_owner: payload.relation_to_owner,
                                is_pending: true,
                                pending_type: 'Addition'
                            });
                        }
                    }
                });
            }

            const filteredFamilies = familiesToRender.filter(f => {
                const fArea = document.getElementById('filterArea')?.value || '';
                const fFinance = document.getElementById('filterFinance')?.value || '';
                const fBlood = document.getElementById('filterBlood')?.value || '';
                
                if (fArea && f.area !== fArea) return false;
                if (fFinance && f.financial_condition !== fFinance) return false;
                if (fBlood && !f.members?.some(m => m.blood_group === fBlood)) return false;
                if(!searchQuery) return true;
                const q = searchQuery.toLowerCase();
                
                const matchFamily = f.house_owner_name.toLowerCase().includes(q) ||
                                   f.area.toLowerCase().includes(q) ||
                                   (f.house_no && f.house_no.toLowerCase().includes(q));
                
                const matchMembers = f.members?.some(m => 
                    m.name.toLowerCase().includes(q) ||
                    (m.nick_name && m.nick_name.toLowerCase().includes(q)) ||
                    (m.mobile_number && m.mobile_number.toLowerCase().includes(q))
                );
                
                return matchFamily || matchMembers;
            });

            if(filteredFamilies.length === 0) {
                grid.innerHTML = '<div style="color:var(--text-muted); grid-column: 1/-1; text-align: center; padding: 3rem;">No matching families or members found.</div>';
                return;
            }
            
            let html = '';
            filteredFamilies.forEach(f => {
                let membersHtml = '';
                if(f.members && f.members.length > 0) {
                    f.members.forEach((m, index) => {
                        const mPendingBadge = m.is_pending ? `<span class="pending-badge" style="font-size:0.5rem; padding:0.1rem 0.3rem; vertical-align: middle;">⏳ ${m.pending_type}</span>` : '';
                        const mClass = m.is_pending ? 'member-item pending' : 'member-item';
                        membersHtml += `
                        <li class="${mClass}" style="padding: 1rem 0;">
                            <div class="member-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span class="member-name">${m.name} ${m.nick_name ? `(${m.nick_name})` : ''} ${mPendingBadge}</span>
                                    <span class="member-relation" style="display:block; font-size:0.75rem; color:var(--primary); margin-top:0.2rem;">${m.relation_to_owner} ${m.child_type ? `(${m.child_type})` : ''}</span>
                                </div>
                                ${(userRole === 'admin' || userRole === 'super_admin') ? `
                                    <div style="display: flex; gap: 0.3rem;">
                                        <button class="btn-sm btn-outline" style="padding: 0.1rem 0.4rem; font-size: 0.7rem;" onclick="openEditMemberModal('${f.id}', '${m.id}')">Edit</button>
                                        <button class="btn-sm btn-outline btn-danger" style="padding: 0.1rem 0.4rem; font-size: 0.7rem;" onclick="deleteMember('${m.id}')">Delete</button>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="member-details" style="display: grid; grid-template-columns: 1fr; gap: 0.3rem; margin-top: 0.5rem;">
                                ${m.blood_group ? `<div>🩸 <strong>Blood Group:</strong> ${m.blood_group}</div>` : ''}
                                ${m.dob_dod ? `<div>📅 <strong>${m.dob_dod_type || 'DOB'}:</strong> ${m.dob_dod}</div>` : ''}
                                ${m.mobile_number ? `<div>📱 <strong>Mobile:</strong> ${m.mobile_number}</div>` : ''}
                                ${m.education ? `<div>🎓 <strong>Edu:</strong> ${m.education}</div>` : ''}
                                ${m.job_status || m.job_details ? `
                                    <div>💼 <strong>Job:</strong> ${m.job_status ? m.job_status : 'N/A'}${m.job_details ? ` - ${m.job_details}` : ''}</div>
                                ` : ''}
                                ${m.marital_status ? `<div>💍 <strong>Marital Status:</strong> ${m.marital_status}${m.spouse_name ? ` (Spouse: ${m.spouse_name})` : ''}</div>` : ''}
                                ${m.date_of_marriage ? `<div>🎉 <strong>Marriage Date:</strong> ${m.date_of_marriage}</div>` : ''}
                                ${m.in_laws_village ? `<div>🏡 <strong>In-law's Village:</strong> ${m.in_laws_village}</div>` : ''}
                                ${m.in_laws_father_name ? `<div>👤 <strong>In-law's Father:</strong> ${m.in_laws_father_name}</div>` : ''}
                                ${m.others ? `<div style="color: #94a3b8; font-style: italic;">📝 ${m.others}</div>` : ''}
                            </div>
                        </li>`;
                    });
                    
                } else {
                    membersHtml = '<div style="color:var(--text-muted); font-size: 0.85rem; padding: 1rem 0;">No members added yet.</div>';
                }

                let lineageHtml = '';
                if (f.parent_family_id && f.parent_family_name) {
                    lineageHtml = `
                        <div style="margin-top: 1rem; padding: 0.8rem; background: rgba(129, 140, 248, 0.1); border-radius: 12px; border: 1px dashed var(--primary); font-size: 0.85rem; display: flex; align-items: center; gap: 0.8rem; cursor: pointer; transition: all 0.2s;" onclick="scrollToFamily('${f.parent_family_id}')" class="lineage-link">
                            <div style="font-size: 1.5rem;">🌳</div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Household Origin</div>
                                <div style="color: var(--primary); font-weight: 700;">Branch of ${f.parent_family_name}'s Family</div>
                            </div>
                        </div>
                    `;
                }

                const viewMembersBtn = (f.members && f.members.length > 0) ? `
                    <div style="text-align: center; margin-top: 1rem;">
                        <button class="btn-sm btn-outline toggle-members-btn" onclick="openViewMembersModal(${f.id})">View Detailed Members List (${f.members.length})</button>
                    </div>
                ` : '';

                const adminActions = (userRole === 'admin' || userRole === 'super_admin') ? `
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="btn-sm btn-outline" onclick="openAddMemberModal('${f.id}')">+ Member</button>
                                    <button class="btn-sm btn-outline" style="border-color: #818cf8; color: #818cf8;" onclick="openEditFamilyModal('${f.id}')">Edit Family</button>
                                    <button class="btn-sm btn-outline btn-danger" onclick="deleteFamily('${f.id}')">Delete</button>
                                </div>
                ` : '';

                let mapUrl = f.google_map_location || '';
                if (mapUrl && !mapUrl.includes('(')) {
                    const label = `House ${f.house_no || 'N/A'}: ${f.house_owner_name}`;
                    if (mapUrl.startsWith('http')) {
                        mapUrl = `${mapUrl}(${encodeURIComponent(label)})`;
                    } else {
                        mapUrl = `https://www.google.com/maps?q=${mapUrl}(${encodeURIComponent(label)})`;
                    }
                }

                const familyPhoto = (f.photo_path && !f.id.toString().startsWith('pending')) ? `<img src="${f.photo_path}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.2); margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">` : `
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #4f46e5); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                        ${f.house_owner_name.charAt(0)}
                    </div>`;

                const pendingBadge = f.is_pending ? `<span class="pending-badge">⏳ Pending ${f.pending_type}</span>` : '';
                const cardClass = f.is_pending ? 'family-card pending' : 'family-card';

                html += `
                <div class="${cardClass}" id="family-${f.id}">
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 1.5rem;">
                        ${familyPhoto}
                        <h3 style="margin: 0; color: white;">${f.house_owner_name} ${pendingBadge}</h3>
                        <p style="color: var(--primary); font-weight: 500; font-size: 0.9rem; margin: 0.3rem 0;">📍 ${f.area}</p>
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; text-transform: capitalize; text-align: left; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        ${lineageHtml}
                        📍 <strong>Area:</strong> ${f.area || 'N/A'} (House No: ${f.house_no || 'N/A'})<br>
                        🏠 <strong>Type:</strong> ${f.type_of_house || 'N/A'}<br>
                        💰 <strong>Finance:</strong> ${f.financial_condition || 'N/A'}<br>
                        📱 <strong>Owner Mobile:</strong> ${f.owner_mobile || 'N/A'}<br>
                        ${f.land ? `🌱 <strong>Land:</strong> ${f.land}<br>` : ''}
                        ${f.members_of_house ? `👥 <strong>Total Summary Members:</strong> ${f.members_of_house}<br>` : ''}
                        ${f.temple_details ? `🕍 <strong>Temple:</strong> ${f.temple_details}<br>` : ''}
                        ${f.google_map_location ? `🗺️ <strong>Map:</strong> <a href="${mapUrl}" target="_blank" style="color:#818cf8; text-transform:none;">View Location (${f.house_owner_name})</a><br>` : ''}
                    </div>
                    ${viewMembersBtn}
                    ${adminActions}
                </div>
                `;
            });
            grid.innerHTML = html;
        }

        // Preview Image Listeners
        function setupPreview(inputId, previewContainerId) {
            document.getElementById(inputId).addEventListener('change', function() {
                const file = this.files[0];
                const preview = document.getElementById(previewContainerId);
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.querySelector('img').src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        setupPreview('family_photo', 'family_photo_preview');
        setupPreview('member_photo', 'member_photo_preview');

        // INIT
        loadFamilies();

        function resetFilters() {
            document.getElementById('globalSearch').value = '';
            document.getElementById('filterArea').value = '';
            document.getElementById('filterFinance').value = '';
            document.getElementById('filterBlood').value = '';
            renderFamilies();
        }

        function exportToExcel() {
            if (globalFamiliesData.length === 0) {
                alert("No data to export.");
                return;
            }

            // CSV Header
            let csv = "\uFEFF"; // UTF-8 BOM for Excel
            csv += "House Owner,Father Name,Mother Name,House No,Area,House Type,Finance,Mobile,Land,Members Count,Map Location\n";

            globalFamiliesData.forEach(f => {
                const row = [
                    f.house_owner_name,
                    f.owner_father_name,
                    f.owner_mother_name,
                    f.house_no,
                    f.area,
                    f.type_of_house,
                    f.financial_condition,
                    f.owner_mobile,
                    f.land,
                    f.members_of_house,
                    f.google_map_location
                ].map(val => `"${(val || "").toString().replace(/"/g, '""')}"`);
                csv += row.join(",") + "\n";
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            const date = new Date().toISOString().split('T')[0];
            link.setAttribute("download", `Family_Directory_${date}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function printReport() {
            window.print();
        }

        // Navigation & Sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        }

        // User Management & View Switching Logic
        let currentView = 'home';

        function switchMainView(view) {
            currentView = view;
            localStorage.setItem('shidhlajury_last_view', view);
            
            // Close sidebar on mobile after navigation
            if (window.innerWidth < 1024) {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar) sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
            const homeView       = document.getElementById('homePortfolioWrapper');
            const analyticsView  = document.getElementById('analyticsView');
            const mapView        = document.getElementById('mapView');
            const newsfeedView   = document.getElementById('newsfeedView');
            const familiesHeader = document.getElementById('familiesDirectoryHeader');
            const familiesView   = document.getElementById('familiesGrid');
            const filtersSection = document.querySelector('.filter-container');
            const usersView      = document.getElementById('userManagementView');
            const approvalsView  = document.getElementById('approvalQueueView');
            
            const navHome        = document.getElementById('nav-home');
            const navAnalytics   = document.getElementById('nav-analytics');
            const navMap         = document.getElementById('nav-map');
            const navNewsfeed    = document.getElementById('nav-newsfeed');
            const navFamilies    = document.getElementById('nav-families');
            const navUsers       = document.getElementById('nav-users');
            const navApprovals   = document.getElementById('nav-approvals');

            // hide all
            if (homeView)       homeView.style.display        = 'none';
            if (analyticsView)  analyticsView.style.display   = 'none';
            if (mapView)        mapView.style.display         = 'none';
            if (newsfeedView)   newsfeedView.style.display    = 'none';
            if (familiesHeader) familiesHeader.style.display  = 'none';
            if (familiesView)   familiesView.style.display    = 'none';
            if (filtersSection) filtersSection.style.display  = 'none';
            if (usersView)      usersView.style.display       = 'none';
            if (approvalsView)  approvalsView.style.display   = 'none';
            
            if (navHome)      navHome.classList.remove('active');
            if (navAnalytics) navAnalytics.classList.remove('active');
            if (navMap)       navMap.classList.remove('active');
            if (navNewsfeed)  navNewsfeed.classList.remove('active');
            if (navFamilies)  navFamilies.classList.remove('active');
            if (navUsers)     navUsers.classList.remove('active');
            if (navApprovals) navApprovals.classList.remove('active');
            if (document.getElementById('nav-bin')) document.getElementById('nav-bin').classList.remove('active');

            if (document.getElementById('recycleBinView')) document.getElementById('recycleBinView').style.display = 'none';

            if (view === 'home' && homeView && navHome) {
                homeView.style.display = 'block';
                navHome.classList.add('active');
            } else if (view === 'analytics') {
                if (analyticsView) analyticsView.style.display = 'block';
                if (navAnalytics) navAnalytics.classList.add('active');
                if (globalFamiliesData.length === 0) loadFamilies();
                setTimeout(() => renderAnalyticsCharts(), 100);
            } else if (view === 'map') {
                if (mapView) mapView.style.display = 'block';
                if (navMap) navMap.classList.add('active');
                if (globalFamiliesData.length === 0) loadFamilies();
                setTimeout(() => renderVillageMap(), 200);
            } else if (view === 'newsfeed') {
                if (newsfeedView) newsfeedView.style.display = 'block';
                if (navNewsfeed) navNewsfeed.classList.add('active');
                if (globalFamiliesData.length === 0) loadFamilies();
                setTimeout(() => renderNewsfeed(), 100);
            } else if (view === 'families') {
                if (familiesHeader) familiesHeader.style.display = 'block';
                if (familiesView)   familiesView.style.display   = 'grid';
                if (filtersSection) filtersSection.style.display = 'flex';
                if (navFamilies)    navFamilies.classList.add('active');
                if (globalFamiliesData.length === 0) loadFamilies();
            } else if (view === 'users') {
                usersView.style.display = 'block';
                if (navUsers) navUsers.classList.add('active');
                loadUsers();
            } else if (view === 'approvals') {
                approvalsView.style.display = 'block';
                if (navApprovals) navApprovals.classList.add('active');
                loadPendingActions();
            } else if (view === 'bin') {
                if (approvalsView) approvalsView.style.display = 'none';
                document.getElementById('recycleBinView').style.display = 'block';
                if (document.getElementById('nav-bin')) document.getElementById('nav-bin').classList.add('active');
                loadRecycleBin();
            }
        }

        async function loadUsers() {
            const res = await apiCall('get_users');
            if (res.status === 'success') {
                renderUsers(res.users);
            }
        }

        function renderUsers(users) {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';
            users.forEach(u => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${u.username}</td>
                    <td><span class="role-badge">${u.role}</span></td>
                    <td>${new Date(u.created_at).toLocaleDateString()}</td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button class="btn-icon" onclick='openEditUserModal(${JSON.stringify(u).replace(/'/g, "&#39;")})'>✏️</button>
                        <button class="btn-icon delete" onclick="deleteUser(${u.id})">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function openAddUserModal() {
            document.getElementById('user_action').value = 'add_user';
            document.getElementById('user_edit_id').value = '';
            document.getElementById('user_username').value = '';
            document.getElementById('user_password').value = '';
            document.getElementById('user_password').required = true;
            document.getElementById('pass_hint').style.display = 'none';
            document.querySelector('#addUserModal h3').textContent = 'Add New User';
            document.getElementById('addUserModal').classList.add('active');
        }

        function openEditUserModal(user) {
            document.getElementById('user_action').value = 'edit_user';
            document.getElementById('user_edit_id').value = user.id;
            document.getElementById('user_username').value = user.username;
            document.getElementById('user_password').value = '';
            document.getElementById('user_password').required = false;
            document.getElementById('pass_hint').style.display = 'block';
            document.getElementById('user_role').value = user.role;
            document.querySelector('#addUserModal h3').textContent = 'Edit User';
            document.getElementById('addUserModal').classList.add('active');
        }

        document.getElementById('addUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = document.getElementById('user_action').value;
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', document.getElementById('user_edit_id').value);
            formData.append('username', document.getElementById('user_username').value);
            formData.append('password', document.getElementById('user_password').value);
            formData.append('role', document.getElementById('user_role').value);

            const res = await apiCall(action, formData);
            if (res.status === 'success') {
                closeModal('addUserModal');
                loadUsers();
            } else {
                alert(res.message);
            }
        });

        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('id', id);
            const res = await apiCall('delete_user', formData);
            if (res.status === 'success') {
                loadUsers();
            } else {
                alert(res.message);
            }
        }

        function toggleMembers(familyId, totalCount) {
            // Deprecated - replaced by openViewMembersModal
        }

        // ── Toast Notification ──────────────────────────────
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + type;
            toast.innerHTML = message; // Use innerHTML to support icons/emojis
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('visible'), 50);
            setTimeout(() => {
                toast.classList.remove('visible');
                setTimeout(() => document.body.removeChild(toast), 400);
            }, 4000);
        }

        // ── Approval Queue ─────────────────────────
        async function loadPendingActions() {
            const res = await apiCall('get_pending_actions');
            if (res.status === 'success') {
                renderApprovalQueue(res.actions);
                updatePendingBadge(res.actions.length);
            }
        }

        function renderApprovalQueue(actions) {
            const tbody = document.getElementById('approvalTableBody');
            if (!tbody) return;
            if (actions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 2rem;">✅ No pending actions — all clear!</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            actions.forEach(a => {
                const date   = new Date(a.created_at).toLocaleString();
                const actionLabel = {
                    add_family:    '➕ Add Family',
                    edit_family:   '✏️ Edit Family',
                    delete_family: '🗑️ Delete Family',
                    add_member:    '➕ Add Member',
                    edit_member:   '✏️ Edit Member',
                    delete_member: '🗑️ Delete Member',
                }[a.action_type] || a.action_type;

                const payload = (() => { try { return JSON.parse(a.payload); } catch(e) { return {}; } })();
                const detail = payload.name || payload.owner_name || (a.target_id ? `ID: ${a.target_id}` : '—');

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${a.submitted_by_username}</strong></td>
                    <td><span class="status-pill action-pill">${actionLabel}</span></td>
                    <td style="color: var(--text-muted);">${detail}</td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">${date}</td>
                    <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick='viewPendingDetails(${JSON.stringify(a).replace(/'/g, "&#39;")})'>🔍 View Data</button>
                        <button class="btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem;" onclick="reviewAction(${a.id}, 'approved', '', '${detail.replace(/'/g, "\\'")}')">✅ Approve</button>
                        <button class="btn-outline" style="padding: 0.4rem 1rem; font-size: 0.8rem; border-color: var(--secondary); color: var(--secondary);" onclick="promptReject(${a.id}, '${detail.replace(/'/g, "\\'")}')">❌ Reject</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function viewPendingDetails(action) {
            const container = document.getElementById('pendingDetailsContent');
            const approveBtn = document.getElementById('detailApproveBtn');
            const rejectBtn = document.getElementById('detailRejectBtn');
            
            let payload = {};
            try { payload = JSON.parse(action.payload); } catch(e) {}

            const isDelete = action.action_type.includes('delete');
            const headerStyle = isDelete 
                ? 'background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;' 
                : 'background: rgba(129, 140, 248, 0.1); border: 1px solid var(--primary); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;';
            const labelColor = isDelete ? '#ef4444' : 'var(--primary)';

            let html = `
                <div style="${headerStyle}">
                    <div style="font-size: 0.8rem; color: ${labelColor}; font-weight: 700; text-transform: uppercase;">${isDelete ? '⚠️ Deletion Request' : 'Submission Info'}</div>
                    <div class="detail-row"><span class="detail-label">Submitted By</span> <span class="detail-value">${action.submitted_by_username}</span></div>
                    <div class="detail-row"><span class="detail-label">Action Type</span> <span class="detail-value" style="color:${labelColor}; font-weight:700;">${action.action_type.replace('_', ' ').toUpperCase()}</span></div>
                    <div class="detail-row"><span class="detail-label">Record ID</span> <span class="detail-value">${action.target_id || 'NEW'}</span></div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">${isDelete ? 'Record Details to be Removed' : 'Data Fields'}</div>
            `;
            
            for (const key in payload) {
                if (key === 'action' || key === 'id') continue;
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                html += `
                    <div class="detail-row">
                        <span class="detail-label">${label}</span>
                        <span class="detail-value">${payload[key] || '—'}</span>
                    </div>
                `;
            }

            if (action.photo_path) {
                html += `
                    <div style="margin-top: 1rem;">
                        <span class="detail-label">Submitted Photo</span>
                        <div style="margin-top: 0.5rem;">
                            <img src="${action.photo_path}" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color);">
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
            
            // Re-bind buttons
            approveBtn.onclick = () => {
                closeModal('pendingDetailsModal');
                const detail = payload.name || payload.owner_name || (action.target_id ? `ID: ${action.target_id}` : '—');
                reviewAction(action.id, 'approved', '', detail);
            };
            rejectBtn.onclick = () => {
                closeModal('pendingDetailsModal');
                const detail = payload.name || payload.owner_name || (action.target_id ? `ID: ${action.target_id}` : '—');
                promptReject(action.id, detail);
            };
            
            document.getElementById('pendingDetailsModal').classList.add('active');
        }

        async function reviewAction(id, decision, note, detail = '') {
            const label = detail ? `'${detail}'` : 'Record';
            showToast(`${decision === 'approved' ? '✅' : '❌'} ${decision === 'approved' ? 'Approving' : 'Rejecting'} ${label}...`, 'info');
            try {
                const formData = new FormData();
                formData.append('action', 'review_action');
                formData.append('id', id);
                formData.append('decision', decision);
                formData.append('note', note);
                const res = await apiCall('review_action', formData);
                if (res.status === 'success') {
                    showToast(`✅ ${label} ${decision} successfully.`, decision === 'approved' ? 'success' : 'info');
                    loadPendingActions();
                    loadFamilies(); 
                    if (window.renderFamilies) window.renderFamilies(); // Refresh grid
                    loadNotifications(); // Refresh bell
                } else {
                    showToast('❌ ' + (res.message || `Error reviewing ${label}`), 'error');
                }
            } catch (e) {
                showToast(`❌ Network error during ${decision}.`, 'error');
                console.error(e);
            }
        }

        function promptReject(id, detail = '') {
            const note = prompt(`Enter a reason for rejecting ${detail || 'this record'} (optional):`);
            if (note === null) return; 
            reviewAction(id, 'rejected', note || '', detail);
        }

        function updatePendingBadge(count) {
            const badge = document.getElementById('pendingBadge');
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }

        async function pollPendingCount() {
            const res = await apiCall('get_pending_count');
            updatePendingBadge(res.count || 0);
        }

        // ── Recycle Bin Logic ─────────────────────────
        async function loadRecycleBin() {
            const res = await apiCall('get_recycle_bin');
            if (res.status === 'success') {
                renderRecycleBin(res.families, res.members);
            }
        }

        function renderRecycleBin(families, members) {
            const fBody = document.getElementById('binFamiliesTable');
            const mBody = document.getElementById('binMembersTable');
            if (!fBody || !mBody) return;
            fBody.innerHTML = '';
            mBody.innerHTML = '';

            if (families.length === 0) fBody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:2rem; color:var(--text-muted);">Bin is empty</td></tr>';
            families.forEach(f => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${f.house_owner_name} <span class="bin-badge">Family</span></td>
                    <td>${new Date(f.deleted_at).toLocaleString()}</td>
                    <td class="recycle-bin-actions">
                        <button class="btn-sm btn-outline" onclick="restoreItem('family', ${f.id})">🔄 Restore</button>
                        <button class="btn-sm btn-outline btn-danger" onclick="permanentDelete('family', ${f.id})">🗑️ Delete Permanently</button>
                    </td>
                `;
                fBody.appendChild(tr);
            });

            if (members.length === 0) mBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">Bin is empty</td></tr>';
            members.forEach(m => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${m.name} <span class="bin-badge">Member</span></td>
                    <td>#${m.family_id}</td>
                    <td>${new Date(m.deleted_at).toLocaleString()}</td>
                    <td class="recycle-bin-actions">
                        <button class="btn-sm btn-outline" onclick="restoreItem('member', ${m.id})">🔄 Restore</button>
                        <button class="btn-sm btn-outline btn-danger" onclick="permanentDelete('member', ${m.id})">🗑️ Delete Permanently</button>
                    </td>
                `;
                mBody.appendChild(tr);
            });
        }

        async function restoreItem(type, id) {
            if (!confirm(`Are you sure you want to restore this ${type}?`)) return;
            showToast(`Restoring ${type}...`, 'info');
            const res = await apiCall('restore_item', { type, id });
            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadRecycleBin();
                loadFamilies();
                if (window.renderFamilies) window.renderFamilies();
            } else {
                showToast(res.message || 'Error restoring item', 'error');
            }
        }

        async function permanentDelete(type, id) {
            if (!confirm(`PERMANENTLY delete this ${type}? This cannot be undone.`)) return;
            const res = await apiCall('permanent_delete', { type, id });
            if (res.status === 'success') {
                showToast(res.message);
                loadRecycleBin();
            }
        }

        // ── Notification Logic ────────────────────────
        async function loadNotifications() {
            try {
                // Fetch in parallel for speed
                const [nRes, pRes] = await Promise.all([
                    apiCall('get_notifications'),
                    <?php echo ($role === 'super_admin' ? "apiCall('get_pending_count')" : "Promise.resolve({count: 0})"); ?>
                ]);

                let pendingCount = pRes.count || 0;
                updatePendingBadge(pendingCount);

                if (nRes.status === 'success') {
                    const list = document.getElementById('notif-list');
                    const badge = document.getElementById('notif-badge');
                    if (!list || !badge) return;
                    
                    const unreadNotifs = nRes.unread_count || 0;
                    const totalAlerts = unreadNotifs + pendingCount;
                    
                    if (totalAlerts > 0) {
                        badge.textContent = totalAlerts;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }

                    let html = '';
                    
                    // Prioritize Pending Approvals at the top of the list
                    if (pendingCount > 0) {
                        html += `
                            <div class="notif-item unread" style="background: rgba(79, 70, 229, 0.08); border-left: 4px solid #4f46e5; cursor: pointer; transition: all 0.2s;" onclick="switchMainView('approvals')">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                                    <strong style="color: #4338ca; font-size: 0.8rem; display:flex; align-items:center; gap: 6px;">
                                        <span>🔔</span> PENDING APPROVALS
                                    </strong>
                                    <span style="background:#4f46e5; color:white; font-size:0.7rem; font-weight:800; padding: 1px 8px; border-radius:99px;">${pendingCount}</span>
                                </div>
                                <div style="font-size: 0.85rem; color: #1e1b4b; line-height: 1.4;">There are ${pendingCount} actions waiting for super admin review.</div>
                                <div style="font-size: 0.7rem; color: #6366f1; margin-top: 6px; font-weight: 600;">Click to open Approval Queue ➔</div>
                            </div>
                        `;
                    }

                    if (nRes.notifications.length === 0 && pendingCount === 0) {
                        list.innerHTML = '<div style="padding: 2rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">✨ All caught up! No new notifications.</div>';
                        return;
                    }

                    html += nRes.notifications.map(n => `
                        <div class="notif-item ${n.is_read ? '' : 'unread'}">
                            <div style="margin-bottom: 4px; font-size: 0.88rem; line-height: 1.5; color: var(--text-main);">${n.message}</div>
                            <div style="font-size: 0.68rem; color: var(--text-muted); display:flex; align-items:center; gap: 4px;">
                                <span>📅</span> ${new Date(n.created_at).toLocaleString()}
                            </div>
                        </div>
                    `).join('');
                    
                    list.innerHTML = html;
                }
            } catch(e) { 
                console.error("Critical: Notification sync failed", e); 
            }
        }

        function toggleNotifications() {
            const dropdown = document.getElementById('notif-dropdown');
            if (!dropdown) return;
            dropdown.classList.toggle('active');
        }

        async function markNotificationsRead(e) {
            if (e) e.stopPropagation();
            const res = await apiCall('mark_notifications_read');
            if (res.status === 'success') {
                loadNotifications();
            }
        }

        function startPolling() {
            <?php if ($role === 'super_admin'): ?>
                pollPendingCount();
                setInterval(pollPendingCount, 30000);
            <?php endif; ?>
            
            loadNotifications();
            setInterval(loadNotifications, 60000);
        }

        function scrollToFamily(id) {
            const card = document.getElementById(`family-${id}`);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.classList.add('highlight-family');
                setTimeout(() => card.classList.remove('highlight-family'), 2000);
            } else {
                showToast("Household not found in current list.");
            }
        }

        document.addEventListener('click', (e) => {
            const container = document.querySelector('.notification-wrapper');
            const dropdown = document.getElementById('notif-dropdown');
            if (container && dropdown && !container.contains(e.target)) {
                dropdown.classList.remove('active');
            }

            // Close search results on click outside
            const sContainer = document.querySelector('.search-container');
            const sDropdown = document.getElementById('searchDropdown');
            if (sContainer && sDropdown && !sContainer.contains(e.target)) {
                sDropdown.classList.remove('active');
            }
        });

        // ── Global Search Handling ─────────────────────
        function handleGlobalSearch(q) {
            const dropdown = document.getElementById('searchDropdown');
            if (!q || q.length < 2) {
                dropdown.classList.remove('active');
                if (currentView === 'families' && window.renderFamilies) renderFamilies(q);
                return;
            }

            let results = [];
            let query = q.toLowerCase();

            globalFamiliesData.forEach(f => {
                let matchType = '';
                let matchMain = '';
                let matchSub  = '';

                // Match Family Name or Owner
                if (f.family_name?.toLowerCase().includes(query) || f.house_owner_name?.toLowerCase().includes(query)) {
                    results.push({
                        id: f.id,
                        title: f.family_name || f.house_owner_name,
                        sub: `Owner: ${f.house_owner_name} | Area: ${f.area}`,
                        type: 'family'
                    });
                }

                // Match Members
                if (f.members) {
                    f.members.forEach(m => {
                        if (m.name?.toLowerCase().includes(query)) {
                            results.push({
                                id: f.id, // Point to the family
                                title: m.name,
                                sub: `Member of ${f.family_name || f.house_owner_name} | Role: ${m.relation_to_owner || 'Member'}`,
                                type: 'member'
                            });
                        }
                    });
                }
            });

            // If we are in the families view, we still want to filter the grid
            if (currentView === 'families' && window.renderFamilies) renderFamilies(q);

            if (results.length === 0) {
                dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No matches found</div>';
            } else {
                dropdown.innerHTML = results.slice(0, 10).map(r => `
                    <div class="search-result-item" onclick="navigateToFamily(${r.id})">
                        <div class="result-main">
                            <span class="result-title">${r.title}</span>
                            <span class="result-sub">${r.sub}</span>
                        </div>
                        <span class="result-badge ${r.type}">${r.type}</span>
                    </div>
                `).join('');
            }
            dropdown.classList.add('active');
        }

        async function navigateToFamily(fid) {
            document.getElementById('searchDropdown').classList.remove('active');
            document.getElementById('globalSearch').value = '';

            // Switch view if not already in families
            if (currentView !== 'families') {
                switchMainView('families');
                // Give it a small delay for the DOM to update
                setTimeout(() => scrollToFamily(fid), 300);
            } else {
                scrollToFamily(fid);
            }
        }

        // ── Analytics Module ──────────────────────────
        let analyticsCharts = {};

        function renderAnalyticsCharts() {
            if (globalFamiliesData.length === 0) return;

            let totalHouseholds = globalFamiliesData.length;
            let totalPopulation = 0;
            let employedCount = 0;
            
            let areaCount = {};
            let financeCount = {};
            let bloodCount = {};
            let eduCount = {};

            globalFamiliesData.forEach(f => {
                totalPopulation += (f.members?.length || 0);
                
                const area = f.area || 'Unknown';
                areaCount[area] = (areaCount[area] || 0) + 1;
                
                const finance = f.financial_condition || 'Unknown';
                financeCount[finance] = (financeCount[finance] || 0) + 1;

                if (f.members) {
                    f.members.forEach(m => {
                        const job = m.job_status ? m.job_status.toLowerCase() : '';
                        if (job && job !== 'student' && job !== 'unemployed' && job !== 'housewife') {
                            employedCount++;
                        }
                        if (m.blood_group) {
                            bloodCount[m.blood_group] = (bloodCount[m.blood_group] || 0) + 1;
                        }
                        if (m.education) {
                            eduCount[m.education] = (eduCount[m.education] || 0) + 1;
                        }
                    });
                }
            });

            document.getElementById('kpiHouseholds').innerText = totalHouseholds;
            document.getElementById('kpiPopulation').innerText = totalPopulation;
            document.getElementById('kpiAvgSize').innerText = totalHouseholds ? (totalPopulation / totalHouseholds).toFixed(1) : 0;
            document.getElementById('kpiEmployed').innerText = employedCount;

            Chart.defaults.color = '#818cf8'; 
            Chart.defaults.font.family = "'system-ui', sans-serif";

            const createChart = (id, type, dataObj, bgColor, label) => {
                const ctx = document.getElementById(id);
                if (!ctx) return;
                if (analyticsCharts[id]) analyticsCharts[id].destroy();

                analyticsCharts[id] = new Chart(ctx, {
                    type: type,
                    data: {
                        labels: Object.keys(dataObj).map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                        datasets: [{
                            label: label,
                            data: Object.values(dataObj),
                            backgroundColor: bgColor,
                            borderWidth: 1,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: type === 'pie' || type === 'doughnut', position: 'right' } }
                    }
                });
            };

            const colors = ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#3b82f6', '#14b8a6'];

            createChart('chartArea', 'bar', areaCount, colors[0], 'Households');
            createChart('chartFinance', 'doughnut', financeCount, colors, 'Households');
            createChart('chartBlood', 'pie', bloodCount, colors, 'Members');
            
            const sortedEdu = Object.fromEntries(Object.entries(eduCount).sort(([,a],[,b]) => b-a).slice(0,10));
            createChart('chartEdu', 'bar', sortedEdu, colors[3], 'Members');
        }

        // ── Village Map Module ────────────────────────────
        let leafletMap = null;
        let mapMarkers = [];

        // Approximate GPS anchor coordinates for each village area
        const AREA_COORDS = {
            'purbo para':   [23.4820, 90.3870],
            'uttor para':   [23.4845, 90.3880],
            'dokhin para':  [23.4795, 90.3875],
            'roy bari':     [23.4830, 90.3855],
            'boshu para':   [23.4810, 90.3900],
            'babu para':    [23.4855, 90.3860],
            'porchim para': [23.4800, 90.3840],
        };
        const VILLAGE_CENTER = [23.4825, 90.3872];

        function parseLatLng(mapUrl) {
            if (!mapUrl) return null;
            // Try to extract coordinates from Google Maps URL formats
            const regQ   = /[?&]q=([-\d.]+),([-\d.]+)/;
            const regAt  = /@([-\d.]+),([-\d.]+)/;
            const regLL  = /ll=([-\d.]+),([-\d.]+)/;
            for (const reg of [regQ, regAt, regLL]) {
                const m = mapUrl.match(reg);
                if (m) return [parseFloat(m[1]), parseFloat(m[2])];
            }
            return null;
        }

        function renderVillageMap() {
            const container = document.getElementById('villageMap');
            if (!container) return;

            // Destroy and re-init if already exists (prevents grey tile bug)
            if (leafletMap) {
                leafletMap.remove();
                leafletMap = null;
            }

            leafletMap = L.map('villageMap', { zoomControl: true }).setView(VILLAGE_CENTER, 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(leafletMap);

            // Custom indigo pin icon
            const houseIcon = L.divIcon({
                className: '',
                html: `<div style="background:#4f46e5;width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(79,70,229,0.5);">
                         <span style="display:block;transform:rotate(45deg);text-align:center;line-height:28px;font-size:14px;">🏠</span>
                       </div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 34],
                popupAnchor: [0, -36]
            });

            let placed = 0;

            globalFamiliesData.forEach(f => {
                const coords = parseLatLng(f.google_map_location);
                
                if (coords) {
                    // Exact GPS pin
                    const memberCount = f.members?.length || 0;
                    const popup = `
                        <div style="font-family:system-ui;min-width:200px;">
                            <div style="font-size:1rem;font-weight:800;color:#312e81;margin-bottom:4px;">${f.house_owner_name}</div>
                            <div style="font-size:0.78rem;color:#6366f1;margin-bottom:8px;">📍 ${f.area || 'Unknown Area'} · House ${f.house_no || 'N/A'}</div>
                            <div style="font-size:0.78rem;color:#475569;">👥 ${memberCount} registered member${memberCount !== 1 ? 's' : ''}</div>
                            <div style="font-size:0.78rem;color:#475569;">💰 ${f.financial_condition || 'N/A'}</div>
                            <a href="${f.google_map_location}" target="_blank" style="display:inline-block;margin-top:8px;font-size:0.75rem;background:#4f46e5;color:#fff;padding:3px 10px;border-radius:99px;text-decoration:none;">Open in Maps ↗</a>
                        </div>`;
                    L.marker(coords, { icon: houseIcon }).addTo(leafletMap).bindPopup(popup);
                    placed++;
                } else {
                    // Area-based cluster with slight random offset
                    const areaKey = (f.area || '').toLowerCase();
                    const base = AREA_COORDS[areaKey] || VILLAGE_CENTER;
                    const jitter = [
                        base[0] + (Math.random() - 0.5) * 0.002,
                        base[1] + (Math.random() - 0.5) * 0.002
                    ];
                    const memberCount = f.members?.length || 0;
                    const popup = `
                        <div style="font-family:system-ui;min-width:180px;">
                            <div style="font-size:0.95rem;font-weight:800;color:#312e81;margin-bottom:4px;">${f.house_owner_name}</div>
                            <div style="font-size:0.75rem;color:#6366f1;margin-bottom:6px;">📍 ${f.area || 'Shidhlajury'}</div>
                            <div style="font-size:0.75rem;color:#94a3b8;font-style:italic;">Exact GPS not recorded</div>
                            <div style="font-size:0.75rem;color:#475569;margin-top:4px;">👥 ${memberCount} member${memberCount !== 1 ? 's' : ''}</div>
                        </div>`;
                    L.circleMarker(jitter, {
                        radius: 9, fillColor: '#a5b4fc', color: '#4f46e5',
                        weight: 2, opacity: 1, fillOpacity: 0.7
                    }).addTo(leafletMap).bindPopup(popup);
                }
            });

            // Fit map to all markers if any with GPS
            if (placed > 0) leafletMap.fitBounds(leafletMap.getBounds(), { padding: [30, 30] });
        }

        // ── Village Newsfeed Module ───────────────────────
        function renderNewsfeed() {
            if (globalFamiliesData.length === 0) return;

            const timeline = document.getElementById('newsfeedTimeline');
            const nfFam    = document.getElementById('nf-total-fam');
            const nfMem    = document.getElementById('nf-total-mem');
            const nfNewest = document.getElementById('nf-newest');
            const nfAreas  = document.getElementById('nf-areas');

            if (!timeline) return;

            const allMembers = globalFamiliesData.flatMap(f => (f.members || []).map(m => ({ ...m, family: f })));
            const totalFam = globalFamiliesData.length;
            const totalMem = allMembers.length;

            if (nfFam)    nfFam.innerText    = totalFam;
            if (nfMem)    nfMem.innerText    = totalMem;
            if (nfNewest) nfNewest.innerText = globalFamiliesData[totalFam - 1]?.house_owner_name || 'N/A';

            // Area breakdown
            const areaCount = {};
            globalFamiliesData.forEach(f => {
                const area = f.area || 'Unknown';
                areaCount[area] = (areaCount[area] || 0) + 1;
            });
            if (nfAreas) {
                nfAreas.innerHTML = Object.entries(areaCount)
                    .sort(([,a],[,b]) => b - a)
                    .map(([area, count]) => {
                        const pct = Math.round((count / totalFam) * 100);
                        return `<div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                                <span style="font-size:0.8rem;color:#475569;text-transform:capitalize;">${area}</span>
                                <span style="font-size:0.8rem;font-weight:700;color:#4f46e5;">${count}</span>
                            </div>
                            <div style="height:5px;background:#e0e7ff;border-radius:99px;overflow:hidden;">
                                <div style="height:100%;width:${pct}%;background:linear-gradient(90deg,#6366f1,#a855f7);border-radius:99px;"></div>
                            </div>
                        </div>`;
                    }).join('');
            }

            // Build auto-generated events timeline
            const events = [];

            // One event per family registration
            globalFamiliesData.forEach((f, i) => {
                events.push({
                    icon: '🏠',
                    color: '#4f46e5',
                    title: `New Household Registered`,
                    desc: `<strong>${f.house_owner_name}</strong> family of ${f.area || 'Shidhlajury'} was added to the registry.`,
                    sub:  `House No. ${f.house_no || 'N/A'} · ${f.financial_condition || ''}`
                });
            });

            // Highlight largest families
            const bigFamilies = [...globalFamiliesData]
                .filter(f => (f.members?.length || 0) >= 5)
                .sort((a, b) => (b.members?.length || 0) - (a.members?.length || 0))
                .slice(0, 3);
            bigFamilies.forEach(f => {
                events.unshift({
                    icon: '👨‍👩‍👧‍👦',
                    color: '#7c3aed',
                    title: 'Largest Household',
                    desc: `<strong>${f.house_owner_name}</strong>'s family has <strong>${f.members.length} members</strong> — one of the largest in the village.`,
                    sub: f.area || ''
                });
            });

            // Blood donor highlights
            const donors = allMembers.filter(m => m.blood_group && m.blood_group.trim());
            if (donors.length > 0) {
                events.unshift({
                    icon: '🩸',
                    color: '#e11d48',
                    title: `${donors.length} Registered Blood Group Records`,
                    desc: `The village registry includes <strong>${donors.length} members</strong> with known blood groups, ready for emergency coordination.`,
                    sub: [...new Set(donors.map(d => d.blood_group))].join(' · ')
                });
            }

            // Summary headline
            events.unshift({
                icon: '📜',
                color: '#059669',
                title: 'Village Registry Milestone',
                desc: `Shidhlajury's digital family archive now holds <strong>${totalFam} registered households</strong> with <strong>${totalMem} documented ancestors</strong>.`,
                sub: 'Shidhlajury Heritage Portal'
            });

            // Render timeline
            timeline.innerHTML = events.slice(0, 20).map((ev, i) => `
                <div style="display:flex;gap:16px;padding-bottom:24px;position:relative;">
                    <div style="display:flex;flex-direction:column;align-items:center;min-width:40px;">
                        <div style="width:40px;height:40px;background:${ev.color}18;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;border:2px solid ${ev.color}30;z-index:1;">
                            ${ev.icon}
                        </div>
                        ${i < events.slice(0,20).length - 1 ? `<div style="flex:1;width:2px;background:linear-gradient(to bottom, ${ev.color}30, transparent);margin-top:4px;"></div>` : ''}
                    </div>
                    <div style="background:white;border:1px solid #e0e7ff;border-radius:12px;padding:14px 16px;flex:1;box-shadow:0 1px 4px rgba(99,102,241,0.06);">
                        <div style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:${ev.color};margin-bottom:4px;">${ev.title}</div>
                        <div style="font-size:0.88rem;color:#1e1b4b;line-height:1.5;">${ev.desc}</div>
                        ${ev.sub ? `<div style="font-size:0.72rem;color:#94a3b8;margin-top:6px;">${ev.sub}</div>` : ''}
                    </div>
                </div>`).join('');
        }

        // Initialize (Restored to standard script block for immediate execution)
        window.addEventListener('DOMContentLoaded', () => {
            const lastView = localStorage.getItem('shidhlajury_last_view') || 'home';
            switchMainView(lastView);
            loadFamilies();
            startPolling();
        });
    </script>

    <script type="text/babel">
        // --- PREMIUM REACT TREE ENGINE ---
        const { useState, useEffect } = React;

        const MemberIcon = ({ member, currentFamilyId, onEdit }) => {
            const role = member.relation_to_owner || 'Member';
            
            // Determine ring color based on generation (depth) - simplified for now
            const ringClass = member.gen === 1 ? 'tw-ring-gen-1' : (member.gen === 2 ? 'tw-ring-gen-2' : 'tw-ring-gen-3');
            
            // Gender-based fallback image if none provided
            const avatarUrl = member.photo ? `uploads/${member.photo}` : 
                            `https://api.dicebear.com/7.x/avataaars/svg?seed=${member.id}&gender=${member.gender === 'Female' ? 'female' : 'male'}`;

            const isPendingNode = member.is_pending;
            const badgeLabel = isPendingNode ? '🕒 PENDING' : (member.relation_to_owner || 'Member');

            return (
                <div className="tw-flex tw-flex-col tw-items-center">
                    <div 
                        className={`tw-cursor-default tw-w-16 tw-h-16 tw-rounded-full tw-bg-white tw-shadow-md tw-overflow-hidden ${ringClass} ${isPendingNode ? 'tw-border-dashed tw-border-indigo-400' : ''}`}
                        title={isPendingNode ? "Pending Approval" : ""}
                    >
                        <img src={avatarUrl} alt={member.name} className="tw-w-full tw-h-full tw-object-cover" />
                    </div>
                    
                    <div className="tw-mt-2 tw-text-center">
                        <div className={`tw-px-3 tw-py-1 tw-rounded-full tw-border tw-shadow-sm ${isPendingNode ? 'tw-bg-indigo-900 tw-text-white tw-border-indigo-500' : 'tw-bg-indigo-50/80 tw-backdrop-blur-sm tw-border-indigo-100'}`}>
                            <div className={`tw-font-black tw-text-[0.8rem] tw-leading-tight ${isPendingNode ? 'tw-text-white' : 'tw-text-indigo-900'}`}>
                                {isPendingNode ? "🕒 PENDING" : member.name}
                            </div>
                            {isPendingNode && <div className="tw-text-[0.65rem] tw-font-bold tw-text-indigo-200">{member.name}</div>}
                        </div>
                    </div>
                </div>
            );
        };

        const PremiumTreeNode = ({ member, allMembers, processedIds, currentFamilyId, onEdit, gen = 1 }) => {
            if (processedIds.has(member.id)) return null;
            processedIds.add(member.id);

            const spouseMember = member.spouse_member_id ? allMembers.find(m => m.id == member.spouse_member_id) : null;
            if (spouseMember) processedIds.add(spouseMember.id);

            // Find children in same family OR cross-family branches
            let children = allMembers.filter(m => m.parent_member_id == member.id);
            
            // Find Virtual Children (Branch Roots)
            globalFamiliesData.forEach(f => {
                if (f.origin_member_id == member.id) {
                    const branchRoot = f.members?.find(m => m.relation_to_owner === 'Self (Owner)');
                    if (branchRoot && !children.some(c => c.id == branchRoot.id)) {
                        children.push(branchRoot);
                    }
                }
            });

            const currentMemberWithGen = { ...member, gen: gen };

            return (
                <li className="tw-flex tw-flex-col tw-items-center">
                    <div className={`tw-flex tw-items-center tw-gap-2 ${spouseMember ? 'tw-bg-indigo-50/30 tw-p-2 tw-rounded-xl tw-border tw-border-indigo-100/40 tw-shadow-[0_4px_20px_rgba(129,140,248,0.05)]' : ''}`}>
                        <MemberIcon member={currentMemberWithGen} currentFamilyId={currentFamilyId} onEdit={onEdit} />
                        
                        {spouseMember && (
                            <>
                                <div className="tw-flex tw-flex-col tw-items-center tw-px-1">
                                    <div className="tw-w-8 tw-h-[2px] tw-bg-indigo-200 tw-rounded-full"></div>
                                    <div className="tw-text-rose-400 tw-text-[0.7rem] tw-mt-[-4px]">❤️</div>
                                </div>
                                <MemberIcon member={{...spouseMember, gen: gen}} currentFamilyId={currentFamilyId} onEdit={onEdit} />
                            </>
                        )}
                    </div>

                    {children.length > 0 && (
                        <ul>
                            {children.map(child => (
                                <PremiumTreeNode 
                                    key={child.id} 
                                    member={child} 
                                    allMembers={allMembers} 
                                    processedIds={processedIds}
                                    currentFamilyId={currentFamilyId}
                                    onEdit={onEdit}
                                    gen={Math.min(gen + 1, 3)}
                                />
                            ))}
                        </ul>
                    )}
                </li>
            );
        };

        const TreeApp = ({ rootNode, allMembers, currentFamilyId }) => {
            const processedIds = new Set();
            
            return (
                <div className="tw-p-4 tw-flex tw-flex-col tw-items-center">
                    <ul className="tw-flex tw-justify-center">
                        <PremiumTreeNode 
                            member={rootNode} 
                            allMembers={allMembers} 
                            processedIds={processedIds}
                            currentFamilyId={currentFamilyId}
                            onEdit={(fid, mid) => window.openEditMemberModal(fid, mid)}
                            gen={1}
                        />
                    </ul>
                </div>
            );
        };

        // Bridge function to mount React from Vanilla JS
        window.renderReactTree = (rootNode, allMembers, currentFamilyId) => {
            const mountPoint = document.getElementById('premium-tree-mount');
            if (mountPoint) {
                const root = ReactDOM.createRoot(mountPoint);
                root.render(<TreeApp rootNode={rootNode} allMembers={allMembers} currentFamilyId={currentFamilyId} />);
            }
        };

    </script>
</body>
</html>

