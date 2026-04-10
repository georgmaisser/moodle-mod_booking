---
id: participant-limit
title: Participant Limit
sidebar_label: Participant Limit
---

# Participant Limit

The **Participant Limit** condition lets you restrict the maximum number of users who can book a booking option. You can also configure a waiting list for users who try to book once the main capacity has been reached.

## Settings

| Field | Description |
|-------|-------------|
| **Limit the number of participants** | Enable this checkbox to activate the participant limit. When unchecked, unlimited bookings are allowed. |
| **Max. number of participants** | The maximum number of confirmed bookings. Once this number is reached, new users are placed on the waiting list (if configured) or blocked from booking. |
| **Max. number of places on waiting list** | The maximum number of users who can be placed on the waiting list. Set to `0` for no waiting list. |

## How to Configure

1. Open the booking option edit form.
2. In the **General** section, check **Limit the number of participants**.
3. Enter the desired value in **Max. number of participants**.
4. Optionally enter a value in **Max. number of places on waiting list**.
5. Save the booking option.

![Participant limit settings](pix/participant-limit-settings.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the participant limit fields in the booking option edit form.

## Behaviour

- When the number of confirmed bookings equals **Max. number of participants**, new users are automatically placed on the waiting list (if a waiting list is configured).
- When a confirmed user cancels their booking, the first user on the waiting list is automatically moved to a confirmed booking (if automatic notification is enabled in the booking instance settings).
- **Warning:** If you reduce the participant limit after users have already booked, some bookings may be removed **without notification**.

## Example

A training course has room for 20 participants and a waiting list of 5 places. Set **Max. number of participants** to `20` and **Max. number of places on waiting list** to `5`. Once 20 people have confirmed bookings, the next 5 users who try to book will be placed on the waiting list. The 26th user will not be able to book at all.

## Related Settings

- The booking instance-level setting for **availability info texts** (admin settings) controls how the available/waiting list place counts are displayed to users.
- The booking instance can be configured to automatically enrol users in a connected Moodle course once their booking is confirmed. This is done by selecting a course in the **Choose a course** field of the booking option edit form.
