# bookingextension_interface — Methoden-Doku
**Datei:** `classes/plugininfo/bookingextension_interface.php` · **LOC:** 111 · **Subsystem:** S22 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
`bookingextension_interface` ist der formale Vertrag, den jede mod_booking-Extension (Subplugin-Typ `bookingextension`, z.B. `bookingextension_agent`) erfuellen muss. Es definiert die Erweiterungspunkte, ueber die der Booking-Kern Extension-Funktionalitaet einsammelt, ohne die einzelnen Extensions namentlich zu kennen: zusaetzliche Option-Felder, Admin-Settings-Einhaengung, ein Settings-Singleton, Template-Daten fuer die Optionansicht, zusaetzliche Spalten-Aktionen in der Optionen-Tabelle, erlaubte Event-Keys fuer Booking-Rules sowie eine History-Beschreibung. Reines Interface — keine Persistenz, keine Logik. Es mischt Instanzmethoden (`get_plugin_name`, `contains_option_fields`, `get_option_fields_info_array`, `load_settings`) und statische Methoden (`load_data_for_settings_singleton`, `set_template_data_for_optionview`, `add_options_to_col_actions`, `get_allowedruleeventkeys`, `get_booking_history_description`). Konsumenten: `bookingextension`-Basisklasse (Default-No-ops), Booking-Kernpfade in Optionview-Rendering, `bookingoptions_wbtable`, Rule-Engine und Booking-History.

## Methoden (Vertragssignaturen)

### `public function get_plugin_name(): string` — public (abstract)
- **Zweck:** Liefert den Plugin-/Komponentennamen der Extension. **Seiteneffekte:** keine (Implementierungsvertrag). **Rueckgabe:** Name als String. **Bewertung:** A.

### `public function contains_option_fields(): bool` — public (abstract)
- **Zweck:** Signalisiert, ob die Extension eigene Option-Felder beisteuert (Gate fuer `get_option_fields_info_array`). **Seiteneffekte:** keine. **Rueckgabe:** bool. **Bewertung:** A.

### `public function get_option_fields_info_array(): array` — public (abstract)
- **Zweck:** Liefert Metadaten der von der Extension hinzugefuegten Option-Felder. **Seiteneffekte:** keine. **Rueckgabe:** Info-Array (Struktur extension-spezifisch). **Bewertung:** B — Rueckgabeform im Vertrag nicht praezisiert (loses `array`); Implementierungen muessen Konvention kennen.

### `public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig): void` — public (abstract)
- **Zweck:** Haengt die Settings der Extension in den Admin-Settings-Baum ein (typischerweise per `include` der settings.php oder via `admin_externalpage`). **Seiteneffekte:** Implementierung mutiert den Admin-Tree. **Rueckgabe:** void. **Bewertung:** A — folgt dem Core-`plugininfo`-Settings-Muster. (Querbezug: Memory „bookingextension-Settings ohne PRO-Gate" — die Einhaengung darf nicht hinter einem PRO-Gate liegen.)

### `public static function load_data_for_settings_singleton(int $optionid): object` — public static (abstract)
- **Zweck:** Gibt Extensions Zugriff auf einen Settings-Singleton-Service je Option. **Seiteneffekte:** Implementierungsabhaengig (Singleton-Zugriff). **Rueckgabe:** `object`. **Bewertung:** B — loser `object`-Rueckgabetyp; Vertrag praezisiert die Struktur nicht.

### `public static function set_template_data_for_optionview(object $settings): array` — public static (abstract)
- **Zweck:** Liefert Daten fuer das Optionview-/Beschreibungs-Template (Array assoziativer Arrays mit Keys `key, value, label, description`). **Seiteneffekte:** keine. **Rueckgabe:** `array[]`. **Bewertung:** A — Rueckgabestruktur hier im Docblock explizit dokumentiert.

### `public static function add_options_to_col_actions(object $settings, mixed $context): string` — public static (abstract)
- **Zweck:** Erlaubt einer Extension, zusaetzliche Aktionen in die `col_action`-Spalte der `bookingoptions_wbtable` einzuschleusen. **Seiteneffekte:** keine. **Rueckgabe:** HTML-Fragment-String (leer = nichts beitragen). **Bewertung:** A.

### `public static function get_allowedruleeventkeys(): array` — public static (abstract)
- **Zweck:** Liefert die von der Extension fuer „Booking-Rule reagiert auf Event" erlaubten Event-Keys. **Seiteneffekte:** keine. **Rueckgabe:** Liste von Event-Keys. **Bewertung:** A.

### `public static function get_booking_history_description(\stdClass $values, array $info): string` — public static (abstract)
- **Zweck:** Rendert eine extension-spezifische Beschreibung fuer einen Booking-History-Eintrag; leerer String, wenn der Eintrag nicht zur Extension gehoert oder keine Custom-Beschreibung gewuenscht ist. **Seiteneffekte:** keine. **Rueckgabe:** Beschreibungs-String. **Bewertung:** A — sauberer „nicht zustaendig"-Vertrag via leerem String.

## Bewertungs-Resümee
Klar geschnittenes Erweiterungs-Interface, das die wesentlichen Booking-Kern-Erweiterungspunkte abdeckt und mit dem Core-`plugininfo`-Settings-Muster harmoniert. Kleinere Vertrags-Schwaeche: mehrere Methoden geben lose `array`/`object` zurueck, ohne die Struktur im Vertrag zu fixieren (nur `set_template_data_for_optionview` dokumentiert die Keys). Die Mischung aus Instanz- und statischen Methoden ist konsistent zur Default-Basisklasse `bookingextension`. Reiner Vertrag, kein Laufzeitrisiko. Klassen-Score **A / -**.
