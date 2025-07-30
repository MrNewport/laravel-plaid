# Contributing to Laravel Plaid

First off, thank you for considering contributing to Laravel Plaid! It's people like you that make Laravel Plaid such a great tool for the Laravel community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How Can I Contribute?](#how-can-i-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Enhancements](#suggesting-enhancements)
  - [Pull Requests](#pull-requests)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Commit Guidelines](#commit-guidelines)
- [Review Process](#review-process)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to contact@mrnewport.com.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Set up your development environment (see [Development Setup](#development-setup))
4. Create a new branch for your feature or bugfix
5. Make your changes
6. Run tests to ensure everything works
7. Submit a pull request

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

**Bug Report Template:**

```markdown
### Description
[A clear and concise description of the bug]

### Steps to Reproduce
1. [First step]
2. [Second step]
3. [...]

### Expected Behavior
[What you expected to happen]

### Actual Behavior
[What actually happened]

### Environment
- Laravel Version: [e.g., 11.x]
- PHP Version: [e.g., 8.2]
- Package Version: [e.g., 1.0.0]
- Plaid Environment: [sandbox/development/production]

### Additional Context
[Add any other context about the problem here]
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

**Enhancement Request Template:**

```markdown
### Description
[A clear and concise description of the enhancement]

### Motivation
[Why is this enhancement needed? What problem does it solve?]

### Proposed Solution
[Describe how you envision this working]

### Alternatives Considered
[What other solutions have you considered?]

### Additional Context
[Any other information that might be helpful]
```

### Pull Requests

1. Fill in the required template
2. Do not include issue numbers in the PR title
3. Include screenshots and animated GIFs in your pull request whenever possible
4. Follow the [Coding Standards](#coding-standards)
5. Include thoughtfully-worded, well-structured tests
6. Document new code
7. End all files with a newline

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Installation

1. Clone your fork:
```bash
git clone https://github.com/your-username/laravel-plaid.git
cd laravel-plaid
```

2. Install dependencies:
```bash
composer install
```

3. Set up git hooks (optional but recommended):
```bash
cp .git/hooks/pre-commit.sample .git/hooks/pre-commit
# Edit the pre-commit hook to run tests and linting
```

### Environment Configuration

Create a `.env.testing` file for running tests:

```env
PLAID_CLIENT_ID=your_test_client_id
PLAID_SECRET=your_test_secret
PLAID_ENVIRONMENT=sandbox
```

## Coding Standards

This project follows PSR-12 coding standards. We use Laravel Pint for code formatting:

```bash
# Format code
composer format

# Check formatting without making changes
./vendor/bin/pint --test
```

### Key Guidelines

1. **Namespace**: All code should use the `MrNewport\LaravelPlaid` namespace
2. **Type Hints**: Use PHP type hints for all parameters and return types
3. **Docblocks**: Add docblocks for all public methods
4. **Naming Conventions**:
   - Classes: `PascalCase`
   - Methods: `camelCase`
   - Properties: `camelCase`
   - Constants: `UPPER_SNAKE_CASE`

### Example Code Style

```php
<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\DTOs\Account;
use MrNewport\LaravelPlaid\Exceptions\PlaidException;

class AccountsService extends BaseService
{
    /**
     * Get accounts for an access token
     *
     * @param string $accessToken The Plaid access token
     * @param array $options Optional parameters
     * @return array<Account>
     * @throws PlaidException
     */
    public function get(string $accessToken, array $options = []): array
    {
        $response = $this->client->post('/accounts/get', [
            'access_token' => $accessToken,
            ...$options,
        ]);

        return array_map(
            fn($account) => new Account($account),
            $response['accounts']
        );
    }
}
```

## Testing

We use Pest for testing. All new features must include tests.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/Unit/Services/AccountsServiceTest.php

# Run tests in parallel (faster)
./vendor/bin/pest --parallel
```

### Writing Tests

1. **Unit Tests**: Place in `tests/Unit/`
2. **Feature Tests**: Place in `tests/Feature/`
3. **Test Naming**: Use descriptive test names with the `it()` function

Example test:

```php
<?php

use MrNewport\LaravelPlaid\Services\AccountsService;
use MrNewport\LaravelPlaid\PlaidClient;

it('gets accounts successfully', function () {
    $mockClient = Mockery::mock(PlaidClient::class);
    $mockClient->shouldReceive('post')
        ->with('/accounts/get', ['access_token' => 'test_token'])
        ->once()
        ->andReturn([
            'accounts' => [
                ['account_id' => 'test123', 'name' => 'Checking'],
            ],
        ]);

    $service = new AccountsService($mockClient);
    $accounts = $service->get('test_token');

    expect($accounts)->toHaveCount(1);
    expect($accounts[0]->account_id)->toBe('test123');
});
```

### Test Coverage

We aim for high test coverage. New code should include tests that cover:
- Happy path scenarios
- Error conditions
- Edge cases
- Different parameter combinations

## Documentation

### Code Documentation

- All public methods must have docblocks
- Include parameter types and descriptions
- Document thrown exceptions
- Add examples for complex methods

### README Updates

When adding new features, update the README.md to include:
- Usage examples
- Configuration options
- Any new requirements

### API Documentation

For new Plaid API endpoints, document:
- The endpoint purpose
- Required parameters
- Optional parameters
- Response structure
- Possible errors

## Commit Guidelines

We follow conventional commits for clear commit history:

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, missing semicolons, etc)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
# Feature
git commit -m "feat(accounts): add support for account balance refresh"

# Bug fix
git commit -m "fix(auth): handle missing routing numbers gracefully"

# Documentation
git commit -m "docs(readme): add examples for investment endpoints"

# Tests
git commit -m "test(transfer): add tests for ACH transfer authorization"
```

### Commit Body

For complex changes, include a body with:
- Motivation for the change
- Contrast with previous behavior
- Side effects or other unintuitive consequences

## Review Process

### Before Submitting

1. **Run Tests**: Ensure all tests pass
   ```bash
   composer test
   ```

2. **Format Code**: Run Pint to format your code
   ```bash
   composer format
   ```

3. **Update Documentation**: If needed, update README or add inline documentation

4. **Check Coverage**: Ensure your changes are tested
   ```bash
   composer test-coverage
   ```

### Pull Request Process

1. **Title**: Use a clear, descriptive title
2. **Description**: Fill out the PR template completely
3. **Link Issues**: Reference any related issues
4. **Small PRs**: Keep pull requests focused and small
5. **Respond to Feedback**: Address review comments promptly

### Review Criteria

PRs will be reviewed for:
- Code quality and adherence to standards
- Test coverage
- Documentation completeness
- Backward compatibility
- Security considerations

## Questions?

Feel free to:
- Open an issue for discussion
- Email support@mrnewport.com
- Check existing issues and PRs for similar topics

Thank you for contributing to Laravel Plaid!