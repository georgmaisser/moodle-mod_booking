# mod_booking — FILE_INDEX

> Jede Code-Datei → Subsystem-Zuordnung, Rolle, LOC. Generiert aus Phase-2-Erfassung (2026-06-28). Siehe [Master-Plan](00_ARCHITECTURE_DOC_PLAN.md) · [Übersicht](01_SYSTEM_OVERVIEW.md).

**843 Dateien** über 26 Subsysteme.

## S01 — core_domain  ([Doc](subsystems/S01_core_domain.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/booking_option.php` | `mod_booking\booking_option` | Domaenenobjekt | 5279 |
| `classes/booking.php` | `mod_booking\booking` | Domaenenobjekt | 2391 |
| `classes/booking_option_settings.php` | `mod_booking\booking_option_settings` | DTO | 1754 |
| `classes/booking_answers/booking_answers.php` | `mod_booking\booking_answers\booking_answers` | Service | 1606 |
| `classes/all_userbookings.php` | `mod_booking\all_userbookings` | Renderer | 1061 |
| `classes/dates.php` | `mod_booking\dates` | Form | 1038 |
| `classes/singleton_service.php` | `mod_booking\singleton_service` | Service | 958 |
| `classes/teachers_handler.php` | `mod_booking\teachers_handler` | Service | 642 |
| `classes/booking_utils.php` | `mod_booking\booking_utils` | Util | 616 |
| `classes/booking_settings.php` | `mod_booking\booking_settings` | DTO | 597 |
| `classes/ical.php` | `mod_booking\ical` | Service | 567 |
| `classes/booking_answers/scopes/optionstoconfirm.php` | `mod_booking\booking_answers\scopes\optionstoconfirm` | Condition | 473 |
| `classes/calendar.php` | `mod_booking\calendar` | Service | 466 |
| `classes/booking_answers/scopes/option.php` | `mod_booking\booking_answers\scopes\option` | Condition | 438 |
| `classes/booking_answers/scopes/optiondate.php` | `mod_booking\booking_answers\scopes\optiondate` | Condition | 313 |
| `classes/booking_answers/scopes/alloptions.php` | `mod_booking\booking_answers\scopes\alloptions` | Condition | 289 |
| `classes/local/calendar/calendar_helper.php` | `mod_booking\local\calendar\calendar_helper` | Service | 230 |
| `classes/coursecategories.php` | `mod_booking\coursecategories` | Service | 218 |
| `classes/booking_answers/scope_base.php` | `mod_booking\booking_answers\scope_base` | Condition | 215 |
| `classes/booking_answers/scopes/systemanswers.php` | `mod_booking\booking_answers\scopes\systemanswers` | Condition | 208 |
| `classes/booking_answers/scopes/courseanswers.php` | `mod_booking\booking_answers\scopes\courseanswers` | Condition | 207 |
| `classes/booking_answers/scopes/instanceanswers.php` | `mod_booking\booking_answers\scopes\instanceanswers` | Condition | 206 |
| `classes/booking_answers/scopes/instance.php` | `mod_booking\booking_answers\scopes\instance` | Condition | 178 |
| `classes/booking_answers/scopes/course.php` | `mod_booking\booking_answers\scopes\course` | Condition | 178 |
| `classes/booking_answers/scopes/system.php` | `mod_booking\booking_answers\scopes\system` | Condition | 176 |
| `classes/booking_tags.php` | `mod_booking\booking_tags` | Domaenenobjekt | 169 |
| `classes/local/optiondates/optiondate_answer.php` | `mod_booking\local\optiondates\optiondate_answer` | DTO | 166 |
| `classes/booking_answers/scopes/supervisorteam.php` | `mod_booking\booking_answers\scopes\supervisorteam` | Condition | 159 |
| `classes/semester.php` | `mod_booking\semester` | DTO | 156 |
| `classes/booking_answers/scopes/supervisorteamreduced.php` | `mod_booking\booking_answers\scopes\supervisorteamreduced` | Condition | 134 |
| `classes/mybookings_table.php` | `mod_booking\mybookings_table` | Renderer | 127 |
| `classes/booking_answers/scope_base_answers.php` | `mod_booking\booking_answers\scope_base_answers` | Condition | 126 |
| `classes/booking_answers/scopes/optionstoconfirmreduced.php` | `mod_booking\booking_answers\scopes\optionstoconfirmreduced` | Condition | 113 |
| `classes/booking_answers/scope_base_options.php` | `mod_booking\booking_answers\scope_base_options` | Condition | 107 |
| `classes/places.php` | `mod_booking\places` | DTO | 80 |
| `classes/booking_context_helper.php` | `mod_booking\booking_context_helper` | Util | 61 |
| `classes/permissions.php` | `mod_booking\permissions` | Util | 53 |

## S02 — option_fields  ([Doc](subsystems/S02_option_fields.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/option/dates_handler.php` | `mod_booking\option\dates_handler` | Service | 938 |
| `classes/option/fields/recurringoptions.php` | `mod_booking\option\fields\recurringoptions` | Field | 869 |
| `classes/option/fields/slotbooking.php` | `mod_booking\option\fields\slotbooking` | Field | 801 |
| `classes/customfield/booking_handler.php` | `mod_booking\customfield\booking_handler` | Service | 536 |
| `classes/option/fields_info.php` | `mod_booking\option\fields_info` | Service | 529 |
| `classes/option/fields/competencies.php` | `mod_booking\option\fields\competencies` | Field | 474 |
| `classes/option/fields/sharedplaces.php` | `mod_booking\option\fields\sharedplaces` | Field | 445 |
| `classes/option/field_base.php` | `mod_booking\option\field_base` | Field | 432 |
| `classes/option/fields/courseid.php` | `mod_booking\option\fields\courseid` | Field | 423 |
| `classes/settings/optionformconfig/optionformconfig_info.php` | `mod_booking\settings\optionformconfig\optionformconfig_info` | Service | 411 |
| `classes/option/fields/certificate.php` | `mod_booking\option\fields\certificate` | Field | 389 |
| `classes/option/fields/entities.php` | `mod_booking\option\fields\entities` | Field | 389 |
| `classes/option/optiondate.php` | `mod_booking\option\optiondate` | Domaenenobjekt | 379 |
| `classes/option/fields/optiondates.php` | `mod_booking\option\fields\optiondates` | Field | 358 |
| `classes/customfield/optiondate_cfields.php` | `mod_booking\customfield\optiondate_cfields` | Service | 333 |
| `classes/option/fields/shoppingcart.php` | `mod_booking\option\fields\shoppingcart` | Field | 327 |
| `classes/option/fields/template.php` | `mod_booking\option\fields\template` | Field | 309 |
| `classes/option/fields/availability.php` | `mod_booking\option\fields\availability` | Field | 295 |
| `classes/option/fields/optiontype.php` | `mod_booking\option\fields\optiontype` | Field | 295 |
| `classes/option/fields/moveoption.php` | `mod_booking\option\fields\moveoption` | Field | 280 |
| `classes/option/fields/customfields.php` | `mod_booking\option\fields\customfields` | Field | 277 |
| `classes/option/fields/price.php` | `mod_booking\option\fields\price` | Field | 277 |
| `classes/option/fields/easy_availability_previouslybooked.php` | `mod_booking\option\fields\easy_availability_previouslybooked` | Field | 271 |
| `classes/option/fields/duration.php` | `mod_booking\option\fields\duration` | Field | 268 |
| `classes/option/fields/easy_availability_selectusers.php` | `mod_booking\option\fields\easy_availability_selectusers` | Field | 262 |
| `classes/option/fields/bookingoptionimage.php` | `mod_booking\option\fields\bookingoptionimage` | Field | 260 |
| `classes/option/fields/applybookingrules.php` | `mod_booking\option\fields\applybookingrules` | Field | 257 |
| `classes/option/fields/responsiblecontact.php` | `mod_booking\option\fields\responsiblecontact` | Field | 247 |
| `classes/option/fields/attachment.php` | `mod_booking\option\fields\attachment` | Field | 245 |
| `classes/option/fields/bookingclosingtime.php` | `mod_booking\option\fields\bookingclosingtime` | Field | 245 |
| `classes/option/fields/bookingopeningtime.php` | `mod_booking\option\fields\bookingopeningtime` | Field | 244 |
| `classes/option/fields/canceluntil.php` | `mod_booking\option\fields\canceluntil` | Field | 243 |
| `classes/option/fields/multiplebookings.php` | `mod_booking\option\fields\multiplebookings` | Field | 240 |
| `classes/option/fields/pollurl.php` | `mod_booking\option\fields\pollurl` | Field | 239 |
| `classes/option/fields/teachers.php` | `mod_booking\option\fields\teachers` | Field | 239 |
| `classes/option/fields/enrolmentstatus.php` | `mod_booking\option\fields\enrolmentstatus` | Field | 223 |
| `classes/option/fields/bookusers.php` | `mod_booking\option\fields\bookusers` | Field | 216 |
| `classes/option/fields/groupid.php` | `mod_booking\option\fields\groupid` | Field | 216 |
| `classes/local/override_user_field.php` | `mod_booking\local\override_user_field` | Service | 212 |
| `classes/option/fields/addastemplate.php` | `mod_booking\option\fields\addastemplate` | Field | 210 |
| `classes/option/fields/invisible.php` | `mod_booking\option\fields\invisible` | Field | 205 |
| `classes/option/fields/coursestarttime.php` | `mod_booking\option\fields\coursestarttime` | Field | 203 |
| `classes/option/fields/waitforconfirmation.php` | `mod_booking\option\fields\waitforconfirmation` | Field | 199 |
| `classes/option/fields/addtocalendar.php` | `mod_booking\option\fields\addtocalendar` | Field | 195 |
| `classes/option/fields/text.php` | `mod_booking\option\fields\text` | Field | 193 |
| `classes/option/fields/description.php` | `mod_booking\option\fields\description` | Field | 191 |
| `classes/option/fields/easy_bookingopeningtime.php` | `mod_booking\option\fields\easy_bookingopeningtime` | Field | 188 |
| `classes/option/fields/easy_bookingclosingtime.php` | `mod_booking\option\fields\easy_bookingclosingtime` | Field | 186 |
| `classes/option/fields/elective.php` | `mod_booking\option\fields\elective` | Field | 184 |
| `classes/option/fields/aftersubmitaction.php` | `mod_booking\option\fields\aftersubmitaction` | Field | 182 |
| `classes/option/fields/duplication.php` | `mod_booking\option\fields\duplication` | Field | 174 |
| `classes/option/fields/id.php` | `mod_booking\option\fields\id` | Field | 173 |
| `classes/option/fields/formconfig.php` | `mod_booking\option\fields\formconfig` | Field | 172 |
| `classes/option/fields/prepare_import.php` | `mod_booking\option\fields\prepare_import` | Field | 171 |
| `classes/option/fields/aftercompletedtext.php` | `mod_booking\option\fields\aftercompletedtext` | Field | 168 |
| `classes/option/fields/annotation.php` | `mod_booking\option\fields\annotation` | Field | 168 |
| `classes/option/fields/beforebookedtext.php` | `mod_booking\option\fields\beforebookedtext` | Field | 168 |
| `classes/option/fields/beforecompletedtext.php` | `mod_booking\option\fields\beforecompletedtext` | Field | 168 |
| `classes/option/fields/notificationtext.php` | `mod_booking\option\fields\notificationtext` | Field | 164 |
| `classes/option/fields/credits.php` | `mod_booking\option\fields\credits` | Field | 162 |
| `classes/option/fields/timecreated.php` | `mod_booking\option\fields\timecreated` | Field | 162 |
| `classes/option/fields/institution.php` | `mod_booking\option\fields\institution` | Field | 159 |
| `classes/option/fields/identifier.php` | `mod_booking\option\fields\identifier` | Field | 157 |
| `classes/option/fields/actions.php` | `mod_booking\option\fields\actions` | Field | 155 |
| `classes/option/fields/maxanswers.php` | `mod_booking\option\fields\maxanswers` | Field | 155 |
| `classes/option/fields/location.php` | `mod_booking\option\fields\location` | Field | 153 |
| `classes/option/fields/disablecancel.php` | `mod_booking\option\fields\disablecancel` | Field | 151 |
| `classes/option/fields/easy_text.php` | `mod_booking\option\fields\easy_text` | Field | 149 |
| `classes/option/fields/addtogroup.php` | `mod_booking\option\fields\addtogroup` | Field | 146 |
| `classes/option/fields/timemodified.php` | `mod_booking\option\fields\timemodified` | Field | 146 |
| `classes/option/fields/json.php` | `mod_booking\option\fields\json` | Field | 142 |
| `classes/option/fields/courseendtime.php` | `mod_booking\option\fields\courseendtime` | Field | 141 |
| `classes/option/fields/address.php` | `mod_booking\option\fields\address` | Field | 140 |
| `classes/option/fields/maxoverbooking.php` | `mod_booking\option\fields\maxoverbooking` | Field | 134 |
| `classes/option/fields/titleprefix.php` | `mod_booking\option\fields\titleprefix` | Field | 134 |
| `classes/option/fields/removeafterminutes.php` | `mod_booking\option\fields\removeafterminutes` | Field | 132 |
| `classes/option/fields/returnurl.php` | `mod_booking\option\fields\returnurl` | Field | 132 |
| `classes/option/fields/eventslist.php` | `mod_booking\option\fields\eventslist` | Field | 131 |
| `classes/option/fields/howmanyusers.php` | `mod_booking\option\fields\howmanyusers` | Field | 128 |
| `classes/option/fields/minanswers.php` | `mod_booking\option\fields\minanswers` | Field | 127 |
| `classes/option/fields/disablebookingusers.php` | `mod_booking\option\fields\disablebookingusers` | Field | 126 |
| `classes/option/fields/subbookings.php` | `mod_booking\option\fields\subbookings` | Field | 119 |
| `classes/option/fields/priceformulaadd.php` | `mod_booking\option\fields\priceformulaadd` | Field | 117 |
| `classes/option/fields/priceformulamultiply.php` | `mod_booking\option\fields\priceformulamultiply` | Field | 117 |
| `classes/option/fields/priceformulaoff.php` | `mod_booking\option\fields\priceformulaoff` | Field | 117 |
| `classes/option/fields/usercreated.php` | `mod_booking\option\fields\usercreated` | Field | 110 |
| `classes/option/type_resolver.php` | `mod_booking\option\type_resolver` | Service | 108 |
| `classes/option/fields/usermodified.php` | `mod_booking\option\fields\usermodified` | Field | 102 |
| `classes/option/fields.php` | `mod_booking\option\fields` | Interface | 67 |
| `classes/option/time_handler.php` | `mod_booking\option\time_handler` | Util | 65 |

