# singleton_service — Methoden-Doku
**Datei:** `classes/singleton_service.php` · **LOC:** 936 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`singleton_service` ist ein request-scoped In-Memory-Cache-Registry (Singleton). Es haelt ~20 oeffentliche Array-Buckets fuer voneinander unabhaengige Domaenen (booking, booking_settings, booking_option(_settings), users, prices, pricecategory, renderer, campaigns, courses, cohorts, entities, customfields, certificate-temp, booking-images …) und bietet pro Domaene `get_instance_of_*`-Lazy-Loader (get-or-create) plus `destroy_*`/`reset_*`-Invalidierer. Kollaborateure: `booking`, `booking_settings`, `booking_option`, `booking_option_settings`, `booking_answers`, `price`, `entitiesrelation_handler`, `core_user`, `$DB`, `$PAGE`. Wird statisch aus dem gesamten Plugin gerufen (God-Call-Magnet). Hauptkritik: oeffentliche mutable Properties (keine Kapselung) und Vermischung vieler Domaenen in einer Klasse.

## Methoden

### `private function __construct()` — private
- **Zweck:** Verhindert externe Instanziierung (Singleton). Leer.
- **Bewertung:** A — leerer, beabsichtigter privater Ctor.

### `public static function get_instance(): singleton_service` — public static
- **Zweck:** Liefert/erzeugt die einzige Instanz. **Seiteneffekte:** setzt `self::$instance`. **Aufrufkette:** von ALLEN anderen statischen Methoden zuerst gerufen. **Bewertung:** A — klassischer Singleton-Accessor.

### `get_instance_of_booking_answers($settings): booking_answers` — public static
- **Zweck:** Get-or-create `booking_answers` indexiert per `$settings->id`. **Param:** `booking_option_settings $settings`. **Seiteneffekte:** instanziiert `new booking_answers($settings)`, cached in `bookinganswers[]`. **Bewertung:** B — Param ist untypisiert (Docblock `booking_option_settings`, Signatur ohne Typehint).

### `get_answers_for_user(int $userid, int $bookingid): array` — public static
- **Zweck:** Liest gecachte Answer-Daten eines Users in einer Instanz; sonst `[]`. **Bewertung:** A.

### `destroy_answers_for_user(int $userid, int $bookingid = 0): array` — public static
- **Zweck:** Invalidiert User-Answers; ohne `$bookingid` ueber alle Instanzen. **Seiteneffekte:** `unset` in `bookinganswersforuser`. **Rueckgabe:** immer `[]` (irrefuehrend — Rueckgabewert ohne Aussage). **Bewertung:** B — leere `[]`-Rueckgabe statt `bool`/`void` inkonsistent zu Geschwistermethoden.

### `set_answers_for_user(int $userid, int $bookingid, array $data): bool` — public static
- **Zweck:** Schreibt Answer-Array in Cache. **Bewertung:** A.

### `destroy_booking_singleton_by_cmid($cmid): bool` — public static
- **Zweck:** Invalidiert booking + booking_settings sowohl per cmid als auch per bookingid. **Seiteneffekte:** ruft `get_instance_of_booking_settings_by_cmid` (kann DB/Objekt erzeugen, nur um die id zu erhalten), `unset` von 4 Buckets. **Bewertung:** B — laedt Settings nur fuer die id; bei nicht-existenter Instanz teurer Seiteneffekt zum Invalidieren.

### `destroy_booking_option_singleton($optionid): bool` — public static
- **Zweck:** Invalidiert `bookingoptionsettings[]` + `bookingoptions[]`. **Bewertung:** A.

### `destroy_booking_answers($optionid): bool` — public static
- **Zweck:** Invalidiert `bookinganswers[]` (Index ist eigentlich settings->id, hier `$optionid` benannt — potenzielle Begriffsverwirrung). **Bewertung:** B — Key-Semantik (option vs. settings-id) uneindeutig.

### `destroy_booking_answers_for_user_in_booking_instance(int $bookingid, int $userid): bool` — public static
- **Zweck:** Invalidiert einzelnen User-Answer-Eintrag. **Bewertung:** A — Duplikat-naehe zu `destroy_answers_for_user` (gleicher unset-Pfad).

### `get_instance_of_booking_by_cmid(int $cmid): booking|null` — public static
- **Zweck:** Get-or-create `booking($cmid)`; bei Exception `null`. **Seiteneffekte:** `new booking($cmid)` (DB), cached. **Bewertung:** B.

