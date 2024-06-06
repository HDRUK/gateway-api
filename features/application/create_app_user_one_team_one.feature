Feature: Create an application with specific user one authorization
  As an developer
  I want to be able to create applications with specific user authorizations
  So that users can perform tasks within their assigned team

  Scenario: Create an application with authorization for user one in team one
    Given I send a POST request to "/api/v1/applications" with user one credentials for team one
    Then I should receive a successful response for create application with status code 201 with user one credentials for team one
    And the response should contain the newly created application information with user one credentials for team one
    And I verify the application is created in the applications table with user one credentials for team one