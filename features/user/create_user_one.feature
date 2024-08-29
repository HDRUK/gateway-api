Feature: Create User One
  In order to create user one within the application
  As an authenticated user
  I want to be able to create new user

  Scenario: Create user one with valid access token
    Given I send a POST request to "/api/v1/users" with random user one details
    Then I should receive a successful create user one response with status code 201
    And the response should contain the newly created user one information
    And I verify the user one is created in the users table