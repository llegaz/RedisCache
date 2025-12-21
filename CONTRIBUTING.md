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

This project follows **Git Flow** for branch management.

### Branch Structure
```
main
  ├─ Production-ready code
  ├─ Protected branch (no direct push allowed)
  ├─ Only updated via Pull Requests
  └─ Tagged with version numbers (v1.0.0, v1.1.0, etc.)

develop
  ├─ Integration branch
  ├─ Latest development code
  ├─ Feature branches merge here first
  └─ Base for new features

feature/*
  ├─ New features and improvements
  ├─ Created from: develop
  └─ Merged into: develop

hotfix/*
  ├─ Urgent production fixes
  ├─ Created from: main
  └─ Merged into: both main and develop

release/*
  ├─ Release preparation
  ├─ Created from: develop
  └─ Merged into: both main and develop
```

### Visual Flow
```
feature/new-feature
       ↓
    [commit & push]
       ↓
    [GitHub Actions runs tests] 🧪
       ↓
    [Create Pull Request to develop]
       ↓
    [Tests run again] 🧪
       ↓
    [Code Review] 👀
       ↓
    [Merge to develop] ✅
       ↓
    develop (stable)
       ↓
    [Create Pull Request to main]
       ↓
    [Tests run] 🧪
       ↓
    [Merge to main] ✅
       ↓
    [Tag release] 🏷️ v1.2.0
```

---

## Common Workflows

### Starting a New Feature
```bash
# 1. Make sure you have the latest develop
git checkout develop
git pull origin develop

# 2. Create a feature branch
git checkout -b feature/my-awesome-feature

# 3. Make your changes
# ... edit files ...

# 4. Commit your changes
git add .
git commit -m "feat: add awesome feature"

# 5. Push your branch
git push origin feature/my-awesome-feature

# 6. Open a Pull Request on GitHub
#    Source: feature/my-awesome-feature
#    Target: develop
```

**GitHub Actions will automatically run tests!** ✅

### Release Process
```bash
# 1. When develop is stable and ready for release
git checkout develop
git pull origin develop

# 2. Create a release branch
git checkout -b release/1.2.0

# 3. Update version in composer.json and CHANGELOG.md
# ... make version updates ...
git commit -m "chore: bump version to 1.2.0"

# 4. Push and create PR to main
git push origin release/1.2.0

# 5. Create PR on GitHub: release/1.2.0 → main
#    Tests will run ✅
#    After approval, merge to main

# 6. Tag the release
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release version 1.2.0"
git push origin v1.2.0

# 7. Merge main back to develop
git checkout develop
git merge main
git push origin develop
```

### Hotfix Process (Urgent Bug)
```bash
# 1. Create hotfix from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-bug-fix

# 2. Fix the bug
# ... fix code ...
git commit -m "fix: resolve critical security issue"

# 3. Push and create PR to main
git push origin hotfix/critical-bug-fix

# 4. Create PR: hotfix/critical-bug-fix → main
#    Tests run, after approval: merge

# 5. Also merge to develop to keep in sync
git checkout develop
git merge main
git push origin develop

# 6. Tag the hotfix release
git checkout main
git tag -a v1.2.1 -m "Hotfix version 1.2.1"
git push origin v1.2.1
```

---

## Development Setup

### Prerequisites

- **PHP:** 8.2 or 8.3
- **Redis:** 7.0 or higher
- **Composer:** 2.x
- **PHP Extensions:** `redis`, `json`, `mbstring`

### Local Installation
```bash
# 1. Clone the repository
git clone https://github.com/llegaz/RedisCache.git
cd RedisCache

# 2. Install dependencies
composer install

# 3. Start Redis (using Docker)
docker run -d -p 6379:6379 redis:7.2-alpine

# 4. Verify setup by running tests
composer test:integration
```

### Environment Variables
```bash
# Redis host (default: 127.0.0.1)
export REDIS_HOST=localhost

# Redis port (default: 6379)
export REDIS_PORT=6379

# Redis adapter (predis or phpredis)
export REDIS_ADAPTER=phpredis

# Persistent connection (true or false)
export REDIS_PERSISTENT=false
```

---

## Running Tests

