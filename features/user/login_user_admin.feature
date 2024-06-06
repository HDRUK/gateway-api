Feature: Authentication admin user
  In order to access protected resources
  As a user
  I need to be able to authenticate using my email and password

  Scenario: Authenticate with valid credentials
    Given I am a user with the email "developers@hdruk.ac.uk" and password "Watch26Task?"
    When I send a POST request to "/api/v1/auth" with my credentials
    Then I should receive a successful response from auth with status code 200
    Then I verify the access token exists in the authorisation_codes table