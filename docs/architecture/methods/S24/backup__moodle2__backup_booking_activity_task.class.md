# backup_booking_activity_task — Methoden-Doku
**Datei:** `backup/moodle2/backup_booking_activity_task.class.php` · **LOC:** 73 · **Subsystem:** S24 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S24_backup_restore.md)

## Klassenueberblick
`backup_booking_activity_task extends backup_activity_task` — der Backup-Task der Booking-Aktivitaet im Moodle-Backup-Framework (moodle2). Er definiert keine instanz-spezifischen Settings, haengt den einzigen Strukturschritt (`backup_booking_activity_structure_step`) ein und stellt die statische URL-Encoding-Logik bereit, mit der das Backup-Framework Volltext-Links in transportable Platzhalter umschreibt. Laedt beim Include die Step- und Settings-Lib (Z.28–29). Persistenz: keine eigene — schreibt in den Backup-Strukturbaum bzw. transformiert Strings. Kollaborateure: Backup-Framework (`backup_activity_task`, `add_step`), `backup_booking_activity_structure_step`, `$CFG->wwwroot`.

## Methoden

### `protected function define_my_settings()` — protected
- **Zweck:** Hook fuer aktivitaetsspezifische Backup-Settings. **Seiteneffekte:** keine (Booking hat keine eigenen Backup-Settings). **Bewertung:** A — bewusst leer, dokumentiert.

### `protected function define_my_steps()` — protected
- **Zweck:** Registriert den einzigen Strukturschritt `backup_booking_activity_structure_step('booking_structure', 'booking.xml')`. **Seiteneffekte:** `$this->add_step(...)`. **Bewertung:** A — kanonisch.

### `public static function encode_content_links($content)` — public static
- **Zweck:** Schreibt im Backup absolute Plugin-URLs in transportable Platzhalter um: `index.php?id=<n>` → `$@BOOKINGINDEX*<n>@$` und `view.php?id=<n>` → `$@BOOKINGVIEWBYID*<n>@$`. **Seiteneffekte:** keine (reine String-Transformation); liest `$CFG->wwwroot`, das via `preg_quote(..., '/')` fuer den Regex-Delimiter `/` maskiert wird. **Rueckgabe:** der transformierte Content (`string`, ggf. `null` falls `preg_replace` fehlschlaegt). **Bewertung:** A — Spiegelbild zu `restore_booking_activity_task::define_decode_rules()` (gleiche Platzhalter-Namen BOOKINGINDEX/BOOKINGVIEWBYID); korrekt gequotetes Pattern.

## Bewertungs-Resümee
Schlanker, idiomatischer Backup-Task. Settings/Steps kanonisch, das Link-Encoding ist sauber gequotet und passt exakt zu den Restore-Decode-Regeln. Keine Auffaelligkeiten. Klassen-Score **A / P3**.