### Available Test Commands
```bash
# Run integration tests (requires Redis)
composer test:integration

# Run unit tests (no Redis required)
composer test:unit

# Run all tests
composer test

# Generate coverage report
composer test:coverage
# → Opens coverage/index.html in your browser
```

### CI Testing

Tests run automatically on:
- ✅ Every push to `develop`
- ✅ Every Pull Request
- ✅ PHP versions: 8.2 and 8.3
- ✅ Redis version: 7.2

You can view test results in the [Actions tab](https://github.com/llegaz/RedisCache/actions).

---

## Code Quality Standards

### Coding Standards

- **Style:** PSR-12
- **Static Analysis:** PHPStan Level 8
- **Testing:** PHPUnit with minimum 80% coverage

### Before Committing
```bash
# Check code style
composer cs:check

# Auto-fix code style issues
composer cs:fix

# Run static analysis
composer stan

# Run all quality checks
composer quality
```

### Commit Message Format

We follow [Conventional Commits](https://www.conventionalcommits.org/):
```bash
feat: add support for Valkey compatibility
fix: resolve memory leak in persistent connections
docs: update README with new examples
test: add integration tests for Hash pools
chore: update dependencies
refactor: simplify key validation logic
perf: optimize serialization performance
style: fix code formatting
```

**Examples:**
```bash
# Good commits
git commit -m "feat: add 8KB key length support"
git commit -m "fix: handle whitespace in cache keys"
git commit -m "docs: add CONTRIBUTING.md"

# Bad commits
git commit -m "updates"
git commit -m "fixed stuff"
git commit -m "WIP"
```

---

## Pull Request Process

### PR Checklist

Before submitting a Pull Request, ensure:

- [ ] Code follows PSR-12 style guidelines
- [ ] All tests pass locally
- [ ] New tests added for new features
- [ ] Code coverage maintained or improved
- [ ] CHANGELOG.md updated (if applicable)
- [ ] Documentation updated (if applicable)
- [ ] Commit messages follow Conventional Commits

### Creating a Pull Request

1. **Push your branch** to GitHub
```bash
   git push origin feature/my-feature
```

2. **Open Pull Request** on GitHub
   - Go to: https://github.com/llegaz/RedisCache/pulls
   - Click "New Pull Request"
   - Select your branch
   - Fill in the PR template

3. **Wait for CI** 
   - GitHub Actions will run tests automatically
   - All checks must pass ✅

4. **Code Review**
   - Maintainer will review your code
   - Address any feedback
   - Push updates to the same branch

5. **Merge**
   - After approval and passing tests
   - PR will be merged (usually squash merge)
   - Your branch will be deleted

### PR Review Timeline

- **Initial review:** Within 2-3 days
- **Follow-up:** Within 1-2 days after updates

---

## Setting Up Pre-commit Hooks (Optional)

Automatically run quality checks before each commit:

**Create `.git/hooks/pre-commit`:**
```bash
#!/bin/sh

echo "🔍 Running pre-commit checks..."

# Check code style
composer cs:check
if [ $? -ne 0 ]; then
    echo "❌ Code style check failed. Run: composer cs:fix"
    exit 1
fi

# Run static analysis
composer stan
if [ $? -ne 0 ]; then
    echo "❌ PHPStan analysis failed."
    exit 1
fi

# Run tests
composer test
if [ $? -ne 0 ]; then
    echo "❌ Tests failed."
    exit 1
fi

echo "✅ All pre-commit checks passed!"
exit 0
```

**Make it executable:**
```bash
chmod +x .git/hooks/pre-commit
```

---

## Questions?

- 💬 **General Questions:** Open a [Discussion](https://github.com/llegaz/RedisCache/discussions)
- 🐛 **Bug Reports:** Open an [Issue](https://github.com/llegaz/RedisCache/issues)
- ✨ **Feature Requests:** Open an [Issue](https://github.com/llegaz/RedisCache/issues)
- 📧 **Direct Contact:** laurent@legaz.eu

---

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (see [LICENSE](LICENSE)).

---

## Recognition

All contributors will be recognized in the project README. Thank you for making RedisCache better! 🙏

---

**Happy coding!** 🚀
