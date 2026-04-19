# YouTube Course Scraper

A Laravel 13 application that turns a list of categories into curated YouTube playlists. Categories go in, the AI generates relevant playlist queries, the YouTube Data API enriches each result with stats, and the frontend renders cards with category tabs, pagination, and shareable URL state.

## Stack

- **Backend** — Laravel 13, PHP 8.4, SQLite (default)
- **AI** — official [`laravel/ai`](https://github.com/laravel/ai) SDK over OpenRouter (default model: `google/gemini-3.1-flash-lite-preview`)
- **YouTube** — Data API v3 (search, playlistItems, videos) via custom service with `Http::pool`
- **Frontend** — Alpine.js 3, Bootstrap 5.3, `postcss-rtlcss`, PurgeCSS
- **Tooling** — Pest 4, PHPStan + Larastan (level max), Pint, Rector

## Setup

Three commands, then add two API keys:

```bash
git clone git@github.com:shaheenfawzy/m3aarf-test.git && cd m3aarf-test
composer setup
npm run build
php artisan serve
```

### Required `.env` keys

```dotenv
YOUTUBE_API_KEY=your-google-cloud-key
OPENROUTER_API_KEY=your-openrouter-key
```

I used OpenRouter as the provider. To swap to any other lab supported by `laravel/ai`, set `AI_DEFAULT_PROVIDER` and supply the matching `{LAB}_API_KEY` (e.g. `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`), then point `AI_TITLE_GENERATOR_MODEL` at a model that lab serves:

```dotenv
AI_DEFAULT_PROVIDER=openrouter
AI_TITLE_GENERATOR_MODEL=google/gemini-3.1-flash-lite-preview
```

## Endpoints

| Method | Path                | Purpose                                                 |
|--------|---------------------|---------------------------------------------------------|
| `GET`  | `/`                 | SPA shell (hydrates from URL)                           |
| `POST` | `/courses/discover` | Generate AI titles, search YouTube, persist results     |
| `GET`  | `/courses`          | Read persisted courses, filter by category, eager-load |

`POST /courses/discover` is throttled to **5 req/min** per IP to protect the YouTube quota.

## Decisions

### `Http::pool` for YouTube API fan-out
The discover flow hits YouTube three times per category and once more per playlist batch. Done sequentially that's seconds of wall time per request, mostly waiting on TLS handshakes. `Http::pool` runs the calls concurrently inside one Guzzle pool — measured ~3× faster than serial for typical 5-category requests. Pool failures are caught per-response so one bad query doesn't poison the batch.

### Custom YoutubeService instead of `alaouy/youtube`
Existing community packages wrap individual endpoints but don't expose `Http::pool`. Since concurrency was the whole point, a thin custom client was simpler than monkey-patching a package. The service is also easier to mock in tests (`Http::fake` works directly, no package internals to stub).

### Official `laravel/ai` SDK over vendor-specific SDKs
Vendor-specific SDKs lock you in. The official `laravel/ai` package exposes a unified provider abstraction with **structured output via JSON schema** and an **agent pattern** — `YoutubeTitleGenerator` declares its output schema and gets back validated PHP objects, no parsing or retries on bad JSON. Switching from OpenRouter → OpenAI → Anthropic is a config flip, not a rewrite.

### Alpine.js over vanilla / over Vue/React
The page has reactive state (tags, filters, pagination, URL sync) but no routing or component tree to justify a SPA framework. Alpine gives declarative reactivity (`x-show`, `x-for`, `x-transition`) directly in Blade with zero build complexity beyond what Vite already does for the SCSS. ~15 KB gzipped vs Vue's ~35 KB or React's ~45 KB, and the markup stays server-rendered and crawlable.

### Bootstrap via npm + PurgeCSS
The CDN build was the first iteration — easy but ships ~230 KB of unused CSS. Migrating to npm and running **PurgeCSS in production** (scanning `.blade.php` + `.js` for actually-referenced classes) drops the final CSS to around a quarter of the CDN size.

### `postcss-rtlcss` in `override` mode
Rather than maintaining two stylesheets or littering Blade with `me-*`/`ms-*` swaps, source SCSS is written once in LTR and PostCSS rewrites directional properties at build time. `override` mode (vs `combined`) skips the `[dir=ltr]` selectors entirely since the app is RTL-only — smaller output.

### URL-as-state with `URLSearchParams` + `history.replaceState`
`?categories=php,laravel&category_id=3&page=2` lives in the address bar. Refresh restores the view, links are shareable, browser back/forward Just Works. `replaceState` (not `pushState`) avoids polluting history with every tag toggle. Hydration runs in `init()` *before* the first fetch.

### YoutubeService caching layer
Search results are cached by query (`Cache::remember`) so identical category lookups inside the same window skip the network entirely. The pool path mixes cache hits and misses transparently — only misses go into the actual HTTP pool. Covered by `it searches many queries in one pool, mixing cache hits and misses`.

### Idempotent persistence via `updateOrCreate(playlist_id)`
Re-running discover with the same categories doesn't duplicate rows; it refreshes stats. `playlist_id` is the natural key from YouTube. Tested by `it is idempotent across repeat calls`.

---

## Quality

```bash
composer check
composer fix
```

## License

MIT.
