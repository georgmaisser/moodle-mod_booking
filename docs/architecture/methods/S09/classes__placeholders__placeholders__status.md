# status — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/status.php` · **LOC:** 105 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`status` ist der Mail-/Text-Platzhalter (`[status]`), der den Buchungsstatus eines Users fuer eine Option als lokalisierten Text liefert (gebucht / Warteliste / storniert etc.). Sie erbt von `\mod_booking\placeholders\placeholder_base`. Im Gegensatz zu `startdate`/`starttime` ist der Wert userabhaengig, weshalb der Request-Memo-Key zusaetzlich `userid` enthaelt. Kollaborateure: `singleton_service` (Option-Settings, `booking_option`, `booking_answers`), `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Ermittelt und formatiert den Buchungsstatus von `$userid` fuer die Option. **Seiteneffekte:** Bei fehlendem `$cmid` wird dieser aus `booking_option_settings->cmid` nachgeladen. Anschliessend `singleton_service::get_instance_of_booking_option($cmid, $optionid)` und `get_instance_of_booking_answers($settings)`; der Status-String stammt aus `$bookingoption->get_user_status_string($userid, $bookinganswer->user_status($userid))`. Ergebnis wird im userabhaengigen Memo `placeholders_info::$placeholders["$classname-$optionid-$userid"]` gespeichert; Cache-Treffer fuehrt zur Fruehruckgabe. Leeres `$optionid` -> Fehler-Sprachzeichenkette `sthwentwrongwithplaceholder`. **Rueckgabe:** Status-String oder Fehlertext. **Bewertung:** B — korrektes user-spezifisches Caching; mehr Kollaborateure als die Datum-Platzhalter, aber alles ueber den Singleton-Service abgefedert. Der `$userid`-Default 0 fuehrt bei nicht uebergebenem User zu einem Gast-/0-Status-Lookup statt zu einem Fehler — fuer den Platzhalter-Kontext (immer mit konkretem Empfaenger aufgerufen) unkritisch.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Vertrags-Hook, ob der Platzhalter verarbeitet wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
User-spezifischer Status-Platzhalter mit korrekter, um `userid` erweiterter Memoisierung. Funktional unkritisch. Klassen-Score **B / P3**.
