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
 * English strings for bookingextension_oneclick.
 *
 * @package    bookingextension_oneclick
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['claim_continue_message'] =
    'Please create my trial instance named "{$a}".';
$string['claim_email_label'] = 'Your email address';
$string['claim_err_email_taken'] =
    'This email address already belongs to an account. Please log in instead: {$a}';
$string['claim_err_invalid_email'] = 'Please enter a valid email address.';
$string['claim_err_not_guest'] =
    'Your account does not need this step. Please just ask again to create your instance.';
$string['claim_error_generic'] = 'Sorry, that did not work. Please try again.';
$string['claim_heading'] = 'Almost there!';
$string['claim_intro'] =
    'To create "{$a}" we just need an email address so you keep access to your instance. You will verify it later.';
$string['claim_login_button'] = 'Log in or register';
$string['claim_or'] = 'or';
$string['claim_sending'] = 'Saving…';
$string['claim_submit'] = 'Use this email';
$string['claim_success'] =
    'Your email address has been saved. You will receive an email to set a password later.';
$string['claim_success_heading'] = 'Email saved!';
$string['claim_success_intro'] =
    'We will send you an email to set a password later. Your request now continues automatically in the chat.';
$string['claim_success_intro_manual'] =
    'We will send you an email to set a password later. Now just ask again in the chat to create your instance.';
$string['clarify_choose_instance'] =
    'You have more than one instance. Which one would you like to delete? Choose by its address or job id:';
$string['clarify_template_choose'] =
    'Which template would you like to use for your instance? Available templates:';
$string['clarify_template_unknown'] =
    'The template "{$a}" is not available. Please choose one of these templates:';
$string['delete_skill_description'] =
    'Delete (remove) the user\'s own trial Moodle/Booking instance. The user\'s own active '
    . 'instance is resolved automatically; this irreversibly tears it down.';
$string['err_email_not_verified'] =
    'Your email address must be confirmed before you can create a trial instance.';
$string['err_guest_must_register'] =
    'To create your own platform you need to register first. Please register here: {$a}';
$string['err_no_instance_to_delete'] =
    'You do not have an instance that can be deleted. It may already have been removed or expired.';
$string['err_not_configured'] =
    'The one-click instance skill is not fully configured. Please contact your administrator.';
$string['err_sitename_required'] = 'Please provide a name for the new instance.';
$string['error_already_active'] =
    'You already have an active trial instance. Please use or wait for your existing one.';
$string['error_auth'] = 'The provisioning service rejected the request. Please contact your administrator.';
$string['error_bad_request'] = 'The provisioning request was invalid. Please try a different name.';
$string['error_delete_terminal'] =
    'This instance can no longer be deleted — it has already been removed or has expired.';
$string['error_generic'] = 'The trial instance could not be created. Please try again later.';
$string['error_job_not_found'] = 'The requested provisioning job was not found.';
$string['error_not_verified'] = 'Your email address must be verified before a trial can be created.';
$string['error_rate_limited'] =
    'The provisioning queue is busy or you are within the cooldown window. Please try again later.';
$string['error_transport'] = 'The provisioning service could not be reached. Please try again later.';
$string['error_unavailable'] = 'The provisioning service is temporarily unavailable. Please try again later.';
$string['list_skill_description'] =
    'List the current user\'s own trial Moodle/Booking instances and their status. Read-only: it only '
    . 'reports the user\'s existing instances and changes nothing.';
$string['msg_claim_required'] =
    'Almost done! To create your instance, please enter your email address in the side panel — or log in.';
$string['msg_delete_started'] =
    'OK, we have started deleting your Booking instance. It will be removed shortly.';
$string['msg_instances_listed'] = 'Here are your instances ({$a}):';
$string['msg_instances_listed_all'] = 'Here are all instances across all users ({$a}):';
$string['msg_no_instances'] =
    'You do not have any Booking instances yet. You can ask me to create one.';
$string['msg_no_instances_all'] = 'There are no provisioned Booking instances for any user yet.';
$string['msg_started'] =
    'OK, we have started the creation of your Booking instance. This will take about two minutes.';
$string['msg_under_review'] =
    'Your request to create a Booking instance has been received and is now awaiting approval. You will see progress here.';
$string['oneclick:skill_oneclick_create_instance'] =
    'Use the agent skill that provisions a personal trial Moodle/Booking instance';
$string['oneclick:skill_oneclick_delete_instance'] =
    'Use the agent skill that deletes the user\'s own trial Moodle/Booking instance';
$string['oneclick:skill_oneclick_list_instances'] =
    'Use the agent skill that lists the user\'s own trial Moodle/Booking instances';
$string['oneclick:viewalljobs'] =
    'See ALL users\' one-click provisioning jobs including owner identity (admin list)';
$string['oneclick:viewjobstatus'] =
    'Poll the status of an own one-click trial instance';
