/**
 * Dashboard Render Logic - Shidhlajury Refactoring
 * Handles rendering the member list, family directory, and tree
 */

function renderFamilies(filter = '') {
    const grid = document.getElementById('familiesGrid');
    if (!grid) return;
    grid.innerHTML = '';
    
    // Combine live families with pending new additions from globalPendingData
    let familiesToRender = [...globalFamiliesData];
    
    // Sort families by owner name
    familiesToRender.sort((a,b) => a.house_owner_name.localeCompare(b.house_owner_name));
    
    let filteredFamilies = familiesToRender.filter(f => {
        const matchesName = f.house_owner_name.toLowerCase().includes(filter);
        const matchesArea = (f.area || '').toLowerCase().includes(filter);
        const matchesMobile = (f.owner_mobile || '').toLowerCase().includes(filter);
        const matchesMembers = f.members?.some(m => m.name.toLowerCase().includes(filter) || (m.nick_name && m.nick_name.toLowerCase().includes(filter)));
        return matchesName || matchesArea || matchesMobile || matchesMembers;
    });

    if (filteredFamilies.length === 0) {
        grid.innerHTML = `<div style="grid-column: 1/-1; text-align:center; padding:3rem; color:var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            No families found matching your search.
        </div>`;
        return;
    }

    let html = '';
    filteredFamilies.forEach(f => {
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
                <button class="btn-sm btn-outline toggle-members-btn" style="width:100%;" onclick="openViewMembersModal(${f.id})">View Detailed Members List (${f.members.length})</button>
            </div>
        ` : '';

        const adminActions = (userRole === 'admin' || userRole === 'super_admin') ? `
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn-sm btn-outline" style="font-size:0.7rem;" onclick="openAddMemberToFamily('${f.id}')">+ Member</button>
                <button class="btn-sm btn-outline" style="border-color: #818cf8; color: #818cf8; font-size:0.7rem;" onclick='openEditFamilyModal(${JSON.stringify(f).replace(/'/g, "&#39;")})'>Edit</button>
                <button class="btn-sm btn-outline btn-danger" style="font-size:0.7rem;" onclick="deleteFamily('${f.id}')">Delete</button>
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

        const familyPhoto = f.photo_path ? `<img src="${f.photo_path}" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.2); margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">` : `
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #4f46e5); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                ${f.house_owner_name.charAt(0)}
            </div>`;

        const isPending = f.id.toString().startsWith('pending');
        const pendingBadge = isPending ? `<span class="badge badge-warning" style="font-size:0.6rem; vertical-align:middle;">⏳ PENDING</span>` : '';
        const cardClass = isPending ? 'family-card pending' : 'family-card';

        html += `
        <div class="${cardClass}" id="family-${f.id}" style="display: flex; flex-direction: column;">
            <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 1.5rem;">
                ${familyPhoto}
                <h3 style="margin: 0; color: white;">${f.house_owner_name} ${pendingBadge}</h3>
                <p style="color: var(--primary); font-weight: 500; font-size: 0.9rem; margin: 0.3rem 0;">📍 ${f.area ? f.area.toUpperCase() : 'N/A'}</p>
            </div>

            <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; text-transform: capitalize; text-align: left; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); flex: 1;">
                ${lineageHtml}
                📍 <strong>Area:</strong> ${f.area || 'N/A'} (House No: ${f.house_no || 'N/A'})<br>
                🏠 <strong>Type:</strong> ${f.type_of_house || 'N/A'}<br>
                💰 <strong>Finance:</strong> ${f.financial_condition || 'N/A'}<br>
                📱 <strong>Owner Mobile:</strong> ${f.owner_mobile || 'N/A'}<br>
                ${f.land ? `🌱 <strong>Land:</strong> ${f.land}<br>` : ''}
                ${f.members_of_house ? `👥 <strong>Members Count:</strong> ${f.members_of_house}<br>` : ''}
                ${f.temple_details ? `🕍 <strong>Temple:</strong> ${f.temple_details}<br>` : ''}
                ${f.google_map_location ? `🗺️ <strong>Map:</strong> <a href="${mapUrl}" target="_blank" style="color:#818cf8; text-transform:none;">View Location</a><br>` : ''}
            </div>
            ${viewMembersBtn}
            ${adminActions}
        </div>
        `;
    });
    grid.innerHTML = html;
}

