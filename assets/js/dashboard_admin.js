/**
 * Dashboard Admin Logic - Shidhlajury Refactoring
 * Handles Admin/Super-Admin specific features: User Management, Approval Queue, Recycle Bin
 */

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
        const date = new Date(a.created_at).toLocaleString();
        const payload = (() => { try { return JSON.parse(a.payload); } catch(e) { return {}; } })();
        const detail = payload.name || payload.owner_name || (a.target_id ? `ID: ${a.target_id}` : '—');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${a.submitted_by_username}</strong></td>
            <td><span class="status-pill action-pill">${a.action_type.replace('_', ' ').toUpperCase()}</span></td>
            <td style="color: var(--text-muted);">${detail}</td>
            <td style="color: var(--text-muted); font-size: 0.8rem;">${date}</td>
            <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick='viewPendingDetails(${JSON.stringify(a).replace(/'/g, "&#39;")})'>🔍 View</button>
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
    showToast(`${decision === 'approved' ? '✅ Approving' : '❌ Rejecting'} ${label}...`, 'info');
    const res = await apiCall('review_action', { id, decision, note });
    if (res.status === 'success') {
        showToast(`✅ ${label} ${decision} successfully.`);
        loadPendingActions();
        loadFamilies();
        loadNotifications();
    } else {
        showToast('❌ ' + res.message, 'error');
    }
}

function promptReject(id, detail = '') {
    const note = prompt(`Enter a reason for rejecting ${detail || 'this record'} (optional):`);
    if (note === null) return;
    reviewAction(id, 'rejected', note || '', detail);
}

// ── Recycle Bin ─────────────────────────
async function loadRecycleBin() {
    const res = await apiCall('get_recycle_bin');
    if (res.status === 'success') renderRecycleBin(res.families, res.members);
}

function renderRecycleBin(families, members) {
    const fBody = document.getElementById('binFamiliesTable');
    const mBody = document.getElementById('binMembersTable');
    if (!fBody || !mBody) return;
    
    fBody.innerHTML = families.length === 0 ? '<tr><td colspan="3" style="text-align:center; padding:2rem; color:var(--text-muted);">Bin is empty</td></tr>' : '';
    families.forEach(f => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${f.house_owner_name}</td><td>${new Date(f.deleted_at).toLocaleString()}</td><td><button class="btn-sm btn-outline" onclick="restoreItem('family', ${f.id})">🔄 Restore</button></td>`;
        fBody.appendChild(tr);
    });

    mBody.innerHTML = members.length === 0 ? '<tr><td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">Bin is empty</td></tr>' : '';
    members.forEach(m => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${m.name}</td><td>#${m.family_id}</td><td>${new Date(m.deleted_at).toLocaleString()}</td><td><button class="btn-sm btn-outline" onclick="restoreItem('member', ${m.id})">🔄 Restore</button></td>`;
        mBody.appendChild(tr);
    });
}

async function restoreItem(type, id) {
    if (!confirm(`Restore this ${type}?`)) return;
    const res = await apiCall('restore_item', { type, id });
    if (res.status === 'success') {
        showToast(res.message);
        loadRecycleBin();
        loadFamilies();
    }
}

// ── User Management ───────────────────────
function renderUsers(users) {
    const tbody = document.getElementById('userTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${u.username}</td>
            <td><span class="role-badge">${u.role}</span></td>
            <td>${new Date(u.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn-icon" onclick='openEditUserModal(${JSON.stringify(u).replace(/'/g, "&#39;")})'>✏️</button>
                <button class="btn-icon delete" onclick="deleteUser(${u.id})">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}
