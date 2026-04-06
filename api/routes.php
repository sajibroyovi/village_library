<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action  = $_POST['action'] ?? '';
$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────
function handleUpload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    $newName  = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target   = 'uploads/' . $newName;
    return move_uploaded_file($file['tmp_name'], $target) ? $target : null;
}

function handlePendingUpload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    $newName  = 'pending_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target   = 'uploads/pending/' . $newName;
    return move_uploaded_file($file['tmp_name'], $target) ? $target : null;
}

function stagePendingAction($conn, $userId, $username, $actionType, $targetId, $payload, $photoPath) {
    $stmt = $conn->prepare(
        "INSERT INTO pending_actions (submitted_by_user_id, submitted_by_username, action_type, target_id, payload, photo_path)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $username, $actionType, $targetId, json_encode($payload), $photoPath]);
}

function addNotification($conn, $userId, $message, $actionType = '') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, action_type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $message, $actionType]);
}

function applyAction($conn, $actionType, $payload, $photoPath) {
    $livePhoto = null;
    if ($photoPath && file_exists($photoPath)) {
        $filename  = basename($photoPath);
        $liveName  = str_replace('pending_', '', $filename);
        $liveTarget = 'uploads/' . $liveName;
        rename($photoPath, $liveTarget);
        $livePhoto = $liveTarget;
    }

    // Helper to resolve pending IDs inside payloads
    $resolveId = function($id) use ($conn) {
        if (!$id || $id === '') return null;
        if (!is_string($id) || !str_starts_with($id, 'pending_')) return $id;
        // Handle both 'pending_' and 'pending_mem_' prefixes
        $pId = str_replace(['pending_mem_', 'pending_'], '', $id);
        $stmt = $conn->prepare("SELECT target_id FROM pending_actions WHERE id=? AND status='approved' LIMIT 1");
        $stmt->execute([$pId]);
        $targetId = $stmt->fetchColumn();
        return $targetId ?: null; 
    };

    switch ($actionType) {
        case 'add_family':
            $p = $payload;
            $parentFamId = $resolveId($p['parent_family_id'] ?? '');
            $stmt = $conn->prepare("INSERT INTO families (house_owner_name, user_id, house_no, google_map_location, area, type_of_house, financial_condition, land, members_of_house, temple_details, owner_father_name, owner_mother_name, owner_mobile, photo_path, parent_family_id, origin_member_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$p['owner_name'], $p['user_id'] ?? 0, $p['house_no'] ?? '', $p['google_map_location'] ?? '', $p['area'], $p['type_of_house'], $p['financial_condition'], $p['land'] ?? '', $p['members_of_house'] ?? '', $p['temple_details'] ?? '', $p['owner_father_name'] ?? '', $p['owner_mother_name'] ?? '', $p['owner_mobile'] ?? '', $livePhoto, $parentFamId, $resolveId($p['origin_member_id'] ?? '')]);
            return $conn->lastInsertId();
        case 'edit_family':
            $p = $payload;
            $parentFamId = $resolveId($p['parent_family_id'] ?? '');
            $originMemId = $resolveId($p['origin_member_id'] ?? '');
            if ($livePhoto) {
                $stmt = $conn->prepare("UPDATE families SET house_owner_name=?, house_no=?, google_map_location=?, area=?, type_of_house=?, financial_condition=?, land=?, members_of_house=?, temple_details=?, owner_father_name=?, owner_mother_name=?, owner_mobile=?, photo_path=?, parent_family_id=?, origin_member_id=? WHERE id=?");
                $stmt->execute([$p['owner_name'], $p['house_no'] ?? '', $p['google_map_location'] ?? '', $p['area'], $p['type_of_house'], $p['financial_condition'], $p['land'] ?? '', $p['members_of_house'] ?? '', $p['temple_details'] ?? '', $p['owner_father_name'] ?? '', $p['owner_mother_name'] ?? '', $p['owner_mobile'] ?? '', $livePhoto, $parentFamId, $originMemId, $p['id']]);
            } else {
                $stmt = $conn->prepare("UPDATE families SET house_owner_name=?, house_no=?, google_map_location=?, area=?, type_of_house=?, financial_condition=?, land=?, members_of_house=?, temple_details=?, owner_father_name=?, owner_mother_name=?, owner_mobile=?, parent_family_id=?, origin_member_id=? WHERE id=?");
                $stmt->execute([$p['owner_name'], $p['house_no'] ?? '', $p['google_map_location'] ?? '', $p['area'], $p['type_of_house'], $p['financial_condition'], $p['land'] ?? '', $p['members_of_house'] ?? '', $p['temple_details'] ?? '', $p['owner_father_name'] ?? '', $p['owner_mother_name'] ?? '', $p['owner_mobile'] ?? '', $parentFamId, $originMemId, $p['id']]);
            }
            break;
        case 'delete_family':
            $stmt = $conn->prepare("UPDATE families SET deleted_at = NOW() WHERE id=?");
            $stmt->execute([$payload['id']]);
            return $payload['id'];
        case 'add_member':
            $p = $payload;
            $pid = $resolveId($p['parent_member_id'] ?? '');
            $sid = $resolveId($p['spouse_member_id'] ?? '');
            $fid = $resolveId($p['family_id'] ?? '');
            
            if (!is_numeric($fid)) throw new Exception("Cannot approve member: Parent household is still pending approval.");

            // Handle Photo Path (from payload or direct upload)
            $livePhoto = $p['photo_path'] ?? null;
            if (isset($photoFile) && $photoFile && $photoFile['tmp_name']) {
                $livePhoto = 'uploads/' . time() . '_' . $photoFile['name'];
                move_uploaded_file($photoFile['tmp_name'], $livePhoto);
            }

            // Logic Correction: Avoid setting 'horizontal' or upward branches as children
            $horizontalRels = ['Parent', 'Uncle', 'Aunt', 'Brother-in-law', 'Sister-in-law'];
            $actualPid = in_array($p['relation_to_owner'], $horizontalRels) ? null : $pid;

            $stmt = $conn->prepare("INSERT INTO family_members (family_id, name, relation_to_owner, mobile_number, job_status, marital_status, nick_name, gender, dob_dod_type, dob_dod, education, job_details, spouse_name, date_of_marriage, in_laws_village, in_laws_father_name, others, blood_group, member_house_no, child_type, parent_member_id, spouse_member_id, photo_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$fid, $p['name'], $p['relation_to_owner'], $p['mobile_number'] ?? '', $p['job_status'] ?? '', $p['marital_status'] ?? 'Single', $p['nick_name'] ?? '', $p['gender'] ?? '', $p['dob_dod_type'] ?? 'DOB', $p['dob_dod'] ?? '', $p['education'] ?? '', $p['job_details'] ?? '', $p['spouse_name'] ?? '', $p['date_of_marriage'] ?? '', $p['in_laws_village'] ?? '', $p['in_laws_father_name'] ?? '', $p['others'] ?? '', $p['blood_group'] ?? '', $p['member_house_no'] ?? '', $p['child_type'] ?? '', $actualPid, $sid, $livePhoto]);
            $newId = $conn->lastInsertId();
            
            // Reverse Update: Link the child ($pid) to this new Parent
            if ($p['relation_to_owner'] === 'Parent' && $pid) {
                $conn->prepare("UPDATE family_members SET parent_member_id=? WHERE id=?")->execute([$newId, $pid]);
            }
            
            if ($sid) $conn->prepare("UPDATE family_members SET spouse_member_id=? WHERE id=?")->execute([$newId, $sid]);
            return $newId;
        case 'edit_member':
            $p = $payload;
            $pid = $resolveId($p['parent_member_id'] ?? '');
            $sid = $resolveId($p['spouse_member_id'] ?? '');

            // Logic Correction: If this person IS a parent of another member ($pid),
            // then $pid is the child, and we should NOT set it as the member's parent.
            $horizontalRels = ['Parent', 'Uncle', 'Aunt', 'Brother-in-law', 'Sister-in-law'];
            $actualPid = in_array($p['relation_to_owner'], $horizontalRels) ? null : $pid;

            if ($livePhoto) {
                $stmt = $conn->prepare("UPDATE family_members SET name=?, relation_to_owner=?, mobile_number=?, job_status=?, marital_status=?, nick_name=?, gender=?, dob_dod_type=?, dob_dod=?, education=?, job_details=?, spouse_name=?, date_of_marriage=?, in_laws_village=?, in_laws_father_name=?, others=?, blood_group=?, member_house_no=?, child_type=?, parent_member_id=?, spouse_member_id=?, photo_path=? WHERE id=?");
                $stmt->execute([$p['name'], $p['relation_to_owner'], $p['mobile_number'] ?? '', $p['job_status'] ?? '', $p['marital_status'] ?? 'Single', $p['nick_name'] ?? '', $p['gender'] ?? '', $p['dob_dod_type'] ?? 'DOB', $p['dob_dod'] ?? '', $p['education'] ?? '', $p['job_details'] ?? '', $p['spouse_name'] ?? '', $p['date_of_marriage'] ?? '', $p['in_laws_village'] ?? '', $p['in_laws_father_name'] ?? '', $p['others'] ?? '', $p['blood_group'] ?? '', $p['member_house_no'] ?? '', $p['child_type'] ?? '', $actualPid, $sid, $livePhoto, $p['id']]);
            } else {
                $stmt = $conn->prepare("UPDATE family_members SET name=?, relation_to_owner=?, mobile_number=?, job_status=?, marital_status=?, nick_name=?, gender=?, dob_dod_type=?, dob_dod=?, education=?, job_details=?, spouse_name=?, date_of_marriage=?, in_laws_village=?, in_laws_father_name=?, others=?, blood_group=?, member_house_no=?, child_type=?, parent_member_id=?, spouse_member_id=? WHERE id=?");
                $stmt->execute([$p['name'], $p['relation_to_owner'], $p['mobile_number'] ?? '', $p['job_status'] ?? '', $p['marital_status'] ?? 'Single', $p['nick_name'] ?? '', $p['gender'] ?? '', $p['dob_dod_type'] ?? 'DOB', $p['dob_dod'] ?? '', $p['education'] ?? '', $p['job_details'] ?? '', $p['spouse_name'] ?? '', $p['date_of_marriage'] ?? '', $p['in_laws_village'] ?? '', $p['in_laws_father_name'] ?? '', $p['others'] ?? '', $p['blood_group'] ?? '', $p['member_house_no'] ?? '', $p['child_type'] ?? '', $actualPid, $sid, $p['id']]);
            }
            
            if ($p['relation_to_owner'] === 'Parent' && $pid) {
                $conn->prepare("UPDATE family_members SET parent_member_id=? WHERE id=?")->execute([$p['id'], $pid]);
            }

            if ($sid) $conn->prepare("UPDATE family_members SET spouse_member_id=? WHERE id=?")->execute([$p['id'], $sid]);
            return $p['id'];
        case 'delete_member':
            $stmt = $conn->prepare("UPDATE family_members SET deleted_at = NOW() WHERE id=?");
            $stmt->execute([$payload['id']]);
            return $payload['id'];
    }
}

// ══════════════════════════════════════════════
// ACTIONS
// ══════════════════════════════════════════════

if ($action === 'get_families') {
    try {
        $stmt = $conn->query("SELECT f.*, p.house_owner_name as parent_family_name FROM families f LEFT JOIN families p ON f.parent_family_id = p.id WHERE f.deleted_at IS NULL ORDER BY f.created_at DESC");
        $families = $stmt->fetchAll();
        $memStmt = $conn->query("SELECT * FROM family_members WHERE deleted_at IS NULL ORDER BY family_id, id");
        $allMembers = $memStmt->fetchAll();
        $grouped = [];
        foreach ($allMembers as $m) { $grouped[$m['family_id']][] = $m; }
        foreach ($families as &$f) { $f['members'] = $grouped[$f['id']] ?? []; }

        $pending = [];
        if ($role === 'admin') {
            $pst = $conn->prepare("SELECT * FROM pending_actions WHERE submitted_by_user_id=? AND status='pending' ORDER BY created_at DESC");
            $pst->execute([$user_id]);
            $pending = $pst->fetchAll();
        }

        echo json_encode([
            'status' => 'success', 
            'families' => $families, 
            'pending' => $pending
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
    }

} elseif ($action === 'add_family') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $owner  = $_POST['owner_name'] ?? ''; $area = $_POST['area'] ?? ''; $type   = $_POST['type_of_house'] ?? ''; $fin    = $_POST['financial_condition'] ?? '';
    if (!$owner || !$area || !$type || !$fin) { echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); exit; }

    if ($role === 'admin') {
        $photo  = handlePendingUpload($_FILES['photo'] ?? null);
        $payload = array_merge($_POST, ['user_id' => $user_id]);
        unset($payload['action']);
        stagePendingAction($conn, $user_id, $username, 'add_family', null, $payload, $photo);
        echo json_encode(['status' => 'pending', 'message' => 'Change submitted for super admin approval.']);
    } else {
        $photo = handleUpload($_FILES['photo'] ?? null);
        try {
            $stmt = $conn->prepare("INSERT INTO families (house_owner_name, user_id, house_no, google_map_location, area, type_of_house, financial_condition, land, members_of_house, temple_details, owner_father_name, owner_mother_name, owner_mobile, photo_path, parent_family_id, origin_member_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$owner, $user_id, $_POST['house_no'] ?? '', $_POST['google_map_location'] ?? '', $area, $type, $fin, $_POST['land'] ?? '', $_POST['members_of_house'] ?? '', $_POST['temple_details'] ?? '', $_POST['owner_father_name'] ?? '', $_POST['owner_mother_name'] ?? '', $_POST['owner_mobile'] ?? '', $photo, $_POST['parent_family_id'] ?: null, $_POST['origin_member_id'] ?: null]);
            echo json_encode(['status' => 'success', 'message' => 'Family added', 'photo' => $photo]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }

} elseif ($action === 'add_member') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    if ($role === 'admin') {
        $photo = handlePendingUpload($_FILES['photo'] ?? null); $payload = (array)$_POST; unset($payload['action']);
        stagePendingAction($conn, $user_id, $username, 'add_member', null, $payload, $photo);
        echo json_encode(['status' => 'pending', 'message' => 'Change submitted for super admin approval.']);
    } else {
        $p = $_POST;
        $photo_path = handleUpload($_FILES['photo'] ?? null);
        try {
            $pid = ($p['parent_member_id'] ?? '') === '' ? null : $p['parent_member_id'];
            $sid = ($p['spouse_member_id'] ?? '') === '' ? null : $p['spouse_member_id'];
            
            // Logic Correction: Avoid setting 'horizontal' or upward branches as children
            $horizontalRels = ['Parent', 'Uncle', 'Aunt', 'Brother-in-law', 'Sister-in-law'];
            $actualPid = in_array($p['relation_to_owner'], $horizontalRels) ? null : $pid;

            $stmt = $conn->prepare("INSERT INTO family_members (family_id, name, relation_to_owner, mobile_number, job_status, marital_status, nick_name, gender, dob_dod_type, dob_dod, education, job_details, spouse_name, date_of_marriage, in_laws_village, in_laws_father_name, others, blood_group, member_house_no, child_type, parent_member_id, spouse_member_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$p['family_id'], $p['name'], $p['relation_to_owner'], $p['mobile_number'] ?? '', $p['job_status'] ?? '', $p['marital_status'] ?? 'Single', $p['nick_name'] ?? '', $p['gender'] ?? '', $p['dob_dod_type'] ?? 'DOB', $p['dob_dod'] ?? '', $p['education'] ?? '', $p['job_details'] ?? '', $p['spouse_name'] ?? '', $p['date_of_marriage'] ?? '', $p['in_laws_village'] ?? '', $p['in_laws_father_name'] ?? '', $p['others'] ?? '', $p['blood_group'] ?? '', $p['member_house_no'] ?? '', $p['child_type'] ?? '', $actualPid, $sid, $photo_path]);
            $new_id = $conn->lastInsertId();
            
            // Reverse Update: Link the child ($pid) to this new Parent
            if ($p['relation_to_owner'] === 'Parent' && $pid) {
                $conn->prepare("UPDATE family_members SET parent_member_id=? WHERE id=?")->execute([$new_id, $pid]);
            }
            
            if ($sid) $conn->prepare("UPDATE family_members SET spouse_member_id=? WHERE id=?")->execute([$new_id, $sid]);
            echo json_encode(['status' => 'success', 'message' => 'Member added', 'photo' => $photo_path]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }

} elseif ($action === 'edit_family') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $id = $_POST['id'] ?? ''; if (!$id) { echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit; }
    if ($role === 'admin') {
        $photo = handlePendingUpload($_FILES['photo'] ?? null); $payload = (array)$_POST; unset($payload['action']);
        stagePendingAction($conn, $user_id, $username, 'edit_family', $id, $payload, $photo);
        echo json_encode(['status' => 'pending', 'message' => 'Change submitted for super admin approval.']);
    } else {
        $p = $_POST; $new_photo = handleUpload($_FILES['photo'] ?? null);
        try {
            if ($new_photo) {
                $stmt = $conn->prepare("UPDATE families SET house_owner_name=?,house_no=?,google_map_location=?,area=?,type_of_house=?,financial_condition=?,land=?,members_of_house=?,temple_details=?,owner_father_name=?,owner_mother_name=?,owner_mobile=?,photo_path=?,parent_family_id=?,origin_member_id=? WHERE id=?");
                $stmt->execute([$p['owner_name'],$p['house_no']??'',$p['google_map_location']??'',$p['area'],$p['type_of_house'],$p['financial_condition'],$p['land']??'',$p['members_of_house']??'',$p['temple_details']??'',$p['owner_father_name']??'',$p['owner_mother_name']??'',$p['owner_mobile']??'',$new_photo,$p['parent_family_id']?:null,$p['origin_member_id']?:null,$id]);
            } else {
                $stmt = $conn->prepare("UPDATE families SET house_owner_name=?,house_no=?,google_map_location=?,area=?,type_of_house=?,financial_condition=?,land=?,members_of_house=?,temple_details=?,owner_father_name=?,owner_mother_name=?,owner_mobile=?,parent_family_id=?,origin_member_id=? WHERE id=?");
                $stmt->execute([$p['owner_name'],$p['house_no']??'',$p['google_map_location']??'',$p['area'],$p['type_of_house'],$p['financial_condition'],$p['land']??'',$p['members_of_house']??'',$p['temple_details']??'',$p['owner_father_name']??'',$p['owner_mother_name']??'',$p['owner_mobile']??'',$p['parent_family_id']?:null,$p['origin_member_id']?:null,$id]);
            }
            echo json_encode(['status' => 'success', 'message' => 'Family updated', 'photo' => $new_photo]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }

} elseif ($action === 'edit_member') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $id = $_POST['id'] ?? ''; if (!$id) { echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit; }
    if ($role === 'admin') {
        $photo = handlePendingUpload($_FILES['photo'] ?? null); $payload = (array)$_POST; unset($payload['action']);
        stagePendingAction($conn, $user_id, $username, 'edit_member', $id, $payload, $photo);
        echo json_encode(['status' => 'pending', 'message' => 'Change submitted for super admin approval.']);
    } else {
        $p = $_POST; $new_photo = handleUpload($_FILES['photo'] ?? null);
        try {
            $pid = ($p['parent_member_id'] ?? '') === '' ? null : $p['parent_member_id'];
            $sid = ($p['spouse_member_id'] ?? '') === '' ? null : $p['spouse_member_id'];
            
            // Logic Correction: Avoid setting 'horizontal' or upward branches as children
            $horizontalRels = ['Parent', 'Uncle', 'Aunt'];
            $actualPid = in_array($p['relation_to_owner'], $horizontalRels) ? null : $pid;

            if ($new_photo) {
                $stmt = $conn->prepare("UPDATE family_members SET name=?,relation_to_owner=?,mobile_number=?,job_status=?,marital_status=?,nick_name=?,gender=?,dob_dod_type=?,dob_dod=?,education=?,job_details=?,spouse_name=?,date_of_marriage=?,in_laws_village=?,in_laws_father_name=?,others=?,blood_group=?,member_house_no=?,child_type=?,parent_member_id=?,spouse_member_id=?,photo_path=? WHERE id=?");
                $stmt->execute([$p['name'],$p['relation_to_owner'],$p['mobile_number']??'',$p['job_status']??'',$p['marital_status']??'Single',$p['nick_name']??'',$p['gender']??'',$p['dob_dod_type']??'DOB',$p['dob_dod']??'',$p['education']??'',$p['job_details']??'',$p['spouse_name']??'',$p['date_of_marriage']??'',$p['in_laws_village']??'',$p['in_laws_father_name']??'',$p['others']??'',$p['blood_group']??'',$p['member_house_no']??'',$p['child_type']??'',$actualPid,$sid,$new_photo,$id]);
            } else {
                $stmt = $conn->prepare("UPDATE family_members SET name=?,relation_to_owner=?,mobile_number=?,job_status=?,marital_status=?,nick_name=?,gender=?,dob_dod_type=?,dob_dod=?,education=?,job_details=?,spouse_name=?,date_of_marriage=?,in_laws_village=?,in_laws_father_name=?,others=?,blood_group=?,member_house_no=?,child_type=?,parent_member_id=?,spouse_member_id=? WHERE id=?");
                $stmt->execute([$p['name'],$p['relation_to_owner'],$p['mobile_number']??'',$p['job_status']??'',$p['marital_status']??'Single',$p['nick_name']??'',$p['gender']??'',$p['dob_dod_type']??'DOB',$p['dob_dod']??'',$p['education']??'',$p['job_details']??'',$p['spouse_name']??'',$p['date_of_marriage']??'',$p['in_laws_village']??'',$p['in_laws_father_name']??'',$p['others']??'',$p['blood_group']??'',$p['member_house_no']??'',$p['child_type']??'',$actualPid,$sid,$id]);
            }
            
            // Reverse Update
            if ($p['relation_to_owner'] === 'Parent' && $pid) {
                $conn->prepare("UPDATE family_members SET parent_member_id=? WHERE id=?")->execute([$id, $pid]);
            }
            
            if ($sid) $conn->prepare("UPDATE family_members SET spouse_member_id=? WHERE id=?")->execute([$id, $sid]);
            echo json_encode(['status' => 'success', 'message' => 'Member updated', 'photo' => $new_photo]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }

} elseif ($action === 'delete_family') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $id = $_POST['id'] ?? '';
    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT house_owner_name, house_no, area FROM families WHERE id=?");
        $stmt->execute([$id]);
        $details = $stmt->fetch();
        stagePendingAction($conn, $user_id, $username, 'delete_family', $id, ['id' => $id, 'Name' => $details['house_owner_name'] ?? 'Unknown', 'House' => $details['house_no'] ?? 'N/A', 'Area' => $details['area'] ?? 'N/A'], null);
        echo json_encode(['status' => 'pending', 'message' => 'Delete request submitted for super admin approval.']);
    } else {
        try {
            $conn->prepare("UPDATE families SET deleted_at = NOW() WHERE id=?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Family moved to Recycle Bin']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }

} elseif ($action === 'delete_member') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $id = $_POST['id'] ?? '';
    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT name, relation_to_owner FROM family_members WHERE id=?");
        $stmt->execute([$id]);
        $details = $stmt->fetch();
        stagePendingAction($conn, $user_id, $username, 'delete_member', $id, ['id' => $id, 'Member Name' => $details['name'] ?? 'Unknown', 'Relation' => $details['relation_to_owner'] ?? 'N/A'], null);
        echo json_encode(['status' => 'pending', 'message' => 'Delete request submitted for super admin approval.']);
    } else {
        try {
            $conn->prepare("UPDATE family_members SET deleted_at = NOW() WHERE id=?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Member moved to Recycle Bin']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }
    }
} elseif ($action === 'update_pending_action') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $pending_id = $_POST['pending_id'] ?? '';
    if (!$pending_id) { echo json_encode(['status' => 'error', 'message' => 'Missing Pending ID']); exit; }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM pending_actions WHERE id=? AND status='pending'");
        $stmt->execute([$pending_id]);
        $row = $stmt->fetch();
        if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Request not found or already reviewed']); exit; }
        
        // Security check: Only the owner or super_admin can update
        if ($role !== 'super_admin' && $row['submitted_by_user_id'] != $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized to edit this request']); exit;
        }

        $photo = $row['photo_path'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $photo = handlePendingUpload($_FILES['photo']);
        }

        $payload = (array)$_POST;
        unset($payload['action'], $payload['pending_id']);
        
        $pst = $conn->prepare("UPDATE pending_actions SET payload=?, photo_path=? WHERE id=?");
        $pst->execute([json_encode($payload), $photo, $pending_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Pending request updated successfully']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'delete_pending_action') {
    if ($role === 'user') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $pending_id = $_POST['pending_id'] ?? '';
    if (!$pending_id) { echo json_encode(['status' => 'error', 'message' => 'Missing Pending ID']); exit; }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM pending_actions WHERE id=? AND status='pending'");
        $stmt->execute([$pending_id]);
        $row = $stmt->fetch();
        if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Request not found or already reviewed']); exit; }
        
        // Security check: Only the owner or super_admin can discard
        if ($role !== 'super_admin' && $row['submitted_by_user_id'] != $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized to discard this request']); exit;
        }

        $conn->prepare("DELETE FROM pending_actions WHERE id=?")->execute([$pending_id]);
        echo json_encode(['status' => 'success', 'message' => 'Pending request discarded successfully']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

// ── Approval Queue ──────────────────────────────

} elseif ($action === 'get_pending_actions') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    try {
        $stmt = $conn->query("SELECT * FROM pending_actions WHERE status='pending' ORDER BY created_at ASC");
        echo json_encode(['status' => 'success', 'actions' => $stmt->fetchAll()]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'get_pending_count') {
    if ($role !== 'super_admin') { echo json_encode(['count' => 0]); exit; }
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM pending_actions WHERE status='pending'");
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
    } catch (Exception $e) { echo json_encode(['count' => 0]); }

} elseif ($action === 'review_action') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $pendingId = $_POST['id'] ?? ''; $decision = $_POST['decision'] ?? ''; $note = $_POST['note'] ?? '';
    if (!$pendingId || !in_array($decision, ['approved', 'rejected'])) { echo json_encode(['status' => 'error', 'message' => 'Invalid request']); exit; }
    try {
        $row = $conn->prepare("SELECT * FROM pending_actions WHERE id=? AND status='pending'");
        $row->execute([$pendingId]); $pending = $row->fetch();
        if (!$pending) { echo json_encode(['status' => 'error', 'message' => 'Pending action not found']); exit; }

        $payload = json_decode($pending['payload'], true);
        $detail = $payload['name'] ?? $payload['owner_name'] ?? 'Record';

        if ($decision === 'approved') {
            $newTargetId = applyAction($conn, $pending['action_type'], $payload, $pending['photo_path']);
            $msg = "✅ Your request to " . str_replace('_', ' ', $pending['action_type']) . " '$detail' has been approved.";
            $conn->prepare("UPDATE pending_actions SET status=?, reviewer_note=?, reviewed_at=NOW(), target_id=? WHERE id=?")->execute([$decision, $note, $newTargetId, $pendingId]);
        } else {
            if ($pending['photo_path'] && file_exists($pending['photo_path'])) unlink($pending['photo_path']);
            $msg = "❌ Your request to " . str_replace('_', ' ', $pending['action_type']) . " '$detail' was rejected" . ($note ? ": $note" : ".");
        }
        $conn->prepare("UPDATE pending_actions SET status=?, reviewer_note=?, reviewed_at=NOW() WHERE id=?")->execute([$decision, $note, $pendingId]);
        addNotification($conn, $pending['submitted_by_user_id'], $msg, $pending['action_type']);
        echo json_encode(['status' => 'success', 'message' => 'Action ' . $decision]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

// ── Recycle Bin ──────────────────────────────

} elseif ($action === 'get_recycle_bin') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    try {
        $families = $conn->query("SELECT * FROM families WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();
        $members = $conn->query("SELECT * FROM family_members WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();
        echo json_encode(['status' => 'success', 'families' => $families, 'members' => $members]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'restore_item') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $type = $_POST['type'] ?? ''; $id = $_POST['id'] ?? '';
    try {
        $table = ($type === 'family') ? 'families' : 'family_members';
        $conn->prepare("UPDATE $table SET deleted_at = NULL WHERE id=?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Restored successfully']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'permanent_delete') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $type = $_POST['type'] ?? ''; $id = $_POST['id'] ?? '';
    try {
        $table = ($type === 'family') ? 'families' : 'family_members';
        $conn->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Permanently deleted']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

// ── Notifications ──────────────────────────────

} elseif ($action === 'get_notifications') {
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$user_id]);
        $all = $stmt->fetchAll();
        $unread = 0; foreach ($all as $n) if (!$n['is_read']) $unread++;
        echo json_encode(['status' => 'success', 'notifications' => $all, 'unread_count' => $unread]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'mark_notifications_read') {
    try {
        $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user_id]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

// ── User Management ──────────────────────────────

} elseif ($action === 'get_users') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    try {
        $stmt = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
        echo json_encode(['status' => 'success', 'users' => $stmt->fetchAll()]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'add_user') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $new_user = $_POST['username'] ?? ''; $new_pass = $_POST['password'] ?? ''; $new_role = $_POST['role'] ?? 'user';
    if (!$new_user || !$new_pass) { echo json_encode(['status' => 'error', 'message' => 'Username and password required']); exit; }
    try {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)")->execute([$new_user, $hash, $new_role]);
        echo json_encode(['status' => 'success', 'message' => 'User created']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'edit_user') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $target_id = $_POST['id'] ?? ''; $new_user = $_POST['username'] ?? ''; $new_pass = $_POST['password'] ?? ''; $new_role = $_POST['role'] ?? '';
    try {
        if ($new_pass) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE users SET username=?,password_hash=?,role=? WHERE id=?")->execute([$new_user,$hash,$new_role,$target_id]);
        } else {
            $conn->prepare("UPDATE users SET username=?,role=? WHERE id=?")->execute([$new_user,$new_role,$target_id]);
        }
        echo json_encode(['status' => 'success', 'message' => 'User updated']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} elseif ($action === 'delete_user') {
    if ($role !== 'super_admin') { echo json_encode(['status' => 'error', 'message' => 'Permission denied']); exit; }
    $target_id = $_POST['id'] ?? '';
    if ($target_id == $user_id) { echo json_encode(['status' => 'error', 'message' => 'You cannot delete yourself']); exit; }
    try {
        $conn->prepare("DELETE FROM users WHERE id=?")->execute([$target_id]);
        echo json_encode(['status' => 'success', 'message' => 'User deleted']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]); }

} else { echo json_encode(['status' => 'error', 'message' => 'Invalid action']); }
?>
