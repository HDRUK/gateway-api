
Feature: Assign user to a team and role

  As an admin
  I want to be able to assign users to teams and roles
  So that I can ensure users have the correct permissions and team assignments

  Scenario: Assign a user one to a team one with a developer role with valid access token
    Given I send a POST request to path with team one and user one and assing "developer" role
    Then I should receive a successful response with status code 201 after user one was assigned to the team one like developer
    Then I verify that the user one should be a member of team one
    And I verify that the user one assigned to team one should have the "developer" role