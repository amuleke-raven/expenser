# RemoteRaven

A staff expense, bonus, and rewards management system built on Laravel 12 and Filament v5.

---

## Feature Overview

- **Expense management** — draft → submit → multi-step approval workflow → payment
- **Reward management** — create reward types, assign recipients, approval workflow, payment posting
- **Multi-currency support** — per-user currency with configurable USD conversion rates
- **Configurable approval workflows** — assign workflows to expense types and reward types by role
- **Business rules engine** — amount/country/role-based rules with reject-or-warn actions
- **Payment posting** — idempotent payment records with pending → paid → failed lifecycle
- **Excel payment run exports** — multi-sheet workbook (expenses + rewards) with styled headers
- **Role-based access** — six roles across two Filament panels with Spatie permission integration
- **Audit trail** — full event/observer architecture capturing state transitions

---

## Stack & Versions

| Package | Version |
|---|---|
| PHP | 8.3.11 |
| Laravel Framework | ^12.0 |
| Filament | ^5.0 |
| Livewire | ^4.0 |
| Spatie Laravel Permission | ^6.25 |
| Maatwebsite Excel | ^3.1 |
| Filament Shield | ^4.1 |
| PHPUnit | ^11.5 |

---

## Setup

### 1. Clone and install

```bash
git clone <repo-url> remoteraven
cd remoteraven
cp .env.example .env
composer install
npm install
```

### 2. Configure environment