## S03 — availability  ([Doc](subsystems/S03_availability.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/bo_availability/bo_info.php` | `mod_booking\bo_availability\bo_info` | Service | 1624 |
| `classes/bo_availability/conditions/booking_time.php` | `mod_booking\bo_availability\conditions\booking_time` | Condition | 1352 |
| `classes/bo_availability/conditions/userprofilefield_2_custom.php` | `mod_booking\bo_availability\conditions\userprofilefield_2_custom` | Condition | 1096 |
| `classes/bo_availability/conditions/customform.php` | `mod_booking\bo_availability\conditions\customform` | Condition | 988 |
| `classes/bo_availability/conditions/enrolledincourse.php` | `mod_booking\bo_availability\conditions\enrolledincourse` | Condition | 757 |
| `classes/bo_availability/conditions/enrolledincohorts.php` | `mod_booking\bo_availability\conditions\enrolledincohorts` | Condition | 738 |
| `classes/bo_availability/conditions/userprofilefield_1_default.php` | `mod_booking\bo_availability\conditions\userprofilefield_1_default` | Condition | 710 |
| `classes/bo_availability/conditions/hascompetency.php` | `mod_booking\bo_availability\conditions\hascompetency` | Condition | 610 |
| `classes/bo_availability/conditions/nooverlappingproxy.php` | `mod_booking\bo_availability\conditions\nooverlappingproxy` | Condition | 601 |
| `classes/bo_availability/conditions/previouslybooked.php` | `mod_booking\bo_availability\conditions\previouslybooked` | Condition | 596 |
| `classes/bo_availability/conditions/nooverlapping.php` | `mod_booking\bo_availability\conditions\nooverlapping` | Condition | 596 |
| `classes/bo_availability/conditions/selectusers.php` | `mod_booking\bo_availability\conditions\selectusers` | Condition | 572 |
| `classes/bo_availability/conditions/allowedtobookininstance.php` | `mod_booking\bo_availability\conditions\allowedtobookininstance` | Condition | 548 |
| `classes/bo_availability/conditions/maxoptionsfromcategory.php` | `mod_booking\bo_availability\conditions\maxoptionsfromcategory` | Condition | 500 |
| `classes/bo_availability/bo_subinfo.php` | `mod_booking\bo_availability\bo_subinfo` | Service | 497 |
| `classes/bo_availability/conditions/cancelmyself.php` | `mod_booking\bo_availability\conditions\cancelmyself` | Condition | 455 |
| `classes/bo_availability/conditions/slotbooking.php` | `mod_booking\bo_availability\conditions\slotbooking` | Condition | 444 |
| `classes/bo_availability/conditions/askforconfirmation.php` | `mod_booking\bo_availability\conditions\askforconfirmation` | Condition | 390 |
| `classes/bo_availability/conditions/bookitbutton.php` | `mod_booking\bo_availability\conditions\bookitbutton` | Condition | 365 |
| `classes/bo_availability/conditions/bookwithcredits.php` | `mod_booking\bo_availability\conditions\bookwithcredits` | Condition | 361 |
| `classes/bo_availability/conditions/bookwithsubscription.php` | `mod_booking\bo_availability\conditions\bookwithsubscription` | Condition | 360 |
| `classes/bo_availability/conditions/alreadybooked.php` | `mod_booking\bo_availability\conditions\alreadybooked` | Condition | 355 |
| `classes/bo_availability/conditions/priceisset.php` | `mod_booking\bo_availability\conditions\priceisset` | Condition | 340 |
| `classes/bo_availability/conditions/onwaitinglist.php` | `mod_booking\bo_availability\conditions\onwaitinglist` | Condition | 335 |
| `classes/bo_availability/conditions/electivenotbookable.php` | `mod_booking\bo_availability\conditions\electivenotbookable` | Condition | 331 |
| `classes/bo_availability/conditions/confirmcancel.php` | `mod_booking\bo_availability\conditions\confirmcancel` | Condition | 330 |
| `classes/bo_availability/conditions/notifymelist.php` | `mod_booking\bo_availability\conditions\notifymelist` | Condition | 329 |
| `classes/bo_availability/conditions/electivebookitbutton.php` | `mod_booking\bo_availability\conditions\electivebookitbutton` | Condition | 321 |
| `classes/bo_availability/conditions/fullybooked.php` | `mod_booking\bo_availability\conditions\fullybooked` | Condition | 319 |
| `classes/bo_availability/conditions/isloggedinprice.php` | `mod_booking\bo_availability\conditions\isloggedinprice` | Condition | 315 |
| `classes/bo_availability/conditions/bookondetail.php` | `mod_booking\bo_availability\conditions\bookondetail` | Condition | 304 |
| `classes/bo_availability/conditions/bookingpolicy.php` | `mod_booking\bo_availability\conditions\bookingpolicy` | Condition | 303 |
| `classes/bo_availability/conditions/isloggedin.php` | `mod_booking\bo_availability\conditions\isloggedin` | Condition | 302 |
| `classes/bo_availability/conditions/alreadyreserved.php` | `mod_booking\bo_availability\conditions\alreadyreserved` | Condition | 302 |
| `classes/bo_availability/conditions/otheroptionsavailable.php` | `mod_booking\bo_availability\conditions\otheroptionsavailable` | Condition | 296 |
| `classes/bo_availability/conditions/subbooking_blocks.php` | `mod_booking\bo_availability\conditions\subbooking_blocks` | Condition | 296 |
| `classes/bo_availability/conditions/instanceavailability.php` | `mod_booking\bo_availability\conditions\instanceavailability` | Condition | 291 |
| `classes/bo_availability/conditions/subbooking.php` | `mod_booking\bo_availability\conditions\subbooking` | Condition | 291 |
| `classes/bo_availability/conditions/max_number_of_bookings.php` | `mod_booking\bo_availability\conditions\max_number_of_bookings` | Condition | 288 |
| `classes/bo_availability/conditions/confirmbookit.php` | `mod_booking\bo_availability\conditions\confirmbookit` | Condition | 286 |
| `classes/bo_availability/conditions/confirmbookwithcredits.php` | `mod_booking\bo_availability\conditions\confirmbookwithcredits` | Condition | 285 |
| `classes/bo_availability/conditions/confirmbookwithsubscription.php` | `mod_booking\bo_availability\conditions\confirmbookwithsubscription` | Condition | 283 |
| `classes/bo_availability/conditions/confirmaskforconfirmation.php` | `mod_booking\bo_availability\conditions\confirmaskforconfirmation` | Condition | 280 |
| `classes/bo_availability/conditions/campaign_blockbooking.php` | `mod_booking\bo_availability\conditions\campaign_blockbooking` | Condition | 279 |
| `classes/bo_availability/conditions/iscancelled.php` | `mod_booking\bo_availability\conditions\iscancelled` | Condition | 276 |
| `classes/bo_availability/conditions/optionhasstarted.php` | `mod_booking\bo_availability\conditions\optionhasstarted` | Condition | 273 |
| `classes/bo_availability/conditions/isbookable.php` | `mod_booking\bo_availability\conditions\isbookable` | Condition | 271 |
| `classes/bo_availability/conditions/confirmation.php` | `mod_booking\bo_availability\conditions\confirmation` | Condition | 270 |
| `classes/bo_availability/conditions/isbookableinstance.php` | `mod_booking\bo_availability\conditions\isbookableinstance` | Condition | 268 |
| `classes/bo_availability/conditions/noshoppingcart.php` | `mod_booking\bo_availability\conditions\noshoppingcart` | Condition | 253 |
| `classes/bo_availability/conditions/capbookingchoose.php` | `mod_booking\bo_availability\conditions\capbookingchoose` | Condition | 249 |
| `classes/bo_availability/conditions/slotmove.php` | `mod_booking\bo_availability\conditions\slotmove` | Condition | 234 |
| `classes/bo_availability/subconditions/isbookable.php` | `mod_booking\bo_availability\subconditions\isbookable` | Condition | 234 |
| `classes/bo_availability/subconditions/alreadybooked.php` | `mod_booking\bo_availability\subconditions\alreadybooked` | Condition | 233 |
| `classes/bo_availability/subconditions/priceisset.php` | `mod_booking\bo_availability\subconditions\priceisset` | Condition | 221 |
| `classes/bo_availability/condition_visibility_manager.php` | `mod_booking\bo_availability\condition_visibility_manager` | Service | 210 |
| `classes/bo_availability/subconditions/bookitbutton.php` | `mod_booking\bo_availability\subconditions\bookitbutton` | Condition | 205 |
| `classes/bo_availability/bo_condition.php` | `mod_booking\bo_availability\bo_condition` | DTO | 184 |
| `classes/bo_availability/condition_state_helper.php` | `mod_booking\bo_availability\condition_state_helper` | Service | 158 |
| `classes/bo_availability/bo_subcondition.php` | `mod_booking\bo_availability\bo_subcondition` | DTO | 137 |
| `classes/bo_availability/freezable_condition.php` | `mod_booking\bo_availability\freezable_condition` | DTO | 50 |

## S04 — booking_process_bookit  ([Doc](subsystems/S04_booking_process_bookit.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/booking_bookit.php` | `mod_booking\booking_bookit` | Service | 745 |
| `classes/elective.php` | `mod_booking\elective` | Domaenenobjekt | 698 |
| `classes/local/book_all_students.php` | `mod_booking\local\book_all_students` | Service | 534 |
| `classes/bo_actions/actions_info.php` | `mod_booking\bo_actions\actions_info` | Service | 350 |
| `classes/booking_subbookit.php` | `mod_booking\booking_subbookit` | Service | 326 |
| `classes/bo_actions/action_types/executerestscript.php` | `mod_booking\bo_actions\action_types\executerestscript` | Condition | 323 |
| `classes/bo_actions/action_types/userprofilefield.php` | `mod_booking\bo_actions\action_types\userprofilefield` | Condition | 168 |
| `classes/bo_actions/action_types/bookotheroptions.php` | `mod_booking\bo_actions\action_types\bookotheroptions` | Condition | 148 |
| `classes/bo_actions/booking_action.php` | `mod_booking\bo_actions\booking_action` | Condition | 127 |
| `classes/bookit_request_overrides.php` | `mod_booking\bookit_request_overrides` | DTO | 117 |
| `classes/local/confirmationworkflow/confirmation.php` | `mod_booking\local\confirmationworkflow\confirmation` | Service | 101 |
| `classes/bo_actions/action_types/cancelbooking.php` | `mod_booking\bo_actions\action_types\cancelbooking` | Condition | 90 |

