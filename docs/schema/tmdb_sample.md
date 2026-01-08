# TMDB API - Trending Movies Response Schema

## Endpoint

`GET https://api.themoviedb.org/3/trending/movie/day`

## Authentication

Bearer token via `Authorization: Bearer {token}` header.
Token configured in `config/services.php` as `services.tmdb.api_key`.

## Response Structure

### Pagination Meta

| Field           | Type | Description                    |
|-----------------|------|--------------------------------|
| `page`          | int  | Current page number            |
| `total_pages`   | int  | Total pages available (max 500)|
| `total_results` | int  | Total movies available         |

### Movie Object (`results[]`)

| Field               | Type    | Example                              | Used In App               |
|---------------------|---------|--------------------------------------|---------------------------|
| `id`                | int     | `1242898`                            | Yes - stored in movie_clicks |
| `title`             | string  | `"Predator: Badlands"`               | Yes - displayed & stored  |
| `poster_path`       | string  | `"/pHpq9yNUIo6aDoCXEBzjSolywgz.jpg"` | Yes - stored              |
| `backdrop_path`     | string  | `"/ebyxeBh56QNXxSJgTnmz7fXAlwk.jpg"` | Optional                  |
| `overview`          | string  | `"Cast out from his clan..."`        | Display only              |
| `release_date`      | string  | `"2025-11-05"`                       | Display only              |
| `vote_average`      | float   | `7.576`                              | Display only              |
| `vote_count`        | int     | `981`                                | No                        |
| `popularity`        | float   | `709.8901`                           | No                        |
| `genre_ids`         | int[]   | `[28, 878, 12]`                      | No                        |
| `adult`             | bool    | `false`                              | No                        |
| `media_type`        | string  | `"movie"`                            | No                        |
| `original_title`    | string  | `"Predator: Badlands"`               | No                        |
| `original_language` | string  | `"en"`                               | No                        |
| `video`             | bool    | `false`                              | No                        |

## Image URL Construction

Base URL: `https://image.tmdb.org/t/p/{size}{path}`

Sizes for posters: `w92`, `w154`, `w185`, `w342`, `w500`, `w780`, `original`

Example: `https://image.tmdb.org/t/p/w500/pHpq9yNUIo6aDoCXEBzjSolywgz.jpg`

## Sample Response (Single Movie)

```json
{
    "id": 1242898,
    "title": "Predator: Badlands",
    "poster_path": "/pHpq9yNUIo6aDoCXEBzjSolywgz.jpg",
    "backdrop_path": "/ebyxeBh56QNXxSJgTnmz7fXAlwk.jpg",
    "overview": "Cast out from his clan, a young Predator finds an unlikely ally in a damaged android and embarks on a treacherous journey in search of the ultimate adversary.",
    "release_date": "2025-11-05",
    "vote_average": 7.576,
    "popularity": 709.8901,
    "genre_ids": [28, 878, 12],
    "adult": false,
    "media_type": "movie"
}
```
