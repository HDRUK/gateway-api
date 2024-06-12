Feature: Remove a user from a team
  In order to manage team memberships
  As an authenticated user
  I want to remove a user from a team

  Scenario: Remove user two from team one using user one credentials
    Given I have user one with credentials I send a DELETE request to path with team one and user two
    Then I should receive a successful response with status code 200 after remove user one from team one using user one credentials
    And I verify that the user one was removed from team one using user one credentials in database