## S05 — pricing_shoppingcart  ([Doc](subsystems/S05_pricing_shoppingcart.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/price.php` | `mod_booking\price` | Service/Domänenobjekt (Preise + Formel + Kampagnen) | 1341 |
| `classes/shopping_cart/service_provider.php` | `mod_booking\shopping_cart\service_provider` | Integration/Callback-Adapter zu local_shopping_cart | 1022 |
| `classes/local/pricecategories_handler.php` | `mod_booking\local\pricecategories_handler` | Service (CRUD Preiskategorien) | 226 |

## S06 — booking_rules  ([Doc](subsystems/S06_booking_rules.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/booking_rules/rules_info.php` | `mod_booking\booking_rules\rules_info` | Service/Orchestrator | 687 |
| `classes/booking_rules/rules/rule_react_on_event.php` | `mod_booking\booking_rules\rules\rule_react_on_event` | Rule/Trigger | 679 |
| `classes/booking_rules/rules/rule_specifictime.php` | `mod_booking\booking_rules\rules\rule_specifictime` | Rule/Trigger | 534 |
| `classes/booking_rules/rules/rule_daysbefore.php` | `mod_booking\booking_rules\rules\rule_daysbefore` | Rule/Trigger | 497 |
| `classes/booking_rules/conditions/select_user_shopping_cart.php` | `mod_booking\booking_rules\conditions\select_user_shopping_cart` | Condition | 313 |
| `classes/booking_rules/actions/send_mail_interval.php` | `mod_booking\booking_rules\actions\send_mail_interval` | Action | 279 |
| `classes/booking_rules/conditions/select_user_from_event.php` | `mod_booking\booking_rules\conditions\select_user_from_event` | Condition | 261 |
| `classes/booking_rules/conditions/select_deputy_of_supervisor.php` | `mod_booking\booking_rules\conditions\select_deputy_of_supervisor` | Condition | 248 |
| `classes/booking_rules/conditions/enter_userprofilefield.php` | `mod_booking\booking_rules\conditions\enter_userprofilefield` | Condition | 248 |
| `classes/booking_rules/conditions/match_userprofilefield.php` | `mod_booking\booking_rules\conditions\match_userprofilefield` | Condition | 248 |
| `classes/booking_rules/actions/send_mail.php` | `mod_booking\booking_rules\actions\send_mail` | Action | 243 |
| `classes/booking_rules/actions/confirm_bookinganswer.php` | `mod_booking\booking_rules\actions\confirm_bookinganswer` | Action | 240 |
| `classes/booking_rules/actions/send_copy_of_mail.php` | `mod_booking\booking_rules\actions\send_copy_of_mail` | Action | 235 |
| `classes/booking_rules/conditions/select_student_in_bo.php` | `mod_booking\booking_rules\conditions\select_student_in_bo` | Condition | 227 |
| `classes/booking_rules/conditions/select_users_from_userfield_of_eventuser.php` | `mod_booking\booking_rules\conditions\select_users_from_userfield_of_eventuser` | Condition | 218 |
| `classes/booking_rules/conditions/select_users.php` | `mod_booking\booking_rules\conditions\select_users` | Condition | 202 |
| `classes/booking_rules/conditions/select_responsible_contact_in_bo.php` | `mod_booking\booking_rules\conditions\select_responsible_contact_in_bo` | Condition | 199 |
| `classes/booking_rules/booking_rules.php` | `mod_booking\booking_rules\booking_rules` | Service/Repository | 189 |
| `classes/booking_rules/conditions/select_booking_manager.php` | `mod_booking\booking_rules\conditions\select_booking_manager` | Condition | 171 |
| `classes/booking_rules/conditions/select_teacher_in_bo.php` | `mod_booking\booking_rules\conditions\select_teacher_in_bo` | Condition | 159 |
| `classes/booking_rules/actions/delete_conditions_from_bookinganswer.php` | `mod_booking\booking_rules\actions\delete_conditions_from_bookinganswer` | Action | 157 |
| `classes/booking_rules/conditions_info.php` | `mod_booking\booking_rules\conditions_info` | Service/Discovery | 153 |
| `classes/booking_rules/actions_info.php` | `mod_booking\booking_rules\actions_info` | Service/Discovery | 149 |
| `classes/booking_rules/booking_rule.php` | `mod_booking\booking_rules\booking_rule` | Interface | 103 |
| `classes/booking_rules/booking_rule_action.php` | `mod_booking\booking_rules\booking_rule_action` | Interface | 97 |
| `classes/booking_rules/booking_rule_condition.php` | `mod_booking\booking_rules\booking_rule_condition` | Interface | 96 |
| `classes/booking_rules/rules/templates/ruletemplate_bookingoption_booked.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoption_booked` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_confirmwaitinglist.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_confirmwaitinglist` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_userstorno.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_userstorno` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_courseupdate.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_courseupdate` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_bookingoptioncompleted.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoptioncompleted` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_usercancellation.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_usercancellation` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_paymentconfirmation.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_paymentconfirmation` | Template/Seed | 94 |
| `classes/booking_rules/rules/templates/ruletemplate_userpoll.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_userpoll` | Template/Seed | 92 |
| `classes/booking_rules/rules/templates/ruletemplate_trainercancellation.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_trainercancellation` | Template/Seed | 92 |
| `classes/booking_rules/rules/templates/ruletemplate_daysbeforestart.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_daysbeforestart` | Template/Seed | 91 |
| `classes/booking_rules/rules/templates/ruletemplate_trainerpoll.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_trainerpoll` | Template/Seed | 90 |
| `classes/booking_rules/rules/templates/ruletemplate_trainerreminderbeforestart.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_trainerreminderbeforestart` | Template/Seed | 89 |
| `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacheradded.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_optiondatesteacheradded` | Template/Seed | 88 |
| `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacherdeleted.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_optiondatesteacherdeleted` | Template/Seed | 88 |
| `classes/booking_rules/rules/templates/ruletemplate_bookingoptionuncompleted.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoptionuncompleted` | Template/Seed | 87 |
| `classes/booking_rules/rules/templates/ruletemplate_sessionreminders.php` | `mod_booking\booking_rules\rules\templates\ruletemplate_sessionreminders` | Template/Seed | 84 |

## S07 — campaigns  ([Doc](subsystems/S07_campaigns.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/booking_campaigns/campaigns_info.php` | `mod_booking\booking_campaigns\campaigns_info` | Service/Factory | 570 |
| `classes/booking_campaigns/campaigns/campaign_customfield.php` | `mod_booking\booking_campaigns\campaigns\campaign_customfield` | Domaenenobjekt/Kampagnentyp | 438 |
| `classes/booking_campaigns/campaigns/campaign_blockbooking.php` | `mod_booking\booking_campaigns\campaigns\campaign_blockbooking` | Domaenenobjekt/Kampagnentyp | 418 |
| `classes/booking_campaigns/booking_campaign.php` | `mod_booking\booking_campaigns\booking_campaign` | Interface/Extension-Point | 126 |

## S08 — subbookings  ([Doc](subsystems/S08_subbookings.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/subbookings/subbookings_info.php` | `mod_booking\subbookings\subbookings_info` | Service/Factory/Status-Maschine | 613 |
| `classes/subbookings/sb_types/subbooking_timeslot.php` | `mod_booking\subbookings\sb_types\subbooking_timeslot` | sb_type | 545 |
| `classes/subbookings/sb_types/subbooking_additionalperson.php` | `mod_booking\subbookings\sb_types\subbooking_additionalperson` | sb_type | 529 |
| `classes/subbookings/sb_types/subbooking_additionalitem.php` | `mod_booking\subbookings\sb_types\subbooking_additionalitem` | sb_type | 480 |
| `classes/subbookings/booking_subbooking.php` | `mod_booking\subbookings\booking_subbooking` | Interface/Extension-Point | 169 |
| `classes/subbookings.php` | `mod_booking\subbookings` | Domaenenobjekt | 98 |
| `classes/subbookings/subbookings_cache.php` | `mod_booking\subbookings\subbookings_cache` | Util/Cache-Marker | 36 |

