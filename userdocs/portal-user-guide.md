# Portal User Guide

Welcome to the Expenser Portal. This guide walks you through everything available to you as a portal user — from submitting expenses to reviewing your rewards.

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Dashboard](#2-dashboard)
3. [My Expenses](#3-my-expenses)
   - [Viewing Your Expenses](#31-viewing-your-expenses)
   - [Creating an Expense](#32-creating-an-expense)
   - [Editing a Draft Expense](#33-editing-a-draft-expense)
   - [Submitting an Expense](#34-submitting-an-expense)
   - [Viewing Expense Details](#35-viewing-expense-details)
   - [Deleting a Draft Expense](#36-deleting-a-draft-expense)
   - [Expense Statuses](#37-expense-statuses)
4. [Pending Approvals](#4-pending-approvals)
   - [Viewing Pending Approvals](#41-viewing-pending-approvals)
   - [Approving a Request](#42-approving-a-request)
   - [Rejecting a Request](#43-rejecting-a-request)
5. [My Rewards](#5-my-rewards)
   - [Viewing Your Rewards](#51-viewing-your-rewards)
   - [Reward Statuses](#52-reward-statuses)
6. [Navigation & Account](#6-navigation--account)
   - [Sidebar Navigation](#61-sidebar-navigation)
   - [Admin Panel Access](#62-admin-panel-access)
7. [Reference: Status Colours](#7-reference-status-colours)

---

## 1. Getting Started

### Logging In

Navigate to the portal login page and enter your email address and password. Once authenticated, you will be taken to your Dashboard.

> **[SCREENSHOT: Login page]**

If you have forgotten your password, use the **Forgot Password** link on the login page.

### Portal Layout

The portal has two main areas:

- **Sidebar** — navigation links to each section of the portal. The sidebar can be collapsed to give you more screen space by clicking the collapse icon at the top.
- **Main content area** — the page you are currently viewing.

> **[SCREENSHOT: Overall portal layout with sidebar and main content area labelled]**

---

## 2. Dashboard

The Dashboard is the first page you see after logging in. It gives you a quick summary of your activity at a glance.

> **[SCREENSHOT: Full dashboard page]**

### Stats Overview

At the top of the Dashboard you will find four summary cards:

| Card | What it shows |
|---|---|
| **Submitted This Month** | Total value of expenses you have submitted in the current calendar month |
| **Approved This Month** | Total value of expenses that have been approved in the current calendar month |
| **Pending Approval** | Number of your expenses currently awaiting a decision |
| **Rewards Received** | Total value of rewards that have been paid out to you |

> **[SCREENSHOT: Stats overview cards]**

### Recent Activity

Below the stats cards is a **Recent Activity** table showing your five most recently created expenses. Each row displays the reference number, amount, current status, and the date the expense was created.

> **[SCREENSHOT: Recent Activity table]**

Click any row to navigate directly to that expense's detail page.

---

## 3. My Expenses

The **My Expenses** section is where you manage all of your expense claims. Only your own expenses are visible here.

### 3.1 Viewing Your Expenses

Click **My Expenses** in the sidebar to open the expenses list.

> **[SCREENSHOT: My Expenses list page]**

The table shows:

- **Reference** — a unique identifier for each expense (e.g. `EXP-00001`)
- **Expense Type** — the category of the expense
- **Project** — the project this expense is linked to (if any)
- **Total** — the total amount claimed
- **Currency** — the currency the expense is in
- **Status** — the current stage in the workflow (see [Expense Statuses](#37-expense-statuses))
- **Submitted** — the date and time the expense was submitted

#### Filtering the List

You can narrow down the list using the filters at the top right of the table:

- **Status** — filter by a specific expense status (e.g. show only Approved expenses)
- **Submitted Date (from / until)** — show only expenses submitted within a date range

> **[SCREENSHOT: Filter panel open on the expenses list]**

---

### 3.2 Creating an Expense

1. Click the **New Expense** button at the top right of the My Expenses list.

> **[SCREENSHOT: New Expense button highlighted]**

2. The **Create Expense** form will open.

> **[SCREENSHOT: Create Expense form — full view]**

Fill in the following fields:

#### Expense Type *(required)*

Select the category that best describes this expense. Only expense types available to your role will appear in the list.

> **[SCREENSHOT: Expense Type dropdown]**

#### Project *(optional)*

If this expense is related to a specific project, select it here. Only projects you are assigned to will appear.

#### Currency *(required)*

Select the currency in which you incurred the expense. Your default currency is pre-selected.

#### Description *(optional)*

Add any additional context or notes about the expense.

---

#### Line Items *(at least one required)*

Each expense must have at least one line item. A line item represents a single charge within the expense.

> **[SCREENSHOT: Line Items repeater with one item filled in]**

For each line item, provide:

| Field | Description |
|---|---|
| **Description** | A short label for this charge (max 255 characters) |
| **Quantity** | How many units (defaults to 1) |
| **Unit Price** | The cost per unit |
| **Total** | Calculated automatically — you cannot edit this field |

Click **Add Line Item** to add more charges. You can remove a line item by clicking the delete icon next to it.

> **[SCREENSHOT: Multiple line items added]**

---

#### Attachments *(conditionally required)*

If the selected Expense Type requires supporting documents (e.g. receipts), an **Attachments** upload area will appear.

> **[SCREENSHOT: Attachments upload field]**

You can upload multiple files. Accepted file types and maximum file sizes are configured by your administrator.

---

3. Once all required fields are filled in, click **Create** to save the expense as a **Draft**.

> **[SCREENSHOT: Create button at the bottom of the form]**

A draft expense is saved but not yet submitted for approval. You can return to edit or submit it later.

---

### 3.3 Editing a Draft Expense

Only expenses in **Draft** status can be edited.

1. In the My Expenses list, click the row of a draft expense — or click the **Edit** action icon on the row.

> **[SCREENSHOT: Edit action on a Draft expense row]**

2. Make your changes in the form and click **Save**.

> **[SCREENSHOT: Edit Expense form]**

Once an expense has been submitted, it is locked and can no longer be edited.

---

### 3.4 Submitting an Expense

When your expense is ready to be reviewed, submit it for approval.

1. Open a **Draft** expense (via the list or the edit form).
2. Click the **Submit** button.

> **[SCREENSHOT: Submit button on a Draft expense]**

3. A confirmation dialog will appear. Click **Confirm** to proceed.

> **[SCREENSHOT: Submit confirmation modal]**

4. If the expense passes all validation rules, the status will change to **Submitted** and you will see a success notification.

> **[SCREENSHOT: Success notification after submission]**

If the expense fails a validation rule (for example, the amount exceeds an allowed limit for your role), an error notification will appear with a description of the issue. Correct the expense and try again.

> **[SCREENSHOT: Error notification from a failed rule]**

---

### 3.5 Viewing Expense Details

Click any expense row in the list to open its detail view. The detail view is read-only and shows all fields, line items, attachments, and the current status.

> **[SCREENSHOT: Expense detail/view page]**

---

### 3.6 Deleting a Draft Expense

If you no longer need a draft expense, you can delete it.

1. In the My Expenses list, locate the draft expense.
2. Click the **Delete** action icon on the row.
3. Confirm the deletion when prompted.

> **[SCREENSHOT: Delete action on a Draft expense row]**

Only **Draft** expenses can be deleted. Submitted or approved expenses cannot be deleted.

---

### 3.7 Expense Statuses

| Status | Meaning |
|---|---|
| **Draft** | The expense has been saved but not yet submitted. You can still edit or delete it. |
| **Submitted** | The expense has been submitted and is queued for the first approval step. |
| **Under Review** | The expense is actively being reviewed in the approval workflow. |
| **Approved** | All approval steps have been completed. The expense has been approved. |
| **Rejected** | The expense was rejected at one of the approval steps. |
| **Paid** | The approved expense has been reimbursed. |

---

## 4. Pending Approvals

> **Note:** The **Pending Approvals** section is only visible if your user account has been assigned to one or more approval workflow roles. If you do not see this item in the sidebar, you do not currently have approval responsibilities.

### 4.1 Viewing Pending Approvals

Click **Pending Approvals** in the sidebar to see all requests waiting for your action.

> **[SCREENSHOT: Pending Approvals list page]**

The table shows:

| Column | Description |
|---|---|
| **Reference** | The reference number of the expense or reward (e.g. `EXP-00042`) |
| **Type** | Whether this is an **Expense** or a **Reward** |
| **Submitted By** | The name of the person who submitted the request |
| **Amount** | The total value of the request |
| **Workflow Step** | The name of the approval step you are being asked to action |
| **Submitted** | The date and time it was submitted |

Only requests at the step assigned to your role are shown here. You will not see requests waiting on other roles.

---

### 4.2 Approving a Request

1. In the Pending Approvals list, find the request you want to approve.
2. Click the **Approve** action (green check icon) on that row.

> **[SCREENSHOT: Approve action icon on a Pending Approvals row]**

3. An approval dialog will appear. You can optionally enter a **Note** to attach to your decision.

> **[SCREENSHOT: Approve confirmation modal with Notes field]**

4. Click **Approve** to confirm. The request will move to the next step in the workflow (or be fully approved if this is the final step). A success notification will confirm your action.

> **[SCREENSHOT: Success notification after approving]**

---

### 4.3 Rejecting a Request

1. In the Pending Approvals list, find the request you want to reject.
2. Click the **Reject** action (red X icon) on that row.

> **[SCREENSHOT: Reject action icon on a Pending Approvals row]**

3. A rejection dialog will appear. Enter a **Rejection Reason** — this field is required so the submitter understands why their request was declined.

> **[SCREENSHOT: Reject confirmation modal with Rejection Reason field]**

4. Click **Reject** to confirm. The request will be marked as Rejected and the submitter will be able to see the reason. A notification will confirm the action.

> **[SCREENSHOT: Warning notification after rejecting]**

---

## 5. My Rewards

The **My Rewards** section shows any rewards or bonuses that have been allocated to you and are in an **Approved** or **Paid** state.

### 5.1 Viewing Your Rewards

Click **My Rewards** in the sidebar.

> **[SCREENSHOT: My Rewards page]**

The table shows:

| Column | Description |
|---|---|
| **Reference** | A unique reference number for the reward |
| **Reward Type** | The category/type of reward |
| **Amount** | The value of the reward in its currency |
| **Status** | The current payment status (see below) |
| **Notified** | The date and time you were notified of the reward |

This page is read-only. Rewards are created and managed by administrators.

---

### 5.2 Reward Statuses

| Status | Meaning |
|---|---|
| **Pending** | The reward has been allocated to you but you have not yet been formally notified. |
| **Notified** | You have been notified that you will receive this reward. |
| **Paid** | The reward payment has been issued to you. |

---

## 6. Navigation & Account

### 6.1 Sidebar Navigation

The sidebar on the left side of the portal gives you access to all sections:

- **Dashboard** — your summary overview
- **My Expenses** — manage your expense claims
- **Pending Approvals** — review requests assigned to your approval role *(visible only if applicable)*
- **My Rewards** — view rewards allocated to you

> **[SCREENSHOT: Sidebar with all navigation items visible]**

The sidebar can be **collapsed** to give more space to the main content area. Click the collapse/expand icon at the top of the sidebar to toggle it.

> **[SCREENSHOT: Collapsed sidebar state]**

---

### 6.2 Admin Panel Access

If your account has administrator privileges, an **Admin Panel** link will appear at the bottom of the sidebar. Clicking it will take you to the administration interface where you can manage users, expense types, workflows, and more.

> **[SCREENSHOT: Admin Panel link at the bottom of the sidebar]**

---

## 7. Reference: Status Colours

Status badges throughout the portal use consistent colours to make it easy to understand state at a glance.

### Expense Statuses

| Status | Badge Colour |
|---|---|
| Draft | Grey |
| Submitted | Blue |
| Under Review | Yellow/Warning |
| Approved | Green |
| Rejected | Red |
| Paid | Green |

### Reward Recipient Statuses

| Status | Badge Colour |
|---|---|
| Pending | Grey |
| Notified | Blue |
| Paid | Green |

### Approval Step Statuses (Pending Approvals)

| Status | Badge Colour |
|---|---|
| Pending | Grey |
| Approved | Green |
| Rejected | Red |

---

*For technical support or to report issues, please contact your system administrator.*
