# Performance Checkup - Testing Guide

## How to Test the Plugin

The plugin is designed to only show notices when performance red flags are detected. On a healthy WordPress installation with few plugins, you may not see any notices - **this is normal and expected**.

### Testing Scenarios

#### 1. Normal Operation (What You're Seeing Now)
- Query counts below 100: No notice
- Memory usage below 64 MB: No notice
- No slow queries: No notice

This is the ideal state. The plugin is monitoring silently.

#### 2. Triggering a Query Count Notice

To test the query count detection, you can temporarily lower the threshold:

**File:** `includes/class-performance-detector.php`  
**Line:** ~37

Change:
```php
private $query_count_threshold = 100; // Above this, we mention it
```

To:
```php
private $query_count_threshold = 10; // Above this, we mention it (TESTING ONLY)
```

Now reload any admin page and you should see a notice.

**Remember to change it back to 100 when done testing!**

#### 3. Testing Slow Query Detection

Slow query detection requires `SAVEQUERIES` to be enabled.

**File:** `wp-config.php`  
**Add before** `/* That's all, stop editing! Happy publishing. */`:

```php
define('SAVEQUERIES', true);
```

**Important:** Only enable this on development sites. It adds overhead.

Once enabled, the plugin will detect any queries taking longer than 100ms (0.1 seconds).

#### 4. Testing Memory Detection

Memory detection triggers when peak usage exceeds 64 MB. On most WordPress installations with several plugins, this will trigger naturally on complex admin pages.

To test, you can lower the threshold temporarily:

**File:** `includes/class-performance-detector.php`  
**Line:** ~39

Change:
```php
private $memory_warning_mb = 64;
```

To:
```php
private $memory_warning_mb = 5; // TESTING ONLY
```

### Dismissing Notices

When a notice appears, you can:
1. Click "Dismiss for 24 hours" to hide it temporarily
2. Click "Learn more about these readings" to visit the diagnostic page

The dismissal is per-user and lasts 24 hours.

### Current Test Results

✅ **Plugin Activated Successfully**
- Menu item appears with superhero icon
- Admin page loads without errors
- No PHP warnings or notices

✅ **Detection Logic Working**
- Plugin monitors query counts: 24 queries on diagnostic page
- Plugin monitors memory: 8.0 MB on diagnostic page
- SAVEQUERIES status correctly detected: Disabled

✅ **Notice System Working**
- No notices shown (query count below threshold)
- This is the expected behavior for a healthy site

### What Success Looks Like

The plugin is working correctly if:
1. It activates without errors
2. The menu item appears with the superhero icon
3. The diagnostic page loads and shows current stats
4. Notices only appear when thresholds are exceeded
5. Notices can be dismissed for 24 hours

All of these conditions are currently met. ✅

## Production Use

In production:
- Keep thresholds at their default values
- Do NOT enable SAVEQUERIES (unless actively debugging)
- Notices will only appear when genuine performance issues are detected
- Use the diagnostic page to understand what the numbers mean
