# Project Guidelines

This file contains the rules and preferences for developing this project.

## Git and Version Control

* **Never run Git commands**: The user prefers to manage all Git operations manually (`add`, `commit`, `push`, and similar). Never attempt to perform these actions autonomously or suggest automatic shell execution.
* **Do not create Git commits or branches on your own**.
* **Do not assume version-control actions are allowed unless explicitly requested**.

## Code Architecture

* **Use the Actions architecture** for application logic and business workflows.
* Keep responsibilities separated and avoid placing business rules directly inside controllers, models, or views.
* Use form requests for validation and input transformation.
* Prefer small, focused classes with a single responsibility.
* Ensure the implementation remains consistent with the project’s architecture and naming conventions.

## Models

* **Use `@property-read` annotations** in models whenever applicable.
* Keep model annotations aligned with the actual attributes, relationships, and casts.
* Use clear and accurate PHPDoc blocks to improve IDE support and code readability.

## Code Quality

* **Always run the code quality pipeline**: `sail artisan analyse`.
* Do not consider a feature complete until it passes the quality checks required by the project.
* Write code that is clean, readable, maintainable, and aligned with the existing standards.

## Testing

* **Cover 100% of the generated backend code** with tests.
* **Never use Mockery** in tests.
* **Always use `RefreshDatabase`** in tests that interact with the database.
* Prefer real behavior over excessive mocking.
* Ensure tests are deterministic, isolated, and easy to understand.

## Enums

* **Use enums whenever necessary** to represent fixed or well-defined domain values.
* Prefer enums over hardcoded strings or numbers when the value set is stable and meaningful.
* Keep enum names expressive and consistent with the domain language.

## Money and Financial Rules

* **Use the `Money` cast** for any money-related value.
* Remember that the project uses **microns** for financial precision.
* Handle monetary values carefully to avoid floating-point errors.
* Make currency conversions and calculations explicit and safe.

## Feature Validation

* **Always verify that every feature matches what is described in the README**.
* Do not implement behavior that conflicts with the documented architecture, rules, or domain expectations.
* When in doubt, use the README as the source of truth for business and technical decisions.

## Financial Operations

* **Guarantee idempotency in financial operations**.
* Prevent duplicate charges, duplicate ledger entries, and repeated side effects.
* Design endpoints, jobs, and actions so they can be safely retried without causing inconsistencies.
* Use safeguards such as unique identifiers, transaction boundaries, and idempotency checks where appropriate.

## General Development Principles

* Keep the implementation aligned with the domain model and business rules.
* Prefer explicit, predictable behavior over implicit assumptions.
* Favor maintainable solutions that are easy to test and evolve.
* Do not introduce unnecessary complexity.
