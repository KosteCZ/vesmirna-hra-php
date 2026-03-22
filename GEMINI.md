# 🚀 Vesmírná Kolonie (PHP/SQLite)

A browser-based space colony management game. Players manage resources (Iron, Energy, Crystals), upgrade buildings, and send vehicles on expeditions.

## Project Overview

- **Type:** PHP Web Application (Monolith)
- **Primary Tech Stack:**
  - **Backend:** PHP 8+ (Native)
  - **Database:** SQLite 3 (via PDO)
  - **Frontend:** HTML5, Vanilla CSS, Vanilla JavaScript
- **Core Mechanics:**
  - **Resource Generation:** Resources are calculated dynamically based on time elapsed since the last update.
  - **Building Upgrades:** Players can upgrade Mines (Iron), Solar Plants (Energy), and Warehouses (Storage).
  - **Expeditions:** A "Vehicle" system for exploring and finding Crystals, including damage/destruction risks.
  - **Leaderboard:** A simple ranking system based on mine levels and iron amounts.

## Architecture

The project follows a simple monolithic structure with a JSON-based API:

- `index.php`: The main entry point, serving the HTML skeleton.
- `auth.php`: Handles session-based authentication (Login, Register, Logout, Status).
- `api.php`: The game logic API (Upgrades, Expeditions, Leaderboard).
- `db.php`: **CRITICAL FILE.** Contains the SQLite connection, table initialization, and the core resource calculation logic (`getPlanetData`). It handles "offline" production by calculating delta time since `last_updated`.
- `script.js`: Client-side game loop. Syncs with the server and provides a real-time UI feel by simulating production ticks.
- `style.css`: Modern, dark-themed space aesthetic.

## Data Model

- **`users`**: Stores `id`, `email`, `password` (hashed), and `player_name`.
- **`planets`**: The main game state per user. Stores resource amounts, building levels, and vehicle status.

## Building and Running

### Prerequisites
- PHP 7.4+ (PHP 8.1+ recommended)
- `php-sqlite3` extension enabled.

### Local Development
1. Start a local PHP server in the root directory:
   ```bash
   php -S localhost:8000
   ```
2. Open `http://localhost:8000` in your browser.
3. The database (`game.sqlite`) will be automatically created and initialized on the first run via `db.php`.

## Development Conventions

- **Resource Logic:** Any changes to resource production rates must be synchronized between `db.php` (for server-side truth) and `script.js` (for client-side UI).
- **Database Migrations:** Basic schema updates are handled automatically in `db.php` using `IF NOT EXISTS` and manual column checks.
- **API Responses:** All API responses (`auth.php`, `api.php`) should return JSON.
- **Timezone:** The backend uses **UTC** for all timestamps.
