/**
 * Dashboard Core Logic - Shidhlajury Refactoring
 * Handles UI, Modals, and core navigation
 */

// Core API Helpers
async function apiCall(action, data = {}) {
    const formData = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) {
        for (const key in data) formData.append(key, data[key]);
    }
    formData.append('action', action);
    
    try {
        const response = await fetch('api/routes.php', {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { status: 'error', message: 'Network or Server Error' };
    }
}

async function authCall(action, data = {}) {
    const formData = new FormData();
    for (const key in data) formData.append(key, data[key]);
    formData.append('action', action);
    
    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        return result;
    } catch (error) {
        return { status: 'error', message: 'Authentication Error' };
    }
}

function startPolling() {
    if (userRole === 'super_admin' || userRole === 'admin') {
        loadPendingActions();
    }
    loadNotifications();
    setInterval(() => {
        if (userRole === 'super_admin' || userRole === 'admin') loadPendingActions();
        loadNotifications();
    }, 30000); // 30s polling
}

async function loadNotifications() {
    const nRes = await apiCall('get_notifications');
    const pRes = (userRole === 'super_admin') ? await apiCall('get_pending_count') : {count: 0};
    if (nRes.status === 'success') {
        const badge = document.getElementById('notif-badge');
        const count = (nRes.unread_count || 0) + (pRes.count || 0);
        if(badge) { 
            badge.textContent = count; 
            badge.style.display = count > 0 ? 'flex' : 'none'; 
        }
    }
}

// Initialize local variables from the bridge
const userRole = window.DashboardApp?.config?.ROLE || 'user';
let globalFamiliesData = [];
let globalPendingData = []; // To store current user's pending actions
let currentViewFamilyId = null;
let currentMemberView = 'list';
let currentView = localStorage.getItem('activeDashboardTab') || 'home';

async function logout() {
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
    let html = '<option value="">🏠 No Parent Family (New Root Household)</option>';
    globalFamiliesData.forEach(f => {
        if (f.id != excludeId) {
            html += `<option value="${f.id}" ${f.id == selectedId ? 'selected' : ''}>${f.house_owner_name} (${f.area})</option>`;
        }
    });
    select.innerHTML = html;
}

function populateOriginMembers(familyId, selectedMemberId = '') {
    const group = document.getElementById('origin_member_group');
    const select = document.getElementById('origin_member_id');
    if (!familyId) { group.style.display = 'none'; return; }
    const family = globalFamiliesData.find(f => f.id == familyId);
    if (family && family.members) {
        let html = '<option value="">Select Member</option>';
        family.members.forEach(m => {
            html += `<option value="${m.id}" ${m.id == selectedMemberId ? 'selected' : ''}>${m.name} (${m.relation_to_owner})</option>`;
        });
        select.innerHTML = html;
        group.style.display = 'block';
    } else { group.style.display = 'none'; }
}

function populateMemberDropdowns(familyId, currentMemberId = null) {
    const pSelect = document.getElementById('parent_member_id');
    const sSelect = document.getElementById('spouse_member_id');
    const sibSelect = document.getElementById('sibling_member_id');
    
    if (!pSelect || !sSelect || !sibSelect) return;

    const family = globalFamiliesData.find(f => f.id == familyId);
    if (!family) return;

    // Get all members, including those currently being seen via getFamilyMembersWithPending
    const members = typeof getFamilyMembersWithPending === 'function' 
        ? getFamilyMembersWithPending(familyId) 
        : (family.members || []);

    let html = '<option value="">Select Member</option>';
    members.forEach(m => {
        // Exclude current member to avoid self-referencing
        if (m.id == currentMemberId || (m.pendingId && `pending_${m.pendingId}` == currentMemberId)) return;
        
        const mId = m.id || `pending_mem_${m.pendingId}`;
        html += `<option value="${mId}">${m.name} (${m.relation_to_owner}${m.pendingId ? ' - PENDING' : ''})</option>`;
    });

    pSelect.innerHTML = html;
    sSelect.innerHTML = html;
    sibSelect.innerHTML = html;
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

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast-notification ' + type;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('visible'), 50);
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => document.body.removeChild(toast), 400);
    }, 4000);
}

