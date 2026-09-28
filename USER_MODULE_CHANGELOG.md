# FITCO — Session Changelog: CSS Fix + User Module (Phases 1–3)

This document covers everything changed or added in this working session,
in order. It's meant to sit alongside `GYM_MODULE_README.md` (which covers
the earlier Gym Owner module work) as a record of what the **User module**
now does and how it's wired together.

---

## 0. Bug fix — CSS/links broken under a subfolder install

**Problem:** the Gym Owner module's stylesheet and internal links used
root-absolute paths like `href="/fitCo/owner/includes/owner.css"`. That
only resolves correctly if the project sits directly at the web server's
document root. Once deployed to `C:\xampp\htdocs\mini-projectTEMP\fitCo`,
the browser requested `localhost/fitCo/...` instead of
`localhost/mini-projectTEMP/fitCo/...`, got a 404, and every owner page
rendered unstyled with broken navigation.

**Fix:** every `/fitCo/...` reference across the owner module — the CSS
link, the sidebar nav, in-page links, and the login-redirects in
`bootstrap.php` — was changed to `/mini-projectTEMP/fitCo/...` to match
the actual `htdocs` path. Nine files touched:

```
fitCo/owner/includes/layout-top.php
fitCo/owner/includes/bootstrap.php
fitCo/owner/attendance/index.php
fitCo/owner/renewals/index.php
fitCo/owner/renewals/renew.php
fitCo/owner/memberships/index.php
fitCo/owner/members/view.php
fitCo/owner/payments/index.php
fitCo/home/home.php
```

All new User module code (below) follows the same `/mini-projectTEMP/fitCo/...`
convention from the start.

**Trade-off to know about:** the folder name `mini-projectTEMP` is now
baked into the code. If you ever rename the folder or move it to a
different path in `htdocs`, these links break again the same way. Worth
switching to a dynamically-computed base path if/when you rename the
project.

---

## 1. The core design decision: linking `users` to `members`

Before any of the User module could be built, one structural gap had to
be resolved: the `users` table (self-service app accounts — login, BMI
tracker, workout splits) had **no relationship at all** to the `members`
table (the gym's own roster, managed by the owner — this is what
memberships, attendance, trainers, workout plans, and payments are all
keyed to). This was flagged as a known limitation in
`GYM_MODULE_README.md` and had to be addressed first.

**Approach taken:** the gym owner stays the source of truth for who is
actually a member — they still register people at the front desk, same
as before. A user account **connects itself** to that roster row after
the fact:

- `members` gained a nullable, unique `user_id` column
  (`fitCo/assets/user_module_phase1_schema.sql`).
- `fitCo/user/link-membership/link.php` lets a logged-in user pick their
  gym and enter the email the gym has on file. If it matches an
  unlinked `members` row for that gym, it links (`UPDATE members SET
  user_id=...`).
- One user account can be linked to **at most one** `members` row at a
  time (enforced with a unique key on `members.user_id`).
- Everywhere else in the User module, `$member`, `$member_id`, `$gym`,
  and `$gym_id` (all set up in `fitCo/user/includes/bootstrap.php`) are
  `null` until this link exists, and every page that needs them redirects
  to the linking page if it doesn't.

This single decision is what the rest of the User module hangs off — it's
why almost every page below checks `if (!$member) { redirect... }`.

---

## 2. Phase 1 — Linking, Dashboard, Profile

### Database
**`fitCo/assets/user_module_phase1_schema.sql`** (run once):
- `members.user_id` — nullable, unique, FK to `users.user_id`
  (`ON DELETE SET NULL`).
- `users` gains: `address`, `profile_photo`, `emergency_contact_name`,
  `emergency_contact_phone`, `status` (`active`/`inactive`, default
  `active`), `updated_at`.

### Shared scaffolding (new — mirrors the owner module's pattern)
```
fitCo/user/includes/bootstrap.php     — session guard, loads user + linked
                                         member/gym context, shared helpers
                                         (esc, h, fmt_money, fmt_date,
                                         days_until, flash_set/get)
fitCo/user/includes/layout-top.php    — sidebar nav, topbar, flash banner
fitCo/user/includes/layout-bottom.php — closing markup
fitCo/user/includes/user.css          — full design-system stylesheet
                                         (cloned from owner.css so both
                                         modules look consistent)
```

### Pages
| File | What it does |
|---|---|
| `fitCo/user/link-membership/link.php` | Connect account to a gym membership by email (see §1) |
| `fitCo/user/home/home.php` | **Rewritten.** Full dashboard — see below |
| `fitCo/user/profile/index.php` | View profile: personal info, emergency contact, account status, registration date, linked-gym status |
| `fitCo/user/profile/edit.php` | Edit personal info · upload profile photo · change password · deactivate account |

### Dashboard (`home.php`) shows, once a membership is linked:
- Membership status, plan, days remaining, expiry alert banner
- Today's attendance + 30-day check-in count
- Assigned trainer + current workout plan
- Recent payments + pending-payment count/total
- A **computed notifications panel** — expiry warnings, pending-payment
  reminders, and new-workout-plan notices, generated fresh on every page
  load rather than stored (see §5, "what wasn't built," for why)
