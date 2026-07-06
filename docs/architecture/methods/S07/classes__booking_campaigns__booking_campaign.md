# booking_campaign — Methoden-Doku

**Datei:** `classes/booking_campaigns/booking_campaign.php` · **LOC:** 126 · **Subsystem:** S07 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S07_*.md)

## Klassenueberblick
`booking_campaign` ist ein **Interface** (kein konkreter Code), das den Kontrakt fuer alle Kampagnen-Typen in `mod_booking\booking_campaigns` definiert. Konkrete Implementierungen (z. B. Preis-Faktor- oder Blocking-Kampagnen) muessen alle deklarierten Methoden umsetzen. Kollaborateure: `booking_option_settings` (Kontext der Buchungsoption), `MoodleQuickForm` (Admin-Formular), `stdClass` (Form-/DB-Records). Reines Polymorphie-/Plug-in-Vertrag-Konstrukt ohne eigene Logik — daher gut testbar (Mock-faehig) und sauber.

## Methoden

Alle Eintraege sind reine **Interface-Deklarationen** (kein Body, keine Seiteneffekte im File selbst). Sichtbarkeit jeweils `public`. Seiteneffekte und Aufrufketten beziehen sich auf den durch die Signatur intendierten Vertrag.

### `add_campaign_to_mform(MoodleQuickForm &$mform, array &$ajaxformdata): void` — public
- **Zweck:** Fuegt die kampagnenspezifischen Formularelemente in das uebergebene mform ein.
- **Parameter:** `$mform` (per Referenz, wird mutiert), `$ajaxformdata` (per Referenz).
- **Rueckgabe:** void.
- **Seiteneffekte (Vertrag):** mutiert das mform-Objekt; keine DB.
- **Aufrufkette:** vermutlich vom Kampagnen-Admin-Form (Settings-UI) gerufen.
- **Bewertung:** A — klare Form-Builder-Verantwortung.

### `get_name_of_campaign_type(bool $localized = true): string` — public
- **Zweck:** Liefert den menschenlesbaren (optional lokalisierten) Typ-Namen der Kampagne.
- **Parameter:** `$localized` (Default true). **Rueckgabe:** string.
- **Seiteneffekte:** keine (ggf. `get_string` in Impl).
- **Bewertung:** A.

### `save_campaign(stdClass &$data)` — public
- **Zweck:** Persistiert die Kampagne aus den Formulardaten.
- **Parameter:** `$data` (per Referenz). **Rueckgabe:** nicht typisiert (implizit).
- **Seiteneffekte (Vertrag):** DB-Write in `booking_campaigns` (in Implementierung erwartet).
- **Aufrufkette:** aus Save-Handler des Kampagnen-Forms.
- **Bewertung:** A.

### `set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt die Default-Werte beim Laden des Formulars aus einem DB-Record.
- **Parameter:** `$data` (Referenz, Default-Container), `$record` (DB-Record aus `booking_campaigns`).
- **Seiteneffekte:** mutiert `$data`.
- **Bewertung:** A.

### `set_campaigndata(stdClass $record)` — public
- **Zweck:** Laedt JSON-Daten aus DB in das Kampagnen-Objekt.
- **Parameter:** `$record`. **Seiteneffekte:** setzt interne Objekt-Properties (Impl).
- **Bewertung:** A.

### `campaign_is_active(int $optionid, booking_option_settings $settings): bool` — public
- **Zweck:** Prueft, ob die Kampagne fuer eine bestimmte Buchungsoption aktuell aktiv ist.
- **Parameter:** `$optionid`, `$settings`. **Rueckgabe:** bool.
- **Seiteneffekte:** lesend (Zeit-/Bereichsabgleich in Impl).
- **Bewertung:** A.

### `get_campaign_price(float $price, int $userid = 0): float` — public
- **Zweck:** Wendet den Kampagnen-Preisfaktor auf den Originalpreis an.
- **Parameter:** `$price`, `$userid` (Default 0, fuer userspezifische Kampagnen). **Rueckgabe:** float (neuer Preis).
- **Bewertung:** A.

### `apply_logic(booking_option_settings &$settings, stdClass &$dbrecord)` — public
- **Zweck:** Wendet die spezifische Kampagnenlogik auf Settings/DB-Record an.
- **Parameter:** `$settings` (Referenz), `$dbrecord` (Referenz mit neuen Daten).
- **Seiteneffekte:** mutiert beide Referenzparameter.
- **Bewertung:** A.

### `is_blocking(booking_option_settings $settings, int $userid): array` — public
- **Zweck:** Prueft, ob die Kampagne aktuell (ggf. userspezifisch) blockiert.
- **Parameter:** `$settings`, `$userid`. **Rueckgabe:** array (Block-Info).
- **Bewertung:** A.

### Triviale Akzessoren
- `get_name_of_campaign(): string` — Name der Kampagne.
- `get_id_of_campaign(): int` — ID der Kampagne.
- `user_specific_price(): bool` — ob der Preis userspezifisch ist.
Reine Getter-Vertraege, keine Seiteneffekte. Bewertung: A.

## Hinweise
- Datei ist ausschliesslich ein Interface (Schluesselwort `interface`), trotz Datei-/Klassenname `booking_campaign`. Enthaelt keinerlei Implementierung, daher keine echten Smells.
- Inkonsistenz im Vertrag: `save_campaign`, `set_defaults`, `set_campaigndata`, `apply_logic` haben **keinen** Rueckgabetyp-Hint, waehrend die uebrigen Methoden voll typisiert sind. Kein Bug, aber stilistische Uneinheitlichkeit (P3).
