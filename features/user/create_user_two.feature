Feature: Create User Two
  In order to create user two within the application
  As an authenticated user
  I want to be able to create new user

  Scenario: Create user two with valid access token
    Given I send a POST request to "/api/v1/users" with random user two details
    Then I should receive a successful create user two response with status code 201
    And the response should contain the newly created user two information
    And I verify the user two was created in the users table