<?php
session_start();
include 'config.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] != 2) {
    header("Location: index.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];
$name = $_SESSION['name'];
$employee_id = $_SESSION['employee_id'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $approver_notes = isset($_POST['approver_notes']) ? trim($_POST['approver_notes']) : '';

    if ($app_id > 0 && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $query = "UPDATE leave_applications SET status = ?, approved_by = ? WHERE app_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $status, $emp_id, $app_id);
        $stmt->execute();
        $stmt->close();
        $message = "Leave " . $status . " successfully!";
    }
}

$query = "SELECT la.*, e.name, e.employee_id FROM leave_applications la 
          JOIN employees e ON la.emp_id = e.emp_id 
          WHERE la.status = 'pending' ORDER BY la.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$pending_apps = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - Leave Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .container {
            padding: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .application-card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .app-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #fff9c4;
            color: #f57f17;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 12px;
        }

        .detail-item label {
            font-size: 12px;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
        }

        .detail-item {
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .approve-btn {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .reject-btn {
            background: #ffcdd2;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HR Dashboard - Leave Manager</h1>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div>
                <strong><?php echo $name; ?></strong>
                <p style="font-size: 12px;"><?php echo $employee_id; ?></p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2 style="margin-bottom: 20px;">Pending Leave Approvals</h2>

        <?php if ($pending_apps->num_rows == 0): ?>
            <div class="empty-state">
                <p>All applications processed!</p>
            </div>
        <?php else: ?>
            <?php while ($app = $pending_apps->fetch_assoc()): ?>
                <div class="application-card">
                    <div class="app-header">
                        <div>
                            <h3><?php echo $app['name']; ?></h3>
                            <p style="font-size: 12px; color: #999;">ID: <?php echo $app['employee_id']; ?></p>
                        </div>
                        <span class="app-status">Pending</span>
                    </div>

                    <div class="detail-row">
                        <div class="detail-item">
                            <label>Leave Type</label>
                            <strong><?php echo $app['leave_type']; ?></strong>
                        </div>
                        <div class="detail-item">
                            <label>Duration</label>
                            <strong><?php echo $app['duration']; ?> days</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <label>Start Date</label>
                            <strong><?php echo date('d M Y', strtotime($app['start_date'])); ?></strong>
                        </div>
                        <div class="detail-item">
                            <label>End Date</label>
                            <strong><?php echo date('d M Y', strtotime($app['end_date'])); ?></strong>
                        </div>
                    </div>
                    <div style="background: #f9f9f9; padding: 12px; border-radius: 5px; margin-bottom: 15px;">
                        <label>Reason</label>
                        <p><?php echo htmlspecialchars($app['reason']); ?></p>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn approve-btn">Approve</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn reject-btn">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>
