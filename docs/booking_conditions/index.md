---
id: booking-conditions-overview
title: Booking Conditions
sidebar_label: Overview
---

# Booking Conditions

Booking conditions control **who can book** a booking option, **when** they can book it, and **how many** places are available. They are configured individually for each booking option through the booking option edit form.

## What are Booking Conditions?

Every booking option in the mod_booking plugin can have one or more conditions that determine the availability of that option to users. These conditions act as gates: if a condition is not met, users cannot book (or cannot even see) the option.

Conditions are defined in the `mod_booking\bo_availability` namespace and are evaluated at the time a user attempts to book.

## How to Edit Booking Conditions

1. Navigate to the **Booking** activity in your course.
2. Open the list of booking options.
3. Click **Edit** next to the booking option you want to configure.
4. The booking option edit form contains all condition-related settings, mostly in the **General** section.

![Booking option edit form](pix/booking-option-edit-form.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the booking option edit form.

## Available Booking Conditions

| Condition | Description |
|-----------|-------------|
| [Participant Limit](participant-limit.md) | Restrict the maximum number of participants and waiting list places. |
| [Booking Closing Time](booking-closing-time.md) | Limit the availability of the booking option until a specific date and time. |
| [Connected Booking](connected-booking.md) | Accept users from another booking instance (prerequisite booking). |
| [Visibility](visibility.md) | Control whether the booking option is visible to all users or only to entitled users. |
| [Disable Booking](disable-booking.md) | Hide the "Book now" button entirely, preventing new bookings. |

## How Conditions Work Together

Multiple conditions can be active on the same booking option at the same time. In that case, **all** active conditions must be satisfied for a user to be able to book. For example, you can combine a participant limit with a booking closing time so that the option closes when either it is full or the closing date has passed — whichever comes first.
