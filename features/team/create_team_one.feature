Feature: Create Team One
  In order to manage teams within the application
  As an authenticated user
  I want to be able to create new teams

  Scenario: Create a team one with valid access token
    Given I send a POST request to "/api/v1/teams" with team one name "Research Group"
    Then I should receive a successful response with status code 200
    And the response should contain the newly created team one information
    And I verify the team one is created in the teams table