async function openViewMembersModal(familyId) {
    currentViewFamilyId = familyId;
    const family = globalFamiliesData.find(f => f.id == familyId);
    if (!family) return;
    
    document.getElementById('viewMembersTitle').textContent = `${family.house_owner_name}'s Household`;
    document.getElementById('viewMembersModal').classList.add('active');
    
    // Default to list view
    switchMemberView('list');
}

function switchMemberView(view) {
    currentMemberView = view;
    const btnList = document.getElementById('btnListView');
    const btnTree = document.getElementById('btnTreeView');
    
    if (view === 'list') {
        btnList.className = 'btn-sm btn-primary';
        btnTree.className = 'btn-sm';
        btnTree.style.background = 'transparent';
        btnTree.style.color = 'var(--text-muted)';
        renderMemberList();
    } else {
        btnTree.className = 'btn-sm btn-primary';
        btnList.className = 'btn-sm';
        btnList.style.background = 'transparent';
        btnList.style.color = 'var(--text-muted)';
        renderFamilyTree();
    }
}

function getSiblings(m, allMembers) {
    if (!m) return [];
    
    return allMembers.filter(s => {
        if (s.id == m.id || (s.pendingId && `pending_${s.pendingId}` == m.id)) return false;
        
        // Match by Shared Parent
        if (m.parent_member_id && m.parent_member_id == s.parent_member_id) return true;
        
        // Match if one is Owner and other is Sibling
        if (m.relation_to_owner === 'Self (Owner)' && s.relation_to_owner === 'Sibling') return true;
        if (s.relation_to_owner === 'Self (Owner)' && m.relation_to_owner === 'Sibling') return true;
        
        // Match if both are Sibling (of the same owner)
        if (m.relation_to_owner === 'Sibling' && s.relation_to_owner === 'Sibling') return true;
        
        // Match if both are Child (of the same owner - i.e. siblings to each other)
        if (m.relation_to_owner === 'Child' && s.relation_to_owner === 'Child') return true;
        
        return false;
    });
}

