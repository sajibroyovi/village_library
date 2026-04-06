/**
 * Premium React Tree Engine - Shidhlajury Refactoring
 * Handles interactive family tree visualization
 */

const { useState, useEffect } = React;

const MemberIcon = ({ member, currentFamilyId, onEdit }) => {
    const role = member.relation_to_owner || 'Member';
    const ringClass = member.gen === 1 ? 'tw-ring-gen-1' : (member.gen === 2 ? 'tw-ring-gen-2' : 'tw-ring-gen-3');
    const avatarUrl = member.photo ? `uploads/${member.photo}` : 
                    `https://api.dicebear.com/7.x/avataaars/svg?seed=${member.id}&gender=${member.gender === 'Female' ? 'female' : 'male'}`;
    const isVirtual = member.isVirtual;
    
    return (
        <div className={`tw-flex tw-flex-col tw-items-center ${isVirtual ? 'tw-opacity-90' : ''}`}>
            <div className={`tw-cursor-default tw-rounded-full tw-shadow-md tw-overflow-hidden ${ringClass} ${isVirtual ? 'tw-w-12 tw-h-12 tw-bg-amber-100 tw-border-amber-400 tw-border-2' : 'tw-w-16 tw-h-16 tw-bg-white'}`}>
                {isVirtual ? <div className="tw-w-full tw-h-full tw-flex tw-items-center tw-justify-center tw-text-amber-600 tw-text-xl">🏛️</div> : <img src={avatarUrl} alt={member.name} className="tw-w-full tw-h-full tw-object-cover" />}
            </div>
            <div className="tw-mt-2 tw-text-center">
                <div className={`tw-backdrop-blur-sm tw-px-3 tw-py-1 tw-rounded-full tw-border tw-shadow-sm ${isVirtual ? 'tw-bg-amber-50/80 tw-border-amber-100' : 'tw-bg-indigo-50/80 tw-border-indigo-100'}`}>
                    <div className={`tw-font-black tw-text-[0.95rem] tw-leading-tight ${isVirtual ? 'tw-text-amber-900' : 'tw-text-indigo-900'}`}>{member.name}</div>
                    <div className={`tw-text-[0.65rem] tw-font-bold tw-uppercase tw-mt-1 ${isVirtual ? 'tw-text-amber-500' : 'tw-text-indigo-500'}`}>{role}</div>
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

    let children = allMembers.filter(m => String(m.parent_member_id) === String(member.id));
    
    // Fuzzy Search for branch owners in global storage
    if (window.globalFamiliesData) {
        window.globalFamiliesData.forEach(f => {
            if (f.origin_member_id == member.id || (f.owner_father_name && f.owner_father_name.trim().toLowerCase() === member.name.trim().toLowerCase())) {
                const branchRoot = f.members?.find(m => m.relation_to_owner === 'Self (Owner)');
                if (branchRoot && branchRoot.id != member.id && !children.some(c => c.id == branchRoot.id)) children.push(branchRoot);
            }
        });
    }

    return (
        <li className="tw-flex tw-flex-col tw-items-center">
            <div className={`tw-flex tw-items-center tw-gap-2 ${spouseMember ? 'tw-bg-indigo-50/30 tw-p-2 tw-rounded-xl tw-border tw-border-indigo-100/40' : ''}`}>
                <MemberIcon member={{...member, gen}} currentFamilyId={currentFamilyId} onEdit={onEdit} />
                {spouseMember && <><div className="tw-w-8 tw-h-[2px] tw-bg-indigo-200"></div><MemberIcon member={{...spouseMember, gen}} currentFamilyId={currentFamilyId} onEdit={onEdit} /></>}
            </div>
            {children.length > 0 && <ul>{children.map(child => <PremiumTreeNode key={child.id} member={child} allMembers={allMembers} processedIds={processedIds} currentFamilyId={currentFamilyId} onEdit={onEdit} gen={Math.min(gen + 1, 3)} />)}</ul>}
        </li>
    );
};

const TreeApp = ({ rootNode, allMembers, currentFamilyId }) => {
    const processedIds = new Set();
    return (
        <div className="tw-p-4 tw-flex tw-flex-col tw-items-center">
            <ul className="tw-flex tw-justify-center">
                <PremiumTreeNode member={rootNode} allMembers={allMembers} processedIds={processedIds} currentFamilyId={currentFamilyId} onEdit={(fid, mid) => window.openEditMemberModal(fid, mid)} gen={1} />
            </ul>
        </div>
    );
};

window.renderReactTree = (rootNode, allMembers, currentFamilyId) => {
    const mountPoint = document.getElementById('premium-tree-mount');
    if (mountPoint) {
        const root = ReactDOM.createRoot(mountPoint);
        root.render(<TreeApp rootNode={rootNode} allMembers={allMembers} currentFamilyId={currentFamilyId} />);
    }
};
