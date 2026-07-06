# userprofilefield_2_custom — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/userprofilefield_2_custom.php` · **LOC:** 1096 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Konfigurierbare Verfuegbarkeits-Bedingung (`bo_condition`, `freezable_condition`), die anhand benutzerdefinierter Profilfelder (`user_info_field`) entscheidet, ob eine Buchungsoption fuer einen User verfuegbar ist. Liest ihre Konfiguration aus dem JSON der `availability`-Spalte von `booking_options`. Unterstuetzt zwei verknuepfte Felder (`&&`/`||`), zwoelf Vergleichsoperatoren, einen SQL-Filter zum kompletten Ausblenden (statt nur Blockieren), Override-Conditions sowie das Umgehen via `circumventcond` (`override_user_field`). Kollaborateure: `singleton_service`, `booking`, `bo_info`, `operator_builder` (SQL-Bau), `override_user_field`, `wb_payment` (PRO-Gate), `MoodleQuickForm`. Singleton mit ueberraschend mutierbarer `$customsettings`-Property, die von aussen befuellt wird.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Liefert die Singleton-Instanz, erzeugt sie bei Bedarf.
- **Parameter/Rueckgabe:** optionale `$id` (Condition-Json-Id) → `self`.
- **Seiteneffekte:** Setzt statisches `self::$instance`.
- **Aufrufkette:** Von `bo_info`/Condition-Registry und intern (`get_condition_object_for_json` ruft `$classname::instance()`).
- **Bewertung:** B. Klassisches Singleton; subtiles Risiko: `$id` wird nur beim ersten Aufruf beruecksichtigt (cached Instanz ignoriert spaetere `$id`).

### `reset_instance(): void` — public static
- **Zweck/Seiteneffekte:** Setzt `self::$instance = null` (Test-Hook). **Bewertung:** A.

### `__construct(?int $id = null)` — private
- **Zweck:** Setzt `$this->id` falls uebergeben. **Bewertung:** A (trivial).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit anhand 1–2 Profilfelder und optionaler Verknuepfung.
- **Parameter/Rueckgabe:** Settings, User-Id, Invertierungs-Flag → bool verfuegbar.
- **Seiteneffekte:** Liest User inkl. Profilfelder via `singleton_service::get_instance_of_user($userid, true)`. Indirekt DB-Read ueber `compare_fields` (`booking::get_value_of_json_by_key`, `override_user_field`). Keine Writes.
- **Aufrufkette:** Von `get_description`; Teil des bo_availability-Frameworks (`bo_info::is_available`).
- **Bewertung:** B. ~55 LOC, mittlere Schachtelung (isloggedin/customsettings/connect-switch). Default-Verfuegbar wenn kein `profilefield` gesetzt — vertretbar. `$not`-Invertierung sauber.

### `compare_operation(string $operator, string $profilefieldvalue, string $formvalue): bool` — private
- **Zweck:** Wendet einen der 12+ Vergleichsoperatoren (`=`,`<`,`>`,`~`,`!=`,`!~`,`[]`,`[!]`,`[~]`,`[!~]`,`()`,`(!)`) auf Feldwert vs. Formwert an.
- **Parameter/Rueckgabe:** Operator, Feldwert, erwarteter Wert → bool.
- **Seiteneffekte:** Keine (reine Logik).
- **Aufrufkette:** Von `compare_fields` (zweimal: Original und Override-Pref).
- **Bewertung:** C. ~87 LOC reine Switch-Kaskade (Smell Laenge >80: userprofilefield_2_custom.php:231-318). `default`-Zweig liefert `true` (unbekannter Operator → verfuegbar) — potenziell fail-open. Numerische Vergleiche `<`/`>` auf Strings (lexikografisch in PHP bei nicht-numerisch) — diese Logik ist im PHP-Pfad anders als der SQL-Pfad (vgl. Memory `condition_sqlfilter`). Gut testbar, aber lang/dupliziert die SQL-Operator-Semantik.

