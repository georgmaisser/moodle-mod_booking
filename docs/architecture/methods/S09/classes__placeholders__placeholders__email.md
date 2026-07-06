# email — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/email.php` · **LOC:** 110 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`email` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den Platzhalter `{email}` durch die E-Mail-Adresse des Empfaengers ersetzt. Stateless; reine statische API (`return_value`, `is_applicable`, `for_pollurl`). Persistenz: keine eigene; liest `$user->email` via `singleton_service::get_instance_of_user`. Request-scoped Memo via `placeholders_info::$placeholders` mit userbasiertem Cachekey. Referenz-Muster fuer alle user-feld-basierten Platzhalter (firstname/lastname/email).

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die E-Mail-Adresse des Users `$userid` als Platzhalterwert; cacht request-scoped pro User.
- **Seiteneffekte:** `singleton_service::get_instance_of_user($userid)`; liest/schreibt `placeholders_info::$placeholders["$classname-$userid"]`.
- **Rueckgabe:** string — `$user->email`; bei fehlender `userid` `get_string('sthwentwrongwithplaceholder', ...)`.
- **Bewertung:** B — Sauberes Cache-on-load-Muster. Da Mails personalisiert sind, ist der userbasierte Cachekey korrekt (im Gegensatz zu optionsbasierten Platzhaltern). Keine Eskapierung/Validierung der E-Mail noetig, da der Aufrufer den Output formatiert.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter in Pollurls sinnvoll ist. **Seiteneffekte:** keine. **Rueckgabe:** `true` (E-Mail kann als Pollurl-Parameter dienen). **Bewertung:** A.

## Bewertungs-Resümee
Referenz-Platzhalter fuer User-Felder mit korrektem userbasiertem Memo. Keine funktionalen Maengel. Klassen-Score **B / P3**.
