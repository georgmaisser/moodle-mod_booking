# customform_prefill — Methoden-Doku
**Datei:** `classes/local/customform_prefill.php` · **LOC:** 315 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`customform_prefill` mappt `prefill_*`-URL-Parameter der Optionview auf Cache-Werte des `customformstore`, sodass die Customform-Availability-Felder beim Buchen vorbefuellt erscheinen. Rein statisch, kein Instanzzustand. Aktivierung global ueber das Plugin-Setting `customformprefillenabled`. Persistenz: schreibt in den per-User/Option `customformstore`-Cache; liest Formdefinitionen ueber `customform::return_formelements()`. Kollaborateure: `booking_option_settings`, `customform` (Availability-Condition), `customformstore`, `optional_param`/`clean_param`/`core_text`.

## Methoden

### `public static function is_enabled(): bool` — public static
- **Zweck:** Globaler Feature-Schalter (`get_config('booking','customformprefillenabled')`). **Seiteneffekte:** ein `get_config`-Read. **Rueckgabe:** bool. **Bewertung:** A.

### `public static function prefill_from_request(booking_option_settings $settings, int $userid): bool` — public static
- **Zweck:** Orchestriert den gesamten Prefill: Guard (enabled + gueltige id/userid), Params aus Request einsammeln, in Cache-Payload uebersetzen, mit evtl. bestehendem Cache-Inhalt mergen und zurueckschreiben. **Seiteneffekte:** instanziiert `customformstore($userid,$settings->id)`, liest `get_customform_data()` und schreibt `set_customform_data($data)` (Cache-Mutation); klont bestehende Daten vor dem Merge. **Rueckgabe:** bool — true nur, wenn tatsaechlich Daten geschrieben wurden. **Bewertung:** B — saubere Guard-Kaskade und nicht-destruktiver Merge (bestehende Felder bleiben, nur Prefill-Keys werden ueberschrieben). Mehrfache `return_formelements()`-Aufrufe in den Submethoden (s.u.) leicht redundant.

### `public static function build_prefill_data(booking_option_settings $settings, array $prefillparams): stdClass` — public static
- **Zweck:** Baut die Cache-Payload: pro Nicht-static-Formelement Identifier ableiten, passenden Prefill-Key (per Identifier oder Label) finden, Wert typgerecht sanitisieren und unter dem Identifier ablegen. **Seiteneffekte:** `customform::return_formelements($settings)`. **Rueckgabe:** stdClass (Identifier => bereinigter Wert). **Bewertung:** B — klare Trennung; ueberspringt static-Elemente und nicht-matchende/nicht-valide Werte korrekt.

### `private static function get_prefill_params_from_request(booking_option_settings $settings): array` — private static
- **Zweck:** Liest die `prefill_<identifier>`- bzw. `prefill_<normalisiertes-label>`-Request-Parameter via `optional_param` mit feldtyp-spezifischem PARAM-Typ und normalisiert die Keys. **Seiteneffekte:** `customform::return_formelements()`, mehrere `optional_param`-Reads. **Rueckgabe:** array normalisierter Key => string-Wert. **Bewertung:** B — pro Feld werden Identifier- und Label-Kandidaten geprueft; `optional_param` mit passendem Typ ist die richtige, sichere Lesequelle.

### `private static function get_identifier_for_formelement(stdClass $formelement, string $key): string` — private static
- **Zweck:** Reproduziert den Laufzeit-Identifier, den `customform_form` verwendet (`customform_<formtype>_<key>`, Sonderfall `deleteinfoscheckboxuser`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — koppelt an die Namenskonvention des Formulars; bei deren Aenderung bruechig, aber unvermeidbar.

### `private static function find_prefill_key_for_formelement(array $prefillparams, string $identifier, string $label): ?string` — private static
- **Zweck:** Findet den passenden eingesammelten Param-Key fuer ein Feld (zuerst normalisierter Identifier, dann normalisiertes Label). **Seiteneffekte:** keine. **Rueckgabe:** string|null. **Bewertung:** A.

### `private static function sanitize_prefill_value(stdClass $formelement, string $value)` — private static
- **Zweck:** Bereinigt einen Prefill-Wert gemaess Feldtyp (PARAM_BOOL/TEXT/URL/EMAIL/INT, Select via Whitelist). Leere Strings -> null (Feld wird nicht gesetzt); `enrolusersaction` nur bei `> 0`. **Seiteneffekte:** keine (reine `clean_param`/Helper-Aufrufe). **Rueckgabe:** int|string|null. **Bewertung:** A — konsequente serverseitige Sanitisierung der nutzergesteuerten URL-Werte; Default-Zweig faellt sicher auf PARAM_TEXT zurueck.

### `private static function get_optional_param_type(stdClass $formelement): string` — private static
- **Zweck:** Liefert den Moodle-PARAM-Typ zum Lesen des Request-Werts je Feldtyp (Select: PARAM_RAW_TRIMMED, danach Whitelist-Validierung in `sanitize`). **Seiteneffekte:** keine. **Rueckgabe:** string (PARAM_*-Konstante). **Bewertung:** A — spiegelt die Sanitize-Typen; Select bewusst roh gelesen, weil die Optionswerte erst gegen die Definition gematcht werden.

### `private static function sanitize_select_prefill_value(string $rawoptions, string $value): ?string` — private static
- **Zweck:** Validiert einen Select-Prefill gegen die Feld-Optionsliste (Zeilen `key => label`); akzeptiert Match auf Key oder Label und gibt stets den Key zurueck, sonst null. **Seiteneffekte:** keine. **Rueckgabe:** string|null. **Bewertung:** B — verhindert das Setzen nicht existierender Select-Werte (Whitelist); parst das `key => label`-Format zeilenweise, leicht fragil ggue. Format-Varianten, aber konsistent mit der Customform-Definition.

### `private static function normalize_prefill_key(string $key): string` — private static
- **Zweck:** Vereinheitlicht Keys/Labels fuer den Vergleich (lowercase, Nicht-Alnum -> `_`, Trim der `_`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A — multibyte-sicher via `core_text`.

## Bewertungs-Resümee
Gut strukturierter, defensiv sanitisierender URL-Prefill-Helfer: alle nutzergesteuerten Werte laufen typgerecht durch `clean_param`/`optional_param`, Selects werden gegen eine Whitelist validiert, der bestehende Cache wird nicht-destruktiv gemerged. Kleinere Punkte: mehrfaches `return_formelements()` und die enge Kopplung an die Identifier-/Optionsformat-Konvention des Customforms. Keine Sicherheits- oder Datenverlust-Maengel. Klassen-Score **B / P3**.