$string['pluginname'] = 'Booking AI: One-click instance';
$string['preview_almost_ready'] = 'Almost ready…';
$string['preview_cancelled_heading'] = 'Request cancelled';
$string['preview_cancelled_intro'] = 'An operator cancelled this request.';
$string['preview_expired_heading'] = 'Trial expired';
$string['preview_expired_intro'] = 'This trial period has ended and the instance has been removed.';
$string['preview_failed_heading'] = 'Provisioning failed';
$string['preview_failed_intro'] = 'Something went wrong while creating your instance. Please try again later.';
$string['preview_loading'] = 'Loading';
$string['preview_open_site'] = 'Open my instance';
$string['preview_ready_heading'] = 'Your instance is ready!';
$string['preview_ready_intro'] = 'Your new Booking instance is live. Open it below.';
$string['preview_rejected_heading'] = 'Request denied';
$string['preview_rejected_intro'] = 'An operator denied this request.';
$string['preview_review_heading'] = 'Request under review';
$string['preview_review_intro'] = 'Your request is awaiting operator approval before provisioning starts.';
$string['preview_started_heading'] = 'Creating your instance';
$string['preview_started_intro'] = 'We are setting up your Booking instance. This will take about two minutes.';
$string['preview_timeout_heading'] = 'Still working';
$string['preview_timeout_intro'] = 'This is taking longer than usual. You can check back again shortly.';
$string['privacy:metadata:bookingextension_oneclick_jobs'] =
    'Records of trial Moodle instance provisioning jobs requested by the user.';
$string['privacy:metadata:bookingextension_oneclick_jobs:sitename'] = 'The site name the user supplied.';
$string['privacy:metadata:bookingextension_oneclick_jobs:status'] = 'The last known provisioning status.';
$string['privacy:metadata:bookingextension_oneclick_jobs:targethost'] = 'The public host of the requested instance.';
$string['privacy:metadata:bookingextension_oneclick_jobs:timecreated'] = 'When the request was made.';
$string['privacy:metadata:bookingextension_oneclick_jobs:userid'] = 'The user who requested the trial instance.';
$string['privacy:metadata:oneclick_provisioner'] =
    'In order to provision a trial instance, data is sent to the external oneclick-provisioner service.';
$string['privacy:metadata:oneclick_provisioner:request_ip'] = 'The request IP is sent for rate-limiting.';
$string['privacy:metadata:oneclick_provisioner:requester_email'] = 'The user email is sent for rate-limiting and notification.';
$string['privacy:metadata:oneclick_provisioner:requester_user_id'] = 'The user id is sent to identify the requester.';
$string['privacy:metadata:oneclick_provisioner:target_host'] = 'The requested public host name is sent.';
$string['privacy:metadata:preference:email_unverified'] =
    'Whether the email address the user set while claiming a guest account is still unverified.';
$string['schema_template_intro'] = 'Choose the template id that best matches the user\'s purpose. Available templates:';
$string['schema_template_none'] = 'No templates are configured yet; an administrator must add at least one before instances can be created.';
$string['setting_baseurl'] = 'Provisioner base URL';
$string['setting_baseurl_desc'] =
    'Base URL of the oneclick-provisioner service, e.g. http://oneclick-provisioner.provisioner.svc.cluster.local:18080. '
    . 'All calls are made server-side only.';
$string['setting_enabled'] = 'Enable the one-click instance skill';
$string['setting_enabled_desc'] =
    'When enabled, the agent can offer to create a personal trial Moodle instance. The skill must also be turned on '
    . 'in the agent skill governance page and the per-skill capability granted.';
$string['setting_hostsuffix'] = 'Public host suffix';
$string['setting_hostsuffix_desc'] =
    'The domain appended to the generated release slug to form the public host, e.g. sofabooking.com.';
$string['setting_registerurl'] = 'Registration URL for guests';
$string['setting_registerurl_desc'] =
    'Where guests are sent to register before they can create their own instance. A Moodle-relative path '
    . '(e.g. /login/index.php?loginredirect=1) or an absolute URL.';
$string['setting_sharedsecret'] = 'Shared secret';
$string['setting_sharedsecret_desc'] =
    'The PROVISIONER_API_SHARED_SECRET sent in the X-Provisioner-Secret header. Stored server-side and never exposed to '
    . 'the browser.';
$string['setting_skilldescription'] = 'Skill description (how the AI addresses the skill)';
$string['setting_skilldescription_desc'] =
    'The action-oriented description the planner uses to decide when to route a request to this skill. Keep it concise '
    . 'and describe what the skill does and when to use it.';
$string['setting_templates'] = 'Available templates';
$string['setting_templates_desc'] =
    'One template per line as "templateid, description". The id must match a template directory on the provisioner. '
    . 'The description helps the AI choose the right template based on the user\'s request. The first line is the default.';
$string['settings_heading_desc'] =
    'Configure the one-click provisioner skill that lets the Booking AI agent create personal trial Moodle instances.';
$string['skilldescription_default'] =
    'Create a personal trial Moodle/Booking instance for the current user. Use this when the user asks to create '
    . 'their own Moodle or Booking site/instance, optionally giving it a name. The instance is provisioned externally '
    . 'and becomes available after a couple of minutes.';
