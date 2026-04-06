<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Family Information Portal</title>
    
    <!-- FOUC Prevention Script: Parse active tab before DOM rendering -->
    <script>
        const savedView = localStorage.getItem('activeDashboardTab') || 'home';
        document.documentElement.setAttribute('data-active-view', savedView);
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script>
        // JS Session Bridge: Pass PHP state to external JS modules
        window.DashboardApp = {
            config: {
                ROLE: '<?php echo $role; ?>',
                USER_ID: '<?php echo $user_id; ?>',
                USERNAME: '<?php echo $username; ?>'
            }
        };
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
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
                <div>
                    <h1 style="margin:0; font-size: 1.8rem; font-weight: 600;">Welcome, <?php echo htmlspecialchars($username); ?></h1>
                </div>
                <div class="user-profile" style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="search-container" style="flex: 1; max-width: 400px; position: relative;">
                        <input type="text" id="globalSearch" class="form-control" placeholder="🔍 Search Family, Name, Mobile, Area..." style="margin-bottom: 0; padding-left: 2.5rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
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

                    <div class="tw-flex tw-justify-center tw-gap-8 tw-mb-10">
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
                <div style="color:var(--text-muted);">Loading families...</div>
            </div>
        </main>
    </div>

    <?php include 'includes/modals.php'; ?>

    <!-- Modular Dashboard Scripts -->
    <script src="assets/js/dashboard_core.js"></script>
    <script src="assets/js/dashboard_crud.js"></script>
    <script src="assets/js/dashboard_render.js"></script>
    <script src="assets/js/dashboard_features.js"></script>
    <script src="assets/js/dashboard_admin.js"></script>
    <script type="text/babel" src="assets/js/tree_engine.js"></script>
</body>
</html>
