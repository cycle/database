# Contributing

We welcome contributions to the Cycle DBAL project. Whether you're looking to fix bugs, add new features, or improve documentation, your help is appreciated. Please follow the guidelines below to ensure a smooth contribution process.

Before submitting your pull request, ensure that your changes adhere to the following principles:

- Keep It Simple, Stupid (KISS)
- Follow PSR-12 coding standards
- Use `declare(strict_types=1);` at the beginning of all PHP files
- Include tests with your code to verify your changes

Feel free to join our **Discord** server for advice or suggestions: 🤖 [SpiralPHP Discord](https://discord.gg/spiralphp)

<br>

## 🛠️ Setting Up for Development

### → Testing Cycle DBAL

To set up a local development environment for testing:

1. Clone the `cycle/database` repository.

    ```bash
    git clone git@github.com:cycle/database.git
    ```
2. Navigate to the `tests/` directory and start the Docker containers:

    ```bash
    cd tests/
    docker compose up
    ```

3. To run the full test suite:

    ```bash
    ./vendor/bin/phpunit
    ```

4. For a quicker test suite, focusing on SQLite:

    ```bash
    ./vendor/bin/phpunit --group driver-sqlite
    ```

### → Workflow

1. Fork the repository on GitHub.
2. Create a new branch on your fork for your feature, fix, or update.
3. Make your changes, commit, and push them to your branch.
4. Submit a pull request to the `master` branch of the original repository.

Please ensure that each pull request focuses on a single feature, fix, or update to maintain clarity and ease of review. 

<br>

## 📝 Contribution Checklist

- **Tests:** Your PR should include tests that cover your changes.
- **Code Quality:** Run `make lint` to ensure your code follows our coding standards and `make lint-psalm` for static analysis with [Psalm](https://psalm.dev).
- **Documentation:** Update the documentation to reflect your changes or additions.

<br>

## ✉️ Commit Message Guidelines

We follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification. Commit messages should be structured as follows to ensure a clear and consistent project history:

**Allowed Prefixes:**

| Prefix     | Purpose                                                       |
|------------|---------------------------------------------------------------|
| `feat`     | Introduces a new feature                                      |
| `fix`      | Fixes a bug                                                   |
| `perf`     | Improves performance                                          |
| `docs`     | Documentation only changes                                    |
| `style`    | Code style changes (formatting, missing semi-colons, etc.)    |
| `deps`     | Updates dependencies                                          |
| `refactor` | Code changes that neither fixes a bug nor adds a feature      |
| `ci`       | Changes to our CI configuration files and scripts             |
| `test`     | Adding missing tests or correcting existing tests             |
| `revert`   | Reverts a previous commit                                     |
| `build`    | Changes that affect the build system or external dependencies |
| `chore`    | Other changes that don't modify src or test files             |

<br>

## 🔒 Reporting Security Vulnerabilities

If you discover a security vulnerability, please report it to us immediately via email at [team@spiralscout.com](mailto:team@spiralscout.com). We take security seriously and will promptly address any issues.

<br>

## 🤝 Help Wanted

If you're looking for ways to contribute but are unsure where to start, consider the following areas:

- **Documentation:** Help us improve and expand our documentation to make it more comprehensive and easier to understand.
- **Architecture Changes:** Propose or implement improvements to the project's architecture to enhance its efficiency, scalability, or usability.
- **Performance Enhancements:** Identify and contribute improvements to make Cycle DBAL faster and more resource-efficient.
- **Feature Suggestions:** Have an idea for a new feature? Let us know or contribute code to make it happen.

For more specific tasks or if you're unsure where to start, check our [Open Issues](https://github.com/cycle/database/issues).

<br>

## 🙋‍♂️ Official Support

Cycle DBAL is maintained by [Spiral Scout](https://spiralscout.com/). For commercial support, contact [team@spiralscout.com](mailto:team@spiralscout.com).

<br>

## 🔖 Licensing

Cycle DBAL is available under the [MIT license](/LICENSE.md).

<br>

## 🌐 Community and Conduct

Please ensure your interactions in the project are respectful and inclusive. Read our [Code of Conduct](https://github.com/cycle/database/blob/2.x/.github/CODE_OF_CONDUCT.md) for more information.
