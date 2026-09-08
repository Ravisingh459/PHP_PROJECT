-- FreelanceHub Database Schema
-- MySQL 8.0+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



-- ============================================================
-- USERS & AUTHENTICATION
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client', 'freelancer') NOT NULL DEFAULT 'client',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    language VARCHAR(10) NOT NULL DEFAULT 'en',
    dark_mode TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    permissions JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE email_verification_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PROFILES
-- ============================================================

CREATE TABLE freelancer_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    title VARCHAR(200) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    experience_years INT UNSIGNED DEFAULT 0,
    location VARCHAR(200) DEFAULT NULL,
    availability ENUM('available', 'busy', 'unavailable') DEFAULT 'available',
    resume VARCHAR(255) DEFAULT NULL,
    total_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    completed_projects INT UNSIGNED NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_fp_rating (avg_rating),
    INDEX idx_fp_location (location)
) ENGINE=InnoDB;

CREATE TABLE client_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    company_name VARCHAR(200) DEFAULT NULL,
    company_website VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    location VARCHAR(200) DEFAULT NULL,
    total_spent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    projects_posted INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SKILLS
-- ============================================================

CREATE TABLE skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(100) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE freelancer_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    proficiency ENUM('beginner', 'intermediate', 'expert') DEFAULT 'intermediate',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_freelancer_skill (freelancer_id, skill_id),
    FOREIGN KEY (freelancer_id) REFERENCES freelancer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skill_tests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    questions JSON NOT NULL,
    passing_score INT UNSIGNED NOT NULL DEFAULT 70,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE freelancer_skill_tests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT UNSIGNED NOT NULL,
    skill_test_id INT UNSIGNED NOT NULL,
    score INT UNSIGNED NOT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (freelancer_id) REFERENCES freelancer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_test_id) REFERENCES skill_tests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PROJECTS & BIDS
-- ============================================================

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    budget DECIMAL(12,2) NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    attachment VARCHAR(255) DEFAULT NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_projects_status (status),
    INDEX idx_projects_category (category),
    INDEX idx_projects_budget (budget)
) ENGINE=InnoDB;