## S09 — messaging_placeholders  ([Doc](subsystems/S09_messaging_placeholders.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/message_controller.php` | `mod_booking\message_controller` | Service | 1052 |
| `classes/placeholders/placeholders_info.php` | `mod_booking\placeholders\placeholders_info` | Service | 314 |
| `classes/local/scheduledmails.php` | `mod_booking\local\scheduledmails` | Service | 232 |
| `classes/placeholders/placeholders/customfields.php` | `mod_booking\placeholders\placeholders\customfields` | Field | 186 |
| `classes/placeholders/placeholders/datesandentities.php` | `mod_booking\placeholders\placeholders\datesandentities` | Field | 146 |
| `classes/placeholders/placeholders/customform.php` | `mod_booking\placeholders\placeholders\customform` | Placeholder | 145 |
| `classes/placeholders/placeholders/pollurl.php` | `mod_booking\placeholders\placeholders\pollurl` | Field | 144 |
| `classes/placeholders/placeholders/pollurlteachers.php` | `mod_booking\placeholders\placeholders\pollurlteachers` | Field | 144 |
| `classes/placeholders/placeholders/description.php` | `mod_booking\placeholders\placeholders\description` | Field | 132 |
| `classes/placeholders/placeholders/bookingoptionname.php` | `mod_booking\placeholders\placeholders\bookingoptionname` | Field | 129 |
| `classes/placeholders/placeholders/bookingoptiondetaillink.php` | `mod_booking\placeholders\placeholders\bookingoptiondetaillink` | Field | 126 |
| `classes/placeholders/placeholders/qrenrollink.php` | `mod_booking\placeholders\placeholders\qrenrollink` | Field | 122 |
| `classes/placeholders/placeholders/coursename.php` | `mod_booking\placeholders\placeholders\coursename` | Field | 121 |
| `classes/placeholders/placeholders/enrollink.php` | `mod_booking\placeholders\placeholders\enrollink` | Field | 121 |
| `classes/placeholders/placeholders/bookingdetails.php` | `mod_booking\placeholders\placeholders\bookingdetails` | Field | 119 |
| `classes/placeholders/placeholders/courseid.php` | `mod_booking\placeholders\placeholders\courseid` | Field | 119 |
| `classes/placeholders/placeholders/optionid.php` | `mod_booking\placeholders\placeholders\optionid` | Field | 119 |
| `classes/placeholders/placeholders/profilepicture.php` | `mod_booking\placeholders\placeholders\profilepicture` | Field | 119 |
| `classes/placeholders/placeholders/selflearningcourse.php` | `mod_booking\placeholders\placeholders\selflearningcourse` | Field | 119 |
| `classes/placeholders/placeholders/gotobookingoption.php` | `mod_booking\placeholders\placeholders\gotobookingoption` | Field | 117 |
| `classes/placeholders/placeholders/type.php` | `mod_booking\placeholders\placeholders\type` | Field | 117 |
| `classes/placeholders/placeholders/bookedslotsfromevent.php` | `mod_booking\placeholders\placeholders\bookedslotsfromevent` | Field | 115 |
| `classes/placeholders/placeholders/dates.php` | `mod_booking\placeholders\placeholders\dates` | Field | 113 |
| `classes/placeholders/placeholders/emailrelated.php` | `mod_booking\placeholders\placeholders\emailrelated` | Field | 112 |
| `classes/placeholders/placeholders/firstnamerelated.php` | `mod_booking\placeholders\placeholders\firstnamerelated` | Field | 112 |
| `classes/placeholders/placeholders/lastnamerelated.php` | `mod_booking\placeholders\placeholders\lastnamerelated` | Field | 112 |
| `classes/placeholders/placeholders/changes.php` | `mod_booking\placeholders\placeholders\changes` | Field | 110 |
| `classes/placeholders/placeholders/bookinglink.php` | `mod_booking\placeholders\placeholders\bookinglink` | Field | 110 |
| `classes/placeholders/placeholders/courselink.php` | `mod_booking\placeholders\placeholders\courselink` | Field | 110 |
| `classes/placeholders/placeholders/email.php` | `mod_booking\placeholders\placeholders\email` | Field | 110 |
| `classes/placeholders/placeholders/firstname.php` | `mod_booking\placeholders\placeholders\firstname` | Field | 110 |
| `classes/placeholders/placeholders/journal.php` | `mod_booking\placeholders\placeholders\journal` | Field | 110 |
| `classes/placeholders/placeholders/lastname.php` | `mod_booking\placeholders\placeholders\lastname` | Field | 110 |
| `classes/placeholders/placeholders/qrusername.php` | `mod_booking\placeholders\placeholders\qrusername` | Field | 108 |
| `classes/placeholders/placeholders/shoppingcartplaceholder.php` | `mod_booking\placeholders\placeholders\shoppingcartplaceholder` | Field | 108 |
| `classes/placeholders/placeholders/baid.php` | `mod_booking\placeholders\placeholders\baid` | Field | 106 |
| `classes/placeholders/placeholders/coursecalendarurl.php` | `mod_booking\placeholders\placeholders\coursecalendarurl` | Field | 105 |
| `classes/placeholders/placeholders/qrid.php` | `mod_booking\placeholders\placeholders\qrid` | Field | 105 |
| `classes/placeholders/placeholders/status.php` | `mod_booking\placeholders\placeholders\status` | Field | 105 |
| `classes/placeholders/placeholders/optiondatefromevent.php` | `mod_booking\placeholders\placeholders\optiondatefromevent` | Field | 104 |
| `classes/placeholders/placeholders/semester.php` | `mod_booking\placeholders\placeholders\semester` | Field | 104 |
| `classes/placeholders/placeholders/usercalendarurl.php` | `mod_booking\placeholders\placeholders\usercalendarurl` | Field | 104 |
| `classes/placeholders/placeholders/bookingreportlink.php` | `mod_booking\placeholders\placeholders\bookingreportlink` | Field | 103 |
| `classes/placeholders/placeholders/enddate.php` | `mod_booking\placeholders\placeholders\enddate` | Field | 103 |
| `classes/placeholders/placeholders/endtime.php` | `mod_booking\placeholders\placeholders\endtime` | Field | 103 |
| `classes/placeholders/placeholders/startdate.php` | `mod_booking\placeholders\placeholders\startdate` | Field | 103 |
| `classes/placeholders/placeholders/numberparticipants.php` | `mod_booking\placeholders\placeholders\numberparticipants` | Field | 102 |
| `classes/placeholders/placeholders/numberwaitinglist.php` | `mod_booking\placeholders\placeholders\numberwaitinglist` | Field | 102 |
| `classes/placeholders/placeholders/pollstartdate.php` | `mod_booking\placeholders\placeholders\pollstartdate` | Field | 102 |
| `classes/placeholders/placeholders/instancename.php` | `mod_booking\placeholders\placeholders\instancename` | Field | 101 |
| `classes/placeholders/placeholders/certificateurl.php` | `mod_booking\placeholders\placeholders\certificateurl` | Field | 100 |
| `classes/placeholders/placeholders/department.php` | `mod_booking\placeholders\placeholders\department` | Field | 100 |
| `classes/placeholders/placeholders/duration.php` | `mod_booking\placeholders\placeholders\duration` | Field | 100 |
| `classes/placeholders/placeholders/eventtype.php` | `mod_booking\placeholders\placeholders\eventtype` | Field | 100 |
| `classes/placeholders/placeholders/institution.php` | `mod_booking\placeholders\placeholders\institution` | Field | 100 |
| `classes/placeholders/placeholders/location.php` | `mod_booking\placeholders\placeholders\location` | Field | 100 |
| `classes/placeholders/placeholders/participant.php` | `mod_booking\placeholders\placeholders\participant` | Field | 100 |
| `classes/placeholders/placeholders/price.php` | `mod_booking\placeholders\placeholders\price` | Field | 100 |
| `classes/placeholders/placeholders/starttime.php` | `mod_booking\placeholders\placeholders\starttime` | Field | 100 |
| `classes/local/templaterule.php` | `mod_booking\local\templaterule` | Service | 99 |
| `classes/placeholders/placeholders/eventdescription.php` | `mod_booking\placeholders\placeholders\eventdescription` | Field | 99 |
| `classes/placeholders/placeholders/bookingconfirmationlink.php` | `mod_booking\placeholders\placeholders\bookingconfirmationlink` | Field | 98 |
| `classes/placeholders/placeholders/address.php` | `mod_booking\placeholders\placeholders\address` | Field | 97 |
| `classes/placeholders/placeholders/teacher.php` | `mod_booking\placeholders\placeholders\teacher` | Field | 97 |
| `classes/placeholders/placeholders/teachers.php` | `mod_booking\placeholders\placeholders\teachers` | Field | 97 |
| `classes/placeholders/placeholders/username.php` | `mod_booking\placeholders\placeholders\username` | Field | 97 |
| `classes/placeholders/placeholders/restresponse.php` | `mod_booking\placeholders\placeholders\restresponse` | Field | 92 |
| `classes/placeholders/placeholders/title.php` | `mod_booking\placeholders\placeholders\title` | Field | 90 |
| `classes/placeholders/placeholders/userid.php` | `mod_booking\placeholders\placeholders\userid` | Field | 87 |
| `classes/placeholders/placeholders/duedate.php` | `mod_booking\placeholders\placeholders\duedate` | Field | 82 |
| `classes/placeholders/placeholders/bookedplaces.php` | `mod_booking\placeholders\placeholders\bookedplaces` | Field | 81 |
| `classes/placeholders/placeholders/numberofinstallment.php` | `mod_booking\placeholders\placeholders\numberofinstallment` | Field | 79 |
| `classes/placeholders/placeholders/installmentprice.php` | `mod_booking\placeholders\placeholders\installmentprice` | Field | 77 |
| `classes/placeholders/placeholders/slotsbooked.php` | `mod_booking\placeholders\placeholders\slotsbooked` | Field | 75 |
| `classes/placeholders/placeholders/slotscancelled.php` | `mod_booking\placeholders\placeholders\slotscancelled` | Field | 75 |
| `classes/placeholders/placeholders/slotsmovedfrom.php` | `mod_booking\placeholders\placeholders\slotsmovedfrom` | Field | 75 |
| `classes/placeholders/placeholders/slotsmovedto.php` | `mod_booking\placeholders\placeholders\slotsmovedto` | Field | 75 |
| `classes/placeholders/placeholder_base.php` | `mod_booking\placeholders\placeholder_base` | DTO | 58 |

## S10 — output_rendering  ([Doc](subsystems/S10_output_rendering.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/shortcodes.php` | `mod_booking\shortcodes` | Script | 2350 |
| `classes/table/bookingoptions_wbtable.php` | `mod_booking\table\bookingoptions_wbtable` | WB-Table | 2096 |
| `classes/output/view.php` | `mod_booking\output\view` | Renderer | 1923 |
| `classes/table/manageusers_table.php` | `mod_booking\table\manageusers_table` | WB-Table | 1086 |
| `classes/output/mobile.php` | `mod_booking\output\mobile` | Renderer | 917 |
| `classes/output/renderer.php` | `mod_booking\output\renderer` | Renderer | 854 |
| `classes/output/bookingoption_description.php` | `mod_booking\output\bookingoption_description` | DTO | 809 |
| `classes/output/booked_users.php` | `mod_booking\output\booked_users` | DTO | 634 |
| `classes/table/teachers_instance_report_table.php` | `mod_booking\table\teachers_instance_report_table` | WB-Table | 477 |
| `classes/local/htmlcomponents.php` | `mod_booking\local\htmlcomponents` | Util | 423 |
| `classes/output/page_teacher.php` | `mod_booking\output\page_teacher` | DTO | 335 |
| `classes/table/scheduledmails_table.php` | `mod_booking\table\scheduledmails_table` | WB-Table | 335 |
| `classes/shortcodes_handler.php` | `mod_booking\shortcodes_handler` | Util | 262 |
| `classes/table/optiondates_teachers_table.php` | `mod_booking\table\optiondates_teachers_table` | WB-Table | 260 |
| `classes/table/bookingoptions_simple_table.php` | `mod_booking\table\bookingoptions_simple_table` | WB-Table | 202 |
| `classes/table/booking_history_table.php` | `mod_booking\table\booking_history_table` | WB-Table | 186 |
| `classes/output/col_coursestarttime.php` | `mod_booking\output\col_coursestarttime` | DTO | 186 |
| `classes/output/bookit_price.php` | `mod_booking\output\bookit_price` | DTO | 178 |
| `classes/output/campaignslist.php` | `mod_booking\output\campaignslist` | DTO | 174 |
| `classes/output/col_price.php` | `mod_booking\output\col_price` | DTO | 171 |
| `classes/output/col_availableplaces.php` | `mod_booking\output\col_availableplaces` | DTO | 166 |
| `classes/output/elective_modal.php` | `mod_booking\output\elective_modal` | DTO | 164 |
| `classes/output/page_allteachers.php` | `mod_booking\output\page_allteachers` | DTO | 164 |
| `classes/output/scheduledmails.php` | `mod_booking\output\scheduledmails` | DTO | 160 |
| `classes/table/teacher_performed_units_table.php` | `mod_booking\table\teacher_performed_units_table` | WB-Table | 160 |
| `classes/output/certificateconditionslist.php` | `mod_booking\output\certificateconditionslist` | DTO | 156 |
| `classes/output/ruleslist.php` | `mod_booking\output\ruleslist` | DTO | 154 |
| `classes/output/prepagemodal.php` | `mod_booking\output\prepagemodal` | DTO | 151 |
| `classes/table/optiontemplatessettings_table.php` | `mod_booking\table\optiontemplatessettings_table` | WB-Table | 150 |
| `classes/output/description/description_base.php` | `mod_booking\output\description\description_base` | DTO | 147 |
| `classes/output/eventslist.php` | `mod_booking\output\eventslist` | DTO | 143 |
| `classes/output/bookingoption_changes.php` | `mod_booking\output\bookingoption_changes` | DTO | 143 |
| `classes/output/business_card.php` | `mod_booking\output\business_card` | DTO | 141 |
| `classes/table/bulkoperations_table.php` | `mod_booking\table\bulkoperations_table` | WB-Table | 126 |
| `classes/table/event_log_table.php` | `mod_booking\table\event_log_table` | WB-Table | 124 |
| `classes/output/button_notifyme.php` | `mod_booking\output\button_notifyme` | DTO | 117 |
| `classes/output/prepageinlinestart.php` | `mod_booking\output\prepageinlinestart` | DTO | 115 |
| `classes/output/signin_downloadform.php` | `mod_booking\output\signin_downloadform` | DTO | 114 |
| `classes/output/coursepage_shortinfo_and_button.php` | `mod_booking\output\coursepage_shortinfo_and_button` | DTO | 111 |
| `classes/output/bookit_button.php` | `mod_booking\output\bookit_button` | DTO | 106 |
| `classes/output/subbooking_timeslot_output.php` | `mod_booking\output\subbooking_timeslot_output` | DTO | 105 |
| `classes/output/semesters_holidays.php` | `mod_booking\output\semesters_holidays` | DTO | 99 |
| `classes/table/instancetemplatessettings_table.php` | `mod_booking\table\instancetemplatessettings_table` | WB-Table | 95 |
| `classes/output/col_teacher.php` | `mod_booking\output\col_teacher` | DTO | 95 |
| `classes/bookinginstancetemplatessettings_table.php` | `mod_booking\bookinginstancetemplatessettings_table` | Renderer/Table | 93 |
| `classes/output/subbooking_additionalitem_output.php` | `mod_booking\output\subbooking_additionalitem_output` | DTO | 89 |
| `classes/output/report_edit_bookingnotes.php` | `mod_booking\output\report_edit_bookingnotes` | DTO | 86 |
| `classes/output/subbooking_additionalperson_output.php` | `mod_booking\output\subbooking_additionalperson_output` | DTO | 85 |
| `classes/output/col_text_with_description.php` | `mod_booking\output\col_text_with_description` | DTO | 85 |
| `classes/output/actionslist.php` | `mod_booking\output\actionslist` | DTO | 85 |
| `classes/output/subbookingslist.php` | `mod_booking\output\subbookingslist` | DTO | 84 |
| `classes/output/optiondates_only.php` | `mod_booking\output\optiondates_only` | DTO | 83 |
| `classes/output/instance_description.php` | `mod_booking\output\instance_description` | DTO | 83 |
| `classes/output/optiondates_with_entities.php` | `mod_booking\output\optiondates_with_entities` | DTO | 78 |
| `classes/output/col_responsiblecontacts.php` | `mod_booking\output\col_responsiblecontacts` | DTO | 76 |
| `classes/local/shortcode_filterfield.php` | `mod_booking\local\shortcode_filterfield` | Field | 76 |
| `classes/output/col_action.php` | `mod_booking\output\col_action` | DTO | 73 |
| `classes/filters/available_places.php` | `mod_booking\filters\available_places` | Filter | 72 |
| `classes/output/pricecategories.php` | `mod_booking\output\pricecategories` | DTO | 70 |
| `classes/output/bookingoption_dates.php` | `mod_booking\output\bookingoption_dates` | DTO | 66 |
| `classes/output/col_text.php` | `mod_booking\output\col_text` | DTO | 63 |
| `classes/output/description/description_ical.php` | `mod_booking\output\description\description_ical` | DTO | 57 |
| `classes/output/description/description_calendarevent.php` | `mod_booking\output\description\description_calendarevent` | DTO | 57 |
| `classes/output/description/description_optionview.php` | `mod_booking\output\description\description_optionview` | DTO | 54 |
| `classes/output/description/description_website.php` | `mod_booking\output\description\description_website` | DTO | 38 |
| `classes/output/description/description_mail.php` | `mod_booking\output\description\description_mail` | DTO | 38 |
| `classes/output/description/description_cartitem.php` | `mod_booking\output\description\description_cartitem` | DTO | 38 |
| `classes/output/description/description_teachers.php` | `mod_booking\output\description\description_teachers` | DTO | 32 |
| `classes/output/description/description_dates.php` | `mod_booking\output\description\description_dates` | DTO | 32 |