- Quick actions that adapt to state (e.g. "Choose a Plan" vs "Renew
  Membership" depending on whether one exists)

### Profile photo uploads
Stored in `fitCo/assets/uploads/avatars/`, validated by real MIME-type
sniffing (`finfo`, not just the filename extension), capped at 2MB,
named `user_{id}_{timestamp}.{ext}`. The old file is deleted when a new
one is uploaded.

---

## 3. Phase 2 — Membership, Renewal, Payments (Razorpay)

No new schema this phase — it reuses `memberships`, `membership_plans`,
and `payments` from the Gym module as-is.

### Pages
| File | What it does |
|---|---|
| `fitCo/user/membership/index.php` | Current plan, benefits, price, dates, days remaining, status |
| `fitCo/user/membership/history.php` | Every past membership period |
| `fitCo/user/membership/renew.php` | Plan picker + Razorpay Checkout — handles both first-time subscription and renewal |
| `fitCo/user/payments/index.php` | Filterable payment history (all / paid / pending) |
| `fitCo/user/payments/receipt.php` | Printable/downloadable receipt (browser print dialog) |

### Payment gateway — Razorpay
```
fitCo/assets/razorpay_config.php   — API key placeholders (see setup below)
fitCo/assets/razorpay.php          — plain-cURL REST wrapper: order
                                      creation + signature verification.
                                      No SDK/composer dependency on purpose.
fitCo/user/membership/create-order.php    — AJAX: creates a Razorpay order
fitCo/user/membership/verify-payment.php  — AJAX: verifies the payment
                                              signature, then runs the
                                              renewal DB transaction
```

**Flow:** user picks a plan on `renew.php` → JS calls `create-order.php`
→ Razorpay Checkout opens in a modal → on success, JS sends the
payment/order id + signature to `verify-payment.php` → server
**re-verifies the signature itself** (`hash_hmac('sha256', order_id . '|'
. payment_id, KEY_SECRET)`, compared with `hash_equals`) → only then
writes to the database, inside a transaction:
1. Old membership row (if renewing) marked `expired` — never deleted.
2. New membership row inserted, `is_renewal=1`,
   `previous_membership_id` pointing at the old row (same chain pattern
   the owner module already uses).
3. New payment row inserted, `status='paid'`, with the Razorpay payment
   ID recorded in `notes`.

**Safety details worth knowing about:**
- The client-side payment callback is **never trusted on its own** —
  everything is re-verified server-side against the signature.
- Order context (which plan, which member, renewing what) is bridged
  between the two AJAX calls via `$_SESSION['pending_orders']`, since
  Razorpay's callback only returns payment/order IDs, not app data.
- `verify-payment.php` is **idempotent** — if the client retries the
  same payment ID, it's detected (`payments.notes LIKE '%paymentid%'`)
  and returns success without inserting a duplicate row.
- The whole DB write is wrapped in `mysqli_begin_transaction` /
  `mysqli_commit` / `mysqli_rollback`, so a failure partway through can't
  leave a half-updated membership.

### Two real bugs found and fixed while testing this phase
1. **`db.php` corrupting JSON responses** — the shared DB-connection
   file echoes a stray `<script>console.log(...)</script>` on every
   include. Harmless on HTML pages, but it broke `create-order.php` and
   `verify-payment.php`'s JSON output. Fixed by wrapping those two
   files' `require`s in `ob_start()` / `ob_end_clean()` rather than
   touching the shared `db.php` (which every other page in the app
   still depends on as-is).
2. **Ambiguous `member_id` column** in `payments/index.php`'s filter
   query — it joins `payments`, `memberships`, and `membership_plans`,
   and both `payments` and `memberships` have a `member_id` column. Fixed
   by qualifying it as `p.member_id`.

---

## 4. Phase 3 — Attendance, Trainer, Workout Plans

### Database
**`fitCo/assets/user_module_phase3_schema.sql`** (run once):
- New table `trainer_change_requests` — a user's request to be assigned
  a different trainer.

### Pages
| File | What it does |
|---|---|
| `fitCo/user/attendance/index.php` | Today's status, this-month/30-day/all-time stats, date-range-filterable history |
| `fitCo/user/trainer/index.php` | Assigned trainer's profile + contact info; submit a reassignment request |
| `fitCo/user/workout/index.php` | Current workout plan (trainer's free-text details) + history of previous plans |

### Design decisions made this phase
- **Attendance is view-only.** There's no self check-in/check-out
  button. The front desk (owner module) stays the authoritative source
  of physical presence — consistent with the membership-linking model in
  §1, and because a phone-based self-check-in doesn't actually prove
  someone is in the building.
- **Trainer reassignment isn't automatic.** Submitting a request just
  writes a row to `trainer_change_requests` with `status='pending'` and
  shows the user that it's pending. There's currently **no owner-side
  screen to review these requests** — a gym would need to check the
  table directly (e.g. via phpMyAdmin) or you'd need to build that
  review UI as a follow-up.
- **Workout plan detail stayed free-text**, by your choice — the
  `workout_plans` table only ever had a single `details` text field (no
  structured exercises/sets/reps/weight/rest columns), and the owner's
  assignment form was never built to populate anything more granular.
  Extending that would have meant changing both the owner module's form
  and the schema, so it was scoped out. The user-facing page just
  renders whatever text the trainer typed, with line breaks preserved.

---

## 5. What wasn't built (explicitly out of scope so far)

From your original 11-section spec, everything is covered **except**:

- **Persistent notifications with read/unread state.** The dashboard's
  notifications panel is fully computed on every page load (expiry
  status, pending payments, new workout plans) rather than stored in a
  table — so there's no "mark as read," no notification history, and no
  delivery outside of viewing the dashboard.
- **A dedicated Gym Information page.** Gym name/address/contact are
  shown inline (dashboard greeting, trainer/membership panels) but there
  isn't a standalone page listing operating hours, facilities,
  announcements, or gym policies — mostly because none of that data
  exists in the schema yet.
- **An owner-side screen to review trainer-change requests** (see §4).

These would be a reasonable Phase 4 if you want to pick it up.

---

## 6. Setup — what to run / configure

**1. Database migrations** (run once each, in order, against `mp_db`):
```sql
SOURCE fitCo/assets/user_module_phase1_schema.sql;
SOURCE fitCo/assets/user_module_phase3_schema.sql;
```
(Phase 2 needed no schema changes.)

**2. Razorpay keys** — edit `fitCo/assets/razorpay_config.php`:
```php
define('RAZORPAY_KEY_ID', 'rzp_test_...');     // from dashboard.razorpay.com/app/keys
define('RAZORPAY_KEY_SECRET', '...');
```
Test-mode keys (`rzp_test_...`) are safe to use while developing.

**3. PHP `curl` extension** must be enabled (`php_curl` in `php.ini`) —
required for `fitCo/assets/razorpay.php` to reach the Razorpay API.
Usually on by default in XAMPP; check `phpinfo()` if payments fail
immediately with a "could not reach the payment gateway" message.

**4. First live payment test** — do this yourself. The sandbox this was
built in has no network access to `api.razorpay.com`, so the
order-creation call itself was never tested against the real API — only
the failure path (network unreachable → clean error message, no crash)
and the signature-verification + database-write logic (tested with a
hand-computed HMAC signature simulating a real Razorpay callback).

---

## 7. Full file map — everything added or touched this session

```
fitCo/assets/
  gym_table_schema.sql              (earlier session — gym table reconstruction)
  user_module_phase1_schema.sql     NEW
  user_module_phase3_schema.sql     NEW
  razorpay_config.php               NEW
  razorpay.php                      NEW
  uploads/avatars/                  NEW (dir, for profile photos)

fitCo/owner/includes/layout-top.php       MODIFIED (path fix, §0)
fitCo/owner/includes/bootstrap.php        MODIFIED (path fix, §0)
fitCo/owner/attendance/index.php          MODIFIED (path fix, §0)
fitCo/owner/renewals/index.php            MODIFIED (path fix, §0)
fitCo/owner/renewals/renew.php            MODIFIED (path fix, §0)
fitCo/owner/memberships/index.php         MODIFIED (path fix, §0)
fitCo/owner/members/view.php              MODIFIED (path fix, §0)
fitCo/owner/payments/index.php            MODIFIED (path fix, §0)
fitCo/home/home.php                       MODIFIED (path fix, §0)

fitCo/user/includes/
  bootstrap.php                     NEW
  layout-top.php                    NEW
  layout-bottom.php                 NEW
  user.css                          NEW

fitCo/user/home/home.php            REWRITTEN (was a 2-card BMI/split demo)
fitCo/user/link-membership/link.php NEW
fitCo/user/profile/index.php        NEW
fitCo/user/profile/edit.php         NEW
fitCo/user/membership/index.php     NEW
fitCo/user/membership/history.php   NEW
fitCo/user/membership/renew.php     NEW
fitCo/user/membership/create-order.php    NEW
fitCo/user/membership/verify-payment.php  NEW
fitCo/user/payments/index.php       NEW
fitCo/user/payments/receipt.php     NEW
fitCo/user/attendance/index.php     NEW
fitCo/user/trainer/index.php        NEW
fitCo/user/workout/index.php        NEW

fitCo/user/bmi/*        UNCHANGED (pre-existing)
fitCo/user/split/*      UNCHANGED (pre-existing)
```

---

## 8. How this was tested

Everything above was exercised live against a real PHP + MariaDB
instance (not just `php -l` syntax-checked), with seeded test data for
each phase: a test gym, a test user account, members, trainers,
membership plans, payments, attendance records, and workout plans. That
included the full flow of logging in, linking an account, choosing/renewing
a plan, verifying a simulated Razorpay signature end-to-end, uploading a
profile photo, changing a password, and confirming a deactivated account
gets locked out. All test data was deleted afterward — the database
migrations are the only lasting change to the schema.
