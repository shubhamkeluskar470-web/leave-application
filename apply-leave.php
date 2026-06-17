<?php
session_start();
include 'config.php';

if (!isset($_SESSION['emp_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] != 1) {
    header("Location: index.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type = isset($_POST['leave_type']) ? trim($_POST['leave_type']) : '';
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $_SESSION['error'] = "All fields required!";
    } else {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        $year = intval($start->format('Y'));
        $query = "SELECT holiday_date FROM public_holidays WHERE year = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $holidays_result = $stmt->get_result();
        $holidays = [];
        while ($h = $holidays_result->fetch_assoc()) {
            $holidays[] = $h['holiday_date'];
        }
        $stmt->close();

        $working_days = 0;
        $working_dates = [];
        foreach ($period as $date) {
            $day_of_week = intval($date->format('w'));
            $date_str = $date->format('Y-m-d');

            if (!in_array($date_str, $holidays) && ($day_of_week != 0 && $day_of_week != 6)) {
                $working_days++;
                $working_dates[] = $date_str;
            }
        }

        $duration = $working_days;

        if ($duration == 0) {
            $_SESSION['error'] = "No working days in selected range!";
        } else {
            $current_month = date('m');
            $current_year = date('Y');

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
            }

            $query = "SELECT salary FROM employees WHERE emp_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $emp_id);
            $stmt->execute();
            $emp_data = $stmt->get_result()->fetch_assoc();
            $salary_per_day = $emp_data['salary'] / 30;
            $stmt->close();

            $remaining_days = $duration;
            $used_leave_types = array();
            $salary_cut = 0;

            $primary_type = strtolower($leave_type);
            $remaining_field = $primary_type . '_remaining';
            $used_field = $primary_type . '_used';

            if ($balance[$remaining_field] >= $remaining_days) {
                $used_leave_types[$leave_type] = $remaining_days;
                $remaining_days = 0;
            } else {
                $allocated = $balance[$remaining_field];
                if ($allocated > 0) {
                    $used_leave_types[$leave_type] = $allocated;
                }
                $remaining_days -= $allocated;
            }

            if ($remaining_days > 0 && $primary_type !== 'casual') {
                $allocated = min($remaining_days, $balance['casual_remaining']);
                if ($allocated > 0) {
                    $used_leave_types['Casual'] = $allocated;
                }
                $remaining_days -= $allocated;
            }

            if ($remaining_days > 0 && $primary_type !== 'sick') {
                $allocated = min($remaining_days, $balance['sick_remaining']);
                if ($allocated > 0) {
                    $used_leave_types['Sick'] = $allocated;
                }
                $remaining_days -= $allocated;
            }

            if ($remaining_days > 0 && $primary_type !== 'paid') {
                $allocated = min($remaining_days, $balance['paid_remaining']);
                if ($allocated > 0) {
                    $used_leave_types['Paid'] = $allocated;
                }
                $remaining_days -= $allocated;
            }

            if ($remaining_days > 0) {
                $salary_cut = $remaining_days * $salary_per_day;
            }

            $status = 'approved';
            $used_leave_json = json_encode($used_leave_types);
            $days_deducted_json = json_encode($working_dates);

            $query = "INSERT INTO leave_applications (emp_id, leave_type, start_date, end_date, duration, reason, status, used_leave_types, days_deducted, salary_cut) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issssdsss", $emp_id, $leave_type, $start_date, $end_date, $duration, $reason, $status, $used_leave_json, $days_deducted_json, $salary_cut);

            if ($stmt->execute()) {
                $app_id = $stmt->insert_id;
                $stmt->close();

                foreach ($used_leave_types as $type => $count) {
                    $type_lower = strtolower($type);
                    $used_field = $type_lower . '_used';
                    $remaining_field = $type_lower . '_remaining';
                    $query = "UPDATE leave_balance SET {$used_field} = {$used_field} + ?, {$remaining_field} = {$remaining_field} - ? WHERE emp_id = ? AND year = ? AND month = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iiiii", $count, $count, $emp_id, $current_year, $current_month);
                    $stmt->execute();
                    $stmt->close();
                }

                if ($salary_cut > 0) {
                    $query = "INSERT INTO salary_cuts (emp_id, app_id, cut_date, amount, reason) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $cut_date = $start_date;
                    $reason_text = "Salary cut for leave";
                    $stmt->bind_param("iisds", $emp_id, $app_id, $cut_date, $salary_cut, $reason_text);
                    $stmt->execute();
                    $stmt->close();
                }

                $_SESSION['success'] = "Leave approved!";
            }
        }
    }
}

header("Location: dashboard.php");
exit();
?>