function renderMemberList(familyId) {
    const family = globalFamiliesData.find(f => f.id == currentViewFamilyId);
    if (!family) return;
    
    const container = document.getElementById('viewMembersContent');
    container.innerHTML = '<ul class="member-list" id="memberListUl" style="list-style:none; padding:0;"></ul>';
    const list = document.getElementById('memberListUl');

    // Get combined list of live members and pending actions
    const members = getFamilyMembersWithPending(family.id);

    if (members.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-muted);">No members recorded yet.</div>';
        return;
    }

    let html = '';
    members.forEach(m => {
        const isPending = m.isPending;
        const mPendingBadge = isPending ? `<span class="badge badge-warning" style="font-size:0.5rem; padding:0.1rem 0.3rem; vertical-align: middle;">⏳ ${m.pendingLabel || 'PENDING'}</span>` : '';
        const mClass = isPending ? 'member-item pending' : 'member-item';
        
        // Deep Relationship Intelligence
        let relationsHtml = '';
        const householdParent = m.parent_member_id ? members.find(p => p.id == m.parent_member_id) : null;
        const householdSpouse = m.spouse_member_id ? members.find(s => s.id == m.spouse_member_id) : null;
        const householdChildren = members.filter(c => c.parent_member_id == m.id);
        const householdSiblings = getSiblings(m, members);
        
        const grandchildren = [];
        const inLaws = [];
        householdChildren.forEach(child => {
            const gc = members.filter(c => c.parent_member_id == child.id);
            grandchildren.push(...gc);
            const spouse = child.spouse_member_id ? members.find(s => s.id == child.spouse_member_id) : null;
            if (spouse) inLaws.push({ name: spouse.name, type: child.gender === 'Male' ? 'Daughter-in-law' : 'Son-in-law' });
        });

        const niecesNephews = [];
        householdSiblings.forEach(sib => {
            const children = members.filter(c => c.parent_member_id == sib.id);
            niecesNephews.push(...children);
        });

        if (householdParent || householdSpouse || householdChildren.length > 0 || householdSiblings.length > 0 || grandchildren.length > 0 || inLaws.length > 0 || niecesNephews.length > 0) {
            relationsHtml = `
            <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                ${householdParent ? `<span style="background: rgba(129, 140, 248, 0.1); color: #818cf8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">👨‍👦 Parent: ${householdParent.name}</span>` : ''}
                ${householdSpouse ? (
                    ['Brother-in-law', 'Sister-in-law', 'Son-in-law', 'Daughter-in-law'].includes(m.relation_to_owner) 
                    ? `<span style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">💍 Spouse of: ${householdSpouse.name}</span>`
                    : (['Brother-in-law', 'Sister-in-law', 'Son-in-law', 'Daughter-in-law'].includes(householdSpouse.relation_to_owner)
                        ? `<span style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">💍 Spouse: ${householdSpouse.name}</span>`
                        : `<span style="background: rgba(244, 63, 94, 0.1); color: #fb7185; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">💍 Spouse: ${householdSpouse.name}</span>`)
                ) : ''}
                ${householdSiblings.map(s => `<span style="background: rgba(129, 140, 248, 0.1); color: #818cf8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🤝 Sibling: ${s.name}</span>`).join('')}
                ${householdChildren.map(c => `<span style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">👶 Child: ${c.name}</span>`).join('')}
                ${grandchildren.map(g => `<span style="background: rgba(245, 158, 11, 0.1); color: #fbbf24; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🧑‍🍼 Grandchild: ${g.name}</span>`).join('')}
                ${inLaws.map(il => `<span style="background: rgba(168, 85, 247, 0.1); color: #c084fc; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">🏠 ${il.type}: ${il.name}</span>`).join('')}
                ${niecesNephews.map(nn => `<span style="background: rgba(6, 182, 212, 0.1); color: #22d3ee; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700;">👧 Niece/Nephew: ${nn.name}</span>`).join('')}
            </div>`;
        }

        html += `
        <li class="${mClass}" style="padding: 1rem 0; border-bottom: 1px dashed rgba(255, 255, 255, 0.1);">
            <div class="member-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <img src="${m.photo_path || `https://api.dicebear.com/7.x/avataaars/svg?seed=${m.id}&gender=${m.gender === 'Female' ? 'female' : 'male'}`}" 
                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid ${isPending ? 'var(--secondary)' : 'var(--primary)'};">
                    <div>
                        <span class="member-name" style="font-weight:700; color:white; font-size:1.1rem;">${m.name} ${m.nick_name ? `• ${m.nick_name}` : ''} ${mPendingBadge}</span>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="member-relation" style="display:block; width:fit-content; font-size:0.75rem; color:var(--secondary); background: rgba(236, 72, 153, 0.1); padding: 0.2rem 0.6rem; border-radius: 12px;">
                                ${m.relation_to_owner}
                            </span>
                        </div>
                        ${relationsHtml}
                    </div>
                </div>
                ${(userRole === 'admin' || userRole === 'super_admin') ? `
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn-sm btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick='openEditMemberModal(${family.id}, ${JSON.stringify(m).replace(/'/g, "&#39;")})'>Edit</button>
                        <button class="btn-sm btn-outline btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="deleteMember(${isPending ? `'pending_mem_${m.pendingId}'` : m.id})">Delete</button>
                    </div>
                ` : ''}
            </div>
            <div class="member-details" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; margin-top: 1rem; padding-left: 60px; font-size: 0.85rem; color: var(--text-muted);">
                ${m.blood_group ? `<div>🩸 <strong>Blood Group:</strong> ${m.blood_group}</div>` : ''}
                ${m.dob_dod ? `<div>📅 <strong>${m.dob_dod_type || 'DOB'}:</strong> ${m.dob_dod}</div>` : ''}
                ${m.mobile_number ? `<div>📱 <strong>Mobile:</strong> ${m.mobile_number}</div>` : ''}
                ${m.education ? `<div>🎓 <strong>Edu:</strong> ${m.education}</div>` : ''}
                ${m.job_status || m.job_details ? `
                    <div>💼 <strong>Job:</strong> ${m.job_status ? m.job_status : 'N/A'}${m.job_details ? ` - ${m.job_details}` : ''}</div>
                ` : ''}
                ${m.marital_status ? `<div>💍 <strong>Marital:</strong> ${m.marital_status}${m.spouse_name ? ` (Spouse: ${m.spouse_name})` : ''}</div>` : ''}
                ${m.date_of_marriage ? `<div>🎉 <strong>Marriage:</strong> ${m.date_of_marriage}</div>` : ''}
                ${m.in_laws_village ? `<div>🏡 <strong>In-law's Village:</strong> ${m.in_laws_village}</div>` : ''}
                ${m.in_laws_father_name ? `<div>👤 <strong>In-law's Father:</strong> ${m.in_laws_father_name}</div>` : ''}
                ${m.others ? `<div style="grid-column: 1/-1; color: #94a3b8; font-style: italic; margin-top:0.3rem;">📝 ${m.others}</div>` : ''}
            </div>
        </li>`;
    });
    list.innerHTML = html;
}

function getFamilyMembersWithPending(familyId) {
    const family = globalFamiliesData.find(f => f.id == familyId);
    let members = family.members ? [...family.members] : [];
    
    // Inject pending members
    globalPendingData.forEach(p => {
        let payload = {};
        try { payload = JSON.parse(p.payload); } catch(e) {}
        
        if (p.action_type === 'add_member' && payload.family_id == familyId) {
            members.push({ ...payload, isPending: true, pendingId: p.id, pendingLabel: 'NEW' });
        }
        if (p.action_type === 'edit_member' && payload.family_id == familyId) {
            const idx = members.findIndex(m => m.id == p.target_id);
            if (idx !== -1) members[idx] = { ...members[idx], ...payload, isPending: true, pendingId: p.id, pendingLabel: 'UPDATED' };
        }
        if (p.action_type === 'delete_member' && p.target_id) {
            const idx = members.findIndex(m => m.id == p.target_id);
            if (idx !== -1) members[idx].isPending = true; // Mark as pending deletion
        }
    });

    return members;
}

function renderFamilyTree() {
    const container = document.getElementById('viewMembersContent');
    container.innerHTML = `
        <div class="premium-tree-container">
            <div id="premium-tree-mount"></div>
        </div>
    `;
    
    const family = globalFamiliesData.find(f => f.id == currentViewFamilyId);
    const members = getFamilyMembersWithPending(family.id);
    
    // FIND ALL MEMBERS WITH NO PARENT IN THIS HOUSEHOLD (Absolute Roots)
    const absoluteRoots = members.filter(m => {
        if (!m.parent_member_id) return true;
        const parentInHousehold = members.find(p => {
            const pId = p.id || `pending_mem_${p.pendingId}`;
            return String(pId) === String(m.parent_member_id);
        });
        return !parentInHousehold;
    });

    if (absoluteRoots.length > 1) {
        // Create a VIRTUAL ROOT to link these siblings/roots together
        const virtualRoot = {
            id: 'virtual_root',
            name: 'Household Lineage',
            relation_to_owner: 'Family Ancestors',
            isVirtual: true,
            gender: 'Male'
        };
        
        // Point all absolute roots to this virtual parent for the tree engine
        const treeMembers = [virtualRoot, ...members.map(m => {
            const isRoot = absoluteRoots.find(r => (r.id && r.id == m.id) || (r.pendingId && r.pendingId == m.pendingId));
            if (isRoot) return { ...m, parent_member_id: 'virtual_root' };
            return m;
        })];
        
        window.renderReactTree(virtualRoot, treeMembers, family.id);
    } else if (absoluteRoots.length === 1) {
        window.renderReactTree(absoluteRoots[0], members, family.id);
    } else {
        document.getElementById('premium-tree-mount').innerHTML = '<div style="padding:2rem; text-align:center; color:white;">No data for tree visualization.</div>';
    }
}
