# booking_user_selector_base — Methoden-Doku
**Datei:** `classes/booking_user_selector_base.php` · **LOC:** 122 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_user_selector_base` ist die abstrakte Basis fuer die Moodle-User-Selector-Widgets von Booking (manuelles Ein-/Ausbuchen von Teilnehmern). Sie erweitert Cores `user_selector_base` und reicht booking-spezifischen Kontext (`bookingid`, `optionid`, `course`, `cm`, `potentialusers`, `bookedvisibleusers`) durch dessen `$options`-Mechanismus, damit AJAX-Nachladungen denselben Kontext rekonstruieren koennen. Konkrete Subklassen: `booking_potential_user_selector`, `booking_existing_user_selector` (sowie die subscriber-Selektoren). Kollaborateure: Core `user_selector_base`, `cm_info`. Wird in den Teilnehmer-Verwaltungs-Seiten/Forms verwendet.

## Methoden

### `public function __construct($name, $options)` — public
- **Zweck:** Setzt `maxusersperpage = 50`, ruft den Parent-Konstruktor und uebernimmt die booking-spezifischen Felder aus dem `$options`-Array (jeweils per `isset`-Guard: bookingid, potentialusers, optionid, course, cm). **Seiteneffekte:** keine DB. **Bewertung:** B — fuenf wiederholte `isset`-Zuweisungen (boilerplate); `$name`/`$options` untypisiert (folgt aber der Core-Signatur).

### `protected function get_options(): array` — protected
- **Zweck:** Serialisiert den eigenen Kontext zurueck in das `$options`-Array, das Core fuer AJAX-Roundtrips nutzt; setzt zusaetzlich `file => 'mod/booking/locallib.php'` (Autoload-Hint fuer den AJAX-Callback). **Bewertung:** B — muss spiegelbildlich zum Konstruktor gepflegt werden (zwei Stellen koennen auseinanderlaufen); der hartkodierte `locallib.php`-Pfad ist ein Legacy-Kopplungspunkt.

### `public function set_potential_users(array $users)` — public
- **Zweck:** Setzt die Liste der waehlbaren User nachtraeglich (`potentialusers`). **Bewertung:** A — schlanker Setter. (Docblock „Sets the existing subscribers" ist irrefuehrend — es geht um potential, nicht existing.)

### Triviale Properties
`bookingid`, `optionid` (protected), `potentialusers`, `bookedvisibleusers`, `course`, `cm` (public) als Kontext-Halter (Z.36–67).

## Bewertungs-Resümee
Solider, zweckmaessiger Adapter auf Cores User-Selector. Die Schwaechen sind kosmetisch: spiegelbildliche Boilerplate zwischen Konstruktor und `get_options`, untypisierte Signaturen (Core-bedingt), ein irrefuehrender Docblock und der hartkodierte `locallib.php`-Pfad. Klassen-Score **B / P3**.
