Ah, paham 😄 Maksudmu **README.md untuk repository GitHub**, bukan dokumentasi checkpoint terpisah.

Untuk DANUM, saya sarankan README dibuat sebagai **landing page project**: menjelaskan apa itu DANUM, tujuan, stack, arsitektur, progress Core Foundation, testing, dan cara menjalankan project.

Berikut versi yang siap menggantikan `README.md`:

````markdown id="4s9c2k"
# DANUM

> SaaS Manajemen Surat berbasis multi-tenant untuk organisasi pemerintahan.

DANUM adalah platform **SaaS (Software as a Service) untuk manajemen surat** yang dirancang dengan arsitektur **multi-tenant**, sehingga satu aplikasi dapat digunakan oleh banyak organisasi dengan isolasi data antar-tenant.

Project ini dikembangkan menggunakan Laravel dengan fokus pada:

- Multi-tenancy
- Data isolation
- Dynamic letter management
- Letter template management
- Controlled letter workflow
- Historical integrity
- PDF generation
- QR-based verification
- Role-based authorization
- Automated testing

---

## 🚧 Project Status

**Current Status: Core Foundation — In Progress**

### Completed

- [x] Authentication
- [x] Tenant
- [x] User
- [x] Tenant Profile
- [x] LetterType
- [x] OutgoingLetter
- [x] OutgoingLetter Status History
- [x] Validation Workflow
- [x] Issuance Workflow
- [x] PDF Output
- [x] Repository-based persistence
- [x] PHPUnit test coverage

### Current Test Status

```text
224 passed
463 assertions
0 failed
0 risky
```
````

> The current Core Foundation checkpoint is fully green and ready for the next development stage.

---

# 🎯 Project Goal

DANUM is designed to simplify and standardize the creation and management of official letters within organizations.

The system aims to provide:

```text
Tenant
   │
   ├── Users
   │
   ├── Letter Types
   │
   └── Outgoing Letters
           │
           ├── Validation
           ├── Issuance
           ├── History
           └── PDF
```

The architecture is designed so that each tenant can manage its own data without accessing another tenant's resources.

---

# 🏗️ Architecture

DANUM follows a layered architecture:

```text
Controller
    ↓
Policy / Authorization
    ↓
Validation
    ↓
Service
    ↓
Repository Contract
    ↓
Repository
    ↓
Model
    ↓
Database
```

The general implementation sequence for a domain module is:

```text
Migration
    ↓
Enum
    ↓
Model
    ↓
Factory
    ↓
Repository Contract
    ↓
Repository
    ↓
Service
    ↓
Controller
    ↓
Policy / Authorization
    ↓
Validation
    ↓
API Route
    ↓
PHPUnit
```

---

# 🧱 Core Foundation

The current Core Foundation consists of:

```text
Authentication
      ↓
Tenant
      ↓
User
      ↓
Tenant Profile
      ↓
LetterType
      ↓
OutgoingLetter
```

## Authentication

DANUM uses Laravel's built-in authentication system.

Implemented:

- Login
- Logout
- Registration
- Password confirmation
- Forgot password
- Password reset
- Authentication middleware
- Dashboard protection

---

## Tenant

Tenant is the primary multi-tenancy boundary.

Characteristics:

- UUID primary key
- Soft deletes
- Tenant status
- Tenant-specific data isolation
- Repository
- Service
- Controller
- Policy
- Validation
- Factory

---

## User

Users are associated with a tenant except for Super Admin users.

### Roles

```text
SUPER_ADMIN
TENANT_USER
```

Rules:

```text
SUPER_ADMIN
    → tenant_id = null

TENANT_USER
    → must belong to a tenant
```

---

## Tenant Profile

Tenant users can manage their own organizational profile.

Supported operations:

```http
GET /api/tenant/profile
PUT /api/tenant/profile
```

Tenant profile fields are separated from system-level tenant management.

---

# 📝 LetterType

`LetterType` represents a reusable letter template.

### Status

```text
DRAFT
VALIDATED
ACTIVE
RETIRED
```

Letter types are isolated by tenant.

A unique constraint is applied to:

```text
tenant_id + code
```

Only the appropriate tenant user can manage a tenant's letter types.

---

# 📄 OutgoingLetter

`OutgoingLetter` represents an actual letter generated from a LetterType.

Supported data includes:

- Letter number
- Recipient
- Recipient address
- Subject
- Content
- Letter type
- Issued date
- Status
- Tenant ownership

Outgoing letters support:

- Create
- List
- Show
- Update
- Delete
- Restore
- Validate
- Issue
- Cancel
- History
- PDF output

---

# 🔄 Letter Workflow

Outgoing letters use a controlled workflow.

```text
DRAFT
  │
  │ Validate
  ▼
VALIDATED
  │
  │ Issue
  ▼
ISSUED
```

Cancellation is available from:

```text
DRAFT ──────────┐
                ▼
            CANCELLED

VALIDATED ──────┘
```

Direct status manipulation is not allowed.

For example, changing:

```json
{
    "status": "issued"
}
```

