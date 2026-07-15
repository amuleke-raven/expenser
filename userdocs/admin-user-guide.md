# Admin Panel User Guide

This guide covers everything available to administrators in the Expenser Admin Panel. The admin panel is the central hub for managing users, configuration, expenses, rewards, payments, and approval workflows.

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Dashboard](#2-dashboard)
3. [Users](#3-users)
   - [Viewing Users](#31-viewing-users)
   - [Creating a User](#32-creating-a-user)
   - [Editing a User](#33-editing-a-user)
   - [Assigning Roles & Projects](#34-assigning-roles--projects)
4. [Role Permissions](#4-role-permissions)
5. [Expenses](#5-expenses)
   - [Viewing Expenses](#51-viewing-expenses)
   - [Approving or Rejecting an Expense](#52-approving-or-rejecting-an-expense)
   - [Viewing Expense Details, Line Items & Attachments](#53-viewing-expense-details-line-items--attachments)
6. [Expense Groups](#6-expense-groups)
   - [Creating & Editing Expense Groups](#61-creating--editing-expense-groups)
   - [Managing Expense Types within a Group](#62-managing-expense-types-within-a-group)
   - [Assigning Roles to an Expense Group](#63-assigning-roles-to-an-expense-group)
   - [Managing Expense Rules](#64-managing-expense-rules)
7. [Expense Types](#7-expense-types)
8. [Rewards](#8-rewards)
   - [Viewing Rewards](#81-viewing-rewards)
   - [Creating a Reward](#82-creating-a-reward)
   - [Adding Recipients to a Reward](#83-adding-recipients-to-a-reward)
   - [Submitting a Reward for Approval](#84-submitting-a-reward-for-approval)
   - [Approving or Rejecting a Reward](#85-approving-or-rejecting-a-reward)
   - [Reward Statuses](#86-reward-statuses)
9. [Reward Types](#9-reward-types)
10. [Pending Payments](#10-pending-payments)
    - [Viewing Pending Payments](#101-viewing-pending-payments)
    - [Marking Payments as Paid](#102-marking-payments-as-paid)
    - [Marking Payments as Failed](#103-marking-payments-as-failed)
    - [Bulk Payment Actions](#104-bulk-payment-actions)
11. [Payment Run Report](#11-payment-run-report)
12. [Configuration](#12-configuration)
    - [Projects](#121-projects)
    - [Countries](#122-countries)
    - [Currencies](#123-currencies)
    - [Payment Methods](#124-payment-methods)
    - [Workflows](#125-workflows)
13. [Navigation & Layout](#13-navigation--layout)
14. [Reference: Status Colours](#14-reference-status-colours)

---

## 1. Getting Started

### Logging In

Navigate to `/admin` and enter your administrator credentials. You will be taken to the Admin Dashboard upon successful login.

> **[SCREENSHOT: Admin panel login page]**

### Admin Panel Layout

The admin panel has two main areas:

- **Sidebar** — navigation links organised into groups (Users, Expenses, Rewards, Finance, Configuration). The sidebar can be collapsed on desktop.
- **Main content area** — the currently active resource, page, or form.

> **[SCREENSHOT: Full admin panel layout with sidebar groups visible]**

### Navigation Groups

| Group | Contents |
|---|---|
| **Users** | Users, Role Permissions |
| **Expenses** | Expenses, Expense Groups, Expense Types |
| **Rewards** | Rewards, Reward Types |
| **Finance** | Pending Payments, Payment Run Report |
| **Configuration** | Projects, Countries, Currencies, Payment Methods, Workflows |

---

## 2. Dashboard

The Dashboard is the first page shown after login. It provides a high-level view of activity across the platform.

> **[SCREENSHOT: Full admin dashboard]**

### Stats Overview

Two rows of summary cards appear at the top.

**General stats (visible to all admins):**

| Card | What it shows |
|---|---|
| **Expenses This Month** | Count of expenses created this calendar month, with the total sum as a sub-label |
| **Rewards This Month** | Count of rewards created this month, with the total sum |
| **Pending Approvals** | Number of workflow step actions currently awaiting a decision (shown in amber) |
| **Active Users** | Total number of user accounts in the system |

> **[SCREENSHOT: General stats cards]**

**Finance stats (visible to Accountant and Super Admin roles only):**

| Card | What it shows |
|---|---|
| **Pending Payments Value** | Total monetary value of all pending payments in the base currency |
| **Pending Payments Count** | Number of pending payments, with a breakdown of expenses vs. rewards |
| **Processed This Month** | Sum of payments marked as Paid this calendar month |
| **Failed Payments** | Count of failed payments (shown in red) |

> **[SCREENSHOT: Finance stats cards]**

### Charts & Tables

Below the stats cards, the dashboard shows:

**Expenses by Status** — a doughnut chart breaking down all expenses by their current status. Each status is colour-coded consistently with the rest of the panel.

> **[SCREENSHOT: Expenses by Status doughnut chart]**

**Top Spenders This Month** — a table listing the five users with the highest total expense amounts submitted this month, showing their name, number of expenses, and total value.

> **[SCREENSHOT: Top Spenders table]**

**Currency Exposure** *(Accountant and Super Admin only)* — a table showing all currencies that have pending payments, the count of pending payments per currency, the local-currency total, and the USD-equivalent total.

> **[SCREENSHOT: Currency Exposure table]**

---

## 3. Users

### 3.1 Viewing Users

Click **Users** under the *Users* group in the sidebar.

> **[SCREENSHOT: Users list page]**

The table shows each user's name, email, phone, country, default currency, and assigned roles. The list is searchable by name and email and can be sorted by name.

---

### 3.2 Creating a User

1. Click **New User** at the top right of the Users list.

> **[SCREENSHOT: New User button]**

2. The **Create User** form opens with three tabs: **Details**, **Roles**, and **Projects**.

> **[SCREENSHOT: Create User form — Details tab]**

#### Details Tab

| Field | Notes |
|---|---|
| **Name** | Required, max 255 characters |
| **Email** | Required, must be a valid email address |
| **Phone** | Optional |
| **Password** | The initial password for the account. Leave blank when editing to keep the existing password. |
| **Country** | Optional, searchable dropdown |
| **Currency** | Optional, searchable dropdown — sets the user's default currency for expense creation |

#### Roles Tab

Check one or more roles to assign to this user. Roles control what the user can approve, what expense types they can access, and whether they can see the Admin Panel.

> **[SCREENSHOT: Roles tab with checkboxes]**

#### Projects Tab

Check the projects this user is assigned to. Only assigned projects appear when the user creates an expense.

> **[SCREENSHOT: Projects tab with checkboxes]**

3. Click **Create** to save the user.

---

### 3.3 Editing a User

Click the **Edit** icon on any user row (or open the user and click **Edit**). The same tabbed form is shown. Leave the password field blank to keep the existing password.

> **[SCREENSHOT: Edit User form]**

---

### 3.4 Assigning Roles & Projects

Roles and project assignments are managed on the **Roles** and **Projects** tabs of the create/edit form. Changes take effect immediately on save.

> **[SCREENSHOT: Roles and Projects tabs side by side]**

---

## 4. Role Permissions

> **Access:** Super Admin only.

Click **Role Permissions** under the *Users* group in the sidebar.

> **[SCREENSHOT: Role Permissions page]**

This page lets you control exactly which permissions each role has.

1. Select a **Role** from the dropdown. The permissions list will load automatically.

> **[SCREENSHOT: Role selected with permissions list visible]**

2. Check or uncheck individual permissions in the list (displayed in three columns).

3. Click **Save** to apply the changes. The permission cache is cleared automatically so changes take effect immediately.

> **[SCREENSHOT: Save button on Role Permissions page]**

---

## 5. Expenses

The Expenses section gives administrators a read-only view of all expenses submitted across the platform, with the ability to approve or reject expenses that are in the approval workflow.

### 5.1 Viewing Expenses

Click **Expenses** under the *Expenses* group in the sidebar.

> **[SCREENSHOT: Admin Expenses list page]**

The table shows:

| Column | Description |
|---|---|
| **Ref** | Unique expense reference (e.g. `EXP-00042`) |
| **Staff** | The user who created the expense |
| **Type** | The expense type |
| **Project** | The associated project (if any) |
| **Amount** | Total expense value |
| **Currency** | Currency of the expense |
| **Status** | Current stage in the lifecycle |
| **Submitted** | Date and time the expense was submitted |

#### Filtering Expenses

Use the filter panel (top right of the table) to narrow results by:

- **Status** — filter by a specific expense status
- **Expense Type** — filter by a specific type
- **Submitted Date** — filter by a date range (from / until)

> **[SCREENSHOT: Expense filter panel open]**

---

### 5.2 Approving or Rejecting an Expense

Approve and Reject actions appear on expense rows where:
- The expense status is **Under Review**, and
- The current workflow step is assigned to one of your roles.

**To approve:**

1. Click the **Approve** action on the expense row.
2. Confirm the action in the dialog that appears.
3. The workflow engine advances the expense to the next step (or marks it as Approved if this was the final step).

> **[SCREENSHOT: Approve action on an expense row]**

> **[SCREENSHOT: Approve confirmation dialog]**

**To reject:**

1. Click the **Reject** action on the expense row.
2. Enter a **Rejection Reason** in the form — this is required so the submitter understands why the expense was declined.
3. Click **Reject** to confirm.

> **[SCREENSHOT: Reject action on an expense row]**

> **[SCREENSHOT: Reject form with Rejection Reason field]**

---

### 5.3 Viewing Expense Details, Line Items & Attachments

Click the **View** action on any expense row to open the detail panel.

> **[SCREENSHOT: Expense detail view / infolist]**

The detail view shows:

- Reference, Staff Member, Expense Type, Project
- Status (with colour badge)
- Total Amount and Submitted date
- **Rejection Reason** — shown only when the expense has been rejected
- **Current Workflow Step** — shown only when the expense is Under Review

Below the detail panel, two read-only sections are shown:

**Line Items** — every individual charge that makes up the expense, including description, quantity, unit price, and total.

> **[SCREENSHOT: Line Items section]**

**Attachments** — files uploaded with the expense. Click the **Download** action on any attachment to open it.

> **[SCREENSHOT: Attachments section with Download action]**

---

## 6. Expense Groups

Expense Groups are top-level categories that contain one or more Expense Types. Groups control which roles can access the expense types within them.

### 6.1 Creating & Editing Expense Groups

Click **Expense Groups** in the *Expenses* group of the sidebar.

> **[SCREENSHOT: Expense Groups list]**

The list shows each group's name, whether it is the default group, and how many expense types belong to it.

Click **New Expense Group** to create one, or click **Edit** on an existing group.

> **[SCREENSHOT: Expense Group create/edit form]**

| Field | Notes |
|---|---|
| **Name** | Required |
| **Description** | Optional, full-width textarea |
| **Default Group** | Toggle — marks this as the default group shown to users |

---

### 6.2 Managing Expense Types within a Group

Open an Expense Group and scroll to the **Expense Types** relation manager.

> **[SCREENSHOT: Expense Types relation manager within an Expense Group]**

Click **Add Expense Type** to create a new type directly within this group.

| Field | Notes |
|---|---|
| **Name** | Required |
| **Description** | Optional |
| **Requires Approval** | Toggle — if enabled, the Workflow field becomes visible |
| **Requires Attachment** | Toggle — if enabled, users must upload at least one file when creating an expense of this type |
| **Workflow** | Select the approval workflow to use. Only visible when Requires Approval is on. |

> **[SCREENSHOT: Add Expense Type form within a group]**

You can also **Edit** or **Delete** expense types from this table.

---

### 6.3 Assigning Roles to an Expense Group

Only users whose roles are assigned to a group will see that group's expense types when creating an expense.

In the Expense Group detail page, scroll to the **Assigned Roles** relation manager.

> **[SCREENSHOT: Assigned Roles relation manager]**

Click **Assign Roles**, select the roles that should have access to this group, and click **Save**. Roles are synced — unchecked roles lose access.

> **[SCREENSHOT: Assign Roles modal with checkboxes]**

---

### 6.4 Managing Expense Rules

Expense rules define validation criteria that must be satisfied before a user can submit an expense belonging to this group.

In the Expense Group detail page, scroll to the **Expense Rules** relation manager.

> **[SCREENSHOT: Expense Rules relation manager]**

Click **Add Rule** to create a new rule.

| Field | Options | Description |
|---|---|---|
| **Dimension** | Amount, Country, Role | What the rule evaluates |
| **Operator** | ≥ (gte), ≤ (lte), = (eq), In (in) | How the value is compared |
| **Value** | JSON | The value(s) to compare against, entered as JSON |

> **[SCREENSHOT: Add Expense Rule form]**

**Examples:**

| Dimension | Operator | Value | Meaning |
|---|---|---|---|
| Amount | ≤ | `500` | Expense total must not exceed 500 |
| Country | In | `["KE","UG"]` | Submitter's country must be Kenya or Uganda |
| Role | = | `"finance"` | Submitter must have the finance role |

Rules defined on a group apply to all expense types in that group. Rules can also be added to individual Expense Types (see [Section 7](#7-expense-types)).

---

## 7. Expense Types

Expense Types can be managed both within their parent Expense Group (as shown in Section 6.2) and independently through the **Expense Types** resource.

Click **Expense Types** under the *Expenses* group in the sidebar.

> **[SCREENSHOT: Expense Types list]**

The list shows each type's name, parent group, whether it requires approval and attachments, and the linked workflow.

Click **New Expense Type** or **Edit** on an existing row to open the form:

> **[SCREENSHOT: Expense Type create/edit form]**

| Field | Notes |
|---|---|
| **Name** | Required |
| **Description** | Optional |
| **Expense Group** | Required, searchable — determines which group this type belongs to |
| **Requires Approval** | Toggle — enables the Workflow selector |
| **Requires Attachment** | Toggle — makes file upload mandatory for users |
| **Workflow** | Only visible when Requires Approval is enabled |

Each Expense Type also has its own **Expense Rules** relation manager — use this to set rules specific to a single type (rather than the whole group).

> **[SCREENSHOT: Expense Rules relation manager on an Expense Type]**

---

## 8. Rewards

Rewards are monetary bonuses or recognitions given to one or more recipients. Unlike expenses (which are user-initiated), rewards are created and managed by administrators.

### 8.1 Viewing Rewards

Click **Rewards** under the *Rewards* group in the sidebar.

> **[SCREENSHOT: Rewards list page]**

The table shows:

| Column | Description |
|---|---|
| **Ref** | Unique reward reference |
| **Type** | The reward type |
| **Initiated By** | The admin who created the reward |
| **Amount** | The reward value |
| **Currency** | The reward currency |
| **Status** | Current stage in the lifecycle |
| **Project** | Associated project (if any) |
| **Payout Date** | Scheduled payout date (if set) |

Use the **Status** filter to narrow the list to a specific stage.

> **[SCREENSHOT: Rewards list with Status filter open]**

---

### 8.2 Creating a Reward

1. Click **New Reward** at the top right.

> **[SCREENSHOT: New Reward button]**

2. Fill in the **Create Reward** form.

> **[SCREENSHOT: Create Reward form]**

| Field | Notes |
|---|---|
| **Reward Type** | Required, searchable. If the selected type has a fixed amount, the Amount and Currency fields are auto-populated and locked. |
| **Amount** | Required. Disabled once a reward has been saved. |
| **Currency** | Required, defaults to USD. Disabled if the reward type has a fixed currency. |
| **Project** | Optional, searchable |
| **Recipient Type** | Required — **Internal** (existing system users) or **External** (outside the system, identified by name and email) |
| **Payout Date** | Optional date picker |
| **Notes** | Optional free-text notes |

3. Click **Create** to save the reward as a **Draft**.

---

### 8.3 Adding Recipients to a Reward

Recipients can be added to any reward that has not yet been Approved, Rejected, or Paid.

**Method 1 — From the rewards list:**

Click the **Add Recipients** action on a reward row.

> **[SCREENSHOT: Add Recipients action on a reward row]**

**Method 2 — From the reward detail page:**

Open the reward, scroll to the **Recipients** relation manager, and click **Add Recipient**.

> **[SCREENSHOT: Add Recipient button in the Recipients relation manager]**

The form shown depends on the reward's **Recipient Type**:

**Internal recipients:**

Select one or more users from the searchable multi-select list.

> **[SCREENSHOT: Add Recipients form — Internal type with user multi-select]**

**External recipients:**

Add one or more rows, each with a **Name** and **Email** address.

> **[SCREENSHOT: Add Recipients form — External type with Name/Email repeater]**

Recipients listed in the relation manager show their name, email, status, notified date, and paid date. You can delete a recipient while their status is still **Pending**.

> **[SCREENSHOT: Recipients relation manager table]**

---

### 8.4 Submitting a Reward for Approval

Once a reward is ready, submit it to begin the approval process.

1. In the Rewards list, click the **Submit for Approval** action on a Draft reward.
2. Confirm the action in the dialog.

> **[SCREENSHOT: Submit for Approval action and confirmation dialog]**

- If the reward type **requires approval** and has a linked workflow, the status moves to **Pending Approval** and the workflow engine initiates.
- If the reward type does **not** require approval, the reward is automatically approved and moves straight to **Approved**.

---

### 8.5 Approving or Rejecting a Reward

When a reward is in **Pending Approval** status and the current workflow step is assigned to your role, Approve and Reject actions appear on the reward row.

**To approve:**

1. Click the **Approve** action on the reward row.
2. Confirm in the dialog.

> **[SCREENSHOT: Approve action on a Pending Approval reward]**

**To reject:**

1. Click the **Reject** action on the reward row.
2. Enter a **Rejection Reason** (required).
3. Click **Reject** to confirm.

> **[SCREENSHOT: Reject form for a reward]**

---

### 8.6 Reward Statuses

| Status | Meaning |
|---|---|
| **Draft** | The reward has been created but not yet submitted for approval. |
| **Pending Approval** | The reward has been submitted and is in the approval workflow. |
| **Approved** | All approval steps completed. The reward is ready for payment processing. |
| **Rejected** | The reward was rejected during the approval workflow. |
| **Paid** | The reward has been paid out to all recipients. |

---

## 9. Reward Types

Reward Types define the categories of rewards that can be issued, including whether the amount is fixed and whether approval is required.

Click **Reward Types** under the *Rewards* group in the sidebar.

> **[SCREENSHOT: Reward Types list]**

The list shows each type's name, whether it has a fixed amount, whether it is client-based, and whether it requires approval.

Click **New Reward Type** or **Edit** on an existing row.

> **[SCREENSHOT: Reward Type create/edit form]**

| Field | Notes |
|---|---|
| **Name** | Required |
| **Description** | Optional |
| **Is Fixed Amount** | Toggle — if enabled, shows Fixed Amount and Fixed Currency fields |
| **Fixed Amount** | The locked amount for all rewards of this type (only visible when Is Fixed Amount is on) |
| **Fixed Currency** | The locked currency (only visible when Is Fixed Amount is on, defaults to USD) |
| **Is Client Based** | Toggle — marks the reward as client-facing |
| **Requires Approval** | Toggle — if enabled, shows the Workflow selector |
| **Workflow** | The approval workflow to use (only visible when Requires Approval is on) |

Each Reward Type also has a **Reward Rules** relation manager, which works the same way as Expense Rules — see [Section 6.4](#64-managing-expense-rules) for the field reference.

> **[SCREENSHOT: Reward Rules relation manager on a Reward Type]**

---

## 10. Pending Payments

> **Access:** Accountant and Super Admin roles only.

The Pending Payments section is the payment processing queue. When an expense or reward is approved, a payment record is automatically created here for the finance team to process.

### 10.1 Viewing Pending Payments

Click **Pending Payments** under the *Finance* group in the sidebar.

> **[SCREENSHOT: Pending Payments list]**

The table shows:

| Column | Description |
|---|---|
| **Type** | Whether this is an **Expense** or **Reward** payment |
| **Ref** | The reference of the underlying expense or reward |
| **Recipient Name** | The person to be paid |
| **Email** | Recipient email address |
| **Amount** | Payment amount in the original currency |
| **Currency** | Payment currency |
| **Payment Method** | The recipient's configured payment method |
| **Status** | Current payment status |

Two additional columns can be toggled on using the column visibility control:
- **Amount (USD)** — the USD-equivalent value based on the configured conversion rate
- **Processed By / Processed At** — who actioned the payment and when

#### Filtering Payments

Use the filter panel to narrow results by:

- **Status** — Pending, Processing, Paid, or Failed
- **Type** — Expense or Reward
- **Created Date** — a date range filter

> **[SCREENSHOT: Pending Payments filter panel]**

---

### 10.2 Marking Payments as Paid

1. Find the payment row you want to mark as paid.
2. Click the **Mark as Paid** action.
3. Confirm the action in the dialog.

> **[SCREENSHOT: Mark as Paid action and confirmation dialog]**

The payment status updates to **Paid**, and the related expense or reward recipient status is updated accordingly. The processed-by user and timestamp are recorded automatically.

---

### 10.3 Marking Payments as Failed

1. Find the payment row.
2. Click the **Mark as Failed** action.
3. Optionally enter notes explaining the failure.
4. Click **Confirm**.

> **[SCREENSHOT: Mark as Failed form with notes field]**

The payment status updates to **Failed**. Failed payments remain in the list and can be retried by marking them as Paid when the issue is resolved.

---

### 10.4 Bulk Payment Actions

You can process multiple payments at once using the checkbox column in the table.

1. Check the boxes next to the payments you want to action (or use the header checkbox to select all visible rows).

> **[SCREENSHOT: Bulk checkboxes selected on the Pending Payments list]**

2. Open the **Bulk Actions** menu that appears at the bottom of the table.

**Bulk Mark as Paid:**
- Confirms before proceeding.
- Processes all selected records that are in Pending or Processing status.

**Bulk Mark as Failed:**
- Shows a form for a shared **Failure Notes** field.
- Applies the same notes to all selected records.

> **[SCREENSHOT: Bulk Actions menu open]**

---

## 11. Payment Run Report

> **Access:** Accountant and Super Admin roles only.

The Payment Run Report allows you to export a filtered list of payments to an Excel file, suitable for submission to a bank or accounting system.

Click **Payment Run Report** under the *Finance* group in the sidebar.

> **[SCREENSHOT: Payment Run Report page]**

### Filters

Set your desired filters before exporting:

| Filter | Description |
|---|---|
| **Date From / Date To** | Restrict to payments created within a date range |
| **Project** | Limit to a specific project |
| **Currency** | Limit to a specific currency |
| **Payment Method Type** | Filter by payment method category (Bank, Mobile Money, etc.) |
| **Include Expenses** | Toggle — include expense payments in the export (default: on) |
| **Include Rewards** | Toggle — include reward payments in the export (default: on) |
| **Status** | Choose **Approved** or **Paid** payments |

> **[SCREENSHOT: Payment Run Report filter form]**

### Exporting

Once your filters are set, click **Export** to download the report as an Excel file. The file is generated instantly and downloaded to your browser.

> **[SCREENSHOT: Export button]**

---

## 12. Configuration

The Configuration group contains reference data and system settings used throughout the platform.

### 12.1 Projects

Click **Projects** under the *Configuration* group.

> **[SCREENSHOT: Projects list]**

The list shows each project's name, client name, active status (editable directly in the table via a toggle), default status, and user count.

**Create / Edit form:**

| Field | Notes |
|---|---|
| **Name** | Required |
| **Client Name** | Optional |
| **Active** | Toggle — inactive projects are hidden from users |
| **Default Project** | Toggle — pre-selects this project when users create an expense |

> **[SCREENSHOT: Project create/edit form]**

---

### 12.2 Countries

Click **Countries** under the *Configuration* group.

> **[SCREENSHOT: Countries list]**

| Field | Notes |
|---|---|
| **Name** | Required |
| **ISO Code** | Required, max 2 characters (e.g. `KE`, `UG`) |

Countries are referenced on user profiles and can be used as dimensions in expense/reward rules.

> **[SCREENSHOT: Country create/edit form]**

---

### 12.3 Currencies

Click **Currencies** under the *Configuration* group.

> **[SCREENSHOT: Currencies list]**

The list shows code, name, symbol, conversion rate, and whether the currency is the base currency.

| Field | Notes |
|---|---|
| **Code** | Required, max 3 characters (e.g. `USD`, `KES`) |
| **Name** | Required |
| **Symbol** | Required, max 10 characters (e.g. `$`, `KSh`) |
| **Conversion Rate** | Required — the exchange rate relative to the base currency |
| **Is Base Currency** | Toggle — only one currency should be the base. The base currency is used for USD-equivalent calculations throughout the Finance section. |

> **[SCREENSHOT: Currency create/edit form]**

---

### 12.4 Payment Methods

Click **Payment Methods** under the *Configuration* group.

> **[SCREENSHOT: Payment Methods list]**

The list shows each method's name, type, whether it is globally available, and if not global, which country it applies to.

| Field | Notes |
|---|---|
| **Name** | Required |
| **Type** | Required — one of: Mobile Money, Bank, Crypto, Cash, Cheque, Credit Card |
| **Available Globally** | Toggle (default on) — if disabled, the Country field appears so you can limit this method to a specific country |
| **Country** | Only visible when Available Globally is off |

> **[SCREENSHOT: Payment Method create/edit form — global]**

> **[SCREENSHOT: Payment Method create/edit form — country-specific, with Country field visible]**

---

### 12.5 Workflows

Workflows define the sequence of approval steps that an expense or reward must pass through before being approved.

Click **Workflows** under the *Configuration* group.

> **[SCREENSHOT: Workflows list]**

The list shows each workflow's name and the number of steps it contains.

**Create / Edit form:**

| Field | Notes |
|---|---|
| **Name** | Required |
| **Description** | Optional |

> **[SCREENSHOT: Workflow create/edit form]**

#### Managing Workflow Steps

Open a workflow and scroll to the **Workflow Steps** relation manager.

> **[SCREENSHOT: Workflow Steps relation manager]**

Steps are displayed in order and can be reordered by dragging. Each step represents one approval gate in the sequence.

Click **Add Step** to create a new step:

| Field | Notes |
|---|---|
| **Order** | Numeric — determines the sequence (lower numbers execute first) |
| **Name** | A label for this step (e.g. "Line Manager Approval", "Finance Sign-off") |
| **Action Type** | One of: **Approval**, **Document Attachment**, **Signature** |
| **Role** | The role whose members are responsible for actioning this step. Users with this role will see the request in their **Pending Approvals** queue. |

> **[SCREENSHOT: Add Workflow Step form]**

> **[SCREENSHOT: Workflow Steps table showing multiple steps in order]**

---

## 13. Navigation & Layout

### Sidebar Collapse

The sidebar can be collapsed on desktop to give more screen space. Click the collapse icon at the top of the sidebar.

> **[SCREENSHOT: Collapsed sidebar state]**

### Portal Panel Link

Admins who also have access to the staff portal can switch to it via the **Portal** link (visible only if you have the `access_staff_panel` permission). This is separate from the Admin Panel and shows only the user-facing features.

---

## 14. Reference: Status Colours

Status badges use consistent colours throughout the admin panel.

### Expense Statuses

| Status | Colour |
|---|---|
| Draft | Grey |
| Submitted | Blue |
| Under Review | Amber |
| Approved | Green |
| Rejected | Red |
| Paid | Green |

### Reward Statuses

| Status | Colour |
|---|---|
| Draft | Grey |
| Pending Approval | Amber |
| Approved | Green |
| Rejected | Red |
| Paid | Green |

### Payment Statuses

| Status | Colour |
|---|---|
| Pending | Grey |
| Processing | Amber |
| Paid | Green |
| Failed | Red |

### Recipient Statuses (Reward Recipients)

| Status | Colour |
|---|---|
| Pending | Grey |
| Notified | Blue |
| Paid | Green |

### Payment Method Types

| Type | Colour |
|---|---|
| Mobile Money | Blue |
| Bank | Green |
| Crypto | Amber |
| Cash | Grey |
| Cheque | Grey |
| Credit Card | Blue |

### Workflow Step Action Types

| Type | Colour |
|---|---|
| Approval | Blue |
| Document Attachment | Amber |
| Signature | Green |

---

*For technical support or to report issues, contact your system administrator or the development team.*