CREATE TABLE project_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uk_project_skill (project_id, skill_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    freelancer_id INT UNSIGNED NOT NULL,
    proposed_budget DECIMAL(12,2) NOT NULL,
    delivery_days INT UNSIGNED NOT NULL,
    cover_letter TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_project_freelancer (project_id, freelancer_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bids_status (status)
) ENGINE=InnoDB;

CREATE TABLE saved_projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_saved (freelancer_id, project_id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CONTRACTS & PAYMENTS
-- ============================================================

CREATE TABLE contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    freelancer_id INT UNSIGNED NOT NULL,
    bid_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('active', 'completed', 'cancelled', 'disputed') NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    progress INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    payer_id INT UNSIGNED NOT NULL,
    payee_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('escrow', 'released', 'refunded', 'pending') NOT NULL DEFAULT 'escrow',
    transaction_ref VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    released_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_payments_status (status)
) ENGINE=InnoDB;

CREATE TABLE disputes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    raised_by INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('open', 'under_review', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    resolution TEXT DEFAULT NULL,
    resolved_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (raised_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- MESSAGING
-- ============================================================

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    project_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_messages_receiver (receiver_id, is_read),
    INDEX idx_messages_conversation (sender_id, receiver_id)
) ENGINE=InnoDB;

CREATE TABLE message_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PORTFOLIO & REVIEWS
-- ============================================================

CREATE TABLE portfolios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    project_url VARCHAR(255) DEFAULT NULL,
    technologies VARCHAR(500) DEFAULT NULL,
    completed_date DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (freelancer_id) REFERENCES freelancer_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    reviewee_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_contract_reviewer (contract_id, reviewer_id),
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reviews_reviewee (reviewee_id)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS & REPORTS
-- ============================================================

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    reported_user_id INT UNSIGNED DEFAULT NULL,
    reported_project_id INT UNSIGNED DEFAULT NULL,
    reason VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ACHIEVEMENTS & CERTIFICATES
-- ============================================================

CREATE TABLE achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'trophy',
    criteria VARCHAR(200) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE freelancer_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_freelancer_achievement (freelancer_id, achievement_id),
    FOREIGN KEY (freelancer_id) REFERENCES freelancer_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    freelancer_id INT UNSIGNED NOT NULL,
    certificate_code VARCHAR(50) NOT NULL UNIQUE,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES freelancer_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SITE SETTINGS
-- ============================================================

CREATE TABLE site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Password for all sample users: password
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (email, password, role, first_name, last_name, phone, is_verified, is_active) VALUES
('admin@freelancehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Admin', '+91 80 4000 0000', 1, 1),
('rajesh.kumar@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Rajesh', 'Kumar', '+91 98765 43210', 1, 1),
('priya.patel@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Priya', 'Patel', '+91 98200 12345', 1, 1),
('arjun.singh@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', 'Arjun', 'Singh', '+91 98765 11111', 1, 1),
('ananya.reddy@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', 'Ananya', 'Reddy', '+91 98480 22222', 1, 1),
('vikram.mehta@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', 'Vikram', 'Mehta', '+91 98989 33333', 1, 1),
('kavita.sharma@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'freelancer', 'Kavita', 'Sharma', '+91 98100 44444', 1, 1);

INSERT INTO admin_users (user_id, permissions) VALUES (1, '{"all": true}');

INSERT INTO client_profiles (user_id, company_name, company_website, bio, location, total_spent, projects_posted) VALUES
(2, 'TechVista Solutions Pvt. Ltd.', 'https://techvista.in', 'Bangalore-based SaaS startup building products for Indian SMEs. We hire top freelancers across India.', 'Bangalore, Karnataka', 1250000.00, 5),
(3, 'Digital Creations India', 'https://digitalcreations.in', 'Full-service digital marketing and design agency serving clients across Mumbai and Pune.', 'Mumbai, Maharashtra', 680000.00, 3);

INSERT INTO freelancer_profiles (user_id, title, bio, hourly_rate, experience_years, location, availability, total_earnings, completed_projects, avg_rating, total_reviews) VALUES
(4, 'Full Stack Developer', 'Experienced PHP and JavaScript developer with 8+ years building web applications for Indian startups and enterprises.', 1500.00, 8, 'Bangalore, Karnataka', 'available', 2850000.00, 32, 4.85, 28),
(5, 'UI/UX Designer', 'Creative designer specializing in modern, user-centered interfaces for Indian fintech and e-commerce brands.', 1200.00, 5, 'Hyderabad, Telangana', 'available', 1920000.00, 24, 4.90, 22),
(6, 'Mobile App Developer', 'iOS and Android developer with expertise in React Native and Flutter. Based in Pune, serving clients pan-India.', 1800.00, 6, 'Pune, Maharashtra', 'busy', 2280000.00, 18, 4.75, 15),
(7, 'Content Writer & SEO Expert', 'Professional Hindi and English content writer with SEO expertise for Indian businesses.', 800.00, 10, 'New Delhi, Delhi', 'available', 1680000.00, 45, 4.95, 40);

INSERT INTO skills (name, category) VALUES
('PHP', 'Development'), ('JavaScript', 'Development'), ('MySQL', 'Development'),
('React', 'Development'), ('Node.js', 'Development'), ('Python', 'Development'),
('UI Design', 'Design'), ('UX Design', 'Design'), ('Figma', 'Design'),
('Photoshop', 'Design'), ('Illustrator', 'Design'),
('iOS Development', 'Mobile'), ('Android Development', 'Mobile'), ('React Native', 'Mobile'),
('Content Writing', 'Writing'), ('SEO', 'Writing'), ('Copywriting', 'Writing'),
('WordPress', 'Development'), ('Laravel', 'Development'), ('Bootstrap', 'Development');

INSERT INTO freelancer_skills (freelancer_id, skill_id, proficiency) VALUES
(1, 1, 'expert'), (1, 2, 'expert'), (1, 3, 'expert'), (1, 18, 'expert'), (1, 19, 'expert'),
(2, 7, 'expert'), (2, 8, 'expert'), (2, 9, 'expert'), (2, 10, 'expert'),
(3, 2, 'expert'), (3, 12, 'expert'), (3, 13, 'expert'), (3, 14, 'expert'),
(4, 15, 'expert'), (4, 16, 'expert'), (4, 17, 'expert');

INSERT INTO projects (client_id, title, description, category, budget, deadline, status) VALUES
(2, 'E-commerce Website for Indian Retail Brand', 'Need a full-featured e-commerce website with UPI and Razorpay payment integration, product catalog in English and Hindi, and admin panel. Must be mobile-first for Indian users.', 'Web Development', 250000.00, '2026-08-15', 'open'),
(2, 'Food Delivery App for Tier-2 Cities', 'Looking for an experienced mobile developer to build a food delivery app for iOS and Android targeting cities like Indore, Jaipur, and Lucknow with real-time tracking.', 'Mobile Development', 450000.00, '2026-09-30', 'open'),
(3, 'Brand Identity for Ayurveda Startup', 'Complete brand identity package for our Mumbai-based wellness brand including logo, colour palette, typography, and bilingual brand guidelines (English/Hindi).', 'Design', 85000.00, '2026-07-20', 'open'),
(3, 'SEO Content Strategy for Indian Market', 'Develop comprehensive SEO content strategy targeting Indian keywords and write 20 blog posts optimised for Google India search.', 'Writing', 120000.00, '2026-08-01', 'in_progress'),
(2, 'Payment Gateway API Integration', 'Integrate Razorpay and Paytm APIs into our existing PHP application for our Bangalore fintech platform.', 'Web Development', 175000.00, '2026-07-10', 'completed');

INSERT INTO project_skills (project_id, skill_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 18),
(2, 12), (2, 13), (2, 14),
(3, 7), (3, 8), (3, 9),
(4, 15), (4, 16),
(5, 1), (5, 3);

INSERT INTO bids (project_id, freelancer_id, proposed_budget, delivery_days, cover_letter, status) VALUES
(1, 4, 235000.00, 45, 'I have built multiple e-commerce platforms for Indian D2C brands with UPI and Razorpay integration. Happy to share case studies from Bangalore and Delhi clients.', 'pending'),
(1, 6, 248000.00, 40, 'I have strong full-stack skills and have delivered e-commerce apps for Indian retail clients across Maharashtra and Karnataka.', 'pending'),
(2, 6, 420000.00, 60, 'I specialise in React Native and have built 5+ food delivery apps for Indian startups in Pune and Hyderabad.', 'pending'),
(3, 5, 78000.00, 14, 'I would love to create a culturally resonant brand identity for your Ayurveda startup. My portfolio includes 20+ Indian wellness and FMCG brands.', 'accepted'),
(4, 7, 115000.00, 30, 'As an SEO expert focused on the Indian market, I can create a Hindi-English content strategy that drives organic traffic from Google India.', 'accepted'),
(5, 4, 168000.00, 21, 'I have integrated Razorpay, Paytm, and CCAvenue APIs in multiple PHP applications for Indian fintech companies.', 'accepted');

INSERT INTO contracts (project_id, client_id, freelancer_id, bid_id, amount, status, start_date, progress) VALUES
(3, 3, 5, 4, 78000.00, 'completed', '2026-06-01', 100),
(4, 3, 7, 5, 115000.00, 'active', '2026-06-05', 40),
(5, 2, 4, 6, 168000.00, 'completed', '2026-05-01', 100);

INSERT INTO payments (contract_id, payer_id, payee_id, amount, status, transaction_ref, description, released_at) VALUES
(1, 3, 5, 78000.00, 'released', 'TXN-IN-2026-001', 'Ayurveda Brand Identity - Released', '2026-06-15 11:00:00'),
(2, 3, 7, 115000.00, 'escrow', 'TXN-IN-2026-002', 'Indian SEO Content Strategy - Escrow', NULL),
(3, 2, 4, 168000.00, 'released', 'TXN-IN-2026-003', 'Razorpay API Integration - Released', '2026-06-10 14:30:00');

INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, project_id) VALUES
(2, 4, 'Regarding E-commerce Project', 'Hi Arjun, I reviewed your bid for our retail e-commerce project. Can you share examples of UPI integration you have done for Indian clients?', 1, 1),
(4, 2, 'Re: E-commerce Project', 'Hi Rajesh, I have integrated Razorpay and PhonePe UPI for three Bangalore-based D2C brands. Happy to walk you through the architecture on a call!', 1, 1),
(3, 5, 'Brand Identity Progress', 'Ananya, the initial logo concepts for our Ayurveda brand look wonderful! Can we schedule a call tomorrow to discuss the Hindi typography options?', 0, 3),
(5, 3, 'Re: Brand Identity Progress', 'Absolutely Priya ji! I am available tomorrow afternoon IST. I will send a few more variations inspired by traditional Indian motifs before our call.', 0, 3);

INSERT INTO portfolios (freelancer_id, title, description, technologies, completed_date) VALUES
(1, 'Indian SaaS Analytics Dashboard', 'Built a complete SaaS analytics dashboard for a Bangalore fintech startup with real-time UPI transaction tracking.', 'PHP, Laravel, React, MySQL', '2025-12-01'),
(1, 'EdTech Learning Platform', 'Developed an e-learning platform for an Indian coaching institute with Hindi video courses and progress tracking.', 'PHP, JavaScript, MySQL', '2025-08-15'),
(2, 'Indian FinTech Mobile App UI', 'Designed complete UI/UX for a Mumbai-based financial management app targeting young professionals.', 'Figma, Adobe XD', '2025-10-20'),
(2, 'D2C E-commerce Redesign', 'Complete redesign of an Indian e-commerce website improving conversion by 35% during festive sale season.', 'Figma, Photoshop', '2025-06-10'),
(3, 'Fitness Tracking App for India', 'Cross-platform fitness app with yoga and workout plans tailored for Indian users.', 'React Native, Firebase', '2025-11-05'),
(4, 'Indian Tech Blog Content Series', 'Wrote 50+ SEO-optimised Hindi-English articles driving 200% traffic increase on Google India.', 'SEO, Content Writing', '2025-09-30');

INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, comment) VALUES
(3, 2, 4, 5, 'Arjun delivered exceptional work on our Razorpay and Paytm API integration. Professional, responsive across IST hours, and met all deadlines. Highly recommended for Indian fintech startups!'),
(1, 3, 5, 5, 'Ananya created a beautiful brand identity for our Ayurveda startup in Mumbai. She understood Indian cultural aesthetics perfectly and delivered bilingual Hindi-English guidelines on time.');

INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES
(2, 'new_bid', 'New Bid Received', 'Arjun Singh submitted a bid on your E-commerce Website project.', '/client/view-bids.php?id=1', 0),
(4, 'new_message', 'New Message', 'Rajesh Kumar sent you a message about E-commerce Project.', '/freelancer/messages.php', 0),
(5, 'project_assigned', 'Project Assigned', 'You have been hired for Ayurveda Brand Identity project by Priya Patel.', '/freelancer/contracts.php', 0),
(4, 'payment_released', 'Payment Released', 'Payment of ₹1,68,000 has been released for Razorpay API Integration Project.', '/freelancer/earnings.php', 1);

INSERT INTO achievements (name, description, icon, criteria) VALUES
('First Project', 'Complete your first project on NexaWork', 'star', 'completed_projects >= 1'),
('Top Rated', 'Maintain a 4.5+ rating with 10+ reviews', 'award', 'avg_rating >= 4.5 AND total_reviews >= 10'),
('Rising Star', 'Complete 5 projects in first 3 months', 'rocket', 'completed_projects >= 5'),
('Expert Coder', 'Pass 3 skill tests with 90%+ score', 'code', 'skill_tests_passed >= 3'),
('Client Favorite', 'Receive 5 five-star reviews', 'heart', 'five_star_reviews >= 5');

INSERT INTO freelancer_achievements (freelancer_id, achievement_id) VALUES
(1, 1), (1, 2), (2, 1), (2, 2), (4, 1), (4, 2);

INSERT INTO certificates (contract_id, freelancer_id, certificate_code) VALUES
(3, 1, 'CERT-NW-2026-0001');

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'NexaWork'),
('site_tagline', 'AI-Powered Talent Marketplace'),
('site_email', 'support@nexawork.com'),
('commission_rate', '10'),
('min_project_budget', '5000'),
('max_project_budget', '5000000'),
('enable_registration', '1'),
('maintenance_mode', '0');

INSERT INTO skill_tests (skill_id, title, questions, passing_score, duration_minutes) VALUES
(1, 'PHP Fundamentals Test', '[{"q":"What does PHP stand for?","options":["Personal Home Page","PHP: Hypertext Preprocessor","Private Hosting Protocol","Programmed HTML Pages"],"answer":1},{"q":"Which function is used to connect to MySQL in PHP?","options":["mysql_connect()","mysqli_connect()","db_connect()","connect_mysql()"],"answer":1}]', 70, 30),
(7, 'UI Design Principles Test', '[{"q":"What is the primary purpose of whitespace in design?","options":["Fill empty space","Improve readability and focus","Make designs colorful","Increase file size"],"answer":1}]', 70, 20);
