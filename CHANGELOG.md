# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Client for all 211 BaseLinker API methods, grouped into resources, plus a generic `call()`.
- Named connections and runtime tokens (`BaseLinker::connection()`, `BaseLinker::using()`).
- Typed DTOs for orders, statuses, sources, packages, catalog products, inventories, warehouses, price groups, invoices, returns and journal entries.
- Lazy iterators for orders, invoices, returns and catalog products; chunked stock, price and external quantity updates.
- Journal poller with `JournalEventReceived` / `ReturnJournalEventReceived` events and the `baselinker:poll` command.
- Cache-based rate limiter per token (wait or throw).
