<?php
session_start();
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($employee_id) || empty($name) || empty($password)) {
        $error = "All fields are required!";
    } else {
        // Check credentials in database
        $query = "SELECT * FROM employees WHERE employee_id = ? AND name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $employee_id, $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $employee = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $employee['password'])) {
                $_SESSION['emp_id'] = $employee['emp_id'];
                $_SESSION['employee_id'] = $employee['employee_id'];
                $_SESSION['name'] = $employee['name'];
                $_SESSION['role'] = $employee['role'];
                $_SESSION['department'] = $employee['department'];
                $_SESSION['salary'] = $employee['salary'];

                if ($employee['role'] == 2) {
                    header("Location: hr-dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Invalid Employee ID or Name!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #999;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: Arial, sans-serif;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #3c3;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #999;
            font-size: 13px;
        }

        .info-box {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 25px;
            border-left: 4px solid #667eea;
        }

        .info-box h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .info-box p {
            color: #666;
            font-size: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 Leave Manager</h1>
            <p>Employee Leave Application System</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <strong>Success:</strong> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" placeholder="e.g., EMP001" required>
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="login-footer">
            <p>Contact HR for account issues</p>
        </div>

        <div class="info-box">
            <h4>📌 Demo Credentials</h4>
            <p>
                <strong>ID:</strong> EMP001<br>
                <strong>Name:</strong> Rahul Kumar<br>
                <strong>Pass:</strong> password@123
            </p>
        </div>
    </div>
</body>
</html>