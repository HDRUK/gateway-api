Feature: Create notification on application submission
  In order to manage teams within the application
  As an authenticated user
  I want to be able to create new notification

  Scenario: Create a new notification with valid access token
    Given I send a POST request to "/api/v1/notifications" with type "team_user_notification" for create a new notification
    Then I should receive a successful response for create notification with status code 201
    And the response should contain the newly created notification information
    And I verify the notification is created in the notifications table