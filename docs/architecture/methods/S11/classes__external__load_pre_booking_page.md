# load_pre_booking_page — Methoden-Doku
**Datei:** `classes/external/load_pre_booking_page.php` · **LOC:** 126 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`load_pre_booking_page` ist eine External-API-Funktion, die einen einzelnen Schritt der Pre-Booking-Modal-Strecke (z.B. Bedingungen, Slotbooking) zum Rendern nachlaedt. Die Klasse delegiert die gesamte Logik an `mod_booking\bo_availability\bo_info::load_pre_booking_page` und reicht deren Ergebnis (`json`/`template`/`buttontype`) durch. Keine Persistenz — statische WS-Klasse (`extends external_api`). Kollaborateure: `bo_info`, `$USER` (global importiert, aber im `execute` nicht direkt benutzt — Auswertung steckt in `bo_info`).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `optionid` (PARAM_INT), `userid` (PARAM_INT, default 0), `pagenumber` (PARAM_INT) und das optionale `skipcondition` (PARAM_ALPHANUMEXT, default `''`) zum Ausblenden einer Bedingung (z.B. `slotbooking`). **Seiteneffekte:** keine. **Bewertung:** A.

### `public static function execute(int $optionid, int $userid, int $pagenumber, string $skipcondition = ''): array` — public static
- **Zweck:** Validiert die Parameter und delegiert an `bo_info::load_pre_booking_page($optionid, $pagenumber, $userid, $skipcondition)`. **Seiteneffekte:** `self::validate_parameters(...)`; Aufruf von `bo_info::load_pre_booking_page(...)` (laedt/rendert den Page-Step). **Rueckgabe:** Das von `bo_info` gelieferte Array (`json`/`template`/`buttontype`). **Bewertung:** B — (1) **Kein `validate_context()`** und kein Capability-Gate auf WS-Ebene; der `userid`-Parameter ist frei waehlbar und wird ohne Eigentuemer-Pruefung an `bo_info` weitergereicht — ob ein fremder `userid` ein Leck darstellt, haengt davon ab, was `bo_info::load_pre_booking_page` zurueckgibt (Pre-Booking-Page kann nutzerspezifische Verfuegbarkeits-/Slot-Infos enthalten). Mindestens P3-wuerdig als IDOR-Risiko. (2) `global $USER` wird importiert, aber in `execute` nicht verwendet — toter Import. (3) Argument-Reihenfolge dreht `pagenumber`/`userid` gegenueber der WS-Signatur, was leicht zu Verwechslungen fuehrt (aber korrekt verdrahtet).

### `public static function execute_returns(): external_function_parameters` — public static
- **Zweck:** Beschreibt die Rueckgabe: `json` (PARAM_RAW, Renderdaten), `template` (PARAM_RAW, Templatename), `buttontype` (PARAM_INT: 0=kein Button, 1=continue, 2=last). **Seiteneffekte:** keine. **Bewertung:** B — verwendet `external_function_parameters` als Rueckgabe-Beschreibung statt des fuer Returns ueblichen `external_single_structure`; funktional gleichwertig, aber stilistisch inkonsistent.

## Bewertungs-Resümee
Duenne Pass-Through-WS-Klasse mit klarer Verantwortungstrennung (gesamte Logik in `bo_info`). Schwachpunkte: fehlendes `validate_context`/Capability-Gate bei frei waehlbarem `userid` (IDOR-Verdacht, je nach `bo_info`-Verhalten), toter `$USER`-Import und `external_function_parameters` statt `external_single_structure` im Returns-Slot. Klassen-Score **B / P3**.
