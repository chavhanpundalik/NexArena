<?php
// Add this section to team_details.php for captain
$join_requests = [];
if ($is_captain) {
    $requests_sql = $conn->prepare("
        SELECT jr.*, u.full_name, u.email 
        FROM team_join_requests jr 
        JOIN users u ON jr.user_id = u.user_id 
        WHERE jr.team_id = ? AND jr.status = 'pending'
    ");
    $requests_sql->bind_param("i", $team_id);
    $requests_sql->execute();
    $join_requests = $requests_sql->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<?php if ($is_captain && !empty($join_requests)): ?>
    <div class="join-requests">
        <h3>Pending Join Requests</h3>
        <?php foreach ($join_requests as $request): ?>
            <div class="request-item">
                <span><?= $request['full_name'] ?> (<?= $request['email'] ?>)</span>
                <p><?= $request['message'] ?></p>
                <div class="request-actions">
                    <form method="POST" action="approve_request.php" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                        <input type="hidden" name="team_id" value="<?= $team_id ?>">
                        <button type="submit" class="btn-approve">Approve</button>
                    </form>
                    <form method="POST" action="reject_request.php" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                        <input type="hidden" name="team_id" value="<?= $team_id ?>">
                        <button type="submit" class="btn-reject">Reject</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>