### `compare_fields(object $user, string $profilefield, string $operator, string $formvalue, int $bookingid, int $userid): bool` — private
- **Zweck:** Liest Profilfeldwert aus User-Objekt und vergleicht; bei Nicht-Treffer optional Override via `circumventcond`/`override_user_field`.
- **Parameter/Rueckgabe:** s. Signatur → bool.
- **Seiteneffekte:** DB-Read `booking::get_value_of_json_by_key($bookingid, 'circumventcond')`; `singleton_service::get_instance_of_booking_by_bookingid`; `override_user_field->get_value_for_user` (DB-Read User-Pref).
- **Aufrufkette:** Von `is_available` (1–2x).
- **Bewertung:** C. Mischt Feldzugriff + Override-Geschaeftslogik (gemischte Verantwortung, userprofilefield_2_custom.php:333-365). Im Override-Zweig wird `$this->customsettings->operator/value` statt der uebergebenen `$operator/$formvalue` verwendet — Inkonsistenz bei zweitem Feld (Bug-Verdacht: bei `profilefield2`-Override wird trotzdem die erste Bedingung verglichen).

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Baut das SQL-WHERE-Fragment, das Optionen mit aktiviertem `sqlfilter` komplett ausblendet (statt nur zu blockieren), dialektspezifisch fuer Postgres und MySQL/MariaDB.
- **Parameter/Rueckgabe:** User-Id, Referenz `$params` → `['', '', '', $params, $where]`.
- **Seiteneffekte:** `global $USER, $DB`; `$DB->get_dbfamily()`; laedt User via `singleton_service`. Delegiert Operatorenbau an `operator_builder::build_profile_field_check` (mutiert `$params`). Keine Writes.
- **Aufrufkette:** Vom bo_availability-SQL-Filter (Listen-/Such-Queries).
- **Bewertung:** D. ~231 LOC (userprofilefield_2_custom.php:374-605), groesster Smell: massiver inline-Heredoc-SQL-Bau, vierfach (logged-in/-out × pg/mysql) mit dupliziertem JSON-Extraktions-/CASE-Geruest. String-Interpolation von `$conditionid` direkt ins SQL (hier int aus Klassenkonstante, also nicht user-tainted, aber stilistisch riskant). Hohe Kopplung an DB-Dialekt-Details. Schwer testbar; Memory bestaetigt mehrere historische Bugfixes (numerische `<`/`>`, NULL-avail COALESCE). Kandidat fuer Auslagerung in dedizierte SQL-Builder-Klasse.

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Sperre kurz vor Buchung; `false` nur fuer User mit `mod/booking:overrideboconditions`.
- **Seiteneffekte:** `context_system::instance()`, `has_capability`.
- **Aufrufkette:** bo_availability-Buchungs-Flow (nur wenn `is_available` bereits false).
- **Bewertung:** B. Kurz/klar; Capability auf System-Kontext statt Modul-Kontext (bewusst grob, aber Doku-wuerdig).

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[isavailable, beschreibungsstring, prepage, button]`.
- **Seiteneffekte:** Ruft `is_available` (indirekte DB-Reads) und `get_description_string`.
- **Aufrufkette:** bo_info-Description-Rendering.
- **Bewertung:** B. Schlank, delegiert sauber.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der von dieser Condition zum Option-Form beigesteuerten Elementnamen (erstes = Warn-Anker).
- **Seiteneffekte:** keine. **Bewertung:** A (deklarativ).

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Fuegt alle Form-Elemente (Feld-Selects, Operatoren, Werte, zweites Feld, SQL-Filter-Checkbox, Override-Block) zum Booking-Option-Form hinzu; PRO-gated.
- **Parameter/Rueckgabe:** Form-Referenz, Option-Id → void.
- **Seiteneffekte:** `global $DB`; DB-Reads `user_info_field`, `booking_options.availability` (via `singleton_service`); `wb_payment::pro_version_is_activated`; `bo_info::get_conditions`. Mutiert `$mform` (addElement/hideIf/setType).
- **Aufrufkette:** Vom Option-Form-Builder (bo_info).
- **Bewertung:** D. ~256 LOC (userprofilefield_2_custom.php:694-949), laengste Methode. Sehr repetitive add/hideIf-Bloecke, eingebettete Operator- und Override-Listenaufbereitung, JSON-Decode-Schleife fuer bereits gespeicherte Override-Conditions. Stark verschachtelt (PRO-if → fields-if → foreach). Refactoring: hideIf-Helper / Element-Definition datengetrieben aus `get_condition_form_elements`.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Prepage-Hook; hier ungenutzt, liefert `[]`. **Bewertung:** A (no-op nach Interface).

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut aus den Formdaten das Condition-Objekt fuer die JSON-Persistierung.
- **Parameter/Rueckgabe:** `$fromform` → `stdClass` (ggf. leer, wenn Restrict/Field/Operator fehlen).
- **Seiteneffekte:** keine (reine Transformation).
- **Aufrufkette:** Beim Speichern der Option (availability-JSON-Bau).
- **Bewertung:** B. ~33 LOC, geradlinige Feldmappings. Docblock sagt `stdClass|null`, Rueckgabe ist aber immer `stdClass` (ggf. leer) — Doc/Code-Mismatch.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Form-Defaultwerte aus dem gespeicherten JSON-Condition-Objekt.
- **Seiteneffekte:** Mutiert `$defaultvalues`.
- **Aufrufkette:** Beim Laden des Option-Forms.
- **Bewertung:** B. Spiegel zu `get_condition_object_for_json`; reine Zuweisungen.

### `render_button(booking_option_settings $settings, $userid = 0, $full = false, $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Warn-Button (Alert) ueber `bo_info::render_button`.
- **Seiteneffekte:** delegiert; `get_description_string`.
- **Bewertung:** B. Schlank.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings): string` — public
- **Zweck:** Liefert lokalisierte Beschreibung; billboard-Override falls vorhanden; rekonstruiert bei Bedarf `customsettings` aus `availability`-JSON.
- **Seiteneffekte:** `bo_info::apply_billboard`; `json_decode($settings->availability)`; mutiert `$this->customsettings` als Seiteneffekt (lazy-fill).
- **Aufrufkette:** Von `get_description`, `render_button`.
- **Bewertung:** C. Gemischte Verantwortung: Lokalisierung + Lazy-Mutation des Singleton-Zustands aus JSON (userprofilefield_2_custom.php:1077-1085). `strpos(...) > 0` als Klassen-Match ist fragil (kein `!== false`; bei Position 0 falsch, hier zufaellig ok da Namespace-Praefix). Mutation eines Singletons in einer Description-Methode ist Doku-wuerdiger Nebeneffekt.

### Triviale Akzessoren
`get_id():int`, `is_json_compatible():bool`(→true), `is_shown_in_mform():bool`(→true), `get_name():string`(get_string), `is_skippable():bool`(→true) — reine Konstant-/Property-Rueckgaben, Score A.
