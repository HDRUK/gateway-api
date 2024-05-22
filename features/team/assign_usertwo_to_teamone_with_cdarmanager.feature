
Feature: Assign user two to a team one and role custodian dar manager

  As an custodian dar manager
  I want to be able to assign users to teams and roles
  So that I can ensure users have the correct permissions and team assignments

  Scenario: Assign a user two to a team one with a dar manager role with valid access token
    Given I send a POST request to path with team one and user two and assigning "custodian.dar.manager" role custodian dar manager
    Then I should receive a successful response with status code 201 after user two was assigned to the team one like custodian dar manager
    And I verify that the user one assigned to team one with role custodian dar manager should receive an email 
    And I verify that the user two should be a member of team one like custodian dar manager
    And I verify that the user two assigned to team one should have the "custodian.dar.manager" role custodian dar manager