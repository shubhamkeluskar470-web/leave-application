# Leave Application System

A complete Leave Management System built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

✨ **Employee Dashboard**
- Interactive calendar with color-coded leaves (Pending-Yellow, Approved-Green, Rejected-Red)
- Today's date highlighted on calendar
- Leave balance display (Total/Remaining for Casual, Sick, Paid)
- Sticky salary cut section showing monthly deductions
- Leave application form with smart leave-type allocation
- View pending, approved, and all applications

✨ **Leave Application Logic**
- Smart leave allocation: Other → Casual → Sick → Paid → Salary Cut
- Auto-excludes weekends and public holidays from leave count
- Adjusts leave count if days already marked as holidays
- Automatic salary deduction when leaves exceed balance
- Shows salary cut details to employee

✨ **HR/Manager Dashboard**
- Pending approval section
- Review employee applications with full details
- Approve or reject leave requests
- Add approver notes
- View all historical applications

✨ **Database Features**
- Employee authentication with secure password hashing
- Monthly leave balance tracking
- Leave application history
- Salary cut records
- Public holidays management
- Notifications system

## Setup Instructions

### 1. Database Setup
```bash
# Import the database.sql file in your MySQL client
mysql -u root -p < database.sql
```

### 2. Configuration
Edit `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'leave_app');
```

### 3. Add Employees
Insert employee records into the `employees` table:

```sql
INSERT INTO employees (employee_id, name, password, email, department, role, salary) VALUES
('EMP001', 'John Doe', '$2y$10$...', 'john@company.com', 'IT', 1, 50000),
('EMP002', 'HR Manager', '$2y$10$...', 'hr@company.com', 'HR', 2, 60000);
```

**For password hashing**, use this PHP script:
```php
<?php
$password = 'password@123';
$hashed = password_hash($password, PASSWORD_BCRYPT);
echo $hashed;
?>
```

### 4. File Structure
```
leave-application/
├── config.php              # Database configuration
├── index.php              # Login page
├── dashboard.php          # Employee dashboard
├── apply-leave.php        # Leave application processing
├── hr-dashboard.php       # HR approval panel
├── logout.php             # Logout handler
└── database.sql           # Database schema
```

## Login Credentials

### Employee Demo Account
- **Employee ID:** EMP001
- **Name:** Rahul Kumar
- **Password:** password@123

### HR Demo Account
- **Employee ID:** EMP002
- **Name:** Priya Singh
- **Password:** password@123
- **Role:** HR (can approve/reject leaves)

## How to Use

### For Employees:
1. Login with your Employee ID, Name, and Password
2. View calendar with marked holidays and existing leaves
3. Check your leave balance (Casual, Sick, Paid)
4. Apply for leave by selecting dates and type
5. System automatically allocates from available balance
6. View pending, approved, and all applications

### For HR/Manager:
1. Login with HR credentials
2. Go to "Pending Approvals" section
3. Review employee applications
4. Add notes if required
5. Click Approve or Reject
6. View historical applications in "All Applications" tab

## Leave Allocation Logic

The system uses smart leave allocation:
1. Allocate from requested leave type
2. If insufficient, use Other leave
3. If still insufficient, use Casual leave
4. If still insufficient, use Sick leave
5. If still insufficient, use Paid leave
6. If still insufficient, allow and cut salary

## Leave Count Display

**Format:** Total | Remaining
- **Casual:** 2 | X (where X = remaining days)
- **Sick:** 2 | X
- **Paid:** 2 | X

## Salary Cut Section

Shows:
- Recent salary cuts (date and amount)
- Total monthly salary cut
- Sticky position (doesn't scroll)
- Only visible if cuts exist

## Calendar Features

- **Today:** Gradient purple (current date)
- **Pending:** Yellow (pending leave applications)
- **Approved:** Green (approved leaves)
- **Rejected:** Red (rejected applications)
- **Holidays:** Light red (public holidays)
- **Weekends:** Gray (Saturdays and Sundays)

## Monthly Reset

Leave balances are tracked per month and year. New records are automatically created when needed.

## Notes

- Weekends (Saturday & Sunday) are not counted as leave days
- Public holidays are not counted as leave days
- If a leave spans multiple days with holidays, only working days are counted
- Salary is calculated as: (Monthly Salary / 30) × Days Deducted
- All timestamps are in IST (Indian Standard Time)

## Security Features

- Password hashing using BCrypt
- Session-based authentication
- Role-based access control (Employee vs HR)
- SQL prepared statements to prevent injection
- Input validation and sanitization

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

This project is free to use and modify.

## Support

For issues or questions, contact your HR department or system administrator.
