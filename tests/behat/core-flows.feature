@local_course_banner_builder
Feature: Access Course Banner Builder administration
  In order to configure course banners
  As a site administrator
  I need to open the Course Banner Builder administration page

  Scenario: Administrator opens course banner management
    Given I log in as "admin"
    When I visit "/local/course_banner_builder/admin_manage.php"
    Then I should see "Manage course banners"
    And ".local-course-banner-builder-admin" "css_element" should exist
