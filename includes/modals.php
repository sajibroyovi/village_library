<!-- Modals -->
<div class="modal-backdrop" id="addFamilyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Family</h3>
            <button class="close-btn" onclick="closeModal('addFamilyModal')">&times;</button>
        </div>
        <form id="addFamilyForm" style="max-height: 70vh; overflow-y: auto; padding-right: 1rem;">
            <input type="hidden" id="family_action" name="action" value="add_family">
            <input type="hidden" id="family_edit_id" name="id" value="">
            <input type="hidden" id="family_pending_id" name="pending_id" value="">
            <div class="form-group">
                <label>House Owner Name</label>
                <input type="text" id="owner_name" name="owner_name" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Owner's Father Name</label>
                    <input type="text" id="owner_father_name" name="owner_father_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Owner's Mother Name</label>
                    <input type="text" id="owner_mother_name" name="owner_mother_name" class="form-control">
                </div>
            </div>

            <div style="background: rgba(129, 140, 248, 0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px dashed var(--primary); margin-top: 0.5rem;">
                <label style="color: var(--primary); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; display: block; margin-bottom: 0.8rem;">🌳 Family Lineage (Household Origin)</label>
                <div class="form-group">
                    <label style="font-size: 0.85rem;">Origin Family (Which household did they come from?)</label>
                    <select id="parent_family_id" name="parent_family_id" class="form-control">
                        <option value="">🏠 No Parent Family (New Root Household)</option>
                    </select>
                </div>
                <div class="form-group" id="origin_member_group" style="display: none;">
                    <label style="font-size: 0.85rem;">Specifically, which member was this owner?</label>
                    <select id="origin_member_id" name="origin_member_id" class="form-control">
                        <option value="">Select Member</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>House No</label>
                <input type="text" id="house_no" name="house_no" class="form-control">
            </div>
            <div class="form-group">
                <label>Area of House</label>
                <select id="area" name="area" class="form-control" required>
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
                <input type="text" id="owner_mobile" name="owner_mobile" class="form-control">
            </div>
            <div class="form-group">
                <label>Type of House</label>
                <select id="type_of_house" name="type_of_house" class="form-control" required>
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
                <select id="financial_condition" name="financial_condition" class="form-control" required>
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
                    <input type="text" id="google_map_location" name="google_map_location" class="form-control" placeholder="Link or coordinates" style="flex: 1;">
                    <button type="button" class="btn-outline" onclick="detectLocation()" style="white-space: nowrap; font-size: 0.8rem; padding: 0.3rem 0.6rem;">📍 Detect Location</button>
                </div>
            </div>
            <div class="form-group">
                <label>Land Amount</label>
                <input type="text" id="land" name="land" class="form-control" placeholder="e.g. 5 decimals">
            </div>
            <div class="form-group">
                <label>Members of House (Summary)</label>
                <input type="text" id="members_of_house" name="members_of_house" class="form-control" placeholder="e.g. 5 members">
            </div>
            <div class="form-group">
                <label>Temple Details</label>
                <textarea id="temple_details" name="temple_details" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Profile Photo</label>
                <input type="file" id="family_photo" name="photo" class="form-control" accept="image/*">
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
            <input type="hidden" id="member_action" name="action" value="add_member">
            <input type="hidden" id="member_edit_id" name="id" value="">
            <input type="hidden" id="member_pending_id" name="pending_id">
            <input type="hidden" id="member_family_id" name="family_id">
            <div class="form-group">
                <label>Member Name</label>
                <input type="text" id="member_name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nick Name</label>
                <input type="text" id="nick_name" name="nick_name" class="form-control">
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Relation to Owner</label>
                <select id="member_relation" name="relation_to_owner" class="form-control" required>
                    <option value="Self (Owner)">Self (Owner)</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                    <option value="Uncle">Uncle</option>
                    <option value="Aunt">Aunt</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Niece">Niece</option>
                    <option value="Grand Child">Grand Child</option>
                    <option value="Son-in-law">Son-in-law</option>
                    <option value="Daughter-in-law">Daughter-in-law</option>
                    <option value="Brother-in-law">Brother-in-law</option>
                    <option value="Sister-in-law">Sister-in-law</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group" id="other_relation_group" style="display: none;">
                <label>Specify Other Relation</label>
                <input type="text" id="other_relation" class="form-control">
            </div>
            <div class="form-group" id="parent_member_group" style="display: none;">
                <label>Father's / Parent's Name (Registered Member)</label>
                <select id="parent_member_id" name="parent_member_id" class="form-control">
                    <option value="">Select Parent</option>
                </select>
            </div>
            <div class="form-group" id="sibling_member_group" style="display: none;">
                <label>Select Sibling (Registered Member)</label>
                <select id="sibling_member_id" name="sibling_member_id" class="form-control">
                    <option value="">Select Sibling</option>
                </select>
            </div>
            <div class="form-group" id="spouse_member_group" style="display: none;">
                <label>Select Spouse (Registered Member)</label>
                <select id="spouse_member_id" name="spouse_member_id" class="form-control">
                    <option value="">Select Spouse</option>
                </select>
            </div>
            <div class="form-group" id="child_type_group" style="display: none;">
                <label>Child Type</label>
                <select id="child_type" name="child_type" class="form-control">
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
                    <select id="dob_dod_type" name="dob_dod_type" class="form-control" required>
                        <option value="DOB">Birth (DOB)</option>
                        <option value="DOD">Death (DOD)</option>
                    </select>
                </div>
                <div style="flex: 2;">
                    <label>Date Value</label>
                    <input type="text" id="dob_dod" name="dob_dod" class="form-control" placeholder="e.g. 1990">
                </div>
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" id="member_mobile" name="mobile_number" class="form-control">
            </div>
            <div class="form-group">
                <label>Blood Group</label>
                <select id="blood_group" name="blood_group" class="form-control">
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
                <select id="education" name="education" class="form-control">
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
                <select id="member_job" name="job_status" class="form-control">
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
                <textarea id="job_details" name="job_details" class="form-control" rows="2" placeholder="e.g. Software Engineer at Google"></textarea>
            </div>
            <div class="form-group">
                <label>Marital Status</label>
                <select id="member_marital" name="marital_status" class="form-control">
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
                    <input type="text" id="spouse_name" name="spouse_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Date of Marriage</label>
                    <input type="text" id="date_of_marriage" name="date_of_marriage" class="form-control" placeholder="e.g. 2015">
                </div>
                <div class="form-group">
                    <label>In-law's Village</label>
                    <input type="text" id="in_laws_village" name="in_laws_village" class="form-control">
                </div>
                <div class="form-group">
                    <label>In-law's Father Name</label>
                    <input type="text" id="in_laws_father_name" name="in_laws_father_name" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Others / Observations</label>
                <textarea id="others" name="others" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Member Photo</label>
                <input type="file" id="member_photo" name="photo" class="form-control" accept="image/*">
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
            <input type="hidden" id="user_action" name="action" value="add_user">
            <input type="hidden" id="user_edit_id" name="id">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="user_username" name="username" class="form-control" required>
            </div>
            <div class="form-group" id="pass_group">
                <label id="pass_label">Password</label>
                <input type="password" id="user_password" name="password" class="form-control">
                <small id="pass_hint" style="color: var(--text-muted); display: none;">Leave blank to keep current password</small>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="user_role" name="role" class="form-control">
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