through a normal update request is rejected.

Status changes must go through the appropriate workflow operation.

---

# 🕒 Historical Integrity

Every important status transition is recorded.

Example:

```text
DRAFT
   ↓
created

VALIDATED
   ↓
validated

ISSUED
   ↓
issued
```

History is stored in:

```text
outgoing_letter_status_histories
```

The history contains:

- `outgoing_letter_id`
- `changed_by`
- `status`
- `action`
- `created_at`

History records are immutable.

---

# 🗂️ Repository Architecture

DANUM separates business logic from persistence.

The Service layer orchestrates business operations:

```text
Service
   ↓
Repository
   ↓
Database
```

For OutgoingLetter:

```text
OutgoingLetterService
       │
       ├── OutgoingLetterRepository
       │
       └── OutgoingLetterStatusHistoryRepository
```

Services do not perform direct Eloquent persistence.

This keeps the implementation consistent with the project's locked Architecture Decisions.

---

# 🔐 Multi-Tenancy & Authorization

Tenant isolation is a core requirement.

Conceptually:

```text
Tenant A
├── Users
├── Letter Types
└── Outgoing Letters

Tenant B
├── Users
├── Letter Types
└── Outgoing Letters
```

Tenant A must never be able to access Tenant B's data.

Authorization is handled through Laravel Policies and tenant-aware repository queries.

---

# 🧪 Testing

DANUM uses **PHPUnit**.

Testing follows a Factory-based approach.

Run the complete test suite:

```bash
php artisan test
```

Current result:

```text
224 passed
463 assertions
0 failed
0 risky
```

---

# 🧰 Tech Stack

| Technology        | Version / Usage    |
| ----------------- | ------------------ |
| Laravel           | 12.66.0            |
| PHP               | 8.4.24             |
| PostgreSQL        | 18                 |
| Livewire          | 4.x                |
| Laravel Volt      | Yes                |
| Queue             | Redis              |
| Filesystem        | Laravel Filesystem |
| Testing           | PHPUnit            |
| Local Development | Laravel Herd       |
| Node.js           | 22.23.1            |
| npm               | 12.0.1             |

---

# 🖥️ Local Development

DANUM is developed locally using **Laravel Herd**.

Clone the repository:

```bash
git clone <repository-url>
cd danum
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Copy environment configuration:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure the database in `.env`.

Run migrations:

```bash
php artisan migrate
```

Run the application:

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

---

# 🧪 Development Workflow

Development follows a strict incremental workflow.

For each domain module:

```text
1. Migration
2. Enum
3. Model
4. Factory
5. Repository Contract
6. Repository
7. Repository Test
8. Service
9. Service Test
10. Controller
11. Policy / Authorization
12. Validation
13. Controller Test
14. Full Test Suite
15. Checkpoint
```

A module is not considered complete until the relevant tests pass.

---

# 📌 Architecture Principles

DANUM follows several important architectural rules.

### Model

Models should contain only:

```text
Trait
Property
Fillable
Hidden
Cast
Helper
Relationship
Scope
```

Business logic does not belong in Models.

### Helper

Helpers must:

- Have no side effects
- Not perform database queries
- Not contain business workflows

### Service

Services are responsible for:

- Business rules
- Workflow orchestration
- Domain operations

Services must not perform direct persistence.

### Repository

Repositories are responsible for:

- Database queries
- Create
- Update
- Delete
- Restore
- Persistence-related operations

### Policy

Authorization decisions belong to Policies.

### Factory

Tests should create domain objects through Laravel Factories whenever possible.

---

# 📍 Current Development Checkpoint

Current foundation:

```text
Authentication       ✅
Tenant               ✅
User                 ✅
Tenant Profile       ✅
LetterType            ✅
OutgoingLetter        ✅
```

OutgoingLetter currently includes:

```text
Repository                 ✅
Service                    ✅
Controller                 ✅
Policy / Authorization     ✅
Validation                 ✅
Workflow                   ✅
Status History             ✅
PDF Output                 ✅
```

Full test suite:

```text
224 passed
463 assertions
```

---

# 🛣️ Roadmap

The system is being developed incrementally according to the DANUM architecture blueprint.

Planned areas include:

- [ ] Additional letter foundation modules
- [ ] Dynamic letter variables
- [ ] Template versioning
- [ ] Letter generation improvements
- [ ] Workflow expansion
- [ ] PDF finalization
- [ ] QR verification
- [ ] Letter verification endpoint
- [ ] Historical integrity enhancements
- [ ] Queue-based processing
- [ ] Tenant administration
- [ ] Reporting
- [ ] Production hardening

> Roadmap items are implemented only after their corresponding architecture decisions and requirements are established.

---

# 🔒 Project Status

DANUM is currently in active development.

The **Core Foundation checkpoint is green**:

```text
224 tests passed
463 assertions
0 failures
0 risky tests
```

The next development stage will continue from this verified foundation rather than modifying previously locked architectural decisions without explicit review.

---

## License

This project is currently under development.

License information will be added when the project's distribution policy is finalized.

```

```
