<div align="center">

# Composer package template

[![Coverage](https://img.shields.io/codecov/c/github/eliashaeussler/composer-package-template?logo=codecov&token=PZ4ICjI2Xb)](https://codecov.io/gh/eliashaeussler/composer-package-template)
[![Maintainability](https://img.shields.io/codeclimate/maintainability/eliashaeussler/composer-package-template?logo=codeclimate)](https://codeclimate.com/github/eliashaeussler/composer-package-template/maintainability)
[![CGL](https://img.shields.io/github/actions/workflow/status/eliashaeussler/composer-package-template/cgl.yaml?label=cgl&logo=github)](https://github.com/eliashaeussler/composer-package-template/actions/workflows/cgl.yaml)
[![Tests](https://img.shields.io/github/actions/workflow/status/eliashaeussler/composer-package-template/tests.yaml?label=tests&logo=github)](https://github.com/eliashaeussler/composer-package-template/actions/workflows/tests.yaml)

</div>

A [Project Builder](https://github.com/CPS-IT/project-builder) template for
new Composer packages, built with several preconfigured components. New
packages may contain configuration for CGL tools like PHPStan, PHPUnit and
Rector as well as preconfigured GitHub Actions and issue templates.

## 🚀 Features

|    | Feature                  | Tool                                                  |
|----|--------------------------|-------------------------------------------------------|
| 🏡 | Automatic code migration | [Rector](https://getrector.com/)                      |
| 🧠 | Code quality gate        | [CodeClimate](https://codeclimate.com/)               |
| 🦄 | Code coverage            | [Codecov](https://about.codecov.io/)                  |
| 🧹 | Coding standards         | [PHP-CS-Fixer](https://cs.symfony.com/)               |
| 🏎 | Continuous integration   | [GitHub Actions](https://github.com/features/actions) |
| 💅 | Dependency handling      | [Renovate](https://renovatebot.com/)                  |
| 🔍 | Static code analysis     | [PHPStan](https://phpstan.org/)                       |
| 💡 | Unit testing             | [PHPUnit](https://phpunit.de/)                        |

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/eliashaeussler/composer-package-template?label=version&logo=packagist)](https://packagist.org/packages/eliashaeussler/composer-package-template)
[![Packagist Downloads](https://img.shields.io/packagist/dt/eliashaeussler/composer-package-template?color=brightgreen)](https://packagist.org/packages/eliashaeussler/composer-package-template)

1. [Create](https://project-builder.cps-it.de/getting-started.html) a new project:

   ```bash
   composer create-project cpsit/project-builder
   ```

2. Select the package `eliashaeussler/composer-package-template`.
3. Follow all instructions and answer the questions.
4. Be happy with your new Composer package 🥳

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
