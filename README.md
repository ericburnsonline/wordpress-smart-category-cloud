# Smart Category Cloud

Smart Category Cloud is a WordPress plugin that generates a dynamic category word cloud where each category’s size reflects both the number of posts and how recently those posts were published.

Clicking a category filters the blog listing and displays the most recent posts first.

---

## Features

- Weighted category cloud based on:
  - Post count (log-scaled)
  - Recency (exponential decay)
- Clean, responsive layout
- Category filtering via URL parameter
- Posts sorted by most recent first
- Lightweight, no dependencies (no jQuery)
- Designed to work across most WordPress themes

---

## How It Works

Each category is scored using a combination of:

- Post volume (normalized using logarithmic scaling)
- Recency of the latest post (decay over time)

These scores are combined to determine font size in the cloud, making active and relevant topics more prominent.

---

## Usage

Add the shortcode to any page or post:

    [smart_category_cloud]

Optional parameters:

    [smart_category_cloud posts_per_page="10" min_font="14" max_font="42"]

---

## Installation

1. Upload the plugin folder to:

       /wp-content/plugins/

2. Activate the plugin in WordPress

3. Add the shortcode to a page

---

## Example

A category with many recent posts will appear larger than one with older or fewer posts, helping highlight active topics.

---

## Current Status

This is an early version of the plugin.

- Core functionality implemented
- Basic styling included
- No AJAX filtering yet (page reload used)
- Not performance optimized yet
- Minimal testing
---

## Roadmap

- AJAX filtering (no page reload)
- Improved performance (reduce query load)
- Admin settings UI
- Caching (transients)
- Pagination support
- Accessibility improvements

---

## Development Notes

- Prefix: SCC_
- Text Domain: wordpress-smart-category-cloud
- No external dependencies
- Built with WordPress best practices in mind

---

## License

GPL v2 or later

---

## Contributing

Contributions, suggestions, and improvements are welcome.

---
