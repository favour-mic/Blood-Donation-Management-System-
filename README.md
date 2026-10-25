# BloodBridge System Documentation

## 1. Overview

BloodBridge is a PHP and MySQL blood donation management system. It connects
donors, recipients, hospitals, and administrators through registration,
verification, donor discovery, blood requests, appointments, donation history,
and hospital inventory.

The main public entry point is `index.php`. The application is intended to run
locally with a PHP/MySQL stack such as XAMPP. Its database is named `bdms` and
is initialized by `includes/config.php`.

## 2. User roles

| Role | Main responsibilities |
| --- | --- |
| Donor | Maintain a donor profile, manage availability, and review donation history and eligibility. |
| Recipient | Find eligible donors, submit requests to a verified hospital, and track request status. |
| Hospital | Maintain blood inventory, schedule donors, record completed donations, and manage assigned requests. |
| Administrator | Review registrations, approve or suspend accounts, and monitor system-wide activity and totals. |

New accounts start in a pending state. An administrator must activate them
before they can use the protected role workspace. Donor and hospital profiles
are also marked verified as part of account approval.

## 3. Typical workflows

1. A donor, recipient, or hospital creates an account from `register.php`.
2. An administrator reviews the account from `admin/users.php`.
3. A verified donor can indicate availability. Recipients can search by blood
   group and city; only active, verified, available donors outside the
   configured deferral and 90-day donation intervals are included.
4. A recipient can submit a request to a verified hospital and check its
   progress in My Requests.
5. A hospital manages stock, schedules eligible donors, and updates appointment
   and request statuses. Completing an appointment records a donation and
   updates donor eligibility and hospital inventory.
6. Administrators can review account status and system totals in the admin
   workspace.

## 4. Project layout

- `includes/` — database/schema setup, session and role authorization, CSRF
  helpers, and shared public and signed-in page layouts.
- `css/` and `js/` — public, registration, and signed-in application styling
  and browser behavior.
- `donor/` — donor workspace pages.
- `recipient_folder/` — recipient search, request creation, and request status
  pages.
- `hospital/` — hospital dashboard, inventory, and appointment/request
  management.
- `admin/` — system dashboard and account management.
- Root PHP pages — public landing, information, sign-in, registration, and
  sign-out pages.
- `docs.md` in each role directory — role-specific usage documentation.

## 5. Security and privacy

The application includes several protective measures:

- Passwords are stored using PHP's `password_hash()` and checked with
  `password_verify()`.
- Successful sign-in regenerates the session ID. Login attempts are counted
  and repeated incorrect passwords trigger a temporary account lock.
- Protected pages enforce role authorization and re-check that the account is
  active and approved.
- CSRF tokens protect sign-in, registration, and supported state-changing
  forms.
- Database operations that use submitted values generally use prepared
  statements. HTML output is escaped with `safe()` or `htmlspecialchars()`.
- Recipient donor search does not expose donor names, email addresses, or phone
  numbers.
- Hospital records and requests are scoped to the signed-in hospital; recipient
  request history is scoped to the signed-in recipient.

### Deployment requirements

The current database configuration in `includes/config.php` uses local
development defaults (`localhost`, user `root`, and a blank password). Replace
these values with dedicated production credentials before deployment, keep
credentials outside the public web root, and never publish them.

Serve the application over HTTPS, configure secure session-cookie settings,
restrict database and filesystem permissions, and maintain protected backups.
The UI loads Google Fonts and Font Awesome from external CDNs; deployments
should review their network/privacy requirements and pin or self-host assets
when appropriate.

This system supports coordination and record keeping. It is not an emergency
service or a substitute for clinical assessment: healthcare professionals must
confirm blood compatibility and medical suitability before a donation or
transfusion.

## 6. Operational notes

- Configure PHP with `mysqli` and a running MySQL/MariaDB service.
- Visit `index.php` through the local web server; opening PHP files directly
  from the filesystem will not run the application.
- Keep database backups current before schema or deployment changes.
- Use the administrator account only for account review and system
  administration.
