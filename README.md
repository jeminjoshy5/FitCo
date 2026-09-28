# FITCO — Gym Management Platform

A multi-tenant gym management platform: gym owners run their gym from one dashboard, members self-serve from another, and a platform admin oversees both. Built on plain PHP + MySQLi (no framework) so it runs on stock XAMPP.

---

## Modules at a glance

| Module | Who it's for | Entry point |
|---|---|---|
| Public site | Anyone | `index.php` |
| Member module | Gym members | `fitCo/user-auth/login/login.php` |
| Gym owner module | Gym owners | `fitCo/auth/login/login.php` |
| Admin module | Platform staff | `fitCo/admin-auth/login/login.php` |

---

## Public site

- **Homepage** (`index.php`) — hero, feature overview, "how it works", a pitch section for gym owners, testimonials, and calls to action into both signup flows. Scroll-reveal animations, sticky nav with a login dropdown (member / gym owner / admin) and a mobile menu.
- Shared header/footer partials in `fitCo/header/` and `fitCo/footer/`.

---

## Member module (`fitCo/user/`, `fitCo/user-auth/`)

**Account**
- Sign up, with email verification via a 6-digit OTP (emailed through PHPMailer) before the account is actually created
- Log in / log out
- Forgot password → OTP → reset password
- Edit profile (name, address, emergency contact, profile photo)
- Change password (from the profile page)
- Deactivate account

**Gym linking**
- Link your account to a membership record your gym owner already created, by matching gym + email (`fitCo/user/link-membership/link.php`)

**Fitness tracking**
- **BMI tracker** — log height/weight, see BMI category, history over time
- **Workout split builder** — plan a weekly split by muscle group, saved to your account
- **Attendance history** — view your own check-in history

