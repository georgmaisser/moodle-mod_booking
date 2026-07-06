# optiontemplate — Methoden-Doku
**Datei:** `classes/external/optiontemplate.php` · **LOC:** 91 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`optiontemplate` ist eine Moodle-External-API-Funktion, die den **kompletten** `booking_options`-Datensatz zu einer id liest und ihn `json_encode`d als Template zurueckgibt (PARAM_RAW). Keine Instanz-Persistenz — statische WS-Klasse (`extends external_api`). Kollaborateur: nur `$DB`. Drei Standard-Slots. Strukturell ein Klon von `instancetemplate`, aber **ohne** dessen Capability-Gate.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `id` (`PARAM_INT`) = Options-id. **Seiteneffekte:** keine. **Bewertung:** A.

### `public static function execute(int $id): array` — public static
- **Zweck:** Validiert Parameter und liest den vollstaendigen Optionsdatensatz, um ihn als JSON-Template auszugeben. **Seiteneffekte:** `self::validate_parameters(...)`; `$DB->get_record('booking_options', ['id' => $id], '*', IGNORE_MISSING)`; `json_encode($template)`. **Rueckgabe:** Array `id` / `name` (`$template->text`) / `template` (`json_encode($template)`). **Bewertung:** C/P2 — drei Schwaechen: (1) **Kein Capability- und kein `validate_context`-Check.** Jede authentifizierte Session mit Zugriff auf die WS-Funktion kann durch Hochzaehlen der `id` jeden beliebigen `booking_options`-Record vollstaendig auslesen (alle Spalten, inkl. evtl. interner/JSON-Felder) — IDOR/Informationsleck. (2) Wie bei `instancetemplate` ist der `IGNORE_MISSING`-Record nicht gegen `false` abgesichert: bei ungueltiger id wirft `$template->text` einen PHP-Fehler (Z.70–75). (3) `json_encode($template)` gibt den **Roh-DB-Record** ungefiltert heraus.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt `id`/`name`/`template` (PARAM_RAW). **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Funktional als Template-Export gedacht, aber sicherheitstechnisch die schwaechste der External-Template-Klassen: Sie exportiert einen kompletten DB-Record **ohne jede Capability- oder Kontext-Pruefung** und ist nicht gegen fehlende Records abgesichert. Im Gegensatz zum Schwester-Service `instancetemplate` fehlt das `has_capability_anywhere`-Gate vollstaendig. Klassen-Score **C / P2**.
