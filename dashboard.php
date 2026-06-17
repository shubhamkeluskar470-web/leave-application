<?php
session_start();
include 'config.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: index.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];
$name = $_SESSION['name'];
$employee_id = $_SESSION['employee_id'];
$salary = $_SESSION['salary'];

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$today = date('Y-m-d');

// Get leave balance
$query = "SELECT * FROM leave_balance WHERE emp_id = ? AND year = ? AND month = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $emp_id, $current_year, $current_month);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$balance) {
    $query = "INSERT INTO leave_balance (emp_id, year, month) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $emp_id, $current_year, $current_month);
    $stmt->execute();
    $stmt->close();
    
    $query = "SELECT * FROM leave_balance WHERE emp_id = ? AND year = ? AND month = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $emp_id, $current_year, $current_month);
    $stmt->execute();
    $balance = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get all leave applications
$query = "SELECT * FROM leave_applications WHERE emp_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();

// Get public holidays
$query = "SELECT * FROM public_holidays WHERE year = ? ORDER BY holiday_date";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_year);
$stmt->execute();
$holidays = $stmt->get_result();
$holidays_array = [];
while ($holiday = $holidays->fetch_assoc()) {
    $holidays_array[$holiday['holiday_date']] = $holiday['holiday_name'];
}
$stmt->close();

// Get salary cuts
$query = "SELECT * FROM salary_cuts WHERE emp_id = ? AND YEAR(cut_date) = ? AND MONTH(cut_date) = ? ORDER BY cut_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $emp_id, $current_year, $current_month);
$stmt->execute();
$salary_cuts = $stmt->get_result();
$total_salary_cut = 0;
$cuts_array = [];
while ($cut = $salary_cuts->fetch_assoc()) {
    $total_salary_cut += $cut['amount'];
    $cuts_array[] = $cut;
}
$stmt->close();

