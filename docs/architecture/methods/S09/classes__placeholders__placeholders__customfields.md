# customfields — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/customfields.php` · **LOC:** 187 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_*.md)

## Klassenueberblick
`customfields` ist ein Platzhalter-Handler (`extends placeholder_base`) der Messaging-/Placeholder-Engine. Er fungiert als Catch-all-Fallback: zuerst versucht er, den Platzhalter als Booking-Customfield der Option (`$settings->customfieldsfortemplates`) aufzuloesen, andernfalls als User-Profilfeld des (related) Nutzers. Persistenz: keine eigene; liest Option-Settings ueber `singleton_service::get_instance_of_booking_option_settings()` und Userdaten ueber `singleton_service::get_instance_of_user()` + `profile_load_data()`. Request-Memoisierung ueber das statische Array `placeholders_info::$placeholders`. Kollaborateure: `singleton_service`, `placeholders_info`, `$DB` (indirekt), `profile/lib.php`. Besonderheit: mutiert `$text` und `$fieldexists` per Referenz.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, string &$text = '', array &$params = [], string $placeholder = '', bool &$fieldexists = true, string $rulejson = ''): string` — public static
- **Zweck:** Loest `$placeholder` auf und ersetzt `{$placeholder}` in `$text`. Zwei Pfade: (1) ist der Platzhalter ein Booking-Customfield (`$settings->customfieldsfortemplates[$placeholder]['value']` gesetzt), wird der Wert genommen (Arrays via `implode(', ', ...)`), (2) sonst wird er als User-Profilfeld behandelt; bei Suffix `-related` wird der related user aus dem restaurierten Event (`$rulejson->datafromevent`) als Quelle gesetzt.
- **Seiteneffekte:** `str_replace` in `$text` (Referenz); setzt `$fieldexists = false` (Referenz), wenn weder Customfield noch Profilfeld existiert; `placeholders_info::$placeholders[$cachekey] = $value` (Request-Memo); `profile_load_data($user)` mutiert das gecachte Singleton-User-Objekt und kopiert `profile_field_*` nach `$user->profile[...]`; restauriert ggf. ein Event via `$class::restore(...)`.
- **Rueckgabe:** Der aufgeloeste Wert als String (leer, wenn nicht gefunden).
- **Bewertung:** C — zwei getrennte Cache-Keys (`$classname-$optionid-$placeholder` fuer Customfields, `$classname-$userid-$placeholder` fuer Profilfelder) sind korrekt scoped. Schwachstellen: der `else`-Zweig im `-related`-Block setzt `$value` auf eine Fehler-String-Meldung, faellt danach aber durch in den Profil-Lookup, der `$value` wieder ueberschreibt — die Fehlermeldung ist toter Schreibzugriff. Ungefilterte Event-Restaurierung (`$class::restore`) aus rulejson ist ein Vertrauen-in-Input-Pfad (vom Rule-Owner kontrolliert, daher P2 nicht hoeher). Methode mischt drei Verantwortlichkeiten (Customfield-, Profil- und Related-User-Aufloesung).

### `public static function return_placeholder_text()` — public static
- **Zweck:** Liefert den lokalisierten Hilfetext fuer die Platzhalter-Uebersicht.
- **Seiteneffekte:** `get_string('customfieldsplaceholdertext', 'mod_booking')`.
- **Rueckgabe:** String.
- **Bewertung:** A.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Handler ueberhaupt aufgerufen wird.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true` (Catch-all).
- **Bewertung:** A.

## Bewertungs-Resümee
Funktional zentraler Catch-all-Handler mit korrekter Request-Memoisierung und sauberer Trennung von Customfield- vs. Profilfeld-Cache-Keys. Abzuege fuer die mehrfach belegte Verantwortung in `return_value`, den toten Fehler-Schreibzugriff im `-related`-Pfad und die per-Referenz-Mutation von `$text`/`$fieldexists` (impliziter Kontrakt). Kein Daten-Verlust, keine eindeutige Sicherheitsluecke (Event-Restore vom Rule-Owner kontrolliert). Klassen-Score **C / P2**.
