```markdown
# Chirper Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill teaches the development patterns and conventions used in the Chirper TypeScript codebase. While Chirper does not use a specific framework, it adheres to clear coding standards for file naming, imports, exports, commit messages, and testing. This guide will help you contribute code that matches the project's established style and workflows.

## Coding Conventions

### File Naming
- Use **PascalCase** for file names.
  - Example: `UserProfile.ts`, `ChirpList.ts`

### Import Style
- Use **relative imports** for modules within the codebase.
  - Example:
    ```typescript
    import { Chirp } from './Chirp';
    ```

### Export Style
- Use **named exports** instead of default exports.
  - Example:
    ```typescript
    // In Chirp.ts
    export interface Chirp { ... }

    // In another file
    import { Chirp } from './Chirp';
    ```

### Commit Messages
- Follow **conventional commit** format.
- Use prefixes such as `ci` for continuous integration related changes.
- Keep commit messages concise (average ~62 characters).
  - Example:
    ```
    ci: update build pipeline for Node 18 compatibility
    ```

## Workflows

### Continuous Integration Updates
**Trigger:** When updating CI/CD configuration or dependencies.
**Command:** `/ci-update`

1. Make necessary changes to CI/CD configuration files.
2. Commit changes using the `ci:` prefix in the commit message.
   - Example: `ci: update GitHub Actions workflow for Node 18`
3. Push your branch and open a pull request for review.

## Testing Patterns

- Test files use the `.test.ts` suffix.
  - Example: `Chirp.test.ts`
- The testing framework is not specified; follow existing patterns in test files.
- Place test files alongside the code they test or in a dedicated `tests` directory if present.

  Example test file:
  ```typescript
  // Chirp.test.ts
  import { Chirp } from './Chirp';

  describe('Chirp', () => {
    it('should create a chirp with text', () => {
      const chirp: Chirp = { text: 'Hello world!' };
      expect(chirp.text).toBe('Hello world!');
    });
  });
  ```

## Commands
| Command      | Purpose                                              |
|--------------|------------------------------------------------------|
| /ci-update   | Guide for updating CI/CD configuration and workflows |
```