// UI Setup Listeners
function setupUIListeners() {
    const area = document.getElementById('area');
    if (area) area.addEventListener('change', () => toggleOther('area', 'other_area_group', 'other_area_name'));
    
    const typeOfHouse = document.getElementById('type_of_house');
    if (typeOfHouse) typeOfHouse.addEventListener('change', () => toggleOther('type_of_house', 'other_house_type_group', 'other_house_type'));
    
    const financialCondition = document.getElementById('financial_condition');
    if (financialCondition) financialCondition.addEventListener('change', () => toggleOther('financial_condition', 'other_finance_group', 'other_finance'));
    
    const parentFamilyId = document.getElementById('parent_family_id');
    if (parentFamilyId) parentFamilyId.addEventListener('change', (e) => populateOriginMembers(e.target.value));
    
    const bloodGroup = document.getElementById('blood_group');
    if (bloodGroup) bloodGroup.addEventListener('change', () => toggleOther('blood_group', 'other_blood_group', 'other_blood'));
    
    const education = document.getElementById('education');
    if (education) education.addEventListener('change', () => toggleOther('education', 'other_edu_group', 'other_edu'));
    
    const memberJob = document.getElementById('member_job');
    if (memberJob) memberJob.addEventListener('change', () => toggleOther('member_job', 'other_job_group', 'other_job'));
    
    const memberMarital = document.getElementById('member_marital');
    if (memberMarital) {
        memberMarital.addEventListener('change', function() {
            const val = this.value;
            toggleOther('member_marital', 'other_marital_group', 'other_marital');
            
            const marriageFields = document.getElementById('dynamic_marriage_fields');
            if (marriageFields) {
                if (val === 'Married') marriageFields.style.display = 'block';
                else marriageFields.style.display = 'none';
            }
        });
    }

    const memberRelation = document.getElementById('member_relation');
    if (memberRelation) {
        memberRelation.addEventListener('change', function() {
            const val = this.value;
            toggleOther('member_relation', 'other_relation_group', 'other_relation');

            const needsParent = ['Self (Owner)', 'Child', 'Niece', 'Grand Child', 'Other', 'Parent', 'Sibling'];
            const needsSpouse = ['Spouse', 'Son-in-law', 'Daughter-in-law', 'Brother-in-law', 'Sister-in-law'];
            
            const pGroup = document.getElementById('parent_member_group');
            const sGroup = document.getElementById('spouse_member_group');
            const sibGroup = document.getElementById('sibling_member_group');
            const cTypeGroup = document.getElementById('child_type_group');

            // Parent Label logic as requested
            if (pGroup) {
                const parentLabel = pGroup.querySelector('label');
                if (val === 'Parent') {
                    parentLabel.innerHTML = `<span style="color:var(--secondary); font-weight:700;">🧬 Parent of which Registered Member?</span>`;
                } else {
                    parentLabel.innerText = "Father's / Parent's Name (Registered Member)";
                }
            }

            // Spouse Label logic for In-laws as requested
            if (sGroup) {
                const spouseLabel = sGroup.querySelector('label');
                if (['Son-in-law', 'Daughter-in-law', 'Brother-in-law', 'Sister-in-law'].includes(val)) {
                    spouseLabel.innerHTML = `<span style="color:var(--secondary); font-weight:700;">💍 Spouse of which Registered Member?</span>`;
                } else {
                    spouseLabel.innerText = "Select Spouse (Registered Member)";
                }
            }

            if (pGroup) pGroup.style.display = needsParent.includes(val) ? 'block' : 'none';
            if (sGroup) sGroup.style.display = needsSpouse.includes(val) ? 'block' : 'none';
            if (sibGroup) sibGroup.style.display = (val === 'Sibling') ? 'block' : 'none';
            if (cTypeGroup) cTypeGroup.style.display = (val === 'Child') ? 'block' : 'none';

            if (pGroup && pGroup.style.display === 'none') {
                const pSelect = document.getElementById('parent_member_id');
                if (pSelect) pSelect.value = '';
            }
            if (sGroup && sGroup.style.display === 'none') {
                const sSelect = document.getElementById('spouse_member_id');
                if (sSelect) sSelect.value = '';
            }
            if (sibGroup && sibGroup.style.display === 'none') {
                const sibSelect = document.getElementById('sibling_member_id');
                if (sibSelect) sibSelect.value = '';
            }
            if (cTypeGroup && cTypeGroup.style.display === 'none') {
                const ctSelect = document.getElementById('child_type');
                if (ctSelect) ctSelect.value = '';
            }
        });
    }
    
    const childType = document.getElementById('child_type');
    if (childType) childType.addEventListener('change', () => toggleOther('child_type', 'other_child_type_group', 'other_child_type'));

    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) globalSearch.addEventListener('input', (e) => renderFamilies(e.target.value.toLowerCase()));
}

