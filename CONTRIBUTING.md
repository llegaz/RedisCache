# Contributing to RedisCache

Thank you for your interest in contributing to RedisCache! 🎉

## Table of Contents

- [Git Flow Workflow](#git-flow-workflow)
- [Development Setup](#development-setup)
- [Running Tests](#running-tests)
- [Code Quality Standards](#code-quality-standards)
- [Pull Request Process](#pull-request-process)
- [Questions](#questions)

---

## Git Flow Workflow

This project follows **A based rebase Git Flow** for branch management.

### Branch Structure
```
main
  ├─ Production-ready code
  ├─ Protected branch (no direct push allowed)
  ├─ Updated only via Pull Requests
  └─ Tagged with semantic versions (1.0.0, 1.1.0, etc.)

dev
  ├─ Integration branch for ongoing development
  ├─ Latest development code
  ├─ Base branch for all new features
  └─ Feature branches merge here first

feature/*
  ├─ New features and improvements
  ├─ Created from: dev
  └─ Merged into: dev

hotfix/*
  ├─ Urgent production fixes
  ├─ Created from: main
  └─ Merged into: both main AND dev

release/*
  ├─ Release preparation and version bumps
  ├─ Created from: dev
  └─ Merged into: both main AND dev
```

### Visual Workflow
```
feature/awesome-feature
       ↓
    [commit & push]
       ↓
    [GitHub Actions runs tests] 🧪
       ↓
    [Create Pull Request → dev]
       ↓
    [Tests run on PR] 🧪
       ↓
    [Code Review] 👀
       ↓history | grep tag
    [Merge to dev] ✅
       ↓
    dev (stable integration branch)
       ↓
    [Create Pull Request → main]
       ↓
    [Final tests run] 🧪
       ↓
    [Merge to main] ✅
       ↓
    [Tag release] 🏷️ 1.2.0
```

---

## Common Workflows

### Starting a New Feature
```bash
# 1. Ensure you have the latest dev branch
git checkout dev
git pull origin dev

# 2. Create a new feature branch
git checkout -b feature/my-awesome-feature

# 3. Make your changes
# ... edit files, write code ...

# 4. Commit your changes using Conventional Commits format
git add .
git commit -m "feat: add awesome feature"

# 5. Push your feature branch to GitHub
git push origin feature/my-awesome-feature

# 6. Open a Pull Request on GitHub
#    Source branch: feature/my-awesome-feature
#    Target branch: dev
#
# GitHub Actions will automatically run tests on your PR! ✅
```

### Release Process
```bash
# 1. Ensure dev branch is stable and ready for release
git checkout dev
git pull origin dev

# 2. Create a release branch
git checkout -b release/1.2.0

# 3. Update version in composer.json and CHANGELOG.md
# ... make version changes ...
git add composer.json CHANGELOG.md
git commit -m "chore: bump version to 1.2.0"

# 4. Push release branch and create Pull Request to main
git push origin release/1.2.0
# Open PR on GitHub: release/1.2.0 → main

# 5. After PR approval and merge to main, tag the release
git checkout main
git pull origin main
git tag -a 1.2.0 -m "Release version 1.2.0"
git push origin 1.2.0

# 6. Merge main back to dev to keep branches in sync
git checkout dev
git merge main
git push origin dev
```

### Hotfix Process (Urgent Production Bug)
```bash
# 1. Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-security-fix

# 2. Fix the urgent bug
# ... fix code ...
git add .
git commit -m "fix: resolve critical security vulnerability"

# 3. Push hotfix branch and create Pull Request to main
git push origin hotfix/critical-security-fix
# Open PR on GitHub: hotfix/critical-security-fix → main

# 4. After merge to main, also merge to dev to keep in sync
git checkout dev
git merge main
git push origin dev

# 5. Tag the hotfix release
git checkout main
git pull origin main
git tag -a 1.2.1 -m "Hotfix version 1.2.1"
git push origin 1.2.1
```

---

## Development Setup

### Prerequisites

- **PHP:** 8.4 or 8.5 (latest stable versions)
- **Redis:** 7.0 or higher
- **Composer:** 2.x
- **PHP Extensions:** `redis`, `json`, `mbstring`

### Local Installation
```bash
# 1. Clone the repository
git clone https://github.com/llegaz/RedisCache.git
cd RedisCache

# 2. Install Composer dependencies
composer install

# 3. Start Redis server (using Docker)
docker run -d -p 6379:6379 redis:7.2-alpine

# 4. Verify setup by running tests
composer test
```

---

## Running Tests

### Available Test Commands
Please check `composer.json` for more commands (verbose or test-only utilitary)
```bash
# Run the PSR-6 integration tests suite
composer test-psr6

# Run the PSR-16 integration tests suite
composer test-psr16

# Run integration tests (requires Redis server running)
composer pui

# Run functionnal tests (requires Redis server running)
composer puf

# Run unit tests (no Redis required)
composer pu

# Run the full tests suite
composer test
```

### Continuous Integration

Tests run automatically on GitHub Actions for:
- ✅ Every push to `dev` branch
- ✅ Every Pull Request to `main` or `dev`
- ✅ PHP versions: 8.2.x, 8.3.x, 8.4.x and 8.5.x (latest patches)
- ✅ Redis version: 7.2

View test results and build status: [GitHub Actions](https://github.com/llegaz/RedisCache/actions)

---

## Code Quality Standards

### Standards

- **Code Style:** PSR-12
- **Static Analysis:** PHPStan Level 8
- **Testing:** PHPUnit with minimum 80% code coverage
- **Documentation:** PHPDoc for all public methods

### Quality Check Commands
```bash
# Automatically fix code style issues
composer cs

# Run static analysis with PHPStan
composer stan
```

### Commit Message Format

Ideally follwing those requirements but this not mandatory.

**Format:**
```
<type>: <description>
```

**Types:**
- `feature`: New feature
- `fix`: Bug fix
- `documentation`: Documentation changes
- `test`: Test additions or modifications
- `chore`: Maintenance tasks (dependencies, build, etc.)
- `refactor`: Code refactoring without behavior changes
- `performance`: Performance improvements
- `style`: Code style changes (formatting, naming)

---

## Pull Request Process

### Pre-submission Checklist

Before submitting a Pull Request, ensure:

- [ ] Code follows PSR-12 style guidelines
- [ ] All tests pass locally (`composer test`)
- [ ] New tests added for new features or bug fixes
- [ ] Code coverage maintained or improved
- [ ] Documentation updated if API changes
- [ ] Commits follow Conventional Commits format
- [ ] `composer validate --strict` passes without errors
- [ ] PHPStan analysis passes (`composer stan`)

### Creating a Pull Request

**Step 1: Push your branch**
```bash
git push origin feature/my-feature
```

**Step 2: Open Pull Request on GitHub**
- Navigate to: https://github.com/llegaz/RedisCache/pulls
- Click "New Pull Request"
- Select your branch as source
- Select `dev` as target (or `main` for hotfixes)
- Fill in the Pull Request template

**Step 3: Automated Checks**
- GitHub Actions will automatically run the full test suite
- All status checks must pass ✅ before merge
- Review any failed checks and fix issues

**Step 4: Code Review**
- Wait for maintainer review (typically 2-3 days)
- Address any feedback or requested changes
- Push additional commits to the same branch
- Tests will run again automatically

**Step 5: Merge**
- After approval and passing tests, PR will be merged
- Merge strategy: usually squash and merge
- Source branch will be automatically deleted after merge

---

## Pre-commit Hooks (Optional but Recommended)

Automatically run quality checks before each commit to catch issues early:

**Create `.git/hooks/pre-commit` file:**
```bash
#!/bin/sh

echo "🔍 Running pre-commit quality checks..."

# Check code style
echo "→ Checking code style (PSR-12)..."
composer cs
if [ $? -ne 0 ]; then
    echo "❌ Code style check failed."
    echo "   Run 'composer cs:fix' to automatically fix issues."
    exit 1
fi

# Run static analysis
echo "→ Running PHPStan static analysis..."
composer stan
if [ $? -ne 0 ]; then
    echo "❌ PHPStan analysis failed."
    echo "   Fix the reported issues before committing."
    exit 1
fi

# Run tests
echo "→ Running test suite..."
composer test
if [ $? -ne 0 ]; then
    echo "❌ Tests failed."
    echo "   All tests must pass before committing."
    exit 1
fi

echo "✅ All pre-commit checks passed!"
echo "   Proceeding with commit..."
exit 0
```

**Make the hook executable:**
```bash
chmod +x .git/hooks/pre-commit
```

**Note:** Pre-commit hooks are local to your repository clone and not tracked by Git.

---

## Questions and Support

### Getting Help

- 💬 **General Questions:** Open a [Discussion](https://github.com/llegaz/RedisCache/discussions)
- 🐛 **Bug Reports:** Open an [Issue](https://github.com/llegaz/RedisCache/issues) with bug report template
- ✨ **Feature Requests:** Open an [Issue](https://github.com/llegaz/RedisCache/issues) with feature request template
- 📧 **Direct Contact:** laurent@legaz.eu

### Reporting Bugs

When reporting bugs, please include:
- PHP version (`php -v`)
- Redis version
- Adapter used (Predis or phpredis)
- Steps to reproduce the issue
- Expected vs actual behavior
- Any relevant error messages or stack traces

### Proposing Features

When proposing new features:
- Explain the use case and problem it solves
- Describe the proposed solution
- Consider backwards compatibility implications
- Be open to feedback and alternative approaches

---

## License

By contributing to RedisCache, you agree that your contributions will be licensed under the same license as the project (see [LICENSE](LICENSE) file).

---

## Recognition

All contributors are recognized and listed in the project README. Thank you for helping make RedisCache better! 🙏

Your contributions, whether code, documentation, bug reports, or feature ideas, are valued and appreciated.

---


**Checklist:**
- [ ] Rebased on target branch
- [ ] PSR-12 compliant
- [ ] Tests pass
- [ ] Coverage maintained
- [ ] Docs updated
- [ ] CHANGELOG updated
- [ ] Clean commit history

**Process:**
1. Push branch (force push after rebase)
2. Open PR on GitHub
3. CI runs automatically
4. Address review feedback
5. Rebase adequately if dev or main changed
6. Maintainer rebases and merges (no merge commits)

## Questions

- 💬 [Discussions](https://github.com/chegaz/RedisCache/discussions)
- 🐛 [Issues](https://github.com/llegaz/RedisCache/issues)
- 📧 laurent@legaz.eu

---

**@See** you space cowboy... 🚀
