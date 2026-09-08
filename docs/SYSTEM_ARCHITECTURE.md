# NexaWork – AI-Powered Freelance Marketplace
## System Architecture & Technical Specifications Document

---

## 1. High-Level Architecture Overview

NexaWork is built on a modular, decoupled 3-tier enterprise architecture powered by Core PHP 8, MySQL 8, and an asynchronous JavaScript/AJAX AI integration layer.

```mermaid
graph TD
    Client[Browser / Client UI] -->|HTTP/HTTPS + AJAX| FrontendLayer[Bootstrap 5 / Glassmorphism UI]
    FrontendLayer -->|API Calls & Form Posts| ControllerLayer[PHP 8 Controller & Auth Layer]
    ControllerLayer -->|PDO Prepared Queries| Database[(MySQL 8 Database)]
    ControllerLayer -->|AI Scope & Proposal Processing| AIService[NexaAI Service Engine]
    ControllerLayer -->|SMTP Notifications| PHPMailer[Email Notification Gateway]
    AIService -->|Semantic Match Vector Scores| Database
```

---

## 2. Entity-Relationship (ER) Diagram

```mermaid
erDiagram
    USERS ||--o{ FREELANCER_PROFILES : "has profile"
    USERS ||--o{ CLIENT_PROFILES : "has profile"
    USERS ||--o{ PROJECTS : "posts"
    USERS ||--o{ BIDS : "submits"
    PROJECTS ||--o{ BIDS : "receives"
    PROJECTS ||--o{ CONTRACTS : "initiates"
    CONTRACTS ||--o{ CONTRACT_MILESTONES : "contains"
    CONTRACTS ||--o{ PAYMENTS : "generates escrow"
    FREELANCER_PROFILES ||--o{ PORTFOLIOS : "showcases"
    FREELANCER_PROFILES ||--o{ FREELANCER_SKILLS : "possesses"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AI_LOGS : "triggers"

    USERS {
        int id PK
        string email UK
        string password
        enum role
        string first_name
        string last_name
        boolean is_verified
        boolean is_active
    }

    PROJECTS {
        int id PK
        int client_id FK
        string title
        text description
        string category
        decimal budget
        date deadline
        enum status
    }

    CONTRACTS {
        int id PK
        int project_id FK
        int client_id FK
        int freelancer_id FK
        decimal amount
        enum status
    }

    CONTRACT_MILESTONES {
        int id PK
        int contract_id FK
        string title
        decimal amount
        enum status
    }
```

---

## 3. AI Service Pipeline & Sequence Diagram

```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Frontend as Web Client
    participant API as NexaAI API (ai_assistant.php)
    participant Engine as AI Service Engine (ai_service.php)
    participant DB as MySQL Database

    Client->>Frontend: Fills title & category, clicks "Auto-Generate Scope"
    Frontend->>API: POST /api/ai_assistant.php (action=generate_scope)
    API->>Engine: aiGenerateProjectScope(title, category)
    Engine-->>API: Returns structured requirements, milestones & budget recommendation
    API-->>Frontend: JSON Response
    Frontend->>Client: Auto-populates description & recommended budget
```

---

## 4. Security Architecture & Controls

1. **Authentication & Session Security**:
   - Passwords hashed using `password_hash()` with BCrypt (cost factor 12).
   - Session identifiers regenerated (`session_regenerate_id(true)`) upon authentication to prevent session fixation.
   - Brute-force protection via `login_attempts` tracking.

2. **Cross-Site Request Forgery (CSRF)**:
   - Cryptographic CSRF tokens generated per session using `random_bytes(32)`.
   - Verified across all state-modifying `POST` requests via `verifyCsrfToken()`.

3. **File Upload Security**:
   - MIME type verification using server-side `finfo_file` (Fileinfo extension).
   - Extension blacklist prohibiting dangerous executables (`.php`, `.phar`, `.sh`, `.exe`, `.cgi`).
   - Obfuscated file renaming (`bin2hex(random_bytes(16))`).
   - Server-level execution prevention via `assets/uploads/.htaccess`.

4. **Database Security**:
   - 100% prepared statements via PDO with parameterized bindings to prevent SQL Injection.

---

## 5. API Endpoints Reference

| Endpoint | Method | Action Parameter | Description |
|----------|--------|------------------|-------------|
| `/api/ai_assistant.php` | `POST` | `generate_scope` | Generates project specifications & milestones |
| `/api/ai_assistant.php` | `POST` | `analyze_proposal` | Evaluates bid proposal text & budget fit |
| `/api/ai_assistant.php` | `POST` | `chat_assistant` | Conversational Marketplace Assistant |
| `/api/search.php` | `GET` | `q` | Autocomplete search for projects, freelancers, skills |
| `/api/activity.php` | `GET` | `limit` | Live marketplace activity ticker stream |
| `/api/notifications.php` | `POST` | `mark_read` | Updates notification read state |
