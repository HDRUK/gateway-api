Feature: Manage datasets using application credentials

  As an API client
  I want to create and delete datasets
  So that I can manage datasets in the application's database

  Scenario: Post a new dataset and verify its existence in the database
    Given I have valid application credentials
    When I post a new dataset with application credentials
    Then I should receive a successful response for create dataset with application with status code 201
    And the new dataset should exist in the database created through the application
    And I delete the dataset
    And the dataset should not exist in the database