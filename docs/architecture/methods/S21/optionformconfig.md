# optionformconfig — Methoden-Doku
**Datei:** `optionformconfig.php` · **LOC:** 81 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Konfigurationsseite fuer die Sichtbarkeit/Anordnung der Optionsformular-Felder — entweder global (kein `cmid`) oder pro Booking-Instanz (mit `cmid`). Die eigentliche Konfigurations-UI wird ueber das Mustache-Template `mod_booking/settings/optionformconfig` (mit `contextid`) gerendert; das Feature ist PRO-gated. Kollaborateure: `singleton_service` (Booking-Settings), `wb_payment` (PRO-Check), Core `$PAGE`/`$OUTPUT`, `html_writer`.

## Request-/Permission-Flow
1. `require_once config.php`. `cmid` als `required_param(... PARAM_INT)`.
2. Verzweigung nach `cmid`:
   - **Instanz-Scope** (`!empty($cmid)`): `get_course_and_cm_from_cmid($cmid, 'booking')`, `require_course_login`, Modul-Kontext, Name aus Booking-Settings, `activityheader->disable()`.
   - **Global-Scope** (`cmid` leer/0): `require_login()`, System-Kontext, Name aus `get_config('booking', 'bookingconfig')`, Pagelayout `admin`.
3. Setzt URL; Header + Heading mit Name-Suffix; dismissible Info-Alert.
4. Inhalts-Gate: nur bei `wb_payment::pro_version_is_activated()`; darin Capability `mod/booking:editoptionformconfig` (System-Kontext) → rendert Template mit `contextid`, sonst „nopermission"-Alert. Ohne PRO: „pro license necessary"-Alert. Footer.

## Bewertung der Logik
- **Bewertung:** B — Scoping-Verzweigung sauber; Inhalts-Gate korrekt zweistufig (PRO + Capability). Mehrere kleinere Defekte: im Global-Zweig wird `require_login()` **zweimal** hintereinander aufgerufen (Z.47 und Z.50) — redundant, harmlos.
- Der Global-Name liest `get_config('booking', 'bookingconfig')`, einen Config-Key, der im Plugin nicht als Setting existiert → liefert i.d.R. leer und faellt auf `get_string('global')` zurueck; die Zwischenzuweisung ist faktisch totes Gewicht.
- Die Capability wird stets im System-Kontext geprueft, obwohl im Instanz-Scope ein Modul-Kontext vorliegt — vermutlich gewollt (globale Berechtigung), aber inkonsistent zur Scope-Trennung.

## Findings
- `optionformconfig.php:47,50` — `require_login()` im else-Zweig doppelt aufgerufen (redundant, harmlos) (P3).
- `optionformconfig.php:49` — `get_config('booking', 'bookingconfig')` liest einen nicht existierenden Config-Key; der Global-Name bleibt leer und faellt immer auf `global` zurueck (toter Code-Pfad) (P3).

## Bewertungs-Resümee
Korrekt abgesicherte, PRO-gated Konfigurationsseite mit sauberer Global/Instanz-Verzweigung; abgewertet nur durch den doppelten `require_login`-Aufruf und den ins Leere laufenden `bookingconfig`-Lookup. Klassen-Score **B / P3**.
