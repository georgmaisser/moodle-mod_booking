[Back to parent section](../../README.md)

# AI Booking Assistant — Overview

> **Primary page** for: the built-in AI chat assistant (`bookingextension_agent`), opening the
> assistant, what it can do, confirmations and safety, where the full guide lives.

mod_booking ships with an AI assistant. You tell it in plain words — in your own language —
what you want to do with your bookings and courses, and it does it for you or answers your
question. There is no special syntax to learn.

## Opening the assistant

- **From anywhere:** click the small **magic-wand icon** in the top navigation bar. A chat
  panel opens on most pages.
- **From a Booking activity:** open the assistant inside the activity and it already knows
  which Booking activity you mean — no need to name it.

The assistant works in the context you open it from: inside a course or Booking activity it
assumes you mean that course or activity unless you say otherwise.

## What it can do

Everything runs with **your own Moodle permissions** — the assistant can never do more than
you could do yourself through the normal interface.

- **Create and edit booking options** — dates, prices per price category, trainers,
  descriptions, self-learning options, bulk updates.
- **Set up courses** — create a course, fill it with chapters and a final quiz, link it to a
  booking option, all from one request.
- **Answer questions and find things** — search options and people, explain settings, look up
  who is booked where.
- **Diagnose problems** — why a user cannot book, why a notification did not arrive, what
  blocks a cancellation.

## Confirmations and safety

Before the assistant changes anything, it shows you a **preview of the exact data** it is
about to write and waits for your confirmation. Read the preview, not just the chat text —
what is in the preview is what gets executed. Questions the assistant asks (for example
"which category?") are answered by simply replying in the chat.

## Availability

The assistant is a subplugin (`bookingextension/agent`) and its features are governed by the
site administrator: individual skills can be enabled or disabled, and write operations may
require a Wunderbyte PRO licence or subscription. If something is unavailable, the assistant
says so.

## Full user guide

This page is only the overview. The complete user documentation — getting started, examples,
multi-step requests, attachments, languages, privacy, troubleshooting — ships with the
subplugin under
[`bookingextension/agent/docs/user/`](../../../bookingextension/agent/docs/user/README.md).