## S11 — external_api  ([Doc](subsystems/S11_external_api.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/external/addbookingoption.php` | `mod_booking\external\addbookingoption` | WS | 533 |
| `classes/external/bookings.php` | `mod_booking\external\bookings` | WS | 303 |
| `classes/external/get_submission_mobile.php` | `mod_booking\external\get_submission_mobile` | WS | 226 |
| `classes/external/save_slot_selection.php` | `mod_booking\external\save_slot_selection` | WS | 183 |
| `classes/external/categories.php` | `mod_booking\external\categories` | WS | 151 |
| `classes/external/bookit.php` | `mod_booking\external\bookit` | WS | 142 |
| `classes/external/get_parent_categories.php` | `mod_booking\external\get_parent_categories` | WS | 142 |
| `classes/external/load_pre_booking_page.php` | `mod_booking\external\load_pre_booking_page` | WS | 126 |
| `classes/external/search_sync_sources.php` | `mod_booking\external\search_sync_sources` | WS | 125 |
| `classes/external/delete_measurement.php` | `mod_booking\external\delete_measurement` | WS | 124 |
| `classes/external/get_booking_option_description.php` | `mod_booking\external\get_booking_option_description` | WS | 119 |
| `classes/external/update_bookingnotes.php` | `mod_booking\external\update_bookingnotes` | WS | 119 |
| `classes/external/save_option_field_config.php` | `mod_booking\external\save_option_field_config` | WS | 116 |
| `classes/external/set_checked_booking_instance.php` | `mod_booking\external\set_checked_booking_instance` | WS | 114 |
| `classes/external/release_slots.php` | `mod_booking\external\release_slots` | WS | 109 |
| `classes/external/save_measurement.php` | `mod_booking\external\save_measurement` | WS | 109 |
| `classes/external/allow_add_item_to_cart.php` | `mod_booking\external\allow_add_item_to_cart` | WS | 108 |
| `classes/external/get_option_field_config.php` | `mod_booking\external\get_option_field_config` | WS | 97 |
| `classes/external/toggle_notify_user.php` | `mod_booking\external\toggle_notify_user` | WS | 97 |
| `classes/external/instancetemplate.php` | `mod_booking\external\instancetemplate` | WS | 96 |
| `classes/external/search_users.php` | `mod_booking\external\search_users` | WS | 96 |
| `classes/external/search_booking_options.php` | `mod_booking\external\search_booking_options` | WS | 93 |
| `classes/external/get_slots.php` | `mod_booking\external\get_slots` | WS | 92 |
| `classes/external/performance.php` | `mod_booking\external\performance` | WS | 92 |
| `classes/external/optiontemplate.php` | `mod_booking\external\optiontemplate` | WS | 91 |
| `classes/external/get_booked_slots.php` | `mod_booking\external\get_booked_slots` | WS | 88 |
| `classes/external/init_comments.php` | `mod_booking\external\init_comments` | WS | 88 |
| `classes/external/search_teachers.php` | `mod_booking\external\search_teachers` | WS | 86 |
| `classes/external/search_templates.php` | `mod_booking\external\search_templates` | WS | 86 |
| `classes/external/search_courses.php` | `mod_booking\external\search_courses` | WS | 83 |
| `classes/external/get_performance_chart.php` | `mod_booking\external\get_performance_chart` | WS | 82 |

## S12 — events  ([Doc](subsystems/S12_events.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/observer.php` | `mod_booking_observer` | Observer/Dispatcher | 786 |
| `classes/event/bookinganswer_slotmoved.php` | `mod_booking\event\bookinganswer_slotmoved` | Event (Slotbooking) | 195 |
| `classes/event/bookingoption_updated.php` | `mod_booking\event\bookingoption_updated` | Event (Renderer-gestuetzt) | 157 |
| `classes/event/message_sent.php` | `mod_booking\event\message_sent` | Event (HTML-Desc) | 157 |
| `classes/event/custom_message_sent.php` | `mod_booking\event\custom_message_sent` | Event (HTML-Desc) | 147 |
| `classes/event/bookinganswer_presencechanged.php` | `mod_booking\event\bookinganswer_presencechanged` | Event | 109 |
| `classes/event/bookinganswer_cancelled.php` | `mod_booking\event\bookinganswer_cancelled` | Event | 108 |
| `classes/event/bookinganswer_notesedited.php` | `mod_booking\event\bookinganswer_notesedited` | Event | 108 |
| `classes/event/bookinganswer_slotbooked.php` | `mod_booking\event\bookinganswer_slotbooked` | Event (Slotbooking) | 100 |
| `classes/event/bookinganswer_slotcancelled.php` | `mod_booking\event\bookinganswer_slotcancelled` | Event (Slotbooking) | 100 |
| `classes/event/bookinganswercustomformconditions_deleted.php` | `mod_booking\event\bookinganswercustomformconditions_deleted` | Event | 99 |
| `classes/event/bookingoption_booked.php` | `mod_booking\event\bookingoption_booked` | Event | 99 |
| `classes/event/bookingoptionwaitinglist_booked.php` | `mod_booking\event\bookingoptionwaitinglist_booked` | Event | 99 |
| `classes/event/bookinginstance_updated.php` | `mod_booking\event\bookinginstance_updated` | Event | 98 |
| `classes/event/bookingoption_uncompleted.php` | `mod_booking\event\bookingoption_uncompleted` | Event | 97 |
| `classes/event/bookingoption_bookedviaautoenrol.php` | `mod_booking\event\bookingoption_bookedviaautoenrol` | Event | 96 |
| `classes/event/bookinganswer_waitingforconfirmation.php` | `mod_booking\event\bookinganswer_waitingforconfirmation` | Event | 95 |
| `classes/event/bookingoption_completed.php` | `mod_booking\event\bookingoption_completed` | Event | 95 |
| `classes/event/certificate_issued.php` | `mod_booking\event\certificate_issued` | Event | 95 |
| `classes/event/bookinganswer_confirmed.php` | `mod_booking\event\bookinganswer_confirmed` | Event | 91 |
| `classes/event/bookinganswer_denied.php` | `mod_booking\event\bookinganswer_denied` | Event | 90 |
| `classes/event/records_imported.php` | `mod_booking\event\records_imported` | Event | 86 |
| `classes/event/teacher_added.php` | `mod_booking\event\teacher_added` | Event | 86 |
| `classes/event/teacher_removed.php` | `mod_booking\event\teacher_removed` | Event | 86 |
| `classes/event/bookinganswer_movedupfromwaitinglist.php` | `mod_booking\event\bookinganswer_movedupfromwaitinglist` | Event | 84 |
| `classes/event/optiondates_teacher_added.php` | `mod_booking\event\optiondates_teacher_added` | Event | 81 |
| `classes/event/optiondates_teacher_deleted.php` | `mod_booking\event\optiondates_teacher_deleted` | Event | 81 |
| `classes/event/bookingoptiondate_deleted.php` | `mod_booking\event\bookingoptiondate_deleted` | Event | 80 |
| `classes/event/bookingoption_created.php` | `mod_booking\event\bookingoption_created` | Event | 79 |
| `classes/event/bookingoptiondate_created.php` | `mod_booking\event\bookingoptiondate_created` | Event | 79 |
| `classes/event/bookingoption_freetobookagain.php` | `mod_booking\event\bookingoption_freetobookagain` | Event | 78 |
| `classes/event/rest_script_failed.php` | `mod_booking\event\rest_script_failed` | Event (Diagnose) | 78 |
| `classes/event/bookingoption_cancelled.php` | `mod_booking\event\bookingoption_cancelled` | Event | 77 |
| `classes/event/bookingoption_deleted.php` | `mod_booking\event\bookingoption_deleted` | Event | 77 |
| `classes/event/booking_afteractionsfailed.php` | `mod_booking\event\booking_afteractionsfailed` | Event (Fehler) | 77 |
| `classes/event/booking_failed.php` | `mod_booking\event\booking_failed` | Event (Fehler) | 77 |
| `classes/event/booking_rulesexecutionfailes.php` | `mod_booking\event\booking_rulesexecutionfailed` | Event (Fehler) | 77 |
| `classes/event/rest_script_success.php` | `mod_booking\event\rest_script_success` | Event (Diagnose) | 76 |
| `classes/event/custom_bulk_message_sent.php` | `mod_booking\event\custom_bulk_message_sent` | Event | 74 |
| `classes/event/custom_field_changed.php` | `mod_booking\event\custom_field_changed` | Event | 72 |
| `classes/event/booking_debug.php` | `mod_booking\event\booking_debug` | Event (Debug) | 72 |
| `classes/event/report_viewed.php` | `mod_booking\event\report_viewed` | Event | 71 |
| `classes/event/pricecategory_changed.php` | `mod_booking\event\pricecategory_changed` | Event | 70 |
| `classes/event/reminder1_sent.php` | `mod_booking\event\reminder1_sent` | Event | 69 |
| `classes/event/reminder2_sent.php` | `mod_booking\event\reminder2_sent` | Event | 69 |
| `classes/event/enrollink_triggered.php` | `mod_booking\event\enrollink_triggered` | Event | 68 |
| `classes/event/reminder_teacher_sent.php` | `mod_booking\event\reminder_teacher_sent` | Event | 68 |
| `classes/event/course_module_viewed.php` | `mod_booking\event\course_module_viewed` | Event (Core-Subclass) | 44 |

## S13 — tasks  ([Doc](subsystems/S13_tasks.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/task/send_reminder_mails.php` | `mod_booking\task\send_reminder_mails` | Task | 279 |
| `classes/task/confirm_bookinganswer_by_rule_adhoc.php` | `mod_booking\task\confirm_bookinganswer_by_rule_adhoc` | Task | 239 |
| `classes/task/send_mail_by_rule_adhoc.php` | `mod_booking\task\send_mail_by_rule_adhoc` | Task | 203 |
| `classes/task/delete_conditions_from_bookinganswer_by_rule_adhoc.php` | `mod_booking\task\delete_conditions_from_bookinganswer_by_rule_adhoc` | Task | 189 |
| `classes/task/send_notification_mails.php` | `mod_booking\task\send_notification_mails` | Task | 164 |
| `classes/task/finalize_template_course.php` | `mod_booking\task\finalize_template_course` | Task | 160 |
| `classes/task/purge_campaign_caches.php` | `mod_booking\task\purge_campaign_caches` | Task | 159 |
| `classes/task/send_confirmation_mails.php` | `mod_booking\task\send_confirmation_mails` | Task | 157 |
| `classes/task/enrol_bookedusers_tocourse.php` | `mod_booking\task\enrol_bookedusers_tocourse` | Task | 127 |
| `classes/task/recalculate_prices.php` | `mod_booking\task\recalculate_prices` | Task | 105 |
| `classes/task/send_completion_mails.php` | `mod_booking\task\send_completion_mails` | Task | 100 |
| `classes/task/remove_activity_completion.php` | `mod_booking\task\remove_activity_completion` | Task | 94 |
| `classes/task/clean_booking_db.php` | `mod_booking\task\clean_booking_db` | Task | 78 |
| `classes/task/check_answers.php` | `mod_booking\task\check_answers` | Task | 77 |
| `classes/task/task_adhoc_reset_optiondates_for_semester.php` | `mod_booking\task\task_adhoc_reset_optiondates_for_semester` | Task | 73 |
| `classes/task/assign_competency.php` | `mod_booking\task\assign_competency` | Task | 72 |
| `classes/task/book_all_students_task.php` | `mod_booking\task\book_all_students_task` | Task | 67 |
| `classes/task/cleanup_invalid_scheduled_mails.php` | `mod_booking\task\cleanup_invalid_scheduled_mails` | Task | 67 |
| `classes/task/process_source_membership_adhoc.php` | `mod_booking\task\process_source_membership_adhoc` | Task | 55 |