document.addEventListener('DOMContentLoaded', () => {
    setupUIListeners();
    const savedView = localStorage.getItem('activeDashboardTab') || 'home';
    switchMainView(savedView);
    loadFamilies();
    startPolling();
});

// Switch Main View logic
function switchMainView(view) {
    currentView = view;
    localStorage.setItem('activeDashboardTab', view);
    document.documentElement.setAttribute('data-active-view', view); 
    
    const views = [
        'homePortfolioWrapper', 'analyticsView', 'mapView', 'newsfeedView', 
        'familiesDirectoryHeader', 'familiesGrid', 'userManagementView', 
        'approvalQueueView', 'recycleBinView'
    ];
    
    const filters = document.querySelector('.filter-container');
    if (filters) filters.style.display = (view === 'families') ? 'flex' : 'none';

    views.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (id === 'familiesGrid' && view === 'families') el.style.display = 'grid';
        else if (id.includes(view + 'View') || (view === 'families' && id.includes('families')) || (view === 'home' && id === 'homePortfolioWrapper')) {
             el.style.display = 'block';
        } else {
             el.style.display = 'none';
        }
    });
    
    // Update active nav state
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    const activeNav = document.getElementById('nav-' + view);
    if (activeNav) activeNav.classList.add('active');

    // Trigger module loaders
    if (view === 'analytics') setTimeout(() => renderAnalyticsCharts(), 100);
    if (view === 'map') setTimeout(() => renderVillageMap(), 200);
    if (view === 'newsfeed') setTimeout(() => renderNewsfeed(), 100);
    if (view === 'users') loadUsers();
    if (view === 'approvals') loadPendingActions();
    if (view === 'bin') loadRecycleBin();
}

function openEditFamilyModal(f) {
    document.getElementById('family_action').value = 'update_family';
    document.getElementById('family_edit_id').value = f.id || '';
    document.getElementById('family_pending_id').value = f.pending_id || '';
    document.getElementById('owner_name').value = f.house_owner_name;
    document.getElementById('owner_father_name').value = f.owner_father_name || '';
    document.getElementById('owner_mother_name').value = f.owner_mother_name || '';
    document.getElementById('house_no').value = f.house_no || '';
    document.getElementById('area').value = f.area || 'purbo para';
    document.getElementById('owner_mobile').value = f.owner_mobile || '';
    document.getElementById('type_of_house').value = f.type_of_house || 'building';
    document.getElementById('financial_condition').value = f.financial_condition || 'middle class';
    document.getElementById('google_map_location').value = f.google_map_location || '';
    document.getElementById('land').value = f.land || '';
    document.getElementById('members_of_house').value = f.members_of_house || '';
    document.getElementById('temple_details').value = f.temple_details || '';
    
    const preview = document.getElementById('family_photo_preview');
    if (f.photo_path) {
        preview.style.display = 'block';
        preview.querySelector('img').src = f.photo_path;
    } else {
        preview.style.display = 'none';
    }

    populateParentFamilies(f.parent_family_id, f.id);
    populateOriginMembers(f.parent_family_id, f.origin_member_id);

    document.querySelector('#addFamilyModal h3').textContent = 'Edit Household Info';
    document.getElementById('addFamilyModal').classList.add('active');
}

