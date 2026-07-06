# booking_subbooking — Methoden-Doku
**Datei:** `classes/subbookings/booking_subbooking.php` · **LOC:** 169 · **Subsystem:** S08 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S08_subbookings.md)

## Klassenueberblick
`booking_subbooking` ist ein **Interface** (kein konkreter Code), das den Vertrag fuer alle Subbooking-Typen im Subsystem S08 definiert. Jeder Subbooking-Typ (z. B. Timeslot-, Additionalitem-Subbooking) muss dieses Interface implementieren. Es buendelt Form-Rendering, Persistenz (JSON in `booking_subbookings`), Preis-/Block-Logik sowie Reservierungs-/Buchungs-Hooks. Kollaborateure: `booking_option_settings` (Kontext der Option), `MoodleQuickForm` (Formular), `stdClass` (Form-/DB-Datensaetze). Da es sich um reine Methoden-Deklarationen ohne Rumpf handelt, gibt es keine Seiteneffekte oder Aufrufketten im Interface selbst — diese entstehen erst in den implementierenden Klassen.

## Methoden

Alle Methoden sind **public** (Interface-Methoden). Keine besitzt einen Rumpf; Seiteneffekte/Aufrufketten sind implementierungsabhaengig. Bewertung jeweils **A** (klare, eng geschnittene Vertragsmethode).

### `add_subbooking_to_mform(MoodleQuickForm &$mform, array &$formdata): void` — public
- **Zweck:** Fuegt die Formularelemente dieses Subbooking-Typs in das uebergebene mform ein.
- **Parameter:** `$mform` (per Referenz, Zielformular), `$formdata` (per Referenz, vorhandene Formdaten). **Rueckgabe:** void.
- **Bewertung:** A.

### `get_name_of_subbooking($localized = true): string` — public
- **Zweck:** Liefert den (optional lokalisierten) menschenlesbaren Namen des Subbooking-Typs.
- **Parameter:** `$localized` (bool). **Rueckgabe:** string.
- **Bewertung:** A.

### `save_subbooking(stdClass &$data)` — public
- **Zweck:** Baut/serialisiert das JSON des Subbookings zur Speicherung in der DB (Tabelle `booking_subbookings` durch Implementierung).
- **Parameter:** `$data` (per Referenz, Formdaten). **Rueckgabe:** unspezifiziert (kein Typehint).
- **Bewertung:** A.

### `set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt Default-Werte fuer das Formular aus einem `booking_subbookings`-Record.
- **Parameter:** `$data` (per Referenz, Default-Container), `$record` (DB-Record). **Rueckgabe:** unspezifiziert.
- **Bewertung:** A.

### `set_subbookingdata(stdClass $record)` — public
- **Zweck:** Laedt die JSON-Daten eines DB-Records in das Objekt.
- **Parameter:** `$record` (Subbooking-Record). **Rueckgabe:** unspezifiziert.
- **Bewertung:** A.

### `set_subbookingdata_from_json(string $json)` — public
- **Zweck:** Laedt Daten direkt aus einem JSON-String ins Objekt.
- **Parameter:** `$json` (string). **Rueckgabe:** unspezifiziert.
- **Bewertung:** A.

### `return_interface(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Gibt das UI-Interface dieses Subbooking-Typs als Array aus Daten + Template zurueck.
- **Parameter:** `$settings`, `$userid`. **Rueckgabe:** array.
- **Bewertung:** A.

### `return_price($user): array` — public
- **Zweck:** Liefert den (ggf. modifizierten, z. B. bei Mehrfachauswahl) Preis als Array.
- **Parameter:** `$user` (object). **Rueckgabe:** array.
- **Bewertung:** A.

### `return_subbooking_information(int $itemid = 0, int $userid = 0): array` — public
- **Zweck:** Gibt alle relevanten Infos des Subbookings als Array zurueck; erlaubt Differenzierung mehrerer Items (z. B. Slot-IDs als itemids).
- **Parameter:** `$itemid`, `$userid`. **Rueckgabe:** array.
- **Bewertung:** A.

### `return_answer_json(int $itemid, ?object $user = null): string` — public
- **Zweck:** Liefert beim Buchen das ergaenzende Answer-JSON, das pro Subbooking-Typ gespeichert wird.
- **Parameter:** `$itemid`, `$user` (nullable). **Rueckgabe:** string.
- **Bewertung:** A.

### `is_blocking(booking_option_settings $settings, int $userid = 0): bool` — public
- **Zweck:** Entscheidet abhaengig von Settings und User, ob dieses Subbooking die Buchung blockiert.
- **Parameter:** `$settings`, `$userid`. **Rueckgabe:** bool.
- **Bewertung:** A.

### `after_booking_action(booking_option_settings $settings, int $userid = 0, int $recordid = 0): bool` — public
- **Zweck:** Hook fuer Aktionen nach erfolgter Buchung.
- **Parameter:** `$settings`, `$userid`, `$recordid`. **Rueckgabe:** bool.
- **Bewertung:** A.

### `reservation_action(booking_option_settings $settings, int $userid = 0, int $recordid = 0): bool` — public
- **Zweck:** Hook fuer Aktionen bei Reservierung.
- **Parameter:** `$settings`, `$userid`, `$recordid`. **Rueckgabe:** bool.
- **Bewertung:** A.

### `reservation_deletion_action(booking_option_settings $settings, int $userid = 0, int $recordid = 0): bool` — public
- **Zweck:** Hook fuer Aktionen, wenn eine zuvor reservierte Subbooking geloescht wird.
- **Parameter:** `$settings`, `$userid`, `$recordid`. **Rueckgabe:** bool.
- **Bewertung:** A.

## Anmerkungen
- Reines Interface ohne Implementierung — keine refactoring-relevanten Methoden (keine C/D/E).
- Kleinere Inkonsistenz: Mehrere Persistenz-/Setter-Methoden (`save_subbooking`, `set_defaults`, `set_subbookingdata`, `set_subbookingdata_from_json`) besitzen keinen Rueckgabetyp-Hint, waehrend die Query-Methoden durchgehend `: array` / `: bool` / `: string` typisieren. Stilistisch, kein Bug.
