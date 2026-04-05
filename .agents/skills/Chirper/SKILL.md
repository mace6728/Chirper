```markdown
# Chirper Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill teaches you the core development patterns and workflows used in the Chirper TypeScript codebase. You'll learn the project's coding conventions, how to contribute using conventional commits, how to manage CI/CD workflows, and how to write and run tests. No framework is used, so patterns are straightforward and easy to follow.

## Coding Conventions

### File Naming
- **PascalCase** is used for file names.
  - Example: `UserProfile.ts`, `PostList.ts`

### Import Style
- **Relative imports** are preferred.
  - Example:
    ```typescript
    import { User } from './User';
    import { PostList } from '../components/PostList';
    ```

### Export Style
- **Named exports** are used throughout the codebase.
  - Example:
    ```typescript
    // UserProfile.ts
    export function UserProfile(props: UserProfileProps) { ... }
    ```

### Commit Messages
- **Conventional commit** format is enforced.
- Common prefixes:
  - `feat:` for new features
  - `ci:` for continuous integration or deployment changes
- Example:
  ```
  ci: update test workflow to use Node 18
  feat: add user mention parsing to posts
  ```

## Workflows

### CI Workflow Update
**Trigger:** When you need to modify or optimize CI/CD processes (e.g., deployment, test running).  
**Command:** `/update-ci`

1. Edit or optimize YAML files in `.github/workflows/` as needed.
   - Example: Update Node.js version in `test.yaml`
2. Update the Dockerfile or related deployment configuration if required.
   - Example: Edit `docker/app/Dockerfile.prod` to change the base image.
3. Commit your changes with a `ci:` prefix in the commit message.
   - Example:
     ```
     ci: switch to multi-stage Docker build for production
     ```
4. Push your changes and create a pull request.

**Files involved:**
- `.github/workflows/deploy.yaml`
- `.github/workflows/test.yaml`
- `docker/app/Dockerfile.prod`

## Testing Patterns

- **Test files** use the pattern `*.test.ts`.
- **Testing framework** is not specified, but tests are written in TypeScript.
- Example test file:
  ```typescript
  // UserProfile.test.ts
  import { UserProfile } from './UserProfile';

  describe('UserProfile', () => {
    it('renders user name', () => {
      // test implementation
    });
  });
  ```

## Commands

| Command     | Purpose                                               |
|-------------|-------------------------------------------------------|
| /update-ci  | Update CI/CD workflows and deployment configuration   |
```