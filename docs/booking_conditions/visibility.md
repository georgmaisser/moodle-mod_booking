---
id: visibility
title: Visibility
sidebar_label: Visibility
---

# Visibility

The **Visibility** condition controls whether a booking option is shown to all users on the course page or only to users with special permissions. This allows administrators and teachers to create "hidden" options that are only accessible to selected users (e.g., staff, privileged groups).

## Settings

| Field | Description |
|-------|-------------|
| **Visibility** | Choose between **Visible to everyone** and **Hide from normal users (visible to entitled users only)**. |

## How to Configure

1. Open the booking option edit form.
2. In the **General** section, find the **Visibility** dropdown.
3. Select the desired visibility:
   - **Visible to everyone** — all users who can access the booking activity will see this option.
   - **Hide from normal users (visible to entitled users only)** — the option is hidden from regular users. Only users with the `booking:canseeinvisibleoptions` capability can see it.
4. Save the booking option.

![Visibility setting](pix/visibility-setting.png)

> **Note:** The screenshot above is a placeholder. Replace it with an actual screenshot of the visibility dropdown in the booking option edit form.

## Behaviour

- When set to **Hide from normal users**, the option does not appear in the booking option list for regular users.
- Users who attempt to access the option directly via URL will receive a "not allowed" message.
- Users with the **`booking:canseeinvisibleoptions`** capability (typically managers, administrators, or custom roles) can still see and interact with the hidden option.

## Permissions

To grant a user the ability to see invisible options, assign them the `booking:canseeinvisibleoptions` capability through Moodle's role management.

## Example

A booking activity lists both public workshops and internal staff sessions. The staff sessions should not be visible to regular students. Set those options to **Hide from normal users**. Students will only see the public workshops, while staff members (who have the appropriate capability) see all options.
