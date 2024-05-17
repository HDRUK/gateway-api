Feature: Authentication user one
  In order to access protected resources
  As a user one
  I need to be able to authenticate using my email and password

  Scenario: Authenticate with valid credentials
    Given I am user one with email and password
    When I send a POST request to "/api/v1/auth" with user one credentials
    Then I should receive a successful response from auth with user one credentials and with status code 200
    Then I verify the access token exists in the authorisation_codes table for user one credentials
    Then I verify the access token contain user one credentials