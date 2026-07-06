# usercreated — Methoden-Doku
**Datei:** `classes/option/fields/usercreated.php` · **LOC:** 110 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`usercreated` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit NORMAL-Speicherung unter dem GENERAL-Header, kategorisiert als NECESSARY. Verantwortung: pflegt die Ersteller-User-ID der Buchungsoption (`booking_option.usercreated`) — gesetzt beim Anlegen, danach unveraenderlich erhalten. Hat keine Form-Definition (rein persistenz-getriebenes Audit-Feld). Kollaborateure: `$USER`, `singleton_service` (Option-Settings), `field_base::prepare_save_field`. Reine statische Klasse; die `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Setzt bei einer NEUEN Option den Ersteller auf den aktuellen `$USER`; bei einer bestehenden Option wird der urspruengliche Ersteller aus den Settings erhalten.
- **Seiteneffekte:** `global $USER`; `parent::prepare_save_field(..., 0)`. Liest `optionid` aus `$formdata->optionid ?? $formdata->id ?? 0`. Bei leerer id: `$newoption->usercreated = $USER->id`. Sonst: `singleton_service::get_instance_of_booking_option_settings($optionid)` und `$newoption->usercreated = $settings->usercreated ?: 0`.
- **Rueckgabe:** immer leeres `array` (kein Change-Tracking — der Ersteller soll sich nicht als „Aenderung" melden).
- **Bewertung:** B — korrekte „set-once/preserve"-Semantik mit Settings-Cache-Lookup (kein N+1). Minor: Fallback auf `0`, wenn ein bestehender Settings-Record keinen `usercreated` hat (Datenluecke wird stillschweigend mit 0 ueberschrieben statt belassen) — in der Praxis selten, da NECESSARY-Feld.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–77).

## Bewertungs-Resümee
Minimaler Audit-Feld-Handler ohne UI: setzt den Ersteller einmalig und bewahrt ihn danach. Korrekte Logik, Lookup ueber den Request-Cache. Einzige Schwaeche: 0-Fallback ueberschreibt eine fehlende Bestands-ID. Klassen-Score **B / P3**.