### `get_instance_of_booking_by_bookingid(int $bookingid): booking|null` — public static
- **Zweck:** Get-or-create `booking` ueber bookingid. **Seiteneffekte:** `get_coursemodule_from_instance('booking', …)` (DB), `new booking`. **Bewertung:** B — hier KEIN try/catch (im Gegensatz zur cmid-Variante): unbehandelte Exception moeglich wenn cm fehlt.

### `get_instance_of_booking_by_optionid(int $optionid): booking` — public static
- **Zweck:** booking via option→settings→bookingid. **Seiteneffekte:** delegiert. **Bewertung:** A — schlanke Delegation.

### `get_instance_of_booking_settings_by_cmid(int $cmid): booking_settings` — public static
- **Zweck:** Get-or-create `booking_settings($cmid)`. **Seiteneffekte:** DB/Objekt. **Bewertung:** A.

### `get_instance_of_booking_settings_by_bookingid(int $bookingid): booking_settings|null` — public static
- **Zweck:** Get-or-create via bookingid; try/catch→null. **Bewertung:** B.

### `get_instance_of_booking_option(int $cmid, int $optionid): booking_option|null` — public static
- **Zweck:** Get-or-create `booking_option`; try/catch→null. **Bewertung:** B — Cache-Key nur `optionid`, `cmid` fliesst nicht in Key ein (bei falschem cmid potenziell falsches Cache-Hit, aber optionid ist global eindeutig).

### `get_instance_of_booking_option_settings($optionid, ?stdClass $dbrecord = null): booking_option_settings` — public static
- **Zweck:** Get-or-create `booking_option_settings`; `0` liefert leeres Objekt. **Bewertung:** B — `$optionid` untypisiert in Signatur.

### `get_instance_of_user(int $userid, bool $includeprofilefields = false): stdClass` — public static
- **Zweck:** Get-or-create Moodle-User; optional Profilfelder nachladen. **Seiteneffekte:** `core_user::get_user`, `require_once user/profile/lib.php`, `profile_load_custom_fields`, nutzt `$USER`/`$CFG`. **Aufrufkette:** breit im Plugin. **Bewertung:** C — gemischte Verantwortung (Cache + bedingtes require_once + Profil-Mutation in 2 Pfaden), Empty-Fallback gibt globalen `$USER` zurueck (Cache-Bypass).

### `unset_instance_of_user(int $userid): bool` / `destroy_user(int $userid): bool` — public static
- **Zweck:** Beide entfernen `users[$userid]`. **Bewertung:** B — funktionales Duplikat (zwei Methoden, identischer Effekt, nur Rueckgabe-Logik minimal verschieden).

### `get_instance_of_price($optionid): price` — public static
- **Zweck:** Get-or-create `new price('option', $optionid)`. **Bewertung:** A.

### `get_price_category($identifier): mixed` — public static
- **Zweck:** Liest gecachte pricecategory; `false` wenn nicht gesetzt (kein Auto-Load, braucht `set_price_category`). **Bewertung:** A.

### `get_pricecategory_for_user($user): mixed` — public static
- **Zweck:** Get-or-create User-Preiskategorie via `price::get_pricecategory_for_user`. **Seiteneffekte:** Delegations-Call. **Bewertung:** A.

### `set_price_category($identifier, $pricecategory): bool` — public static
- **Zweck:** Setzt pricecategory-Cache. **Bewertung:** A.

### `get_renderer(string $renderername): renderer` — public static
- **Zweck:** Get-or-create Renderer via `$PAGE->get_renderer`. **Seiteneffekte:** `$PAGE`. **Bewertung:** A.

### `get_all_campaigns(): array` — public static
- **Zweck:** Laedt/cached alle `booking_campaigns`. **Seiteneffekte:** `$DB->get_records('booking_campaigns')`. **Bewertung:** A.

### `destroy_all_campaigns(): array` / `reset_campaigns($id = 0): array` — public static
- **Zweck:** `destroy_all_campaigns` unsetzt das Bucket komplett; `reset_campaigns` leert oder entfernt einen Eintrag. **Bewertung:** B — zwei sehr aehnliche Invalidierer fuer dieselbe Domaene; `unset` vs. `= []` inkonsistent (nachfolgender Zugriff auf nicht-gesetztes Property moeglich, durch `empty()`-Guard aber abgefangen).

### `get_course(int $courseid): object|bool` — public static
- **Zweck:** Get-or-create Kurs-Record (`IGNORE_MISSING`), `false` wenn weg. **Seiteneffekte:** `$DB->get_record('course')`. **Bewertung:** A.

