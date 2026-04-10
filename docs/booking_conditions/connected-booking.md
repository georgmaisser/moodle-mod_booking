---
id: connected-booking
title: Connected Booking
sidebar_label: Connected Booking
---

# Connected Booking

The **Connected Booking** condition allows you to link a booking option to options in another booking instance. Users who have booked a specific option in the connected instance can be transferred to (or accepted in) options within the current instance. This is used to manage prerequisites or user flow between two related booking activities.

This feature is configured at two levels:

1. **Booking instance level** — defines which other booking instance is connected.
2. **Booking option level** — defines the specific rules (which option to accept from, and how many users).

## Instance-Level Setting: Connected Booking

| Field | Description |
|-------|-------------|
| **Connected booking** | Select another booking instance from which users can be transferred. Select "Not connected" to disable this feature. |

### How to Configure the Instance Connection

1. Navigate to **Edit settings** of the **Booking** activity (not a single option, but the whole activity).
2. In the **Connected booking** section, select the booking instance from which users should be transferred.
3. Save the activity settings.

![Connected booking instance setting](pix/connected-booking-instance.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the connected booking setting in the booking instance form.

## Option-Level Setting: Other Booking Rules

Once a booking instance is connected, you can define rules for each individual booking option that specify which option (from the connected instance) is the source and how many users can be accepted.

| Field | Description |
|-------|-------------|
| **Option** | The specific option (within the connected booking instance) from which users will be accepted. |
| **Limit** | The maximum number of users to accept from that option. Set to `0` for unlimited. |

### How to Configure Other Booking Rules

1. Open the **Booking** activity.
2. In the booking option list, click **Other booking rules** next to the relevant option.
3. Click **Add new rule**.
4. Select the **Option** (from the connected booking instance) and set the **Limit**.
5. Save the rule.

![Other booking rules form](pix/connected-booking-rules.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the "Other booking rules" management page.

## Behaviour

- When a rule is configured, users who have booked the specified option in the connected booking instance can be accepted into the current option.
- The **Limit** field controls the maximum number of such transfers. If `0`, there is no limit.
- Multiple rules can be defined for the same option, allowing users to be accepted from several different source options.
- The "Book users to other booking" button (if enabled and named in the booking instance settings) triggers the transfer action.

## Example

You have two booking instances: **Course A** and **Course B**. Course A has an option called "Module 1". As a prerequisite for certain options in Course B, you want to accept users who completed Module 1. Connect Course B to Course A, then add a rule on the target Course B option pointing to "Module 1" with a limit of `10`. Up to 10 users from "Module 1" can then be moved to that Course B option.

## Related Settings (Booking Instance)

The following labels in the booking instance settings are related to this feature:

| Setting | Description |
|---------|-------------|
| **Name of button: Book users to other booking** | Customise the label shown on the transfer button. |