## S14 — slotbooking  ([Doc](subsystems/S14_slotbooking.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/local/slotbooking/slot_availability.php` | `mod_booking\local\slotbooking\slot_availability` | Service | 1422 |
| `classes/local/slotbooking/slot_mover.php` | `mod_booking\local\slotbooking\slot_mover` | Service | 916 |
| `classes/local/slotbooking/slot_update_service.php` | `mod_booking\local\slotbooking\slot_update_service` | Service | 499 |
| `classes/local/slotbooking/slot_rules.php` | `mod_booking\local\slotbooking\slot_rules` | Service | 451 |
| `classes/local/slotbooking/slot_dto.php` | `mod_booking\local\slotbooking\slot_dto` | DTO | 432 |
| `classes/local/slotbooking/slot_move_store.php` | `mod_booking\local\slotbooking\slot_move_store` | DTO | 260 |
| `classes/local/slotbooking/slot_rule_manager.php` | `mod_booking\local\slotbooking\slot_rule_manager` | DTO | 198 |
| `classes/local/slotbooking/slot_price.php` | `mod_booking\local\slotbooking\slot_price` | Service | 158 |
| `classes/local/slotbooking/slot_change_policy.php` | `mod_booking\local\slotbooking\slot_change_policy` | Condition | 132 |
| `classes/local/slotbooking/target_price_policy.php` | `mod_booking\local\slotbooking\target_price_policy` | Condition | 127 |
| `classes/local/slotbooking/slot_answer.php` | `mod_booking\local\slotbooking\slot_answer` | DTO | 75 |
| `classes/local/slotbooking/slot_event_placeholders.php` | `mod_booking\local\slotbooking\slot_event_placeholders` | Renderer | 75 |
| `classes/local/slotbooking/slot_feature.php` | `mod_booking\local\slotbooking\slot_feature` | Condition | 51 |

## S15 — wizard_ai  ([Doc](subsystems/S15_wizard_ai.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/local/wizard/booking/booking_skill_support.php` | `mod_booking\local\wizard\booking\booking_skill_support` | Service | 2940 |
| `classes/local/wizard/options/skills/create_option_skill.php` | `mod_booking\local\wizard\options\skills\create_option_skill` | Skill | 1501 |
| `classes/local/wizard/booking/booking_skill_mutation_execute_service.php` | `mod_booking\local\wizard\booking\booking_skill_mutation_execute_service` | Service | 1449 |
| `classes/local/wizard/options/skills/booking_skill_base.php` | `mod_booking\local\wizard\options\skills\booking_skill_base` | Skill | 1224 |
| `classes/local/wizard/options/skills/diagnose_booking_issue_skill.php` | `mod_booking\local\wizard\options\skills\diagnose_booking_issue_skill` | Skill | 1140 |
| `classes/local/wizard/options/skills/diagnose_user_booking_skill.php` | `mod_booking\local\wizard\options\skills\diagnose_user_booking_skill` | Skill | 1001 |
| `classes/local/wizard/options/skills/diagnose_cancellation_issue_skill.php` | `mod_booking\local\wizard\options\skills\diagnose_cancellation_issue_skill` | Skill | 907 |
| `classes/local/wizard/options/skills/book_users_skill.php` | `mod_booking\local\wizard\options\skills\book_users_skill` | Skill | 741 |
| `classes/local/wizard/options/skills/get_option_details_skill.php` | `mod_booking\local\wizard\options\skills\get_option_details_skill` | Skill | 704 |
| `classes/local/wizard/booking/support/booking_rules_agent_service.php` | `mod_booking\local\wizard\booking\support\booking_rules_agent_service` | Service | 650 |
| `classes/local/wizard/options/skills/configure_booking_instance_skill.php` | `mod_booking\local\wizard\options\skills\configure_booking_instance_skill` | Skill | 625 |
| `classes/local/wizard/options/skills/option_schema_definition.php` | `mod_booking\local\wizard\options\skills\option_schema_definition` | DTO | 530 |
| `classes/local/wizard/options/skills/update_option_skill.php` | `mod_booking\local\wizard\options\skills\update_option_skill` | Skill | 479 |
| `classes/local/wizard/options/skills/create_rule_from_template_skill.php` | `mod_booking\local\wizard\options\skills\create_rule_from_template_skill` | Skill | 462 |
| `classes/local/wizard/options/skills/bulk_update_options_skill.php` | `mod_booking\local\wizard\options\skills\bulk_update_options_skill` | Skill | 414 |
| `classes/local/wizard/booking/support/booking_mutation_validation.php` | `mod_booking\local\wizard\booking\support\booking_mutation_validation` | Condition | 407 |
| `classes/local/wizard/options/skills/analyze_rules_skill.php` | `mod_booking\local\wizard\options\skills\analyze_rules_skill` | Skill | 400 |
| `classes/local/wizard/options/skills/create_slotbooking_option_skill.php` | `mod_booking\local\wizard\options\skills\create_slotbooking_option_skill` | Skill | 377 |
| `classes/local/wizard/options/skills/update_option_trainer_skill.php` | `mod_booking\local\wizard\options\skills\update_option_trainer_skill` | Skill | 374 |
| `classes/local/wizard/options/skills/search_options_skill.php` | `mod_booking\local\wizard\options\skills\search_options_skill` | Skill | 373 |
| `classes/local/wizard/options/skills/option_input_verification.php` | `mod_booking\local\wizard\options\skills\option_input_verification` | Condition | 353 |
| `classes/local/wizard/options/skills/update_rule_from_template_skill.php` | `mod_booking\local\wizard\options\skills\update_rule_from_template_skill` | Skill | 347 |
| `classes/local/wizard/options/skills/add_price_category_skill.php` | `mod_booking\local\wizard\options\skills\add_price_category_skill` | Skill | 284 |
| `classes/local/wizard/options/skills/list_option_properties_skill.php` | `mod_booking\local\wizard\options\skills\list_option_properties_skill` | Skill | 283 |
| `classes/local/wizard/booking/support/slot_booking_normalizer.php` | `mod_booking\local\wizard\booking\support\slot_booking_normalizer` | Util | 254 |
| `classes/local/wizard/options/skills/create_selflearning_option_skill.php` | `mod_booking\local\wizard\options\skills\create_selflearning_option_skill` | Skill | 177 |
| `classes/local/wizard/services/mutation/option_mutation_service.php` | `mod_booking\local\wizard\services\mutation\option_mutation_service` | Service | 138 |
| `classes/local/wizard/skill_provider.php` | `mod_booking\local\wizard\skill_provider` | Service | 126 |
| `classes/local/wizard/services/mutation/entity_mutation_service.php` | `mod_booking\local\wizard\services\mutation\entity_mutation_service` | Service | 97 |
| `classes/local/wizard/booking_option_preview_renderer.php` | `mod_booking\local\wizard\booking_option_preview_renderer` | Renderer | 94 |
| `classes/local/wizard/dto/create_option_input_dto.php` | `mod_booking\local\wizard\dto\create_option_input_dto` | DTO | 82 |
| `classes/local/wizard/dto/create_entity_input_dto.php` | `mod_booking\local\wizard\dto\create_entity_input_dto` | DTO | 82 |
| `classes/local/wizard/dto/bulk_update_options_input_dto.php` | `mod_booking\local\wizard\dto\bulk_update_options_input_dto` | DTO | 78 |
| `classes/local/wizard/dto/update_option_input_dto.php` | `mod_booking\local\wizard\dto\update_option_input_dto` | DTO | 78 |
| `classes/local/wizard/services/lookup/option_lookup_service.php` | `mod_booking\local\wizard\services\lookup\option_lookup_service` | Service | 75 |
| `classes/local/wizard/booking/booking_readiness_provider.php` | `mod_booking\local\wizard\booking\booking_readiness_provider` | Service | 73 |
| `classes/local/interfaces/bookingextension/confirmbooking_interface.php` | `mod_booking\local\interfaces\bookingextension\confirmbooking_interface` | Interface | 53 |
| `classes/local/wizard/booking/provider_skill_input_normalizer.php` | `mod_booking\local\wizard\booking\provider_skill_input_normalizer` | Service | 50 |
| `classes/local/wizard/booking/booking_skill_provider.php` | `mod_booking\local\wizard\booking\booking_skill_provider` | Service | 29 |

## S16 — forms  ([Doc](subsystems/S16_forms.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/form/teacherunavailability_form.php` | `mod_booking\form\teacherunavailability_form` | Form | 884 |
| `classes/form/condition/slotbooking_form.php` | `mod_booking\form\condition\slotbooking_form` | Form | 759 |
| `classes/form/editteachersforoptiondate_form.php` | `mod_booking\form\editteachersforoptiondate_form` | Form | 438 |
| `classes/form/slotteacherassignments_form.php` | `mod_booking\form\slotteacherassignments_form` | Form | 431 |
| `classes/form/condition/customform_form.php` | `mod_booking\form\condition\customform_form` | Form | 425 |
| `classes/form/rulesform.php` | `mod_booking\form\rulesform` | Form | 367 |
| `classes/form/modal_send_custom_message.php` | `mod_booking\form\modal_send_custom_message` | Form | 345 |
| `classes/form/option_form_bulk.php` | `mod_booking\form\option_form_bulk` | Form | 341 |
| `classes/form/dynamicdeputyselect.php` | `mod_booking\form\dynamicdeputyselect` | Form | 320 |
| `classes/local/customform_prefill.php` | `mod_booking\local\customform_prefill` | Util | 315 |
| `classes/form/dynamicsemestersform.php` | `mod_booking\form\dynamicsemestersform` | Form | 292 |
| `classes/form/condition/slotupdate_form.php` | `mod_booking\form\condition\slotupdate_form` | Form | 283 |
| `classes/form/optiondates/modal_change_status.php` | `mod_booking\form\optiondates\modal_change_status` | Form | 283 |
| `classes/form/subbooking/additionalperson_form.php` | `mod_booking\form\subbooking\additionalperson_form` | Form | 283 |
| `classes/form/optiondates/modal_change_notes.php` | `mod_booking\form\optiondates\modal_change_notes` | Form | 276 |
| `classes/form/pricecategories_form.php` | `mod_booking\form\pricecategories_form` | Form | 264 |
| `classes/form/dynamicholidaysform.php` | `mod_booking\form\dynamicholidaysform` | Form | 263 |
| `classes/form/csvimport.php` | `mod_booking\form\csvimport` | Form | 242 |
| `classes/form/option_form.php` | `mod_booking\form\option_form` | Form | 240 |
| `classes/form/certificateconditionsform.php` | `mod_booking\form\certificateconditionsform` | Form | 232 |
| `classes/form/customfield.php` | `mod_booking\form\customfield` | Form | 222 |
| `classes/form/modaloptiondateform.php` | `mod_booking\form\modaloptiondateform` | Form | 222 |
| `classes/form/sync_rule_form.php` | `mod_booking\form\sync_rule_form` | Form | 215 |
| `classes/form/slotrules_page_form.php` | `mod_booking\form\slotrules_page_form` | Form | 203 |
| `classes/form/dynamicoptiondateform.php` | `mod_booking\form\dynamicoptiondateform` | Form | 202 |
| `classes/form/dynamicchangesemesterform.php` | `mod_booking\form\dynamicchangesemesterform` | Form | 199 |
| `classes/form/send_mail_to_teachers.php` | `mod_booking\form\send_mail_to_teachers` | Form | 179 |
| `classes/form/modal_editteacherdescription.php` | `mod_booking\form\modal_editteacherdescription` | Form | 174 |
| `classes/form/sync_rule_delete_form.php` | `mod_booking\form\sync_rule_delete_form` | Form | 172 |
| `classes/form/sync_rule_activate_form.php` | `mod_booking\form\sync_rule_activate_form` | Form | 167 |
| `classes/form/condition/bookingpolicy_form.php` | `mod_booking\form\condition\bookingpolicy_form` | Form | 162 |
| `classes/form/subbookingsform.php` | `mod_booking\form\subbookingsform` | Form | 158 |
| `classes/form/campaignsform.php` | `mod_booking\form\campaignsform` | Form | 155 |
| `classes/form/modal_confirmcancel.php` | `mod_booking\form\modal_confirmcancel` | Form | 155 |
| `classes/form/actions/actionsform.php` | `mod_booking\form\actions\actionsform` | Form | 152 |
| `classes/form/deleteruleform.php` | `mod_booking\form\deleteruleform` | Form | 140 |
| `classes/form/subbookingsdeleteform.php` | `mod_booking\form\subbookingsdeleteform` | Form | 131 |
| `classes/form/deletecampaignform.php` | `mod_booking\form\deletecampaignform` | Form | 126 |
| `classes/form/actions/deleteactionsform.php` | `mod_booking\form\actions\deleteactionsform` | Form | 116 |
| `classes/form/subscribe_cohort_or_group_form.php` | `mod_booking\form\subscribe_cohort_or_group_form` | Form | 114 |
| `classes/form/deletecertificateconditionform.php` | `mod_booking\form\deletecertificateconditionform` | Form | 112 |
| `classes/form/subscribeusersactivity.php` | `mod_booking\form\subscribeusersactivity` | Form | 99 |
| `classes/form/importoptions_form.php` | `mod_booking\form\importoptions_form` | Form | 98 |
| `classes/form/teachers_instance_report_form.php` | `mod_booking\form\teachers_instance_report_form` | Form | 93 |
| `classes/form/confirmactivity.php` | `mod_booking\form\confirmactivity` | Form | 92 |
| `classes/form/teacher_performed_units_report_form.php` | `mod_booking\form\teacher_performed_units_report_form` | Form | 82 |
| `classes/form/instancetemplateadd_form.php` | `mod_booking\form\instancetemplateadd_form` | Form | 57 |

