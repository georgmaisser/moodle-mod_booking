# optionstoconfirmreduced — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/optionstoconfirmreduced.php` · **LOC:** 113 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`optionstoconfirmreduced` ist die reduzierte Variante des Bestaetigungs-Scopes `optionstoconfirm`, von dem sie erbt (und damit die SQL-Konstruktion `return_sql_for_booked_users` sowie Capability-/Download-Logik). Reduziert bedeutet: ein schlankeres Spaltenset und eine vereinfachte Tabellen-Konstruktion ohne Sortier-/Download-Button-Logik. Sie unterstuetzt zusaetzlich Custom-Field-Spalten (booking_handler). Persistenz: keine eigene; Datenbasis ueber die geerbte SQL. Kollaborateure: `optionstoconfirm` (Basis), `manageusers_table`, `booking_handler` (Custom-Field-Headings).

## Methoden

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Baut eine minimalistische Tabelle: SQL via geerbtem `return_sql_for_booked_users` (mit `$customfields`), bei nicht-leeren `$customfields` werden ueber `booking_handler::get_customfields(array_values($customfields))` Header und Spalten angereichert; Cache, Spalten/Header, optionale Pagination, `set_sql`. **Seiteneffekte:** `booking_handler::get_customfields(...)`, Tabellen-Konfiguration. **Rueckgabe:** `wunderbyte_table`. **Bewertung:** B — bewusst reduziert: kein `show_download_button`, kein Sortable-Flag-Handling, keine Sortierspalten; das ist fuer die „reduced"-Sicht angemessen, weicht aber vom Verhalten der Basisklasse ab.

### `public function return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Liefert das reduzierte Spaltenset: Fullname, Buchungsoptionsname, Bestaetigen/Loeschen-Action, Kursstartzeit. **Seiteneffekte:** `get_string`. **Rueckgabe:** Spalten-Map. **Bewertung:** A — kompakt und statusunabhaengig.

### Triviale Properties
`public $scope = 'optionstoconfirmreduced';` (Z.45).

## Bewertungs-Resümee
Schmale Override-Klasse, die nur das Spaltenset und eine vereinfachte Tabellen-Konstruktion liefert und den Rest von `optionstoconfirm` erbt. Korrekt und uebersichtlich; einzige Anmerkung ist das absichtliche Weglassen von Download-/Sortier-Logik. Klassen-Score **B / P3**.
