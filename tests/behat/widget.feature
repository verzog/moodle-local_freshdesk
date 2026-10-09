@local @local_freshdesk @javascript
Feature: Freshdesk support widget
  In order to get help without leaving Moodle
  As a user
  I need to open the Freshdesk support widget from any page

  Background:
    Given the following config values are set as admin:
      | enabled    | 1                             | local_freshdesk |
      | portal_url | https://example.freshdesk.com | local_freshdesk |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Student  | student1@example.com |

  Scenario: A logged-in user opens the support window and the contact form
    Given I log in as "student1"
    When I click on "#fd-help-btn" "css_element"
    Then I should see "Support — Sam Student"
    And I should see "Search for help articles above, or contact support below."
    When I click on "Contact Support" "button"
    Then I should see "Submitting as Sam Student"
    And the field "Subject" matches value "Support request"
    When I press the escape key
    Then I should not see "Submitting as Sam Student"

  Scenario: A visitor who is not logged in gets a link to the Freshdesk portal
    When I am on site homepage
    Then "a#fd-help-btn[href='https://example.freshdesk.com/support/home']" "css_element" should exist

  Scenario: The widget stays hidden until the portal URL is set
    Given the following config values are set as admin:
      | portal_url |  | local_freshdesk |
    When I log in as "student1"
    Then "#fd-help-btn" "css_element" should not exist
