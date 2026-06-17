-- Create Database
CREATE DATABASE IF NOT EXISTS leave_app;
USE leave_app;

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
  emp_id INT PRIMARY KEY AUTO_INCREMENT,
  employee_id VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  phone VARCHAR(15),
  department VARCHAR(100),
  role INT DEFAULT 1,
  salary DECIMAL(10, 2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(employee_id)
);

-- Leave Types Table
CREATE TABLE IF NOT EXISTS leave_types (
  leave_type_id INT PRIMARY KEY AUTO_INCREMENT,
  type_name VARCHAR(50) UNIQUE NOT NULL,
  description VARCHAR(255),
  monthly_limit INT DEFAULT 2,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leave Balance Table (Monthly Tracking)
CREATE TABLE IF NOT EXISTS leave_balance (
  balance_id INT PRIMARY KEY AUTO_INCREMENT,
  emp_id INT NOT NULL,
  year INT NOT NULL,
  month INT NOT NULL,
  casual_total INT DEFAULT 2,
  casual_used INT DEFAULT 0,
  casual_remaining INT DEFAULT 2,
  sick_total INT DEFAULT 2,
  sick_used INT DEFAULT 0,
  sick_remaining INT DEFAULT 2,
  paid_total INT DEFAULT 2,
  paid_used INT DEFAULT 0,
  paid_remaining INT DEFAULT 2,
  other_total INT DEFAULT 2,
  other_used INT DEFAULT 0,
  other_remaining INT DEFAULT 2,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (emp_id) REFERENCES employees(emp_id),
  UNIQUE(emp_id, year, month)
);

-- Leave Applications Table
CREATE TABLE IF NOT EXISTS leave_applications (
  app_id INT PRIMARY KEY AUTO_INCREMENT,
  emp_id INT NOT NULL,
  leave_type VARCHAR(50),
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  duration INT NOT NULL,
  reason VARCHAR(500),
  status VARCHAR(20) DEFAULT 'pending',
  approved_by INT,
  used_leave_types JSON,
  days_deducted JSON,
  salary_cut DECIMAL(10, 2) DEFAULT 0,
  approver_notes VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (emp_id) REFERENCES employees(emp_id),
  FOREIGN KEY (approved_by) REFERENCES employees(emp_id),
  INDEX(emp_id, status),
  INDEX(created_at)
);

-- Public Holidays Table
CREATE TABLE IF NOT EXISTS public_holidays (
  holiday_id INT PRIMARY KEY AUTO_INCREMENT,
  holiday_name VARCHAR(100) NOT NULL,
  holiday_date DATE NOT NULL,
  year INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(holiday_date, year)
);

-- Salary Cuts Table
CREATE TABLE IF NOT EXISTS salary_cuts (
  cut_id INT PRIMARY KEY AUTO_INCREMENT,
  emp_id INT NOT NULL,
  app_id INT NOT NULL,
  cut_date DATE,
  amount DECIMAL(10, 2),
  reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (emp_id) REFERENCES employees(emp_id),
  FOREIGN KEY (app_id) REFERENCES leave_applications(app_id)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
  notif_id INT PRIMARY KEY AUTO_INCREMENT,
  emp_id INT NOT NULL,
  app_id INT NOT NULL,
  message VARCHAR(500),
  notif_type VARCHAR(50),
  is_read INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (emp_id) REFERENCES employees(emp_id),
  FOREIGN KEY (app_id) REFERENCES leave_applications(app_id)
);

-- Insert Leave Types
INSERT INTO leave_types (type_name, description, monthly_limit) VALUES
('Casual', 'Casual Leave', 2),
('Sick', 'Sick Leave', 2),
('Paid', 'Paid Leave', 2),
('Other', 'Other Leave', 2);

-- Insert Sample Public Holidays for 2024-2025
INSERT INTO public_holidays (holiday_name, holiday_date, year) VALUES
('New Year', '2024-01-01', 2024),
('Republic Day', '2024-01-26', 2024),
('Holi', '2024-03-08', 2024),
('Good Friday', '2024-03-29', 2024),
('Eid ul-Fitr', '2024-04-10', 2024),
('Independence Day', '2024-08-15', 2024),
('Janmashtami', '2024-08-26', 2024),
('Ganesh Chaturthi', '2024-09-07', 2024),
('Dussehra', '2024-10-12', 2024),
('Diwali', '2024-11-01', 2024),
('Christmas', '2024-12-25', 2024),
('New Year', '2025-01-01', 2025),
('Republic Day', '2025-01-26', 2025);

-- Sample Employee Data (Change password field after insertion for security)
INSERT INTO employees (employee_id, name, password, email, phone, department, role, salary) VALUES
('EMP001', 'Rahul Kumar', '$2y$10$N9qo8uLOickgx2ZMRZoHe.3RjEMGRmkd7h7F5K5G9K5K5K5K5K5K5', 'rahul@company.com', '9999999999', 'IT', 1, 50000),
('EMP002', 'Priya Singh', '$2y$10$N9qo8uLOickgx2ZMRZoHe.3RjEMGRmkd7h7F5K5G9K5K5K5K5K5K5', 'priya@company.com', '8888888888', 'HR', 2, 60000),
('EMP003', 'Amit Patel', '$2y$10$N9qo8uLOickgx2ZMRZoHe.3RjEMGRmkd7h7F5K5G9K5K5K5K5K5K5', 'amit@company.com', '7777777777', 'Finance', 1, 55000);

-- Create initial leave balance records for sample employees
INSERT INTO leave_balance (emp_id, year, month, casual_total, casual_used, casual_remaining, sick_total, sick_used, sick_remaining, paid_total, paid_used, paid_remaining, other_total, other_used, other_remaining) 
SELECT e.emp_id, 2024, 6, 2, 0, 2, 2, 0, 2, 2, 0, 2, 2, 0, 2 FROM employees e;
