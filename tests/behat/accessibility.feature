@local_course_banner_builder @javascript @accessibility
Feature: Course Banner Builder administration accessibility
  In order to manage banners with assistive technology
  As a site administrator
  I need the plugin administration region to meet accessibility standards

  Scenario: The course banner administration region meets accessibility standards
    Given I log in as "admin"
    When I visit "/local/course_banner_builder/admin_manage.php"
    Then the ".local-course-banner-builder-admin" "css_element" should meet accessibility standards with "best-practice" extra tests