function openAddMemberToFamily(familyId) {
    document.getElementById('member_action').value = 'add_member';
    document.getElementById('addMemberForm').reset();
    document.getElementById('member_family_id').value = familyId;
    document.getElementById('member_edit_id').value = '';
    document.getElementById('member_pending_id').value = '';
    document.getElementById('member_photo_preview').style.display = 'none';
    
    // Hide all sub-menu groups initially
    const groups = ['parent_member_group', 'spouse_member_group', 'sibling_member_group', 'child_type_group', 'other_relation_group', 'dynamic_marriage_fields'];
    groups.forEach(gid => { const g = document.getElementById(gid); if(g) g.style.display = 'none'; });

    populateMemberDropdowns(familyId);

    document.querySelector('#addMemberModal h3').textContent = 'Add Family Member';
    document.getElementById('addMemberModal').classList.add('active');
}

function openEditMemberModal(familyId, m) {
    document.getElementById('member_action').value = 'edit_member';
    document.getElementById('member_edit_id').value = m.id || '';
    document.getElementById('member_family_id').value = familyId;
    document.getElementById('member_name').value = m.name;
    document.getElementById('nick_name').value = m.nick_name || '';
    document.getElementById('gender').value = m.gender || '';
    document.getElementById('member_relation').value = m.relation_to_owner || 'Other';
    document.getElementById('dob_dod').value = m.dob_dod || '';
    document.getElementById('member_mobile').value = m.mobile_number || '';
    document.getElementById('blood_group').value = m.blood_group || '';
    document.getElementById('education').value = m.education || '';
    document.getElementById('member_job').value = m.job_status || '';
    document.getElementById('job_details').value = m.job_details || '';
    document.getElementById('member_marital').value = m.marital_status || 'Single';

    // Populate and pre-select relationships
    populateMemberDropdowns(familyId, m.id);
    document.getElementById('parent_member_id').value = m.parent_member_id || '';
    document.getElementById('spouse_member_id').value = m.spouse_member_id || '';
    
    // Trigger relation toggle to show current groups
    const relTrigger = document.getElementById('member_relation');
    if (relTrigger) relTrigger.dispatchEvent(new Event('change'));
    
    // Trigger marital toggle
    const maritalTrigger = document.getElementById('member_marital');
    if (maritalTrigger) maritalTrigger.dispatchEvent(new Event('change'));

    const preview = document.getElementById('member_photo_preview');
    if (m.photo_path) {
        preview.style.display = 'block';
        preview.querySelector('img').src = m.photo_path;
    } else {
        preview.style.display = 'none';
    }

    document.querySelector('#addMemberModal h3').textContent = 'Edit Member Details';
    document.getElementById('addMemberModal').classList.add('active');
}

function detectLocation() {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser.");
        return;
    }
    showToast("Detecting coordinates...", "info");
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const val = `${pos.coords.latitude},${pos.coords.longitude}`;
            document.getElementById('google_map_location').value = `https://www.google.com/maps?q=${val}`;
            showToast("Location detected successfully!", "success");
        },
        (err) => {
            alert(`Error: ${err.message}`);
        }
    );
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

function scrollToFamily(id) {
    if (currentView !== 'families') switchMainView('families');
    setTimeout(() => {
        const el = document.getElementById('family-' + id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.style.boxShadow = '0 0 30px var(--primary)';
            setTimeout(() => el.style.boxShadow = '', 2000);
        }
    }, 100);
}
