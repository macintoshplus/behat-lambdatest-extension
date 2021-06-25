# Version 1.2.3

* Add support `isVisible` on element for Safari (not supported by SafaWebDriver) [W3C WebDriver displayedness](https://w3c.github.io/webdriver/#element-displayedness) [Apple Safari WebDriver supported commands](https://developer.apple.com/documentation/webkit/macos_webdriver_commands_for_safari_12_and_later)

# Version 1.2.2

* Remove force W3c option for chrome because Lambdatest does not support: ` Could not open connection: w3c is not a valid option in chromeOptions capability, please refer to capability generator https://www.lambdatest.com/capabilities-generator or contact support` (2021, 24 june).
* Accept two event type on SessionStateListener.
* Remove `chromeOptions` array if empty.

# Version 1.2.1

* Add content in error message when check concurency.
* Manage w3c options without change in subclass.
* Fix `InvalidSessionIdException` on quit session already close.

# Version 1.2.0

* Fix Lambdatest ending session.
* Before stop the session, define the test execution status on Lambdatest

# Version 1.1.0

* Use the Facebook web driver instead of Selenium2 web driver.
* Before launch, throw an exception if no concurrency automation test is available.


# Version 1.0.0

* Allow configuring Lambdatest credential with configuration or environment variables.
