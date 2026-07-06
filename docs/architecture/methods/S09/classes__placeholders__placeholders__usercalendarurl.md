# usercalendarurl — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/usercalendarurl.php` · **LOC:** 104 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`usercalendarurl` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den persoenlichen Kalender-Abonnement-Link eines Nutzers als HTML-Anker liefert. Im Gegensatz zu den optionsbezogenen Platzhaltern ist dieser **userbezogen** (Cachekey enthaelt `$userid`). Keine Persistenz; erzeugt den Link zur Laufzeit. Kollaborateure: `booking_utils::booking_generate_calendar_subscription_link()`, `singleton_service::get_instance_of_user()`, `placeholders_info` (Request-Memo), `get_string()` fuer den Fehlertext.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Erzeugt fuer `$userid` einen `<a>`-Tag, dessen Inhalt der via `booking_utils::booking_generate_calendar_subscription_link($user, 'user')` generierte Abo-Link ist. **Seiteneffekte:** instanziiert `booking_utils`; holt den User-Record per Singleton; schreibt das Ergebnis in `placeholders_info::$placeholders["usercalendarurl-$optionid-$userid"]`. Ablauf: Klassenname aus `get_called_class()`; bei leerem `$userid` Rueckgabe des `sthwentwrongwithplaceholder`-Fehlerstrings; sonst Cache-Lookup, Link-Generierung, Cache-Befuellung. **Rueckgabe:** HTML-Anker-String bzw. Fehler-String; kein deklarierter Rueckgabetyp. **Bewertung:** B — korrekt mit funktionierendem userbezogenem Memo. Der `href="#"` mit inline-Style ist eine Praesentationsentscheidung; der eigentliche Subscription-Link sitzt im Anker-Text (vom Helper geliefert).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Userbezogener Kalender-Link-Platzhalter mit korrektem, `$userid`-sensitivem Request-Memo. Die HTML-Verpackung (`href="#"` + inline-Style) ist kosmetisch, der nutzbare Link kommt vom `booking_utils`-Helper. Keine funktionalen Maengel gefunden. Klassen-Score **B / P3**.
