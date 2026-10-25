# Donor Workspace

## Purpose

The donor workspace lets an approved donor maintain their profile, control
whether they are available to donate, and review their recorded contribution
history.

## Pages

- `dashboard.php` — shows the donor's blood group and city, availability,
  next eligible date, and lifetime donation total.
- `profile.php` — updates contact and donor profile details, including blood
  group, age, gender, weight, and city.
- `availability.php` — processes the donor's availability change.
- `history.php` — lists recorded donation dates, hospitals, locations, and
  units.

## How it works

After an administrator activates and verifies the donor account, the donor
can sign in and use the workspace. Recipients see donors only when the account
is active and verified, the donor has opted in as available, the donor is not
under a deferral, and the configured donation interval has elapsed. Completing
an appointment at a hospital records the donation and updates the donor's
last-donation date and availability.

## Access and safety

Donor pages require the donor role. Availability changes use a CSRF token.
Profile and donation information is specific to the signed-in donor. Keep
profile details current, and consult healthcare professionals about eligibility;
the system's date calculation is informational, not medical advice.
