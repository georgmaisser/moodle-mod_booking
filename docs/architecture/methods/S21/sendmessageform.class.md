# mod_booking_sendmessage_form — Methoden-Doku
**Datei:** `sendmessageform.class.php` · **LOC:** 85 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
`mod_booking_sendmessage_form` ist eine klassische `moodleform` (kein DynamicForm) zum Verfassen einer Custom-Nachricht an gebuchte Nutzer einer Booking-Option. Die Klasse traegt keinen eigenen Zustand und keine Persistenz; sie definiert lediglich die Formularfelder. Verarbeitet wird das abgesendete Formular ausserhalb (im konsumierenden Skript / WS, das `optionid`, `id` und die Empfaenger-`uids` aus den Hidden-Feldern aufgreift). Kollaborateure: `moodleform`-Basis (`$this->_form`), `context_system::instance()` fuer den Editor-Kontext, Sprachstrings aus `mod_booking`/`form`/core.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Formular auf: Header `general`; Textfeld `subject` (Pflicht, `PARAM_TEXT`, size 64); `editor`-Feld `message` (Pflicht, `PARAM_RAW`, 20x50, `subdirs=0`, `maxfiles=0`, Kontext `context_system::instance()`); drei Hidden-Felder `optionid` (`PARAM_INT`), `id` (`PARAM_INT`), `uids` (`PARAM_RAW`); plus Action-Buttons mit Submit-Label `sendmessage`.
- **Seiteneffekte:** Mutiert das interne `$this->_form` (QuickForm). Keine DB-/IO-Zugriffe.
- **Bewertung:** B — sauber und kompakt. Anmerkungen: Der Editor-Kontext ist fest auf `context_system::instance()` verdrahtet statt auf den Modul-Kontext der Option (bei `maxfiles=0` praktisch folgenlos, da keine eingebetteten Dateien erlaubt sind); auskommentierter Legacy-`textarea`-Code (Z.58-59) ist toter Ballast; `uids` als `PARAM_RAW`-Hidden verlagert die Validierung/Sanitisierung der Empfaengerliste komplett in den verarbeitenden Code.

## Bewertungs-Resümee
Reine Formular-Definition ohne Logik oder Zustand. Funktional unauffaellig; einzig der hartkodierte System-Editor-Kontext und das `PARAM_RAW`-`uids`-Hidden sind als Hinweise fuer den konsumierenden Verarbeitungspfad festzuhalten (dort muss die Empfaengerliste gegen die tatsaechlich gebuchten Nutzer der Option validiert werden). Klassen-Score **B / P3**.
