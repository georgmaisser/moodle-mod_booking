@mod @mod_booking @booking_paymentchoices
Feature: Users can choose payment method in prepage modal
  As a student
  I want to choose how I pay before booking
  So I can continue with the selected method

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname | name   |
      | text     | credit    | Credit |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber | profile_field_credit |
      | teacher1 | Teacher   | 1        | teacher1@example.com | T1       |                      |
      | student1 | Student   | 1        | student1@example.com | S1       | 200                  |
    And I clean booking cache
    And the following "core_payment > payment accounts" exist:
      | name     |
      | Account1 |
    And the following "local_shopping_cart > payment gateways" exist:
      | account  | gateway | enabled | config                                                                                 |
      | Account1 | paypal  | 1       | {"brandname":"Test paypal","clientid":"Test","secret":"Test","environment":"sandbox"} |
    And the following "local_shopping_cart > plugin setup" exist:
      | account  | cancelationfee |
      | Account1 | 0              |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | name       | intro               | bookingmanager | eventtype | Default view for booking options |
      | booking  | C1     | BookingPay | Booking description | teacher1       | Webinar   | All bookings                     |
    And the following "mod_booking > options" exist:
      | booking    | text            | course | description | useprice | credits | maxanswers | datesmarker | optiondateid_0 | daystonotify_0 | coursestarttime_0 | courseendtime_0 |
      | BookingPay | Option-payment  | C1     | Price-option | 1        | 50      | 10         | 1           | 0              | 0              | ## tomorrow ##    | ## +2 days ##   |
    And I change viewport size to "1366x10000"

  @javascript
  Scenario: Payment choice prepage is displayed and shopping cart can be selected
    Given the following config values are set as admin:
      | config                      | value  | plugin  |
      | paymentchoiceenabled        | 1      | booking |
      | paymentchoicecredits        | 1      | booking |
      | paymentchoicesubscription   | 1      | booking |
      | paymentchoiceshoppingcart   | 1      | booking |
      | bookwithcreditsactive       | 1      | booking |
      | bookwithcreditsprofilefield | credit | booking |
    When I am on the "BookingPay" Activity page logged in as student1
    And I click on "Add to cart" "text" in the ".allbookingoptionstable_r1" "css_element"
    Then I should see "Choose payment method" in the ".condition-paymentchoices" "css_element"
    And I should see "Credits" in the ".condition-paymentchoices" "css_element"
    And I should see "Shopping cart" in the ".condition-paymentchoices" "css_element"
    And I click on "Shopping cart" "radio" in the ".condition-paymentchoices" "css_element"
    And I follow "Continue"
    Then I should see "Thank you! You have successfully put Option-payment into the shopping cart." in the ".modal-dialog.modal-xl .modalMainContent" "css_element"
