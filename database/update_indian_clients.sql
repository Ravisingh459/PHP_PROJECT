-- NexaWork: Update existing database to Indian clients and reviews
-- Run: mysql -u root -p freelancehub < database/update_indian_clients.sql

USE freelancehub;

-- Update client users
UPDATE users SET email='rajesh.kumar@email.com', first_name='Rajesh', last_name='Kumar', phone='+91 98765 43210' WHERE id=2 AND role='client';
UPDATE users SET email='priya.patel@email.com', first_name='Priya', last_name='Patel', phone='+91 98200 12345' WHERE id=3 AND role='client';

-- Update freelancer users (for consistent demo data)
UPDATE users SET email='arjun.singh@email.com', first_name='Arjun', last_name='Singh', phone='+91 98765 11111' WHERE id=4 AND role='freelancer';
UPDATE users SET email='ananya.reddy@email.com', first_name='Ananya', last_name='Reddy', phone='+91 98480 22222' WHERE id=5 AND role='freelancer';
UPDATE users SET email='vikram.mehta@email.com', first_name='Vikram', last_name='Mehta', phone='+91 98989 33333' WHERE id=6 AND role='freelancer';
UPDATE users SET email='kavita.sharma@email.com', first_name='Kavita', last_name='Sharma', phone='+91 98100 44444' WHERE id=7 AND role='freelancer';

-- Update client profiles
UPDATE client_profiles SET company_name='TechVista Solutions Pvt. Ltd.', company_website='https://techvista.in', bio='Bangalore-based SaaS startup building products for Indian SMEs.', location='Bangalore, Karnataka' WHERE user_id=2;
UPDATE client_profiles SET company_name='Digital Creations India', company_website='https://digitalcreations.in', bio='Full-service digital marketing and design agency serving clients across Mumbai and Pune.', location='Mumbai, Maharashtra' WHERE user_id=3;

-- Update freelancer locations to India
UPDATE freelancer_profiles SET location='Bangalore, Karnataka' WHERE user_id=4;
UPDATE freelancer_profiles SET location='Hyderabad, Telangana' WHERE user_id=5;
UPDATE freelancer_profiles SET location='Pune, Maharashtra' WHERE user_id=6;
UPDATE freelancer_profiles SET location='New Delhi, Delhi' WHERE user_id=7;

-- Update reviews to Indian client voices
UPDATE reviews SET comment='Arjun delivered exceptional work on our Razorpay and Paytm API integration. Professional, responsive across IST hours, and met all deadlines. Highly recommended for Indian fintech startups!' WHERE contract_id=3 AND reviewer_id=2;

-- Insert Priya Patel review if not exists (contract 1 = brand identity)
INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, comment)
SELECT 1, 3, 5, 5, 'Ananya created a beautiful brand identity for our Ayurveda startup in Mumbai. She understood Indian cultural aesthetics perfectly and delivered bilingual Hindi-English guidelines on time.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM reviews WHERE contract_id=1 AND reviewer_id=3);

-- Update notifications with Indian names
UPDATE notifications SET message='Arjun Singh submitted a bid on your E-commerce Website project.' WHERE message LIKE '%Mike Chen%';
UPDATE notifications SET message='Rajesh Kumar sent you a message about E-commerce Project.' WHERE message LIKE '%John Smith%';
UPDATE notifications SET message='Payment of ₹1,68,000 has been released for Razorpay API Integration Project.' WHERE message LIKE '%$3,200%';

-- Update messages with Indian client names
UPDATE messages SET body='Hi Arjun, I reviewed your bid for our retail e-commerce project. Can you share examples of UPI integration you have done for Indian clients?' WHERE sender_id=2 AND body LIKE '%Hi Mike%';
UPDATE messages SET body='Hi Rajesh, I have integrated Razorpay and PhonePe UPI for three Bangalore-based D2C brands. Happy to walk you through the architecture on a call!' WHERE sender_id=4 AND body LIKE '%Hi John%';
UPDATE messages SET body='Ananya, the initial logo concepts for our Ayurveda brand look wonderful! Can we schedule a call tomorrow to discuss the Hindi typography options?' WHERE sender_id=3 AND body LIKE '%Emma%';
