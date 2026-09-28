# FITCO — Gym Owner Module

This document describes what was added to implement the **Gym Owner
module**: Dashboard, Member Management, Membership Management, and
Membership Renewal. It sits alongside the existing `user` (member
self-service) and `gym` (owner auth) modules that were already in the
project.

## What's new on disk

```
fitCo/assets/gym_module_schema.sql   — schema for all new tables (run once)

fitCo/home/home.php                  — Dashboard (rewritten; was demo data before)

fitCo/owner/
  includes/
    bootstrap.php       — session guard + gym context + shared helpers
    layout-top.php       — page shell: sidebar nav, topbar, flash messages
    layout-bottom.php     — closing markup
    owner.css              — shared styles for the whole module
  members/
    index.php   — list, search, filter members
    add.php      — register a new member
    view.php      — full profile: overview, membership/payment/attendance
                     history, workout plans, trainer, "assign plan" form
    edit.php       — edit details, activate/deactivate
  plans/
    index.php   — list plans, activate/deactivate/delete
    add.php      — create a plan
    edit.php      — edit a plan
  memberships/
    index.php   — every member's current membership, filterable by
                    active / expiring soon / expired / all
  renewals/
    index.php   — expired + expiring-soon queues
    renew.php    — renewal form and handler
  attendance/
    index.php   — search a member, mark today's check-in, today's log
  trainers/
    index.php   — add trainers, list, activate/deactivate
  payments/
    index.php   — recent payments, filter by paid/pending, mark paid

GYM_MODULE_README.md   — this file
```

## 1. Dashboard (`fitCo/home/home.php`)

Replaced the placeholder/demo numbers with real queries against the
new tables, scoped to the logged-in gym via `gym_id`:

- Total / active / inactive / newly-registered (last 30 days) members
- Expired memberships and memberships expiring within 7 days, computed
  from each member's *latest* membership row
- Today's attendance count, active trainer count
- Daily revenue, monthly revenue, pending payments (count + amount)
- Recent registrations, recent payments
- A membership-expiry alert banner when anything is expired or
  expiring soon
- Quick actions: Add Member, Mark Attendance, Renewals, Membership
  Plans

## 2. Member Management (`fitCo/owner/members/`)

- **Register** members with name, contact, gender, DOB, address, and
  an optional trainer assignment
- **List / search / filter** by name, email, phone, and status
- **Profile page** with tabs for:
  - Overview — current plan status, assigned trainer, an inline
    "assign a membership plan" form, recent attendance
  - Membership history — every period the member has ever had
  - Payment history — every payment tied to the member
  - Attendance — full check-in log
  - Workout plans — assign and view workout plans, with the
    responsible trainer
- **Edit** contact details and trainer assignment
- **Activate / deactivate** without deleting any history

## 3. Membership Management (`fitCo/owner/plans/` + `.../memberships/`)

- **Plans** are the catalog (name, duration in days, price,
  description). Full CRUD: create, edit, activate/deactivate; delete
  is only allowed for plans with zero membership history, to avoid
  orphaning past records.
- **Assigning** a plan to a member happens from the member's profile
  page (Overview tab) — it creates a `memberships` row and a matching
  `payments` row in one step.
- **Memberships** view lists every member's *current* period across
  the whole gym, filterable by Active / Expiring soon / Expired / All.

## 4. Membership Renewal (`fitCo/owner/renewals/`)

- Two queues: **already expired** and **expiring within 7 days**,
  each built from each member's latest membership row.
- **Renew** lets the owner pick a plan (defaults to the same one),
  a start date (defaults to the day after the old period ends, or
  today if it's already lapsed), a payment method, and payment status.
- On submit:
  1. The **old** `memberships` row is kept and marked `status='expired'`
     — it is never deleted or overwritten.
  2. A **new** `memberships` row is inserted with `is_renewal=1` and
     `previous_membership_id` pointing back at the old row, so the
     renewal chain is traceable.
  3. A new `payments` row is recorded against the new membership.

This was verified end-to-end: a membership was force-expired, renewed
through the UI, and the database confirmed both rows existed with the
correct statuses and the link between them intact.

## Supporting pieces

These weren't asked for as their own top-level sections, but the
Dashboard and Member Management requirements referenced data that
needed somewhere to come from:

- **Attendance** (`fitCo/owner/attendance/`) — search a member and
  mark them present for today (one check-in per member per day);
  shows today's full log.
- **Trainers** (`fitCo/owner/trainers/`) — minimal add/list/toggle so
  members and workout plans have someone to assign.
- **Payments** (`fitCo/owner/payments/`) — a flat, filterable list of
  every payment with the ability to mark a pending one as paid.

## Database

All new tables are in `fitCo/assets/gym_module_schema.sql`:
`trainers`, `members`, `membership_plans`, `memberships`, `payments`,
`attendance`, `workout_plans`. Every foreign key points back to `gym`
(directly or via `members`), so each gym owner only ever sees their
own data. Run this file once against `mp_db`, after the `gym` table
exists.

## Known scope boundary

`members` (the gym's own roster, managed by the owner) is currently
**separate** from `users` (the app's self-service member accounts —
BMI tracker, workout splits). A person who signs up through the app
is not automatically a row in `members`, and vice versa. Linking the
two — e.g. a nullable `user_id` on `members` — is a reasonable next
step but wasn't part of this implementation.

## Setup

```sql
-- Already exists (owner auth):
SOURCE fitCo/assets/user_module_schema.sql;   -- users, bmi_records, user_splits, split_exercises
-- (the `gym` table is expected to already exist)

-- New for this module:
SOURCE fitCo/assets/gym_module_schema.sql;    -- trainers, members, membership_plans,
                                               -- memberships, payments, attendance, workout_plans
```

Everything else (PHP, routes, styling) is ready to run as-is once the
database is seeded — no extra composer packages were needed for this
module.
