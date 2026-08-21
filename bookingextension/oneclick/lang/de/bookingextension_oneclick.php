<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German strings for bookingextension_oneclick.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['claim_continue_message'] =
    'Bitte erstelle meine Test-Instanz mit dem Namen „{$a}“.';
$string['claim_email_label'] = 'Ihre E-Mail-Adresse';
$string['claim_err_email_taken'] =
    'Diese E-Mail-Adresse gehört bereits zu einem Konto. Bitte melden Sie sich stattdessen an: {$a}';
$string['claim_err_invalid_email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
$string['claim_err_not_guest'] =
    'Ihr Konto benötigt diesen Schritt nicht. Bitten Sie einfach erneut um die Erstellung Ihrer Instanz.';
$string['claim_error_generic'] = 'Das hat leider nicht geklappt. Bitte versuchen Sie es erneut.';
$string['claim_heading'] = 'Fast geschafft!';
$string['claim_intro'] =
    'Um „{$a}“ zu erstellen, brauchen wir nur eine E-Mail-Adresse, damit Sie den Zugang zu Ihrer Instanz behalten. '
    . 'Die Bestätigung erfolgt später.';
$string['claim_login_button'] = 'Anmelden oder registrieren';
$string['claim_or'] = 'oder';
$string['claim_sending'] = 'Wird gespeichert…';
$string['claim_submit'] = 'Diese E-Mail verwenden';
$string['claim_success'] =
    'Ihre E-Mail-Adresse wurde gespeichert. Sie erhalten später eine E-Mail, um ein Passwort zu setzen.';
$string['claim_success_heading'] = 'E-Mail gespeichert!';
$string['claim_success_intro'] =
    'Sie erhalten später eine E-Mail, um ein Passwort zu setzen. Ihre Anfrage läuft jetzt automatisch im Chat weiter.';
$string['claim_success_intro_manual'] =
    'Sie erhalten später eine E-Mail, um ein Passwort zu setzen. Bitten Sie jetzt einfach im Chat erneut um die '
    . 'Erstellung Ihrer Instanz.';
$string['clarify_choose_instance'] =
    'Sie haben mehrere Instanzen. Welche möchten Sie löschen? Wählen Sie nach Adresse oder Auftrags-ID:';
$string['clarify_template_choose'] =
    'Welche Vorlage möchten Sie für Ihre Instanz verwenden? Verfügbare Vorlagen:';
$string['clarify_template_unknown'] =
    'Die Vorlage „{$a}“ ist nicht verfügbar. Bitte wählen Sie eine dieser Vorlagen:';
$string['delete_skill_description'] =
    'Die eigene Test-Moodle-/Booking-Instanz der Nutzerin/des Nutzers löschen (entfernen). Die eigene '
    . 'aktive Instanz wird automatisch ermittelt; sie wird dabei unwiderruflich abgebaut.';
$string['err_email_not_verified'] =
    'Ihre E-Mail-Adresse muss bestätigt sein, bevor Sie eine Testinstanz erstellen können.';
$string['err_guest_must_register'] =
    'Um deine eigene Plattform zu erstellen, musst du dich vorher registrieren. Bitte registriere dich hier: {$a}';
$string['err_no_instance_to_delete'] =
    'Sie haben keine Instanz, die gelöscht werden kann. Möglicherweise wurde sie bereits entfernt oder ist abgelaufen.';
$string['err_not_configured'] =
    'Der Ein-Klick-Instanz-Skill ist nicht vollständig konfiguriert. Bitte wenden Sie sich an Ihre Administration.';
$string['err_sitename_required'] = 'Bitte geben Sie einen Namen für die neue Instanz an.';
$string['error_already_active'] =
    'Sie haben bereits eine aktive Testinstanz. Bitte nutzen Sie diese oder warten Sie darauf.';
$string['error_auth'] = 'Der Provisionierungsdienst hat die Anfrage abgelehnt. Bitte wenden Sie sich an Ihre Administration.';
$string['error_bad_request'] = 'Die Provisionierungsanfrage war ungültig. Bitte versuchen Sie einen anderen Namen.';
$string['error_delete_terminal'] =
    'Diese Instanz kann nicht mehr gelöscht werden – sie wurde bereits entfernt oder ist abgelaufen.';
$string['error_generic'] = 'Die Testinstanz konnte nicht erstellt werden. Bitte versuchen Sie es später erneut.';
$string['error_job_not_found'] = 'Der angeforderte Provisionierungsauftrag wurde nicht gefunden.';
$string['error_not_verified'] = 'Ihre E-Mail-Adresse muss bestätigt sein, bevor eine Testinstanz erstellt werden kann.';
$string['error_rate_limited'] =
    'Die Provisionierungs-Warteschlange ist ausgelastet oder Sie befinden sich in der Abklingphase. Bitte später erneut versuchen.';
$string['error_transport'] = 'Der Provisionierungsdienst konnte nicht erreicht werden. Bitte versuchen Sie es später erneut.';
$string['error_unavailable'] = 'Der Provisionierungsdienst ist vorübergehend nicht verfügbar. Bitte versuchen Sie es später erneut.';
$string['list_skill_description'] =
    'Die eigenen Test-Moodle-/Booking-Instanzen des Benutzers samt Status auflisten. Nur lesend: es werden '
    . 'ausschließlich die vorhandenen Instanzen angezeigt, nichts verändert.';
$string['msg_claim_required'] =
    'Fast geschafft! Um Ihre Instanz zu erstellen, geben Sie bitte Ihre E-Mail-Adresse im Seitenbereich ein — '
    . 'oder melden Sie sich an.';
$string['msg_delete_started'] =
    'OK, wir haben das Löschen Ihrer Booking-Instanz gestartet. Sie wird in Kürze entfernt.';
$string['msg_instances_listed'] = 'Hier sind Ihre Instanzen ({$a}):';
$string['msg_instances_listed_all'] = 'Hier sind alle Instanzen aller Benutzer ({$a}):';
$string['msg_no_instances'] =
    'Sie haben noch keine Booking-Instanzen. Sie können mich bitten, eine zu erstellen.';
$string['msg_no_instances_all'] = 'Es gibt noch keine provisionierten Booking-Instanzen.';
$string['msg_started'] =
    'OK, wir haben die Erstellung Ihrer Booking-Instanz gestartet. Das dauert etwa zwei Minuten.';
$string['msg_under_review'] =
    'Ihre Anfrage zur Erstellung einer Booking-Instanz wurde empfangen und wartet nun auf Freigabe. Den Fortschritt sehen Sie hier.';
$string['oneclick:skill_oneclick_create_instance'] =
    'Den Agenten-Skill nutzen, der eine persönliche Test-Moodle-/Booking-Instanz bereitstellt';
$string['oneclick:skill_oneclick_delete_instance'] =
    'Den Agenten-Skill nutzen, der die eigene Test-Moodle-/Booking-Instanz löscht';
$string['oneclick:skill_oneclick_list_instances'] =
    'Den Agenten-Skill nutzen, der die eigenen Test-Moodle-/Booking-Instanzen auflistet';
$string['oneclick:viewalljobs'] =
    'Die Ein-Klick-Provisionierungs-Jobs ALLER Benutzer samt Besitzeridentität einsehen (Admin-Liste)';
$string['oneclick:viewjobstatus'] =
    'Den Status einer eigenen Ein-Klick-Testinstanz abfragen';
$string['pluginname'] = 'Booking KI: Ein-Klick-Instanz';
$string['preview_almost_ready'] = 'Fast fertig…';
$string['preview_cancelled_heading'] = 'Anfrage abgebrochen';
$string['preview_cancelled_intro'] = 'Eine Betreiberin bzw. ein Betreiber hat diese Anfrage abgebrochen.';
$string['preview_expired_heading'] = 'Testphase abgelaufen';
$string['preview_expired_intro'] = 'Die Testphase ist beendet und die Instanz wurde entfernt.';
$string['preview_failed_heading'] = 'Provisionierung fehlgeschlagen';
$string['preview_failed_intro'] = 'Beim Erstellen Ihrer Instanz ist etwas schiefgelaufen. Bitte versuchen Sie es später erneut.';
$string['preview_loading'] = 'Wird geladen';
$string['preview_open_site'] = 'Meine Instanz öffnen';
$string['preview_ready_heading'] = 'Ihre Instanz ist bereit!';
$string['preview_ready_intro'] = 'Ihre neue Booking-Instanz ist online. Öffnen Sie sie unten.';
$string['preview_rejected_heading'] = 'Anfrage abgelehnt';
$string['preview_rejected_intro'] = 'Eine Betreiberin bzw. ein Betreiber hat diese Anfrage abgelehnt.';
$string['preview_review_heading'] = 'Anfrage in Prüfung';
$string['preview_review_intro'] = 'Ihre Anfrage wartet auf die Freigabe durch eine Betreiberin bzw. einen Betreiber.';
$string['preview_started_heading'] = 'Ihre Instanz wird erstellt';
$string['preview_started_intro'] = 'Wir richten Ihre Booking-Instanz ein. Das dauert etwa zwei Minuten.';
$string['preview_timeout_heading'] = 'Noch in Arbeit';
$string['preview_timeout_intro'] = 'Das dauert länger als üblich. Bitte schauen Sie in Kürze noch einmal vorbei.';
$string['privacy:metadata:bookingextension_oneclick_jobs'] =
    'Datensätze über angeforderte Provisionierungen von Test-Moodle-Instanzen.';
$string['privacy:metadata:bookingextension_oneclick_jobs:sitename'] = 'Der von der Person angegebene Seitenname.';
$string['privacy:metadata:bookingextension_oneclick_jobs:status'] = 'Der zuletzt bekannte Provisionierungsstatus.';
$string['privacy:metadata:bookingextension_oneclick_jobs:targethost'] = 'Der öffentliche Host der angeforderten Instanz.';
$string['privacy:metadata:bookingextension_oneclick_jobs:timecreated'] = 'Zeitpunkt der Anfrage.';
$string['privacy:metadata:bookingextension_oneclick_jobs:userid'] = 'Die Person, die die Testinstanz angefordert hat.';
$string['privacy:metadata:oneclick_provisioner'] =
    'Zur Bereitstellung einer Testinstanz werden Daten an den externen oneclick-provisioner-Dienst gesendet.';
$string['privacy:metadata:oneclick_provisioner:request_ip'] = 'Die Anfrage-IP wird für Limits gesendet.';
$string['privacy:metadata:oneclick_provisioner:requester_email'] = 'Die E-Mail-Adresse wird für Limits und Benachrichtigung gesendet.';
$string['privacy:metadata:oneclick_provisioner:requester_user_id'] = 'Die Nutzer-ID wird zur Identifikation gesendet.';
$string['privacy:metadata:oneclick_provisioner:target_host'] = 'Der angeforderte öffentliche Hostname wird gesendet.';
$string['privacy:metadata:preference:email_unverified'] =
    'Ob die beim Übernehmen eines Gastkontos angegebene E-Mail-Adresse noch unbestätigt ist.';
$string['schema_template_intro'] = 'Wählen Sie die Vorlagen-ID, die am besten zum Zweck passt. Verfügbare Vorlagen:';
$string['schema_template_none'] = 'Es sind noch keine Vorlagen konfiguriert; die Administration muss zuerst mindestens eine hinzufügen, bevor Instanzen erstellt werden können.';
$string['setting_baseurl'] = 'Basis-URL des Provisionierers';
$string['setting_baseurl_desc'] =
    'Basis-URL des oneclick-provisioner-Dienstes, z. B. http://oneclick-provisioner.provisioner.svc.cluster.local:18080. '
    . 'Alle Aufrufe erfolgen ausschließlich serverseitig.';
$string['setting_enabled'] = 'Ein-Klick-Instanz-Skill aktivieren';
$string['setting_enabled_desc'] =
    'Wenn aktiviert, kann der Agent anbieten, eine persönliche Test-Moodle-Instanz zu erstellen. Der Skill muss '
    . 'zusätzlich in der Skill-Governance-Seite des Agenten aktiviert und die zugehörige Berechtigung vergeben sein.';
$string['setting_hostsuffix'] = 'Öffentliches Host-Suffix';
$string['setting_hostsuffix_desc'] =
    'Die Domain, die an den generierten Release-Slug angehängt wird, um den öffentlichen Host zu bilden, z. B. sofabooking.com.';
$string['setting_registerurl'] = 'Registrierungs-URL für Gäste';
$string['setting_registerurl_desc'] =
    'Wohin Gäste zur Registrierung geleitet werden, bevor sie eine eigene Instanz erstellen können. Ein '
    . 'Moodle-relativer Pfad (z. B. /login/index.php?loginredirect=1) oder eine absolute URL.';
$string['setting_sharedsecret'] = 'Gemeinsames Geheimnis';
$string['setting_sharedsecret_desc'] =
    'Das PROVISIONER_API_SHARED_SECRET, das im Header X-Provisioner-Secret gesendet wird. Es wird serverseitig '
    . 'gespeichert und niemals an den Browser weitergegeben.';
$string['setting_skilldescription'] = 'Skill-Beschreibung (wie die KI den Skill anspricht)';
$string['setting_skilldescription_desc'] =
    'Die handlungsorientierte Beschreibung, anhand derer der Planer entscheidet, wann eine Anfrage an diesen Skill '
    . 'geleitet wird. Halten Sie sie knapp und beschreiben Sie, was der Skill tut und wann er einzusetzen ist.';
$string['setting_templates'] = 'Verfügbare Vorlagen';
$string['setting_templates_desc'] =
    'Eine Vorlage pro Zeile als „vorlagenid, Beschreibung". Die ID muss einem Vorlagenverzeichnis auf dem '
    . 'Provisionierer entsprechen. Die Beschreibung hilft der KI, die passende Vorlage anhand der Anfrage zu wählen. '
    . 'Die erste Zeile ist die Standardvorlage.';
$string['settings_heading_desc'] =
    'Konfiguriert den Ein-Klick-Provisionierungs-Skill, mit dem der Booking-KI-Agent persönliche Test-Moodle-Instanzen erstellen kann.';
$string['skilldescription_default'] =
    'Erstellt eine persönliche Test-Moodle-/Booking-Instanz für die aktuelle Nutzerin bzw. den aktuellen Nutzer. '
    . 'Verwenden, wenn jemand darum bittet, eine eigene Moodle- oder Booking-Seite/-Instanz zu erstellen, optional mit '
    . 'einem Namen. Die Instanz wird extern bereitgestellt und ist nach wenigen Minuten verfügbar.';
