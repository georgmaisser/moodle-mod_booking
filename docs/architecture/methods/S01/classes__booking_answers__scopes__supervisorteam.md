# supervisorteam — Methoden-Doku
**Datei:** `classes/booking_answers/scopes/supervisorteam.php` · **LOC:** 159 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_core_domain.md)

## Klassenueberblick
`supervisorteam` ist der Booking-Answers-Scope fuer die Supervisor-Team-Sicht: Antworten, die ein Supervisor fuer sein Team bestaetigen kann. Sie erbt von `optionstoconfirm` und unterscheidet sich vor allem durch die WHERE-Einschraenkung, die aus dem Subplugin `bookingextension_confirmation_supervisor` stammt (dynamisch eingebunden) — mit gesetztem `supervisorteam = true`, wodurch Bestaetigungs-Reihenfolge-Regeln (Supervisor, HR …) uebersprungen werden. Persistenz: keine eigene; Datenbasis ueber die geerbte SQL plus subplugin-gelieferte WHERE-Klausel. Kollaborateure: `optionstoconfirm` (Basis), `manageusers_table`, dynamisch `\bookingextension_confirmation_supervisor\local\confirmbooking`.

## Methoden

### `public function return_users_table(string $scope, int $scopeid, int $statusparam, string $tablenameprefix, array $columns, array $headers = [], bool $sortable = false, bool $paginate = false, array $customfields = [])` — public
- **Zweck:** Baut die Tabelle: SQL via geerbtem `return_sql_for_booked_users` (mit `$customfields`), Cache, Spalten/Header, optionale Sortier-/Paginier-Flags, Download-Button, Fulltext-Suche (Name/Email) und Default-Sortierung `lastname ASC`. **Seiteneffekte:** `set_sql`, `show_download_button` (geerbt). **Rueckgabe:** `wunderbyte_table`. **Bewertung:** B — Standard-Tabellenaufbau ohne Besonderheiten.

### `public function get_whereneedtoconfirm_sql(array &$params): string` — public
- **Zweck:** Liefert die subplugin-abhaengige WHERE-Einschraenkung fuer Antworten, die der Supervisor sehen darf. Prueft `class_exists` der Confirm-Klasse und wirft `Exception`, falls die Extension fehlt; setzt `$class->supervisorteam = true` (ignoriert Confirmation-Order-Restriktionen) und delegiert an `return_where_sql($params)`. **Seiteneffekte:** instanziiert die Subplugin-Klasse; mutiert `$params` by-reference; wirft Exception bei fehlender Extension. **Rueckgabe:** SQL-WHERE-Fragment in Klammern. **Bewertung:** B — saubere harte Abhaengigkeit mit klarer Fehlermeldung; die dynamische Klassenbindung ist der bewusste Erweiterungspunkt. Das `supervisorteam`-Flag ist genau der Unterschied zur Basis `optionstoconfirm`.

### `public function return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Liefert das Spaltenset: Buchungsoptionsname, Vorname/Nachname/Email, Buchungsstatus. **Seiteneffekte:** `get_string`. **Rueckgabe:** Spalten-Map. **Bewertung:** A — statusunabhaengig, kompakt.

### `public function get_lables_of_tables(array $defaultlables): array` — public
- **Zweck:** Ueberschreibt das Label der Warteliste-Tabelle mit `tableheaderwaitforconfirmation` (warten auf Bestaetigung). **Seiteneffekte:** `get_string`. **Rueckgabe:** modifiziertes Label-Array. **Bewertung:** A — minimaler, gezielter Override (Tippfehler „lables" stammt aus der Basis-Signatur).

### Triviale Properties
`public $scope = 'supervisorteam';` (Z.36).

## Bewertungs-Resümee
Klar fokussierter Erweiterungs-Scope: erbt das Gros von `optionstoconfirm` und setzt nur die supervisor-spezifische WHERE-Klausel, ein Spaltenset und ein Tabellenlabel. Die harte Abhaengigkeit zum `confirmation_supervisor`-Subplugin ist explizit per `class_exists`-Guard abgesichert. Keine funktionalen Defekte. Klassen-Score **B / P3**.
