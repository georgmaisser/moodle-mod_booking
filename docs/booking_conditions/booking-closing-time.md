---
id: booking-closing-time
title: Booking Closing Time
sidebar_label: Booking Closing Time
---

# Booking Closing Time

The **Booking Closing Time** condition lets you set a deadline after which users can no longer book a booking option. Once the closing time has passed, the "Book now" button is no longer available.

## Settings

| Field | Description |
|-------|-------------|
| **Limit the availability of this booking option until a certain date** | Enable this checkbox to activate the closing time restriction. |
| **Until** (bookingclosingtime) | The exact date and time after which no new bookings are accepted. |

## How to Configure

1. Open the booking option edit form.
2. In the **General** section, check **Limit the availability of this booking option until a certain date**.
3. Use the date/time selector to set the closing date and time.
4. Save the booking option.

![Booking closing time settings](pix/booking-closing-time-settings.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the booking closing time fields.

## Behaviour

- After the closing time, users who have not yet booked will see the option as unavailable for booking.
- Users who have already booked are **not** affected — their existing bookings remain valid.
- The closing time applies to new bookings only. Administrators and users with the `booking:updatebooking` capability may still be able to manage existing bookings.

## Example

A workshop starts on 15 March at 09:00 and registration should close one day before. Set the closing time to 14 March at 23:59. After that date and time, no new participants can register.

## Notes

> The older `timerestrict` field (visible in some versions of the plugin) is **deprecated** and will be removed. Use the **Booking Closing Time** condition instead, or configure access restrictions directly through Moodle's built-in **Restrict Access** settings at the activity level for more complex time-based rules.