### `get_cohort(int $cohortid): object` — public static
- **Zweck:** Get-or-create Cohort-Record; leeres stdClass-Fallback. **Seiteneffekte:** `$DB->get_record('cohort')`. **Bewertung:** A.

### `get_cohorts_of_user(int $userid): array` — public static
- **Zweck:** Get-or-create `cohort_get_user_cohorts`. **Seiteneffekte:** core-Call (DB). **Bewertung:** A.

### `get_entity_by_id(int $id): object` — public static
- **Zweck:** Get-or-create Entity via `entitiesrelation_handler::get_entities_by_id`. **Seiteneffekte:** externer Plugin-Call (`local_entities`). **Bewertung:** A — harte Abhaengigkeit auf optionales Plugin (kein Existenz-Guard hier; impliziert Aufrufer prueft).

### `get_index_number(string $uniqueid, string $indexid): int` — public static
- **Zweck:** Vergibt aufsteigende, stabile Indexnummer pro (uniqueid,indexid). **Seiteneffekte:** mutiert `index[]`-Counter. **Bewertung:** B — Counter-Bucket mischt `'counter'`-Key mit echten indexids im selben Array (fragil wenn indexid == 'counter').

### `get_id_of_booking_module(): int` — public static
- **Zweck:** Get-or-cache modules.id fuer 'booking'. **Seiteneffekte:** `$DB->get_record('modules')`. **Bewertung:** A.

### `get_all_booking_instances(): array` — public static
- **Zweck:** Get-or-cache alle `booking`-Records. **Seiteneffekte:** `$DB->get_records('booking')` (potenziell grosse Menge). **Bewertung:** B — laedt komplette Tabelle in den Request-Speicher.

### `get_customfield_field_by_shortname(string $field): object` — public static
- **Zweck:** Get-or-cache customfield_field per shortname (mod_booking/area booking). **Seiteneffekte:** inline-SQL `$DB->get_record_sql` (JOIN customfield_field/category). **Bewertung:** C — handgebauter SQL-String im Cache-Service (SQL-Bau gehoert in Persistenz-/Repository-Schicht, vermischte Verantwortung).

### `destroy_instance(): bool` — public static
- **Zweck:** Setzt `self::$instance = null` (kompletter Reset). **Bewertung:** A.

### `set_temp_values_for_certificates(int $optionid, int $userid, int $conditionid)` — public static
- **Zweck:** Schreibt Zertifikats-Temp-Daten. **Seiteneffekte:** pusht userid, optionid, conditionid als 3 FLACHE Eintraege in `tempdataforcertificate[]`. **Bewertung:** C — positionsbasierte Flach-Array-Struktur (keine assoziativen Keys, kein Tupel/DTO): fehleranfaellig beim Auslesen, kein Bezug zwischen den 3 Werten erkennbar; bei Mehrfachaufruf vermischen sich Tripel.

### `get_temp_values_for_certificates(): array` / `unset_temp_values_for_certificates()` — public static
- **Zweck:** Lesen / Loeschen der Zertifikats-Temp-Daten. **Bewertung:** B — erben Fragilitaet der flachen Struktur des Setters.

### `load_booking_image(int $bookingid): array` / `set_booking_image(int $bookingid, array $filerecords)` — public static
- **Zweck:** Get/Set gecachte Booking-Image-Filerecords. **Bewertung:** A — schlanke Akzessoren.

### Triviale Akzessoren
Property-Deklarationen (20+ oeffentliche `array`/`int`-Buckets, Z.41–119) sind reine Zustands-Halter ohne Logik. Kritik gebuendelt: alle Cache-Buckets sind **public** (keine Kapselung) und direkt mutierbar von aussen — bewusste Performance-Designentscheidung, aber Encapsulation-Smell auf Klassenebene (`classes/singleton_service.php:44-119`).

## Bewertungs-Resümee
Einzelmethoden sind ueberwiegend klein und klar (viel A/B), das Muster „get-or-create + destroy" ist konsistent. Die Klassen-Schwaeche liegt in der Aggregation: eine Klasse verwaltet ~17 unzusammenhaengende Cache-Domaenen (Single-Responsibility verletzt), wird statisch ueberall gerufen (hohe Kopplung, schwer testbar ausser via `destroy_instance`), oeffentliche mutable Properties, und an 3 Stellen Verantwortungs-Vermischung (Profil-Load, Inline-SQL, flache Cert-Tupel). Daher Klassen-Score **C / P2**.
