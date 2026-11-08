# Hospital Workspace

## Purpose

The hospital workspace supports blood stock management and coordination of
donations and recipient requests assigned to the hospital.

## Pages

- `dashboard.php` — summarizes the hospital's inventory, upcoming
  appointments, open requests, and low-stock blood groups.
- `inventory.php` — sets the exact units held for each blood group.
- `appointments.php` — schedules donors and manages appointment and request
  statuses.

## How it works

An administrator must approve the hospital account before it can use the
workspace. Hospitals can schedule donors who are currently active, available,
and eligible under the configured deferral and donation-interval checks.

When an appointment is marked Completed, the system records a donation,
updates the donor's last-donation date and availability, and adds a unit of the
donor's blood group to that hospital's inventory. Inventory and request views
are scoped to the signed-in hospital.

Recipients select a verified hospital when creating a request. The hospital
then coordinates the request and updates its status so the recipient can track
progress.

## Access and safety

Hospital pages require the hospital role and an active, approved account.
State-changing forms use CSRF tokens and validate submitted choices. Confirm
real stock levels and clinical suitability independently; the application is
for coordination and record keeping, not clinical decision-making.
