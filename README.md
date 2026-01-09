# Performance Checkup

A WordPress plugin that helps site administrators identify basic performance red flags without overwhelming them.

## The Real Story Behind This Plugin

This plugin came out of real support work. When site admins complain about slowness, they usually need simple answers to simple questions:

- Is this page making too many database queries?
- Are any of those queries particularly slow?
- Is memory usage a factor?

They don't need complex profiling tools with flame graphs and microsecond timings. They need a gentle nudge when something is obviously wrong.

**Performance Checkup** provides that nudge. Nothing more, nothing less.

## What It Does

When you load admin pages, this plugin checks three things:

1. **Query Count** - How many database queries were executed
2. **Slow Queries** - Individual queries that took over 100ms (requires `SAVEQUERIES`)
3. **Memory Usage** - Peak memory consumption for the page

If any of these cross conservative thresholds, you'll see a calm, informational notice explaining what was found and why it might matter.

## What It Doesn't Do

- ❌ No frontend monitoring (admin only, by design)
- ❌ No continuous data collection or logging
- ❌ No automatic fixes or optimizations
- ❌ No database writes (except one transient for notice dismissal)
- ❌ No performance overhead (runs after page load)
- ❌ Not a replacement for Query Monitor or similar profilers

## Installation

1. Download or clone this repository into `/wp-content/plugins/performance-checkup/`
2. Activate the plugin through the WordPress admin
3. That's it. No configuration needed.

You'll see notices when performance red flags are detected. The plugin adds its own menu item in the WordPress admin sidebar with a superhero icon - click **Performance Checkup** to learn what the numbers mean.

## Understanding the Metrics

### Query Counts

**Normal range:** 20-50 queries for typical admin pages

**Worth noting:** 100+ queries suggests potential inefficiency

**Concerning:** 200+ queries often indicates a plugin running queries in loops

**When it's okay:** Complex pages (WooCommerce product editor, page builders) sometimes legitimately need more queries.

### Slow Queries

**Threshold:** 100ms (0.1 seconds) per individual query

**Common causes:**
- Missing database indexes
- Large tables without optimization
- Complex JOINs on big datasets
- Inefficient plugin code

**Important:** Requires `define('SAVEQUERIES', true);` in `wp-config.php`. Don't enable this in production unless actively debugging.

### Memory Usage

**Typical:** 20-40 MB for WordPress core, 60-80 MB with plugins

**Worth noting:** 64+ MB

**When it matters:** Only if you're hitting PHP memory limits and getting "memory exhausted" errors

## When to Ignore the Warnings

Performance Checkup intentionally uses conservative thresholds. You can safely ignore notices if:

- The admin feels fast for your needs
- You're on a page that legitimately needs lots of data
- You're on a development site where performance isn't critical
- You've investigated and the cause is acceptable
- You know the plugin/theme responsible and accept the trade-off

**The goal isn't perfection.** The goal is awareness.

## Technical Design Choices

### Why admin-only?

Frontend performance is a different beast that deserves dedicated tools. Mixing admin and frontend monitoring creates complexity we don't need. This plugin stays in its lane.

### Why no database logging?

Every database write is overhead and potential bloat. We show notices when detected and let admins decide what to do. No historical tracking needed.

### Why conservative thresholds?

Alarmist warnings train people to ignore warnings. We'd rather miss edge cases than cry wolf constantly.

### Why singleton classes?

Simple, predictable, easy to debug. We're not building a framework here.

### Why no automatic fixes?

Performance issues are symptoms, not diseases. Automatic fixes often mask underlying problems without addressing root causes. Better to identify and let humans decide.

## Limitations & Trade-offs

1. **Snapshot only** - Shows metrics for individual page loads, not trends over time
2. **Admin context only** - Frontend performance requires different tools
3. **SAVEQUERIES dependency** - Slow query detection needs this enabled (not recommended for production)
4. **No query breakdown** - We don't show which plugin caused which query (use Query Monitor for that)
5. **Basic heuristics** - Thresholds are educated guesses, not scientific measurements

These aren't bugs. They're intentional choices to keep the plugin simple, focused, and safe.

## FAQ

**Q: Should I use this instead of Query Monitor?**  
A: No. Query Monitor is a comprehensive profiling tool for developers. This is a simple health check for admins. They serve different purposes. You can run both.

**Q: Will this slow down my site?**  
A: No. It only runs after admin pages have already loaded. The overhead is negligible (a few array operations and number formatting).

**Q: Can I customize the thresholds?**  
A: Not through settings. You'd need to edit the class directly. We intentionally avoided a settings page to keep things simple.

**Q: Why are there notices on every admin page?**  
A: Use the "Dismiss for 24 hours" link. Notices only reappear if red flags are detected after the timeout. If you're seeing them constantly, something is genuinely worth investigating.

**Q: Does this work with multisite?**  
A: Yes, but it monitors each site independently. Network admin pages are just another admin context.

**Q: Should I enable SAVEQUERIES in production?**  
A: Generally no. It adds overhead. Enable it temporarily when debugging, then disable it. This plugin works fine without it (just without slow query detection).

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- `manage_options` capability to see notices and admin page

## Support Philosophy

This is a utility plugin born from support work, not a product. It's MIT licensed - use it, fork it, modify it as needed.

If you find bugs or have specific use cases not covered, issues and PRs are welcome. But remember: the goal is simplicity. Feature requests that add complexity probably won't fit.

## Credits

**Author:** Shafinoid  
**License:** GPL v2 or later

Built with appreciation for WordPress admins who just need clear, actionable information without the noise.

## Changelog

### 1.0.0 - 2026-01-09
- Initial release
- Query count detection
- Slow query detection (with SAVEQUERIES)
- Memory usage detection
- Admin notices with dismissal
- Explanatory admin page under Tools