## S17 — reporting  ([Doc](subsystems/S17_reporting.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/signinsheet/signinsheet_generator.php` | `mod_booking\signinsheet\signinsheet_generator` | Renderer/Export | 1632 |
| `classes/local/performance/performance_measurer.php` | `mod_booking\local\performance\performance_measurer` | Service/Singleton | 327 |
| `classes/reportbuilder/local/entities/booking_answers.php` | `mod_booking\reportbuilder\local\entities\booking_answers` | Entity | 281 |
| `classes/local/bookingstracker/bookingstracker_helper.php` | `mod_booking\local\bookingstracker\bookingstracker_helper` | Helper/Renderer | 279 |
| `classes/checklist/checklist_generator.php` | `mod_booking\checklist\checklist_generator` | Renderer/Export | 273 |
| `classes/local/performance/performance_renderer.php` | `mod_booking\local\performance\performance_renderer` | Renderer/Aggregator | 265 |
| `classes/local/checkanswers/checkanswers.php` | `mod_booking\local\checkanswers\checkanswers` | Service/Orchestrator | 255 |
| `classes/reportbuilder/local/entities/booking_options.php` | `mod_booking\reportbuilder\local\entities\booking_options` | Entity | 244 |
| `classes/reportbuilder/datasource/booking_answers_datasource.php` | `mod_booking\reportbuilder\datasource\booking_answers_datasource` | DTO/Datasource | 197 |
| `classes/local/performance/table/measurements_table.php` | `mod_booking\local\performance\table\measurements_table` | Table | 152 |
| `classes/local/performance/performance_facade.php` | `mod_booking\local\performance\performance_facade` | Service/Facade | 150 |
| `classes/local/performance/table/performance_table.php` | `mod_booking\local\performance\table\performance_table` | Table | 143 |
| `classes/reportbuilder/datasource/booking_options_datasource.php` | `mod_booking\reportbuilder\datasource\booking_options_datasource` | Datasource | 128 |
| `classes/reportbuilder/local/filters/profile_field_current_user.php` | `mod_booking\reportbuilder\local\filters\profile_field_current_user` | Filter/Condition | 121 |
| `classes/reportbuilder/local/filters/timestamp_years_past.php` | `mod_booking\reportbuilder\local\filters\timestamp_years_past` | Filter | 119 |
| `classes/signinsheet/signin_pdf.php` | `mod_booking\signinsheet\signin_pdf` | PDF-Adapter | 115 |
| `classes/checklist/checklist_pdf.php` | `mod_booking\checklist\checklist_pdf` | PDF-Adapter | 111 |
| `classes/local/performance/actions/execution_times.php` | `mod_booking\local\performance\actions\execution_times` | Action | 111 |
| `classes/local/performance/actions/purge_cache_action_before.php` | `mod_booking\local\performance\actions\purge_cache_action_before` | Action | 98 |
| `classes/local/performance/actions/purge_cache_action_inbetween.php` | `mod_booking\local\performance\actions\purge_cache_action_inbetween` | Action | 97 |
| `classes/local/performance/actions/action_registry.php` | `mod_booking\local\performance\actions\action_registry` | Registry | 88 |
| `classes/reportbuilder/local/filters/cohort_selector.php` | `mod_booking\reportbuilder\local\filters\cohort_selector` | Filter/Condition | 85 |
| `classes/local/performance/actions/action_executor.php` | `mod_booking\local\performance\actions\action_executor` | Service | 85 |
| `classes/local/performance/actions/performance_action_interface.php` | `mod_booking\local\performance\actions\performance_action_interface` | Interface | 75 |
| `classes/local/checkanswers/checks/cmvisibility.php` | `mod_booking\local\checkanswers\checks\cmvisibility` | Check | 75 |
| `classes/local/checkanswers/checks/enrolledincourse.php` | `mod_booking\local\checkanswers\checks\enrolledincourse` | Check | 71 |
| `classes/local/checkanswers/actions/deleteanswer.php` | `mod_booking\local\checkanswers\actions\deleteanswer` | Action | 67 |
| `classes/local/performance/actions/execution_point.php` | `mod_booking\local\performance\actions\execution_point` | Enum | 42 |

## S18 — import_export  ([Doc](subsystems/S18_import_export.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/import/fileparser.php` | `mod_booking\import\fileparser` | Service | 827 |
| `classes/importer/bookingoptionsimporter.php` | `mod_booking\importer\bookingoptionsimporter` | Service | 325 |
| `classes/import/csvsettings.php` | `mod_booking\import\csvsettings` | DTO | 258 |
| `classes/import/csvcolumn.php` | `mod_booking\import\csvcolumn` | DTO | 175 |
| `classes/import/README.md` | `(keine Klasse - Entwicklerdoku)` | Util | 0 |
| `classes/importer/demo.csv` | `(keine Klasse - Beispieldatei)` | Util | 0 |

## S19 — certificates  ([Doc](subsystems/S19_certificates.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/local/certificateclass.php` | `mod_booking\local\certificateclass` | Service | 417 |
| `classes/local/certificate_conditions/certificate_conditions.php` | `mod_booking\local\certificate_conditions\certificate_conditions` | Service | 355 |
| `classes/local/certificate_conditions/conditions/bookingoption.php` | `mod_booking\local\certificate_conditions\conditions\bookingoption` | Condition | 352 |
| `classes/local/certificate_conditions/conditions/taggedoptions.php` | `mod_booking\local\certificate_conditions\conditions\taggedoptions` | Condition | 270 |
| `classes/local/certificate_conditions/actions/createcertificate.php` | `mod_booking\local\certificate_conditions\actions\createcertificate` | Action | 231 |
| `classes/local/certificate_conditions/filters/userprofilefield.php` | `mod_booking\local\certificate_conditions\filters\userprofilefield` | Filter | 223 |
| `classes/local/certificate_conditions/option_conditions_info.php` | `mod_booking\local\certificate_conditions\option_conditions_info` | Util | 201 |
| `classes/local/certificate_conditions/actions_info.php` | `mod_booking\local\certificate_conditions\actions_info` | Service | 143 |
| `classes/local/certificate_conditions/filters_info.php` | `mod_booking\local\certificate_conditions\filters_info` | Service | 137 |
| `classes/local/certificate_conditions/certificate_conditions_interface.php` | `mod_booking\local\certificate_conditions\certificate_conditions_interface` | Interface | 115 |
| `classes/local/certificate_conditions/conditions_info.php` | `mod_booking\local\certificate_conditions\conditions_info` | Service | 111 |
| `classes/local/certificate_conditions/filter_interface.php` | `mod_booking\local\certificate_conditions\filter_interface` | Interface | 105 |
| `classes/local/certificate_conditions/action_interface.php` | `mod_booking\local\certificate_conditions\action_interface` | Interface | 105 |
| `classes/local/certificate_conditions/README.md` | `README.md` | Script | 0 |

## S20 — sync_enrolment  ([Doc](subsystems/S20_sync_enrolment.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/local/sync/booking_enrolment.php` | `mod_booking\local\sync\booking_enrolment` | Service | 1241 |
| `classes/enrollink.php` | `mod_booking\enrollink` | Service | 695 |
| `classes/local/connectedcourse.php` | `mod_booking\local\connectedcourse` | Service | 428 |
| `classes/booking_potential_user_selector.php` | `mod_booking\booking_potential_user_selector` | Form | 172 |
| `classes/potential_subscriber_selector.php` | `mod_booking\potential_subscriber_selector` | Form | 165 |
| `classes/local/competencies/competencies_handler.php` | `mod_booking\local\competencies\competencies_handler` | Service | 139 |
| `classes/booking_existing_user_selector.php` | `mod_booking\booking_existing_user_selector` | Form | 123 |
| `classes/booking_user_selector_base.php` | `mod_booking\booking_user_selector_base` | Form | 122 |
| `classes/subscriber_selector_base.php` | `mod_booking\subscriber_selector_base` | Form | 84 |
| `classes/existing_subscriber_selector.php` | `mod_booking\existing_subscriber_selector` | Form | 55 |

## S21 — entry_scripts  ([Doc](subsystems/S21_entry_scripts.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `mod_form.php` | `mod_booking_mod_form` | Form | 1781 |
| `report.php` | `report.php` | Script | 1596 |
| `subscribeusers.php` | `subscribeusers.php` | Script | 583 |
| `report2.php` | `report2.php` | Script | 482 |
| `slotrules.php` | `slotrules.php` | Script | 333 |
| `teacher_performed_units_report.php` | `teacher_performed_units_report.php` | Script | 258 |
| `teachers_instance_report.php` | `teachers_instance_report.php` | Script | 246 |
| `view.php` | `view.php` | Script | 224 |
| `subbooking_timetabletest.php` | `subbooking_timetabletest.php` | Script | 182 |
| `optionview.php` | `optionview.php` | Script | 173 |
| `availabilityconditions.php` | `availabilityconditions.php` | Script | 169 |
| `sendmessage.php` | `send_custom_message` | Script | 168 |
| `optiondates_teachers_report.php` | `optiondates_teachers_report.php` | Script | 162 |
| `importexcel.php` | `importexcel.php` | Script | 147 |
| `otherbooking.php` | `otherbooking.php` | Script | 135 |
| `edit_rules.php` | `edit_rules.php` | Script | 132 |
| `editoptions.php` | `editoptions.php` | Script | 132 |
| `edit_optiontemplates.php` | `edit_optiontemplates.php` | Script | 130 |
| `teachers_form.php` | `mod_booking_teachers_form` | Form | 126 |
| `teacherunavailability.php` | `teacherunavailability.php` | Script | 124 |
| `link.php` | `link.php` | Script | 122 |
| `categoryadd.php` | `categoryadd.php` | Script | 121 |
| `teachers.php` | `teachers.php` | Script | 116 |
| `unsubscribe.php` | `unsubscribe.php` | Script | 114 |
| `otherbookingaddrule_form.php` | `otherbookingaddrule_form` | Form | 113 |
| `edit_certificateconditions.php` | `edit_certificateconditions.php` | Script | 109 |
| `categoriesform.class.php` | `mod_booking_categories_form` | Form | 107 |
| `index.php` | `index.php` | Script | 107 |
| `scheduledmails.php` | `scheduledmails.php` | Script | 107 |
| `otherbookingaddrule.php` | `otherbookingaddrule.php` | Script | 106 |
| `moveoption.php` | `moveoption.php` | Script | 105 |
| `recalculateprices.php` | `recalculateprices.php` | Task | 103 |
| `semesters.php` | `semesters.php` | Script | 98 |
| `slotteacherassignments.php` | `slotteacherassignments.php` | Script | 97 |
| `tagtemplates.php` | `tagtemplates.php` | Script | 97 |
| `tagtemplatesadd.php` | `tagtemplatesadd.php` | Script | 96 |
| `categories.php` | `categories.php` | Script | 94 |
| `confirmactivity.php` | `confirmactivity.php` | Script | 94 |
| `optiontemplatessettings.php` | `optiontemplatessettings.php` | Script | 94 |
| `instancetemplateadd.php` | `instancetemplateadd.php` | Script | 92 |
| `mybookings.php` | `mybookings.php` | Script | 92 |
| `enrollink.php` | `enrollink.php` | Script | 91 |
| `viewconfirmation.php` | `viewconfirmation.php` | Script | 90 |
| `slotcalendar.php` | `slotcalendar.php` | Script | 87 |
| `moveslot.php` | `moveslot.php` | Script | 86 |
| `rebookslot.php` | `rebookslot.php` | Script | 86 |
| `tagtemplatesadd_form.php` | `tagtemplatesadd_form` | Form | 86 |
| `sendmessageform.class.php` | `mod_booking_sendmessage_form` | Form | 85 |
| `optionformconfig.php` | `optionformconfig.php` | Script | 81 |
| `edit_campaigns.php` | `edit_campaigns.php` | Script | 80 |
| `subscribeusersactivity.php` | `subscribeusersactivity.php` | Script | 80 |
| `importoptions.php` | `importoptions.php` | Script | 79 |
| `instancetemplatessettings.php` | `instancetemplatessettings.php` | Script | 77 |
| `download.php` | `download.php` | Download | 76 |
| `bulk_book_handler.php` | `bulk_book_handler.php` | Task | 75 |
| `category.php` | `category.php` | Script | 75 |
| `performance.php` | `performance.php` | Script | 75 |
| `download_optiondates_teachers_report.php` | `download_optiondates_teachers_report.php` | Download | 73 |
| `pricecategories.php` | `pricecategories.php` | Script | 73 |
| `importexcel_form.php` | `importexcel_form` | Form | 71 |
| `tag.php` | `tag.php` | Script | 70 |
| `search_sync_sources.php` | `search_sync_sources.php` | WS | 69 |
| `sync_diagnostics.php` | `sync_diagnostics.php` | WS | 69 |
| `rating_rest.php` | `rating_rest.php` | WS | 68 |
| `download_report2.php` | `download_report2.php` | Download | 65 |
| `teacher.php` | `teacher.php` | Script | 64 |
| `option_date_template.php` | `option_date_template.php` | Script | 61 |
| `bookinginstancetemplatessettings.php` | `bookinginstancetemplatessettings.php` | Script | 59 |
| `viewpolicy.php` | `viewpolicy.php` | Script | 54 |
| `customfield.php` | `customfield.php` | Script | 53 |
| `customfieldsettings.php` | `customfieldsettings.php` | Script | 49 |
| `bookingredirect.php` | `bookingredirect.php` | Script | 48 |

