# send_notification_mails — Methoden-Doku
**Datei:** `classes/task/send_notification_mails.php` · **LOC:** 164 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_tasks.md)

## Klassenueberblick
`send_notification_mails` ist ein `\core\task\scheduled_task` (Legacy-Mail-Pfad), der die „Notify-Me"-Warteliste abarbeitet: alle `booking_answers` mit `waitinglist = MOD_BOOKING_STATUSPARAM_NOTIFYMELIST` werden durchlaufen und der Interessent ueber eine wieder buchbar gewordene Option per Mail informiert (oder entfernt, falls die Option vorbei/geloescht ist). Die Klasse ist gegen das Setting `booking/uselegacymailtemplates` gegated und ein No-op, wenn dieses leer ist. Persistenz: liest/loescht `booking_answers`. Kollaborateure: `$DB`, `singleton_service` (booking-Instanz, option_settings, booking_answers), `booking_option::purge_cache_for_answers`, `message_controller`, `moodle_url`, Sprachstrings.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`tasksendnotificationmails`). **Seiteneffekte:** keine (Sprachstring). **Rueckgabe:** string. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Iteriert ueber alle Notify-Me-Eintraege; pro Eintrag wird entweder der User aus der Liste entfernt (Option vorbei oder Instanz/Option geloescht) oder — sofern nicht ausgebucht — eine „Option ist buchbar"-Custom-Message inkl. Abmelde-Link erzeugt und via `message_controller->send_or_queue()` versendet. **Seiteneffekte:** `get_config`; `$DB->get_records('booking_answers', ...)`; ggf. `$DB->delete_records('booking_answers', ...)` + `booking_option::purge_cache_for_answers($optionid)`; baut `moodle_url`s (optionview, unsubscribe); instanziiert `message_controller` und sendet/queued Mail; reichlich `mtrace`-Logging. **Bewertung:** C — zwei funktionale Schwaechen: (1) In den „Option vorbei"/„geloescht"-Zweigen wird `return;` statt `continue;` verwendet (Z.105 und Z.111) — der erste abgelaufene oder verwaiste Eintrag bricht den GESAMTEN Batch ab, alle nachfolgenden Notify-Me-Eintraege bleiben unverarbeitet (Data-/Delivery-Bug). (2) `mtrace('number of records', count($results ?? 0))` (Z.73) uebergibt den Count als 2. `mtrace`-Argument (`$eol`) statt als Teil des Strings — Logging-Misuse; `$results ?? 0` ist zudem ein No-op, da `get_records` immer ein Array liefert.

## Bewertungs-Resümee
Schmaler, gut nachvollziehbarer Legacy-Task mit korrektem Gating und Cache-Purge nach Loeschung. Belastet durch den `return`-statt-`continue`-Fehler (Batch-Abbruch beim ersten abgelaufenen/verwaisten Eintrag) und das fehlgeleitete `mtrace`-Logging. Klassen-Score **C / P2**.
