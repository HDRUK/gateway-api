Feature: Remove role for user from a team
  In order to manage team memberships
  As an authenticated user
  I want to remove a user from a team

  Scenario: Remove user two from team one using user one credentials
    Given I have user one with credentials I send a update request to path with team one and user two for update roles
    Then I should receive a successful response with status code 200 after update role user one for team one using user one credentials
    And I verify that the role for user one was updated for team one using user one credentials in email
    And I verify that the role for user one was updated for team one using user one credentials in database