**Membership & payments**
- View current membership status, plan, and days remaining
- **Membership history** — every past period, kept even after renewal
- **Renew / subscribe** to a plan — payment goes through a local **payment simulator** (see [Payments](#payments) below), styled as a real credit-card checkout with full client + logical validation
- **Payment receipts**

**Other**
- View assigned trainer

---

## Gym owner module (`fitCo/owner/`, `fitCo/auth/`)

**Account**
- Sign up (with gym license upload) → email OTP verification → account created with `pending` approval status
- Log in / log out — blocked with a clear message if the gym is `pending`, `rejected`, or `suspended` (see [Admin module](#admin-module))
- Forgot password → OTP → reset password

**Dashboard**
- Member counts (total / active / inactive / new in last 30 days)
- Expiring-soon and already-expired membership lists
- Today's attendance, active trainer count
- Daily and monthly revenue

**Members**
- Register, view, edit, list/search/filter members
- Assign a trainer

**Plans & memberships**
- Create/edit membership plans (price, duration, description)
- View all memberships across members
- **Renewals** — process a renewal on a member's behalf

**Operations**
- Attendance log
- Trainer roster
- Payments list

---

## Admin module (`fitCo/admin/`, `fitCo/admin-auth/`)

Platform-level oversight, separate from gym owners and members. Admin accounts are seeded directly in the database (no public signup).

- **Dashboard** — total gyms (by approval status), total users, active memberships platform-wide, total and monthly revenue, and a queue of gyms awaiting review
- **Gyms** — search/filter by status; review a gym's full details (owner, contact, address, license document); **approve**, **reject**, **suspend**, or **reinstate**
- **Users** — search/filter; **suspend** or **reactivate** any user account

**Default login:** `admin@fitco.com` / `admin123` — **change this after your first login** (there's no admin profile page yet; update it directly: `UPDATE admins SET password='...' WHERE email='admin@fitco.com';`).

### Gym approval workflow

- New gym signups start as `pending` and cannot log in until an admin approves them
- Gyms that existed before this feature was added are backfilled as `approved`, so nothing already running gets locked out
- A gym's status is re-checked on every dashboard page load — a mid-session suspension logs the owner out immediately

---

## Payments

Payments run entirely on a **local simulator**, not the real Razorpay API — no account, API keys, or internet connection required (see `fitCo/assets/razorpay.php` and `fitCo/assets/razorpay_config.php`). This is intentional: it's a college project, and the simulator preserves the *real* part of a payment integration (server-side order creation, server-signed payments, and genuine HMAC signature verification that rejects tampering) without needing a live gateway.

The checkout modal (`fitCo/user/membership/renew.php`) presents a real-credit-card-style UI:
- A flippable 3D card visual that mirrors what's typed and flips to show the CVV
- Card brand detection (Visa / Mastercard) from the number
- Full validation: Luhn checksum on the card number, MM/YY expiry that must be in the future, 3–4 digit CVV, non-empty cardholder name — each with inline errors and a shake animation on invalid submit

If you ever want to switch to the real Razorpay API (test or live mode), that's a contained change in `fitCo/assets/razorpay.php` / `razorpay_config.php` and `renew.php`'s checkout script — the rest of the payment flow (`create-order.php`, `verify-payment.php`) doesn't need to change.

---

## Membership expiry notices

`fitCo/cron/send-membership-notices.php` emails members automatically:
- **2 days before** their membership expires (once per membership)
- **Once, if expired and not renewed** — also flips that membership's status to `expired`

This is a script, not a web page — nothing in the app links to it, and it has no login guard. It's meant to run once a day via a scheduler:

**Windows (XAMPP) — Task Scheduler:**
- Program/script: `C:\xampp\php\php.exe`
- Arguments: `C:\xampp\htdocs\mini-projectTEMP\fitCo\cron\send-membership-notices.php`
- Trigger: daily, e.g. 8:00 AM

**Linux/macOS — cron:**
```
0 8 * * * /usr/bin/php /path/to/mini-projectTEMP/fitCo/cron/send-membership-notices.php
```

---

## Setup

1. Create a database named `mp_db` (MySQL/MariaDB).
2. Load the schema — two ways to do this:
   - **Recommended:** run `fitCo/assets/full_schema.sql` once — it's every table and column the project needs, combined into a single file.
   - **Or**, run the individual files in `fitCo/assets/` in this order (useful if you're upgrading an existing install one step at a time): `gym_table_schema.sql` → `user_module_schema.sql` → `gym_module_schema.sql` → `user_module_phase1_schema.sql` → `user_module_phase3_schema.sql` → `admin_module_schema.sql`.
   - Both paths produce an identical schema — `full_schema.sql` is just the same 6 files concatenated in order, verified to match statement-for-statement.
3. Confirm `fitCo/assets/db.php` matches your MySQL credentials (defaults to `root` / no password / `localhost`, standard for XAMPP).
4. Serve the project so it's reachable at `/mini-projectTEMP/` (e.g. drop the whole folder into `C:\xampp\htdocs\`).
5. Visit `http://localhost/mini-projectTEMP/` — that's the public homepage.
6. (Optional) Schedule `fitCo/cron/send-membership-notices.php` as described above.

**Email-dependent features** (OTP verification, forgot password, membership notices) need a working SMTP account — the project ships with one already configured in `verify-otp.php` (both modules) and the cron script. If you rotate to your own, update the `PHPMailer` block in each of those files.

---

## Security notes — please read

A few decisions here were made explicitly for this being a college project, not general best practice for a real deployed app:

- **Passwords are stored in plain text**, not hashed. This was an explicit choice made during development — if this project is ever used for anything beyond a demo/coursework, switch back to `password_hash()` / `password_verify()` first.
- **SMTP credentials are hardcoded** in `verify-otp.php` (both modules) and the cron script, rather than pulled from an environment variable or gitignored config. Don't push this repo somewhere public without rotating that password first.
- The **payment gateway is simulated**, not connected to a real processor (see [Payments](#payments)).
- Several pages build SQL with string interpolation rather than prepared statements. IDs are cast to `(int)` before use in most places, and string values generally go through `mysqli_real_escape_string()`, but this hasn't been audited/converted end-to-end.
- There's no CSRF protection on state-changing forms.

None of this blocks the project from working or being demoed — it's just worth knowing what's a deliberate demo shortcut versus what you'd want to change before this became a real, publicly-reachable product.
