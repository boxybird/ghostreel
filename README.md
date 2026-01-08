# GhostReel

An experimental "zero-auth" movie dashboard. Leveraging Laravel 12 and the TMDB API, this app tracks visitor engagement via IP address to power real-time community pulse heatmaps and "ghost" watchlists in a neon-drenched interface.

## Features

- **Zero-Auth Architecture** - No user accounts required. All interactions are tracked by IP address, creating anonymous "ghost" profiles.
- **Real-Time Heatmaps** - Community engagement visualized through click-based heatmaps showing trending movies.
- **TMDB Integration** - Movie data powered by The Movie Database API via a dedicated `TmdbService`.
- **Ghost Watchlists** - Personal viewing history tracked per IP, creating ephemeral watchlists without registration.
- **Movie Search** - Search the TMDB catalog directly from the dashboard.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4
- **Database:** SQLite
- **Frontend:** Tailwind CSS 4
- **API:** TMDB (The Movie Database)
- **Testing:** Pest 4, Playwright

## Requirements

- PHP 8.4+
- Composer
- Node.js & npm
- TMDB API Key

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd ghostreel

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Add your TMDB API key to .env
# TMDB_API_KEY=your_api_key_here

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start the development server
composer run dev
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Main dashboard with heatmap |
| GET | `/search` | Search movies via TMDB |
| POST | `/click` | Log a movie click event |
| GET | `/heatmap-data` | Fetch heatmap statistics |
| GET | `/recent-views` | Get recent viewing activity |

## How It Works

1. **Visitor arrives** - IP address is captured automatically
2. **Browse movies** - Trending and popular movies displayed from TMDB
3. **Click tracking** - Each movie interaction is logged with IP, movie ID, and timestamp
4. **Heatmap generation** - Aggregate click data creates community engagement heatmaps
5. **Ghost watchlist** - IP-based history shows what "you" have explored

## Development

```bash
# Run tests
php artisan test

# Run Playwright E2E tests
npx playwright test

# Code formatting
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse

# Development server with hot reload
npm run dev
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
