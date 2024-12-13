# Contributing to `phpunit/php-code-coverage`

## Welcome!

We look forward to your contributions! Here are some examples how you can contribute:

* [Report a bug](https://github.com/sebastianbergmann/php-code-coverage/issues/new)
* [Send a pull request to fix a bug](https://github.com/sebastianbergmann/php-code-coverage/pulls)

Please do not send pull requests that expand the scope of this project (see below).


## Any contributions you make will be under the BSD-3-Clause License

When you submit code changes, your submissions are understood to be under the same [BSD-3-Clause License](https://github.com/sebastianbergmann/php-code-coverage/blob/main/LICENSE) that covers the project. By contributing to this project, you agree that your contributions will be licensed under its BSD-3-Clause License.


## Write bug reports with detail, background, and sample code

[This is an example](https://github.com/sebastianbergmann/phpunit/issues/4376) of a bug report I wrote, and I think it's not too bad.

In your bug report, please provide the following:

* A quick summary and/or background
* Steps to reproduce
    * Be specific!
    * Give sample code if you can.
* What you expected would happen
* What actually happens
* Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

Please post code and output as text ([using proper markup](https://guides.github.com/features/mastering-markdown/)). Do not post screenshots of code or output.


## Workflow for Pull Requests

1. Fork the repository.
2. Create your branch from the oldest branch that is affected by the bug you plan to fix.
3. Implement your change and add tests for it.
4. Ensure the test suite passes.
5. Ensure the code complies with our coding guidelines (see below).
6. Send that pull request!

Please make sure you have [set up your username and email address](https://git-scm.com/book/en/v2/Getting-Started-First-Time-Git-Setup) for use with Git. Strings such as `silly nick name <root@localhost>` look really stupid in the commit history of a project.

We encourage you to [sign your Git commits with your GPG key](https://docs.github.com/en/github/authenticating-to-github/signing-commits).


## Development

This project uses [PHPUnit](https://phpunit.de/) for testing:

```shell
./vendor/bin/phpunit
```

This project uses [PHPStan](https://phpstan.org/) for static analysis:

```shell
./tools/phpstan
```

This project uses [PHP-CS-Fixer](https://cs.symfony.com/) to enforce coding guidelines:

```shell
./tools/php-cs-fixer fix
```

The commands shown above require an autoloader script at `vendor/autoload.php`. This can be generated like so:

```shell
./tools/composer dump-autoload
```

Please understand that we will not accept a pull request when its changes violate this project's coding guidelines or break the test suite.