## S22 — db_layer  ([Doc](subsystems/S22_db_layer.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `db/upgrade.php` | `xmldb_booking_upgrade` | Script | 5566 |
| `lib.php` | `(MOD_BOOKING_*-Konstanten + booking_*-Callbacks)` | Script | 2941 |
| `settings.php` | `(Admin-Settings-Baum)` | Script | 2788 |
| `db/access.php` | `$capabilities` | DTO | 674 |
| `classes/utils/webservice_import.php` | `mod_booking\utils\webservice_import` | Service | 400 |
| `classes/local/sql/operator_builder.php` | `mod_booking\local\sql\operator_builder` | Service | 367 |
| `db/upgradelib.php` | `(13 Migrations-Funktionen)` | Script | 289 |
| `db/services.php` | `$functions/$services` | WS | 259 |
| `db/caches.php` | `$definitions` | DTO | 220 |
| `locallib.php` | `(7 prozedurale Helper)` | Util | 202 |
| `classes/local/modechecker.php` | `mod_booking\local\modechecker` | Util | 187 |
| `db/events.php` | `$observers` | Event | 168 |
| `classes/utils/db.php` | `mod_booking\utils\db` | Util | 159 |
| `classes/utils/wb_payment.php` | `mod_booking\utils\wb_payment` | Util | 144 |
| `classes/local/sql/operators/equals.php` | `mod_booking\local\sql\operators\equals` | Service | 127 |
| `classes/local/sql/operators/not_equals.php` | `mod_booking\local\sql\operators\not_equals` | Service | 125 |
| `classes/local/sql/operators/contains.php` | `mod_booking\local\sql\operators\contains` | Service | 119 |
| `classes/plugininfo/bookingextension_interface.php` | `mod_booking\plugininfo\bookingextension_interface` | DTO | 111 |
| `classes/plugininfo/bookingextension.php` | `mod_booking\plugininfo\bookingextension` | Service | 100 |
| `classes/completion/custom_completion.php` | `mod_booking\completion\custom_completion` | Service | 99 |
| `classes/GoogleUrlApi.php` | `mod_booking\GoogleUrlApi` | Util | 96 |
| `db/shortcodes.php` | `$shortcodes` | DTO | 86 |
| `classes/local/sql/operators/base_operator.php` | `mod_booking\local\sql\operators\base_operator` | DTO | 82 |
| `db/tasks.php` | `$tasks` | Task | 78 |
| `classes/booking_advanced_testcase.php` | `mod_booking\booking_advanced_testcase` | TestBase | 56 |
| `db/install.php` | `xmldb_booking_install` | Script | 44 |
| `db/messages.php` | `$messageproviders` | DTO | 43 |
| `classes/local/testing/booking_advanced_testcase.php` | `mod_booking\local\testing\booking_advanced_testcase` | Test | 36 |
| `version.php` | `$plugin` | DTO | 36 |
| `db/log.php` | `$logs` | DTO | 33 |
| `db/install.xml` | `(40 TABLE-Definitionen)` | DTO/Schema | 0 |
| `db/subplugins.json` | `(JSON subplugintypes)` | DTO | 0 |

## S23 — frontend_js  ([Doc](subsystems/S23_frontend_js.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `amd/src/bookit.js` | `bookit` | Controller | 1128 |
| `amd/src/condition/slotBooking.js` | `condition/slotBooking` | Condition | 959 |
| `amd/src/slotCalendarPicker.js` | `SlotCalendarPicker` | Renderer | 957 |
| `amd/src/jquery.barrating.js` | `jquery.barrating` | Util | 614 |
| `amd/src/bookingpage/prepageFooter.js` | `prepageFooter` | Controller | 477 |
| `amd/src/condition/slotUpdate.js` | `condition/slotUpdate` | Condition | 422 |
| `amd/src/csvimport.js` | `csvimport` | Form | 406 |
| `vue3/components/BookingDashboard.vue` | `BookingDashboard` | JS | 348 |
| `amd/src/slotbooking/slot_day_renderers.js` | `slot_day_renderers` | Renderer | 322 |
| `amd/src/teacherUnavailability.js` | `teacherUnavailability` | Controller | 304 |
| `amd/src/performance_chart.js` | `performance_chart` | Renderer | 275 |
| `amd/src/dynamiceditoptionform.js` | `dynamiceditoptionform` | Form | 257 |
| `vue3/components/helper/CapabilityButtons.vue` | `CapabilityButtons` | JS | 254 |
| `vue3/components/helper/CapabilityOptions.vue` | `CapabilityOptions` | JS | 243 |
| `amd/src/dynamicoptiondateform.js` | `dynamicoptiondateform` | Form | 233 |
| `vue3/components/dashboard/TabInformation.vue` | `TabInformation` | JS | 215 |
| `amd/src/condition/subbookingAdditionalPerson.js` | `condition/subbookingAdditionalPerson` | Condition | 195 |
| `amd/src/wunderbyte.js` | `WunderByteJS` | Util | 177 |
| `amd/src/bookinginstancetemplateselect.js` | `bookinginstancetemplateselect` | Controller | 175 |
| `vue3/components/dashboard/StatisticsView.vue` | `StatisticsView` | JS | 170 |
| `amd/src/slotCalendarReport.js` | `slotCalendarReport` | Controller | 169 |
| `amd/src/edit_note.js` | `edit_note` | Controller | 162 |
| `amd/src/dynamicrulesform.js` | `dynamicrulesform` | Form | 156 |
| `vue3/store.js` | `vue3/store` | Service | 156 |
| `amd/src/dynamicactionsform.js` | `dynamicactionsform` | Form | 149 |
| `amd/src/dynamiccampaignsform.js` | `dynamiccampaignsform` | Form | 148 |
| `amd/src/modal_init.js` | `modal_init` | Service | 147 |
| `amd/src/condition/customForm.js` | `condition/customForm` | Condition | 141 |
| `amd/src/dynamicsubbookingsform.js` | `dynamicsubbookingsform` | Form | 130 |
| `amd/src/form_booking_options_selector.js` | `form_booking_options_selector` | Field | 117 |
| `amd/src/dynamiccertificateconditionsform.js` | `dynamiccertificateconditionsform` | Form | 114 |
| `amd/src/performance_submit.js` | `performance_submit` | Controller | 114 |
| `amd/src/condition/bookingPolicy.js` | `condition/bookingPolicy` | Condition | 110 |
| `amd/src/sync_rule_modal.js` | `sync_rule_modal` | Form | 108 |
| `vue3/components/helper/SubLists.vue` | `SubLists` | JS | 108 |
| `amd/src/view_actions.js` | `view_actions` | Controller | 106 |
| `amd/src/button_notifyme.js` | `button_notifyme` | Controller | 100 |
| `amd/src/slotbooking/repository.js` | `slotbooking/repository` | WS | 100 |
| `amd/src/sync_diagnostics.js` | `sync_diagnostics` | Service | 94 |
| `amd/src/editteachersforoptiondate_form.js` | `editteachersforoptiondate_form` | Form | 87 |
| `amd/src/form_courses_selector.js` | `form_courses_selector` | Field | 87 |
| `amd/src/form_teachers_selector.js` | `form_teachers_selector` | Field | 87 |
| `amd/src/form_users_selector.js` | `form_users_selector` | Field | 87 |
| `amd/src/form_templates_selector.js` | `form_templates_selector` | Field | 87 |
| `amd/src/form_sync_source_selector.js` | `form_sync_source_selector` | Field | 87 |
| `amd/src/bookingcompetencies.js` | `bookingcompetencies` | Controller | 83 |
| `amd/src/confirm_cancel.js` | `confirm_cancel` | Form | 82 |
| `amd/src/edit-teacher-description.js` | `edit-teacher-description` | Form | 81 |
| `vue3/components/dashboard/BookingStats.vue` | `BookingStats` | JS | 76 |
| `amd/src/dynamicdeputymodal.js` | `dynamicdeputymodal` | Form | 75 |
| `vue3/main.js` | `vue3/main` | Script | 74 |
| `vue3/components/modal/ConfirmationModal.vue` | `ConfirmationModal` | JS | 73 |
| `amd/src/signinsheetdownload.js` | `signinsheetdownload` | Controller | 72 |
| `amd/src/elective-sorting.js` | `elective-sorting` | Controller | 72 |
| `amd/src/bookingfavorite.js` | `bookingfavorite` | Controller | 71 |
| `amd/src/dynamicsemestersform.js` | `dynamicsemestersform` | Form | 71 |
| `amd/src/dynamicchangesemesterform.js` | `dynamicchangesemesterform` | Form | 64 |
| `amd/src/dynamicholidaysform.js` | `dynamicholidaysform` | Form | 62 |
| `vue3/router/router.js` | `vue3/router` | Script | 61 |
| `amd/src/slotteacherassignments_form.js` | `slotteacherassignments_form` | Form | 58 |
| `vue3/components/dashboard/ConfigForm.vue` | `ConfigForm` | JS | 52 |
| `vue3/components/helper/SkeletonContent.vue` | `SkeletonContent` | JS | 51 |
| `amd/src/bookingjslib.js` | `bookingjslib` | Controller | 43 |
| `vue3/components/helper/SkeletonTab.vue` | `SkeletonTab` | JS | 43 |
| `amd/src/dynamicpricecategoriesform.js` | `dynamicpricecategoriesform` | Form | 42 |
| `amd/src/init_comments.js` | `init_comments` | Service | 40 |
| `vue3/components/FilterSearchbar.vue` | `FilterSearchbar` | JS | 37 |
| `vue3/components/NotFound.vue` | `NotFound` | JS | 34 |
| `amd/src/notifications.js` | `notifications` | Util | 30 |
| `amd/src/app-lazy.js` | `app-lazy` | Script | 1 |

## S24 — backup_restore  ([Doc](subsystems/S24_backup_restore.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `backup/moodle2/restore_booking_stepslib.php` | `restore_booking_activity_structure_step` | Script | 643 |
| `backup/moodle2/backup_booking_stepslib.php` | `backup_booking_activity_structure_step` | Script | 321 |
| `backup/moodle2/restore_booking_activity_task.class.php` | `restore_booking_activity_task` | Task | 146 |
| `backup/moodle2/backup_booking_activity_task.class.php` | `backup_booking_activity_task` | Task | 73 |
| `backup/moodle2/backup_booking_settingslib.php` | `-` | Script | 30 |

## S25 — mobile  ([Doc](subsystems/S25_mobile.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/local/mobile/customformstore.php` | `mod_booking\local\mobile\customformstore` | Service | 346 |
| `classes/local/mobile/mobileformbuilder.php` | `mod_booking\local\mobile\mobileformbuilder` | Renderer | 203 |
| `classes/local/mobile/slotbookingstore.php` | `mod_booking\local\mobile\slotbookingstore` | Service | 193 |
| `db/mobile.php` | `db/mobile.php (Config $addons)` | Config | 74 |
| `classes/entities/service_provider.php` | `mod_booking\entities\service_provider` | DTO | 43 |

## S26 — privacy_gdpr  ([Doc](subsystems/S26_privacy_gdpr.md))

| Datei | Klasse | Rolle | LOC |
|---|---|---|---:|
| `classes/privacy/provider.php` | `mod_booking\privacy\provider` | Privacy/GDPR-Provider (Service) | 563 |
