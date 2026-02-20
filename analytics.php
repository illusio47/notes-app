<?php
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();

// Get statistics
$stats = [];

// Total notes
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND is_deleted = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['total_notes'] = $stmt->get_result()->fetch_assoc()['count'];

// Notes in trash
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND is_deleted = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['trash_count'] = $stmt->get_result()->fetch_assoc()['count'];

// Notes created this week
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND is_deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['this_week'] = $stmt->get_result()->fetch_assoc()['count'];

// Notes created this month
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ? AND is_deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['this_month'] = $stmt->get_result()->fetch_assoc()['count'];

// Recent activity (last 5 notes)
$stmt = $conn->prepare("SELECT title, updated_at FROM notes WHERE user_id = ? AND is_deleted = 0 ORDER BY updated_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_notes = $stmt->get_result();

$stmt->close();
?>

<div class="analytics-container">
    <h2>Analytics</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $stats['total_notes']; ?></h3>
            <p>Total Notes</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['this_week']; ?></h3>
            <p>Created This Week</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['this_month']; ?></h3>
            <p>Created This Month</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['trash_count']; ?></h3>
            <p>In Trash</p>
        </div>
    </div>
    
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <?php if ($recent_notes->num_rows > 0): ?>
            <ul>
                <?php while ($note = $recent_notes->fetch_assoc()): ?>
                    <li>
                        <span class="activity-title"><?php echo sanitize($note['title']); ?></span>
                        <span class="activity-date"><?php echo date('M d, Y H:i', strtotime($note['updated_at'])); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No recent activity</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
