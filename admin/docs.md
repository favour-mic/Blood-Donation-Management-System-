# Administrator Workspace

## Purpose

The administrator workspace provides system-wide operational summaries and
controls for reviewing donor, recipient, and hospital accounts.

## Pages

- `dashboard.php` — displays totals for users, hospitals, requests, inventory,
  and donations, along with recent activity and pending accounts.
- `users.php` — filters accounts by status and approves, suspends, or restores
  non-administrator accounts.

## How it works

New accounts are pending and cannot use their role workspace until approved.
Approving or restoring an account activates it; donor and hospital approvals
also update the corresponding profile verification state. Suspending an
account disables access. Administrators cannot use this interface to change
their own account status.

## Access and safety

Administrator pages are restricted to the admin role. Account actions validate
the target and allowed action, exclude administrator accounts, and use CSRF
tokens. Review identity and hospital licensing information according to
organizational policy before approving accounts, and grant administrator access
only to trusted operators.