Edit `.env` — the minimum required values:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=remoteraven
DB_USERNAME=postgres
DB_PASSWORD=secret
```

See the [Environment Variables Reference](#environment-variables-reference) section for the full list.

### 3. Generate key and run migrations

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 4. Build assets (or run dev server)

```bash
npm run build
# or for development:
composer run dev
```

---

## Panel URLs & Seeded Credentials

| Panel | URL | Accessible Roles |
|---|---|---|
| Admin | `/admin` | `super_admin`, `admin`, `accountant` |
| Staff | `/portal` | `manager`, `staff`, `backoffice` |

All seeded passwords: **`password`**

| Email | Role | Panel |
|---|---|---|
| admin@remoteraven.com | super_admin | Admin |
| manager@remoteraven.com | manager | Staff |
| staff@remoteraven.com | staff | Staff |
| accountant@remoteraven.com | accountant | Admin |
| backoffice@remoteraven.com | backoffice | Staff |

---

## Domain Architecture

### Events & Listeners

Events are fired at every domain state transition. Listeners are registered in `AppServiceProvider` via `Event::listen()`.

| Event | Listener(s) |
|---|---|
| `ExpenseSubmitted` | `TriggerExpenseWorkflow` |
| `WorkflowCompleted` | `HandleWorkflowCompleted` |
| `WorkflowRejected` | `HandleWorkflowRejected` |
| `ExpenseApproved` | `NotifyAccountingOnExpenseApproval` |
| `RewardApproved` | `NotifyRecipientsOnRewardApproval` |

### Observers

| Observer | Trigger |
|---|---|
| `ExpenseObserver` | Sets `submitted_at`; fires `ExpenseSubmitted` on status → `submitted` |
| `ExpenseLineItemObserver` | Calls `expense->recalculateTotal()` on line item save/delete |
| `ProjectObserver` | Reassigns users' `default_project_id` when a project is deactivated |

### Workflow Engine (`App\Services\WorkflowEngine`)

The `WorkflowEngine` drives multi-step approvals for both expenses and rewards through the `ModelHasWorkflow` polymorphic pivot.

- **`initiate(Model $workflowable, Workflow $workflow)`** — creates the `ModelHasWorkflow` record, creates the first `WorkflowStepAction`, and notifies all users in the step's required role.
- **`advance(WorkflowStepAction $action, string $decision, ?string $comment)`** — records the approval/rejection decision. If all steps are approved, fires `WorkflowCompleted`. If any step is rejected, fires `WorkflowRejected`. On completion it advances to the next step and notifies the next role group.
- **`getPendingActionForUser(Model $workflowable, User $user)`** — returns the pending `WorkflowStepAction` for a given user's role, used by the staff approvals resource.

The morphable pattern allows one workflow configuration to serve both `Expense` and `Reward` models without duplication.

### Expense Rule Engine (`App\Services\ExpenseRuleEngine`)

Rules are attached to `ExpenseGroup` and `ExpenseType` records. When a staff member submits an expense, the engine evaluates all active rules and either rejects the submission or warns the user.

**Supported dimensions (`RuleDimension` enum):**
- `amount` — checks line item total against a threshold (`gte`, `lte`, `eq`)
- `country` — checks submitting user's country against an allowed/blocked set (`in`)
- `role` — checks submitting user's role (`in`)

**Supported operators (`RuleOperator` enum):** `gte`, `lte`, `eq`, `in`

Violations throw `ExpenseRuleViolationException` which the staff expense resource catches and displays to the user.

### Payment Posting Service (`App\Services\PaymentPostingService`)

- **`postExpense(Expense $expense)`** — creates a `PendingPayment` record morphed to the expense. Idempotent: skips if a payment already exists.
- **`postReward(RewardRecipient $recipient)`** — creates a `PendingPayment` morphed to the reward recipient.

Accountants mark payments as paid/failed from the admin panel's **Pending Payments** resource.

---

## Currency Conversion

The application stores expenses in the submitting user's local currency and converts to USD for reporting.

**Direction:** `local_amount / conversion_rate = USD amount`

Example: a user in EUR with a conversion rate of `0.92` submitting a €460 expense:

```
460 / 0.92 = $500 USD
```

Conversion rates are stored on each `Currency` record. The `User::toUsd(float $amount): float` helper applies this automatically.

The base currency is configured via `config('remoteraven.base_currency_code')` (default: `USD`) and marked with `is_base = true` on its `Currency` record.

---

## How to Add a New Expense Type with a Workflow

1. **Admin → Workflows** — create or select a workflow. Add steps, each with a required role and order.
2. **Admin → Expense Groups** — assign the workflow to an expense group if group-level approval is required.
3. **Admin → Expense Types** — create the expense type, enable **Requires Approval**, and select the workflow.
4. The workflow will now trigger automatically when a staff member submits an expense of that type.

---

## How to Run a Payment Export

1. Log in as an `accountant` or `super_admin` user.
2. Navigate to **Admin → Payment Run Report**.
3. Set the date range, project, currency, and payment method type filters.
4. Click **Export to Excel**.

The downloaded `.xlsx` file contains two sheets:
- **Expenses** — one row per line item, with subtotal rows per expense, styled headers
- **Rewards** — one row per reward recipient

---

## How to Add a New Filament Panel Role

1. **Add the role to the seeder** — add the role name to `database/seeders/RolesAndPermissionsSeeder.php`.
2. **Assign permissions** — call `->givePermissionTo([...])` on the new role in the seeder.
3. **Update `canAccessPanel()`** on `app/Models/User.php` — add the role name to the appropriate panel's array check.
4. **Guard resources conditionally** — add `canAccess()` or `canView()` overrides in any resources restricted to the new role.
5. Re-run `php artisan db:seed --class=RolesAndPermissionsSeeder` to apply without wiping data.

---

## Environment Variables Reference

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `RemoteRaven` | Application display name |
| `APP_ENV` | `local` | `local`, `staging`, `production` |
| `APP_KEY` | — | Generated by `php artisan key:generate` |
| `APP_URL` | `http://localhost` | Used for mail links and asset URLs |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `5432` | Database port |
| `DB_DATABASE` | — | Database name |
| `DB_USERNAME` | — | Database user |
| `DB_PASSWORD` | — | Database password |
| `MAIL_MAILER` | `log` | Mail driver (`smtp`, `ses`, `log`, `array`) |
| `MAIL_HOST` | `127.0.0.1` | SMTP host |
| `MAIL_PORT` | `2525` | SMTP port |
| `MAIL_USERNAME` | — | SMTP username |
| `MAIL_PASSWORD` | — | SMTP password |
| `MAIL_FROM_ADDRESS` | — | Sender address for notifications |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender name for notifications |
| `QUEUE_CONNECTION` | `database` | Queue driver (`database`, `redis`, `sync`) |
| `FILESYSTEM_DISK` | `local` | Default filesystem (`local`, `s3`) |
| `REMOTERAVEN_BASE_CURRENCY` | `USD` | ISO code of the base reporting currency |
| `REMOTERAVEN_EXPENSE_REF_PREFIX` | `EXP` | Prefix for auto-generated expense refs |
| `REMOTERAVEN_REWARD_REF_PREFIX` | `RWD` | Prefix for auto-generated reward refs |
| `REMOTERAVEN_MAX_ATTACHMENT_MB` | `10` | Maximum file upload size in megabytes |

---

## Running Tests

```bash
php artisan test
```

To run a single test file:

```bash
php artisan test tests/Feature/ExampleTest.php
```
