# Lambdatest integration for Behat with Mink Selenium2 Extension

This Behat extension provide a Mink Selenium2 Extension integration for [Lambdatest SaaS](https://lambdatest.com).

Tested browser :

* Firefox 88+
* Chrome 90+
* Safari 13+

## Installation

Run this command to add this extension on your projet:

```shell
$ php composer require --dev macintoshplus/behat-lambdatest-extension
```

## Configuration

In your `behat.yml.dist` file, enable this extension

```yaml
default:
    extensions:
      Macintoshplus\Lambdatest\LambdatestExtension: ~
```

Configure the Lambdatest Mink extension in `behat.yml.dist` file:

```yaml
default:
  extensions:
      Behat\MinkExtension:
        lambdatest:
          # You can use the LT_USERNAME and LT_USERKEY environment variables instead of this keys:
          user: your_email@domain.tld # Your Lambdatest login
          key: xxxxx # Your Lambdatest key available here: https://accounts.lambdatest.com/detail/profile
          # The rest of key are the same as Mink Extension
          wd_host: https://hub.lambdatest.com/wd/hub # The URL of Selenium2 Hub
          browser: firefox # The browser name
          marionette: true
          extra_capabilities:
            resolution: 1920x1080
            platform: windows10
            browserName: firefox
            version: latest
            
            # If you need select file to upload in your tests
            # Upload the files before run test. See: https://www.lambdatest.com/support/docs/upload-files-using-lambdatest/
            # Define below all files names needed in your test.
            'lambda:userFiles': [file_name_uploaded_to_lamdatest.zip, file_2.zip]
            
            #If you need use the Lambdatest tunnel
            tunnel: true
            tunnelName: test_tunnel
```

See this [documentation](https://www.lambdatest.com/support/docs/selenium-automation-capabilities/) to customize your capabilities configuration.

### Credential priority

When `LT_USERNAME` and `LT_USERKEY` environment variable are defined, they are used.

Otherwise, the values provided into `behat.yml.dist` file are used. 

## Define your credential

To define environment variables, on Windows, open a `cmd` window and run these commands after change the value with your personnal information:

```shell
set LT_USERNAME=user@domain.tld
set LT_USERKEY=xxxxxxxxxx
```

To define environment variables, on Unix, open a `terminal` window and run these commands after change the value with your personnal information:

```shell
export LT_USERNAME=user@domain.tld
export LT_USERKEY=xxxxxxxxxx
```

## Run Behat

Run Behat command, and view the result on you Lambdatest account:

```shell
vendor/bin/behat --tags=@javascript
```
