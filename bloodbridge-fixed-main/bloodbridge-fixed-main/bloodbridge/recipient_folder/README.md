# Recipient Workspace

## Purpose

The recipient workspace helps users find eligible donors, send blood requests
to verified hospitals, and follow the status of their own requests.

## Pages

- `search-donors.php` — filters eligible donors by blood group and city without
  exposing personal contact details.
- `submit-request.php` — submits the needed blood group, number of units,
  urgency, and selected verified hospital. Patient name is optional.
- `my-request.php` — lists the signed-in recipient's requests, coordinating
  hospital, and current status.

## How it works

An approved recipient can search donors by blood group and location. Search
results include only active, verified, available donors who are not in a
deferral period and have passed the configured 90-day interval since their
last donation.

Requests are associated with the signed-in recipient and a verified hospital.
The hospital coordinates fulfillment and updates the request status. Recipients
can follow the request through Pending, Matched, and Fulfilled, or see if it
was Cancelled.

## Access and safety

Recipient pages require the recipient role, and request history is limited to
the signed-in recipient. Request submission validates the supplied values and
uses a CSRF token. Donor contact details remain private in the search results.
For urgent or emergency medical needs, contact local emergency and healthcare
services directly; this application is not an emergency service.
