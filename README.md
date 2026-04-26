# CEI326 Web Engineering — Group Project

## Team Members

| Name             |Student ID|         Module          |
|------------------|-------|----------------------------|
| Andreas Nikolaou | 27675 | Admin (`/admin/`)   |
| Michalis Gavriel | 27379 | Submit (`/submit/`) |
| Giannis Loizou   | 30872 | Search (`/search/`) |

## Project Structure

```
CSE_326_Project/
├── api/
│   └── index.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
│       ├── assets.png
│       ├── favicon.ico
│       └── web-engineering-header.svg
├── auth/
│   ├── auth.css
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── database/
│   ├── schema.sql
│   └── seed.sql
├── includes/
│   ├── db.php
│   └── header.php
├── modules/
│   ├── admin/
│   │   ├── admin_dashboard.php
│   │   ├── change_password.php
│   │   ├── configure_system.php
│   │   ├── manage_submissions.php
│   │   ├── manage_users.php
│   │   ├── reports.php
│   │   └── style.css
│   ├── dashboard/
│   │   └── dashboard.php
│   ├── list/
│   │   └── list.php
│   ├── search/
│   │   ├── search_dashboard.php
│   │   ├── statistics.php
│   │   └── style.css
│   └── submit/
│       ├── change_password.php
│       ├── my_profile.php
│       ├── my_submissions.php
│       ├── style.css
│       └── submit_dashboard.php
├── favicon.ico
├── index.php
├── package-lock.json
└── README.md
```

## Prerequisites

Before you begin, make sure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) (includes Apache, MySQL, PHP)
- [Git](https://git-scm.com/)

---

## Installation

### 1. Clone the Repository

Open a terminal (or Git Bash / PowerShell) and navigate to your XAMPP `htdocs` directory:

```bash
cd C:/xampp/htdocs
```

Then clone the project:

```bash
git clone https://github.com/andreasn7/CSE_326_Project.git
```

---

### 2. Start XAMPP Services

Open the **XAMPP Control Panel** and start:
- **Apache**
- **MySQL**

---

### 3. Set Up the Database

1. Open your browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** and create a database (e.g. `webengineering`)
3. Select the new database, go to the **Import** tab
4. Import the schema first:
   - Browse to `database/schema.sql` → click **Go**
5. Then import the seed data:
   - Browse to `database/seed.sql` → click **Go**

---

### 4. Configure the Database Connection

Open the file `includes/db.php` and update the credentials to match your local setup:

```php
$host = 'localhost';
$dbname = 'webengineering'; // your database name
$username = 'root';          // default XAMPP username
$password = '';              // default XAMPP password (empty)
```

---

### 5. Run the Application

Open your browser and navigate to:

```
http://localhost/WebEngineeringPHP/index.php
```

---

### 6. Navigate to the Register Page

Click on the Register button, create an account a login with your credentials to access the system:
