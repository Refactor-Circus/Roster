# Product Mission

## Problem

Laravel apps re-implement user management every time: users and profiles, organizations and teams, memberships and invitations, roles and permissions. Each implementation ships its own controllers, API, admin screens, and authorization rules, and they drift apart across apps.

## Target Users

- Laravel developers building multi-tenant SaaS apps that need users, teams, and roles out of the box.
- Teams building internal/admin Laravel tools that need user administration quickly.
- The Refactor Circus package ecosystem (Atrium, Impex, Cortex) as a shared user-management layer.

## Solution

Headless, Action-first user management. Every operation is a single Action class, and that Action is the one API: callable from PHP, exposed over the HTTP API, exposed as an MCP tool, and reachable from the Atrium dashboard. Surface parity is enforced, so no capability exists in one surface but not the others.
