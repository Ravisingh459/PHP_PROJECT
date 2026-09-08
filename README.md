# NexaWork – AI-Powered Talent Marketplace

NexaWork is a full-stack freelance marketplace where clients post projects, freelancers bid with smart match scoring, and both parties collaborate through messaging, milestone escrow, contracts, and reviews.

## What's New in v2.0

- **Smart Match Engine** — Multi-factor scoring (skills, ratings, experience, budget fit)
- **Trust Score System** — Verified badges for reliable hiring decisions
- **Live Activity Feed** — Real-time marketplace pulse on the homepage
- **Global Search Autocomplete** — Instant suggestions for projects, freelancers, and skills
- **Freelancer Leaderboard** — Ranked by ratings, projects, and earnings
- **Milestone Escrow** — Phased payments per contract deliverable
- **Modern UI** — Teal/orange design system with glassmorphism and animations

## Tech Stack

| Layer | Technologies |
|-------|-------------|
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript, jQuery, AJAX, Chart.js |
| Backend | PHP 8 (Core PHP, no framework) |
| Database | MySQL 8 |
| Email | PHPMailer |
| Security | PDO prepared statements, CSRF tokens, XSS sanitization, bcrypt passwords |

## Quick Start

```bash
# 1. Import database
mysql -u root -p < database/freelancehub.sql

# 2. Run v2 migration (milestones)
mysql -u root -p freelancehub < database/migration_v2.sql

# 3. Install dependencies
composer install

# 4. Configure includes/config.php with your DB and email settings

# 5. Visit http://localhost/PHP/freelancehub
```

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@freelancehub.com | password |
| Client | rajesh.kumar@email.com | password |
| Client | priya.patel@email.com | password |
| Freelancer | arjun.singh@email.com | password |
| Freelancer | ananya.reddy@email.com | password |

## Key Pages

| Page | Description |
|------|-------------|
| `/` | Landing page with live ticker, smart search, animated stats |
| `/leaderboard.php` | Top freelancers ranked by performance |
| `/projects.php` | Browse and filter open projects |
| `/api/search.php` | Autocomplete search API |
| `/api/activity.php` | Live marketplace activity feed |

## Project Structure

```
freelancehub/
├── admin/              # Admin panel
├── client/             # Client dashboard
├── freelancer/         # Freelancer dashboard
├── api/                # AJAX endpoints (activity, search, messages, notifications)
├── assets/             # CSS, JS, images, uploads
├── database/           # SQL schema + migrations
├── includes/           # Core PHP (config, auth, functions)
├── leaderboard.php     # Top talent rankings
└── index.php           # Landing page
```

## License

MIT License – free for educational and commercial use.

See [INSTALLATION.md](INSTALLATION.md) for detailed setup instructions.
