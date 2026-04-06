/**
 * Dashboard CRUD Logic - Shidhlajury Refactoring
 * Handles data persistence and retrieval
 */

async function loadFamilies() {
    const res = await apiCall('get_families');
    if (res.status === 'success') {
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
        const grid = document.getElementById('familiesGrid');
        if (grid) grid.innerHTML = `<div style="color:var(--secondary); text-align:center; padding:2rem;">${res.message}</div>`;
    }
}

async function saveFamily(formData) {
    const action = document.getElementById('family_action').value;
    const res = await apiCall(action, formData);
    if (res.status === 'success' || res.status === 'pending') {
        closeModal('addFamilyModal');
        document.getElementById('addFamilyForm').reset();
        document.getElementById('family_photo_preview').style.display = 'none';
        document.getElementById('origin_member_group').style.display = 'none';
        loadFamilies();
        const msg = (res.status === 'pending') 
            ? (action === 'update_pending_action' ? '✏️ Changes sent for approval.' : '✅ Family request submitted!')
            : (res.message || '✅ Family saved successfully!');
        showToast(msg, res.status === 'pending' ? 'info' : 'success');
    } else {
        showToast('❌ ' + (res.message || 'Something went wrong.'), 'error');
    }
}

async function saveMember(formData) {
    const action = document.getElementById('member_action').value;
    const res = await apiCall(action, formData);
    if (res.status === 'success' || res.status === 'pending') {
        closeModal('addMemberModal');
        document.getElementById('addMemberForm').reset();
        document.getElementById('member_photo_preview').style.display = 'none';
        document.getElementById('sibling_member_group').style.display = 'none';
        loadFamilies();
        const msg = (res.status === 'pending')
            ? (action === 'update_pending_action' ? '✏️ Changes sent for approval.' : '✅ Member request submitted!')
            : (res.message || '✅ Member saved successfully!');
        showToast(msg, res.status === 'pending' ? 'info' : 'success');
    } else {
        alert(res.message);
    }
}

async function deleteFamily(id) {
    if (String(id).startsWith('pending_')) {
        if(!confirm("Are you sure you want to discard this pending family request?")) return;
        const res = await apiCall('delete_pending_action', { pending_id: String(id).replace('pending_', '') });
        if(res.status === 'success') {
            showToast("Pending family request discarded", "success");
            loadFamilies();
        } else {
            alert(res.message);
        }
        return;
    }

    if(!confirm("Are you sure you want to delete this family?")) return;
    const res = await apiCall('delete_family', { id });
    if(res.status === 'success' || res.status === 'pending') {
        if (res.status === 'pending') alert("✅ Delete Request Submitted");
        else { loadFamilies(); showToast(res.message); }
    } else { alert(res.message); }
}

async function deleteMember(id) {
    if (String(id).startsWith('pending_mem_')) {
        if(!confirm("Are you sure you want to discard this pending member request?")) return;
        const res = await apiCall('delete_pending_action', { pending_id: String(id).replace('pending_mem_', '') });
        if(res.status === 'success') {
            showToast("Pending member request discarded", "success");
            loadFamilies();
            const viewModal = document.getElementById('viewMembersModal');
            if (viewModal.classList.contains('active')) {
                if (currentMemberView === 'list') renderMemberList();
                else renderFamilyTree();
            }
        } else { alert(res.message); }
        return;
    }

    if(!confirm("Are you sure you want to delete this family member?")) return;
    const res = await apiCall('delete_member', { id });
    if(res.status === 'success' || res.status === 'pending') {
        if (res.status === 'pending') alert("✅ Delete Request Submitted");
        else { loadFamilies(); showToast(res.message); }
    } else { alert(res.message); }
}

// User CRUD
async function loadUsers() {
    const res = await apiCall('get_users');
    if (res.status === 'success') renderUsers(res.users);
}

// Form Listeners
function setupFormListeners() {
    const addFamilyForm = document.getElementById('addFamilyForm');
    if (addFamilyForm) {
        addFamilyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addFamilyForm);
            
            // Handle Other Area
            let finalArea = document.getElementById('area').value;
            if (finalArea === 'others') finalArea = document.getElementById('other_area_name').value;
            formData.set('area', finalArea);

            // Handle Other House Type
            let finalType = document.getElementById('type_of_house').value;
            if (finalType === 'others') finalType = document.getElementById('other_house_type').value;
            formData.set('type_of_house', finalType);

            // Handle Other Finance
            let finalFinance = document.getElementById('financial_condition').value;
            if (finalFinance === 'others') finalFinance = document.getElementById('other_finance').value;
            formData.set('financial_condition', finalFinance);
            
            // Add ID if editing
            formData.set('id', document.getElementById('family_edit_id').value);
            formData.set('pending_id', document.getElementById('family_pending_id').value);

            await saveFamily(formData);
        });
    }

    const addMemberForm = document.getElementById('addMemberForm');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addMemberForm);
            
            // Handle Custom Relation
            const rel = document.getElementById('member_relation').value;
            if (rel === 'Other') formData.set('relation_to_owner', document.getElementById('other_relation').value);
            
            // Handle Custom Child Type
            const childType = document.getElementById('child_type').value;
            if (childType === 'Other') formData.set('child_type', document.getElementById('other_child_type').value);

             // Handle Custom Blood Group
            const blood = document.getElementById('blood_group').value;
            if (blood === 'Others') formData.set('blood_group', document.getElementById('other_blood').value);

            // Handle Custom Education
            const edu = document.getElementById('education').value;
            if (edu === 'Others') formData.set('education', document.getElementById('other_edu').value);

            // Handle Custom Job
            const job = document.getElementById('member_job').value;
            if (job === 'Others') formData.set('job_status', document.getElementById('other_job').value);

            // Handle Sibling inheritance
            const siblingId = document.getElementById('sibling_member_id').value;
            const manualParentId = document.getElementById('parent_member_id').value;
            
            if (rel === 'Sibling' && siblingId && !manualParentId) {
                let parentId = null;
                globalFamiliesData.forEach(f => {
                    const sibling = f.members?.find(m => m.id == siblingId);
                    if (sibling) parentId = sibling.parent_member_id;
                });
                if (parentId) formData.set('parent_member_id', parentId);
            }

            formData.set('id', document.getElementById('member_edit_id').value);
            formData.set('pending_id', document.getElementById('member_pending_id').value);

            await saveMember(formData);
        });
    }

    // Auto-inherit parent when sibling is selected
    const siblingSelect = document.getElementById('sibling_member_id');
    if (siblingSelect) {
        siblingSelect.addEventListener('change', function() {
            const siblingId = this.value;
            if (!siblingId) return;
            
            let parentId = null;
            globalFamiliesData.forEach(f => {
                const sibling = f.members?.find(m => m.id == siblingId);
                if (sibling) parentId = sibling.parent_member_id;
            });
            
            if (parentId) {
                document.getElementById('parent_member_id').value = parentId;
                showToast("🧬 Parent linked automatically from sibling.", "success");
            }
        });
    }

    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = document.getElementById('user_action').value;
            const formData = new FormData(addUserForm);
            formData.append('id', document.getElementById('user_edit_id').value);

            const res = await apiCall(action, formData);
            if (res.status === 'success') {
                closeModal('addUserModal');
                loadUsers();
            } else {
                alert(res.message);
            }
        });
    }
}

// Attach Form Listeners on startup
document.addEventListener('DOMContentLoaded', () => {
    setupFormListeners();
});

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    const formData = new FormData();
    formData.append('id', id);
    const res = await apiCall('delete_user', formData);
    if (res.status === 'success') loadUsers();
}
