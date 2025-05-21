# Contributing to `phpunit/php-code-coverage`

## Welcome!

We look forward to your contributions! Here are some examples how you can contribute:

* [Report a bug](https://github.com/sebastianbergmann/php-code-coverage/issues/new)
* [Send a pull request to fix a bug](https://github.com/sebastianbergmann/php-code-coverage/pulls)


## We have a Code of Conduct

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.


## Any contributions you make will be under the BSD-3-Clause License

When you submit code changes, your submissions are understood to be under the same [BSD-3-Clause License](https://github.com/sebastianbergmann/php-code-coverage/blob/main/LICENSE) that covers the project. By contributing to this project, you agree that your contributions will be licensed under its BSD-3-Clause License.


### Do Not Violate Copyright

Only submit a pull request with your own original code. Do NOT submit a pull request containing code which you have largely copied from
another project, unless you wrote the respective code yourself.

Open Source does not mean that copyright does not apply. Copyright infringements will not be tolerated and can lead to you being banned from this project and repository.


### Do Not Submit AI-Generated Pull Requests

The same goes for (largely) AI-generated pull requests. These are not welcome as they will be based on copyrighted code from others
without accreditation and without taking the license of the original code into account, let alone getting permission
for the use of the code or for re-licensing.

Aside from that, the experience is that AI-generated pull requests will be incorrect 100% of the time and cost reviewers too much time.
Submitting a (largely) AI-generated pull request will lead to you being banned from this project and repository.


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

Please do not report a bug for a version of this library that is no longer supported. Please do not report a bug if you are using a version of PHP that is not supported by the version of this library you are using.

The library that is developed in this repository was either extracted from [PHPUnit](https://github.com/sebastianbergmann/phpunit) or developed specifically as a dependency for PHPUnit. Support for this library follows the [support for the version of PHPUnit that uses a specific version of this library](https://phpunit.de/supported-versions.html).

Please post code and output as text ([using proper markup](https://guides.github.com/features/mastering-markdown/)). Do not post screenshots of code or output.


## Workflow for Pull Requests

1. Fork the repository.
2. Create your branch from `main` if you plan to implement new functionality or change existing code significantly; create your branch from the oldest branch that is affected by the bug if you plan to fix a bug.
3. Implement your change and add tests for it.
4. Ensure the test suite passes.
5. Ensure the code complies with our coding guidelines (see below).
6. Send that pull request!

Please make sure you have [set up your username and email address](https://git-scm.com/book/en/v2/Getting-Started-First-Time-Git-Setup) for use with Git. Strings such as `silly nick name <root@localhost>` look really stupid in the commit history of a project.

We encourage you to [sign your Git commits with your GPG key](https://docs.github.com/en/github/authenticating-to-github/signing-commits).

Pull requests for bug fixes must be made for the oldest branch that is supported (see above). Pull requests for new features must be based on the `main` branch.

We are trying to keep backwards compatibility breaks to an absolute minimum. Please take this into account when proposing changes.

Due to time constraints, we are not always able to respond as quickly as we would like. Please do not take delays personal and feel free to remind us if you feel that we forgot to respond.


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
