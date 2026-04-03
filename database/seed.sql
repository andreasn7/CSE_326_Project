USE cei326_project;

INSERT INTO users (username, email, password_hash, role) VALUES
('Admin User',  'admin@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Alice Smith', 'alice@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Bob Jones',   'bob@example.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO posts (user_id, title, category, description) VALUES
(1, 'Introduction to Web Engineering',  'report',  'An overview of web engineering concepts and best practices for modern web development.'),
(2, 'RESTful API Design Principles',    'project', 'A deep dive into REST architecture, HTTP verbs, status codes, and API versioning strategies.'),
(1, 'Database Normalisation Guide',     'report',  'Step-by-step guide to 1NF, 2NF and 3NF with practical SQL examples.'),
(3, 'Responsive Design with CSS Grid',  'project', 'How to build fully responsive layouts using CSS Grid and media queries without external libraries.'),
(2, 'PHP Security Checklist',           'other',   'Essential security practices: prepared statements, password hashing, XSS prevention, and session management.');
