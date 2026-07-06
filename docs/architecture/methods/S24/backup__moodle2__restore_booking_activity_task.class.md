# restore_booking_activity_task — Methoden-Doku
**Datei:** `backup/moodle2/restore_booking_activity_task.class.php` · **LOC:** 146 · **Subsystem:** S24 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S24_backup_restore.md)

## Klassenueberblick
`restore_booking_activity_task extends restore_activity_task` — das Restore-Gegenstueck zum Backup-Task. Es haengt den einzigen Restore-Strukturschritt (`restore_booking_activity_structure_step`, geladen via `restore_booking_stepslib.php`, Z.26) ein und definiert die deklarativen Regeln, mit denen das Restore-Framework Inhalte und Links wiederherstellt: welche Felder der Link-Decoder verarbeitet, wie die Backup-Platzhalter zurueck in URLs uebersetzt werden und wie alte Log-Eintraege (Modul- und Kursebene) gemappt werden. Persistenz: keine eigene — alle Methoden liefern Regel-Objekte an das Restore-Framework. Kollaborateure: `restore_decode_content`, `restore_decode_rule`, `restore_log_rule`, `restore_booking_activity_structure_step`.

## Methoden

### `protected function define_my_settings()` — protected
- **Zweck:** Hook fuer restore-spezifische Settings. **Seiteneffekte:** keine (Booking hat keine). **Bewertung:** A — bewusst leer.

### `protected function define_my_steps()` — protected
- **Zweck:** Registriert den einzigen Strukturschritt `restore_booking_activity_structure_step('booking_structure', 'booking.xml')`. **Seiteneffekte:** `$this->add_step(...)`. **Bewertung:** A — kanonisch.

### `public static function define_decode_contents()` — public static
- **Zweck:** Deklariert die Felder, die der Link-Decoder nach dem Restore verarbeitet: `booking.intro` und `booking_options.description`. **Seiteneffekte:** keine; baut ein Array aus `restore_decode_content`. **Rueckgabe:** `restore_decode_content[]`. **Bewertung:** A.

### `public static function define_decode_rules()` — public static
- **Zweck:** Uebersetzt die Backup-Platzhalter zurueck in URLs: `BOOKINGVIEWBYID` → `/mod/booking/view.php?id=$1` (gemappt auf `course_module`) und `BOOKINGINDEX` → `/mod/booking/index.php?id=$1` (gemappt auf `course`). **Seiteneffekte:** keine. **Rueckgabe:** `restore_decode_rule[]`. **Bewertung:** A — exakt spiegelbildlich zu `backup_booking_activity_task::encode_content_links()`.

### `public static function define_restore_log_rules()` — public static
- **Zweck:** Definiert Mappings fuer Booking-Log-Eintraege auf Modulebene (`add`/`update`/`view`/`choose`/`choose again` → `view.php?id={course_module}`, `report` → `report.php?id={course_module}`). **Seiteneffekte:** keine. **Rueckgabe:** `restore_log_rule[]`. **Bewertung:** A.

### `public static function define_restore_log_rules_for_course()` — public static
- **Zweck:** Kurslevel-Log-Regeln (cmid=0), inklusive einer Korrektur fuer einen alten fehlerhaften Eintrag ohne `.php`-Endung (`index?id=` → `index.php?id={course}`) sowie der korrekten `view all`-Regel. **Seiteneffekte:** keine. **Rueckgabe:** `restore_log_rule[]`. **Bewertung:** A — die Legacy-Korrektur ist sauber via 7-Argument-Konstruktor (`urlmatch`) umgesetzt.

## Bewertungs-Resümee
Vollstaendiger, idiomatischer Restore-Task. Decode-Regeln spiegeln das Backup-Encoding exakt, Log-Regeln decken Modul- und Kursebene inkl. Legacy-Fix ab. Keine Auffaelligkeiten. Klassen-Score **A / P3**.
