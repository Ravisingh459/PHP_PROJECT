-- NexaWork: Full Indian localization for existing database
-- Run: mysql -u root -p freelancehub < database/update_indian_full.sql

USE freelancehub;

-- ============================================================
-- USERS: Indian names, phones, emails
-- ============================================================

UPDATE users SET email='rajesh.kumar@email.com', first_name='Rajesh', last_name='Kumar', phone='+91 98765 43210' WHERE email='john.client@email.com' OR (id=2 AND role='client');
UPDATE users SET email='priya.patel@email.com', first_name='Priya', last_name='Patel', phone='+91 98200 12345' WHERE email='sarah.client@email.com' OR (id=3 AND role='client');

UPDATE users SET email='arjun.singh@email.com', first_name='Arjun', last_name='Singh', phone='+91 98765 11111' WHERE email='mike.freelancer@email.com' OR (id=4 AND role='freelancer');
UPDATE users SET email='ananya.reddy@email.com', first_name='Ananya', last_name='Reddy', phone='+91 98480 22222' WHERE email='emma.freelancer@email.com' OR (id=5 AND role='freelancer');
UPDATE users SET email='vikram.mehta@email.com', first_name='Vikram', last_name='Mehta', phone='+91 98989 33333' WHERE email='david.freelancer@email.com' OR (id=6 AND role='freelancer');
UPDATE users SET email='kavita.sharma@email.com', first_name='Kavita', last_name='Sharma', phone='+91 98100 44444' WHERE email='lisa.freelancer@email.com' OR (id=7 AND role='freelancer');

-- ============================================================
-- CLIENT PROFILES: Indian companies & cities
-- ============================================================

UPDATE client_profiles cp
JOIN users u ON cp.user_id = u.id
SET cp.company_name='TechVista Solutions Pvt. Ltd.',
    cp.company_website='https://techvista.in',
    cp.bio='Bangalore-based SaaS startup building products for Indian SMEs. We hire top freelancers across India.',
    cp.location='Bangalore, Karnataka',
    cp.total_spent=1250000.00
WHERE u.email='rajesh.kumar@email.com';

UPDATE client_profiles cp
JOIN users u ON cp.user_id = u.id
SET cp.company_name='Digital Creations India',
    cp.company_website='https://digitalcreations.in',
    cp.bio='Full-service digital marketing and design agency serving clients across Mumbai and Pune.',
    cp.location='Mumbai, Maharashtra',
    cp.total_spent=680000.00
WHERE u.email='priya.patel@email.com';

-- ============================================================
-- FREELANCER PROFILES: Indian locations & realistic INR rates
-- ============================================================

UPDATE freelancer_profiles fp
JOIN users u ON fp.user_id = u.id
SET fp.title='Full Stack Developer',
    fp.bio='Experienced PHP and JavaScript developer with 8+ years building web applications for Indian startups and enterprises.',
    fp.hourly_rate=1500.00,
    fp.location='Bangalore, Karnataka',
    fp.total_earnings=2850000.00
WHERE u.email='arjun.singh@email.com';

UPDATE freelancer_profiles fp
JOIN users u ON fp.user_id = u.id
SET fp.title='UI/UX Designer',
    fp.bio='Creative designer specializing in modern, user-centered interfaces for Indian fintech and e-commerce brands.',
    fp.hourly_rate=1200.00,
    fp.location='Hyderabad, Telangana',
    fp.total_earnings=1920000.00
WHERE u.email='ananya.reddy@email.com';

UPDATE freelancer_profiles fp
JOIN users u ON fp.user_id = u.id
SET fp.title='Mobile App Developer',
    fp.bio='iOS and Android developer with expertise in React Native and Flutter. Based in Pune, serving clients pan-India.',
    fp.hourly_rate=1800.00,
    fp.location='Pune, Maharashtra',
    fp.total_earnings=2280000.00
WHERE u.email='vikram.mehta@email.com';

UPDATE freelancer_profiles fp
JOIN users u ON fp.user_id = u.id
SET fp.title='Content Writer & SEO Expert',
    fp.bio='Professional Hindi and English content writer with SEO expertise for Indian businesses and D2C brands.',
    fp.hourly_rate=800.00,
    fp.location='New Delhi, Delhi',
    fp.total_earnings=1680000.00
WHERE u.email='kavita.sharma@email.com';

-- ============================================================
-- PROJECTS: Indian context & INR budgets
-- ============================================================