// Get leaves for calendar display
$calendar_leaves = [];
$temp_apps = $applications;
while ($app = $temp_apps->fetch_assoc()) {
    $start = new DateTime($app['start_date']);
    $end = new DateTime($app['end_date']);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $calendar_leaves[$date_str] = $app['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Leave Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-title {
            font-size: 24px;
            font-weight: 600;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            font-size: 14px;
            opacity: 0.9;
        }

        .user-info strong {
            display: block;
            font-size: 16px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 300px;
            background: white;
            padding: 25px;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
            max-height: calc(100vh - 80px);
        }

        .sidebar h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .calendar-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .calendar-month {
            text-align: center;
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            margin-bottom: 5px;
        }

        .calendar-weekdays div {
            text-align: center;
            font-weight: 600;
            color: #666;
            font-size: 11px;
            padding: 5px 0;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
        }

        .calendar-day.empty {
            background: transparent;
            border: none;
        }

        .calendar-day.weekend {
            background: #f0f0f0;
            color: #999;
        }

        .calendar-day.holiday {
            background: #ffcccb;
            color: #c33;
            font-weight: 600;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }

        .calendar-day.pending {
            background: #fff9c4;
            color: #f57f17;
            font-weight: 600;
            border: 2px solid #f57f17;
        }

        .calendar-day.approved {
            background: #c8e6c9;
            color: #2e7d32;
            font-weight: 600;
            border: 2px solid #2e7d32;
        }

        .calendar-day.rejected {
            background: #ffcdd2;
            color: #c62828;
            font-weight: 600;
            border: 2px solid #c62828;
        }

        .leave-counts {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .leave-count-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .leave-count-item:last-child {
            border-bottom: none;
        }

        .count-box {
            display: flex;
            gap: 5px;
        }

        .count-total {
            color: #667eea;
            background: #f0f4ff;
            padding: 2px 8px;
            border-radius: 3px;
        }

        .count-remaining {
            color: #2e7d32;
            background: #e8f5e9;
            padding: 2px 8px;
            border-radius: 3px;
        }

        .salary-cut-section {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: sticky;
            bottom: 0;
            margin-top: 20px;
        }

        .salary-cut-section h4 {
            color: #c62828;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .salary-cut-total {
            background: #ffebee;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .salary-cut-total strong {
            color: #c62828;
        }

        .main-content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            max-height: calc(100vh - 80px);
        }

        .form-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
        }

        .applications-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .app-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .app-item:last-child {
            border-bottom: none;
        }

        .app-details {
            flex: 1;
        }

        .app-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .app-status.pending {
            background: #fff9c4;
            color: #f57f17;
        }

        .app-status.approved {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .app-status.rejected {
            background: #ffcdd2;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
            }
            .form-group-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Leave Manager</div>
        <div class="header-user">
            <div class="user-info">
                <p>Welcome!</p>
                <strong><?php echo $name; ?></strong>
                <p>ID: <?php echo $employee_id; ?></p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="calendar-container">
                <h3>Calendar</h3>
                <div class="calendar-month" id="calendarMonth"></div>
                <div class="calendar-weekdays">
                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                    <div>Thu</div><div>Fri</div><div>Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays"></div>
            </div>

            <div class="leave-counts">
                <h3>Leave Counts</h3>
                <div class="leave-count-item">
                    <span>Casual</span>
                    <div class="count-box">
                        <span class="count-total"><?php echo $balance['casual_total']; ?></span>
                        <span class="count-remaining"><?php echo $balance['casual_remaining']; ?></span>
                    </div>
                </div>
                <div class="leave-count-item">
                    <span>Sick</span>
                    <div class="count-box">
                        <span class="count-total"><?php echo $balance['sick_total']; ?></span>
                        <span class="count-remaining"><?php echo $balance['sick_remaining']; ?></span>
                    </div>
                </div>
                <div class="leave-count-item">
                    <span>Paid</span>
                    <div class="count-box">
                        <span class="count-total"><?php echo $balance['paid_total']; ?></span>
                        <span class="count-remaining"><?php echo $balance['paid_remaining']; ?></span>
                    </div>
                </div>
            </div>

            <div class="salary-cut-section">
                <h4>Salary Cuts</h4>
                <?php if (count($cuts_array) > 0): ?>
                    <?php foreach (array_slice($cuts_array, 0, 3) as $cut): ?>
                        <div style="margin-bottom: 5px; font-size: 12px;">
                            <strong><?php echo date('d M', strtotime($cut['cut_date'])); ?>:</strong> -Rs.<?php echo number_format($cut['amount'], 2); ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="salary-cut-total" style="margin-top: 10px;">
                        <strong>Total: Rs.<?php echo number_format($total_salary_cut, 2); ?></strong>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #999; font-size: 12px;">No salary cuts</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-content">
            <div class="form-container">
                <h2 style="margin-bottom: 20px;">Apply for Leave</h2>
                <form method="POST" action="apply-leave.php">
                    <div class="form-group-row">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select name="leave_type" required>
                                <option value="">-- Select --</option>
                                <option value="Casual">Casual Leave</option>
                                <option value="Sick">Sick Leave</option>
                                <option value="Paid">Paid Leave</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" required>
                        </div>
                    </div>
                    <div class="form-group-row">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" required></textarea>
                    </div>
                    <button type="submit" class="form-btn">Submit Application</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function generateCalendar() {
            const today = new Date();
            const year = today.getFullYear();
            const month = today.getMonth();
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            
            document.getElementById('calendarMonth').textContent = monthNames[month] + ' ' + year;
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';
            
            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'calendar-day empty';
                calendarDays.appendChild(emptyCell);
            }
            
            const holidays = <?php echo json_encode($holidays_array); ?>;
            
            for (let day = 1; day <= daysInMonth; day++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'calendar-day';
                dayCell.textContent = day;
                
                const dayOfWeek = new Date(year, month, day).getDay();
                const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                
                if (day === today.getDate() && month === today.getMonth()) {
                    dayCell.classList.add('today');
                } else if (dayOfWeek === 0 || dayOfWeek === 6) {
                    dayCell.classList.add('weekend');
                } else if (holidays[dateStr]) {
                    dayCell.classList.add('holiday');
                }
                
                calendarDays.appendChild(dayCell);
            }
        }
        
        generateCalendar();
    </script>
</body>
</html>
