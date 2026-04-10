---
id: disable-booking
title: Disable Booking
sidebar_label: Disable Booking
---

# Disable Booking

The **Disable Booking** condition completely hides the "Book now" button for a booking option, preventing any new bookings from being made. The option remains visible to users — they can still see its description and details — but they cannot book it.

## Settings

| Field | Description |
|-------|-------------|
| **Disable booking of users – hide Book now button** | When set to **Yes**, the "Book now" button is hidden and no new bookings can be made for this option. |

## How to Configure

1. Open the booking option edit form.
2. Scroll to the **Disable booking of users** field (a Yes/No selector).
3. Select **Yes** to disable booking, or **No** (default) to allow booking.
4. Save the booking option.

![Disable booking setting](pix/disable-booking-setting.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the disable booking field in the booking option edit form.

## Behaviour

- When enabled, no user (regardless of role) will see the "Book now" button on the option.
- Users who are already booked are **not** removed from the option. Their existing bookings remain valid.
- This setting is useful for "view only" options or for closing bookings without deleting the option.
- Administrators and users with `booking:updatebooking` can still manage existing bookings from the management interface.

## Example

A conference session is now fully organised and bookings are being managed manually off-platform. You want the session to remain visible in the booking activity (so participants can see dates and details) but you want to stop any further self-service bookings. Set **Disable booking** to **Yes**.

## Difference from Booking Closing Time

| Feature | Disable Booking | Booking Closing Time |
|---------|----------------|----------------------|
| Hides the "Book now" button | Yes | Yes (after the deadline) |
| Time-based | No (always active when enabled) | Yes |
| Reversible | Yes, manually toggle back to No | Passes automatically |

Use [Booking Closing Time](booking-closing-time.md) when you want bookings to close at a specific date and time automatically. Use **Disable Booking** when you want to manually control availability without a time-based trigger.