UPDATE projects SET
    title='E-commerce Website for Indian Retail Brand',
    description='Need a full-featured e-commerce website with UPI and Razorpay payment integration, product catalog in English and Hindi, and admin panel. Must be mobile-first for Indian users.',
    budget=250000.00
WHERE title LIKE '%E-commerce Website%' OR title='E-commerce Website Development';

UPDATE projects SET
    title='Food Delivery App for Tier-2 Cities',
    description='Looking for an experienced mobile developer to build a food delivery app for iOS and Android targeting cities like Indore, Jaipur, and Lucknow with real-time tracking.',
    budget=450000.00
WHERE title LIKE '%Food Delivery%';

UPDATE projects SET
    title='Brand Identity for Ayurveda Startup',
    description='Complete brand identity package for our Mumbai-based wellness brand including logo, colour palette, typography, and bilingual brand guidelines (English/Hindi).',
    budget=85000.00
WHERE title LIKE '%Brand Identity%';

UPDATE projects SET
    title='SEO Content Strategy for Indian Market',
    description='Develop comprehensive SEO content strategy targeting Indian keywords and write 20 blog posts optimised for Google India search.',
    budget=120000.00
WHERE title LIKE '%SEO Content%';

UPDATE projects SET
    title='Payment Gateway API Integration',
    description='Integrate Razorpay and Paytm APIs into our existing PHP application for our Bangalore fintech platform.',
    budget=175000.00
WHERE title LIKE '%API Integration%';

-- ============================================================
-- REVIEWS: Indian client voices
-- ============================================================

UPDATE reviews r
JOIN users u ON r.reviewer_id = u.id
JOIN users f ON r.reviewee_id = f.id
SET r.comment='Arjun delivered exceptional work on our Razorpay and Paytm API integration. Professional, responsive across IST hours, and met all deadlines. Highly recommended for Indian fintech startups!'
WHERE f.email='arjun.singh@email.com' AND u.role='client';

UPDATE reviews r
JOIN users u ON r.reviewer_id = u.id
JOIN users f ON r.reviewee_id = f.id
SET r.comment='Ananya created a beautiful brand identity for our Ayurveda startup in Mumbai. She understood Indian cultural aesthetics perfectly and delivered bilingual Hindi-English guidelines on time.'
WHERE f.email='ananya.reddy@email.com' AND u.role='client';

-- ============================================================
-- MESSAGES & NOTIFICATIONS
-- ============================================================

UPDATE messages SET body='Hi Arjun, I reviewed your bid for our retail e-commerce project. Can you share examples of UPI integration you have done for Indian clients?' WHERE body LIKE '%Hi Mike%' OR body LIKE '%John%';
UPDATE messages SET body='Hi Rajesh, I have integrated Razorpay and PhonePe UPI for three Bangalore-based D2C brands. Happy to walk you through the architecture on a call!' WHERE body LIKE '%Hi John%';
UPDATE messages SET body='Ananya, the initial logo concepts for our Ayurveda brand look wonderful! Can we schedule a call tomorrow to discuss the Hindi typography options?' WHERE body LIKE '%Emma%';
UPDATE messages SET body='Absolutely Priya ji! I am available tomorrow afternoon IST. I will send a few more variations inspired by traditional Indian motifs before our call.' WHERE body LIKE '%Absolutely! I am available tomorrow afternoon.%' AND body NOT LIKE '%IST%';

UPDATE notifications SET message='Arjun Singh submitted a bid on your E-commerce Website project.' WHERE message LIKE '%Mike Chen%';
UPDATE notifications SET message='Rajesh Kumar sent you a message about E-commerce Project.' WHERE message LIKE '%John Smith%';
UPDATE notifications SET message='Payment of ₹1,68,000 has been released for Razorpay API Integration Project.' WHERE message LIKE '%$3,200%' OR message LIKE '%3,200 has been released%';
UPDATE notifications SET message='You have been hired for Ayurveda Brand Identity project by Priya Patel.' WHERE message LIKE '%Brand Identity Design project.%';

-- ============================================================
-- SITE SETTINGS
-- ============================================================

UPDATE site_settings SET setting_value='NexaWork' WHERE setting_key='site_name';
UPDATE site_settings SET setting_value='AI-Powered Talent Marketplace' WHERE setting_key='site_tagline';
UPDATE site_settings SET setting_value='support@nexawork.com' WHERE setting_key='site_email';
UPDATE site_settings SET setting_value='5000' WHERE setting_key='min_project_budget';
UPDATE site_settings SET setting_value='5000000' WHERE setting_key='max_project_budget';
