# Content Writing Tips

**Content Writing Tips** is a WordPress plugin developed by the **U.S. Department of Veterans Affairs** that surfaces centrally managed content-writing guidance across WordPress.

The plugin pulls tips from a remote WordPress REST API endpoint and makes them available as:

- A **Dashboard widget** with a slider  
- A **Shortcode** for use in posts, pages, or widgets  
- A **Gutenberg block** (Block API v3, server-rendered)

It is designed to meet **WordPress VIP coding standards**, while remaining fully functional in non-VIP environments.

---

## Features

- Displays accessibility, VA content style, and AP style tips  
- Daily cached pool of randomized tips  
- Slider UI for browsing multiple tips  
- Category filtering  
- Configurable tip count  
- Gutenberg block with live editor preview (ServerSideRender)  
- Shortcode support  
- Admin settings page  
- Cache flush control  

---

## Data Source

Tips are pulled from a remote REST API endpoint.

Each tip is expected to provide:

- Title  
- Tip text  
- Detail link  
- ARIA label for link  
- One or more Tip Categories  

The plugin assumes the remote post type and taxonomy already exist and does not register them locally.

---

## Installation

1. Upload the plugin to your WordPress installation:  
   `wp-content/plugins/content-writing-tips/`

2. Activate the plugin from **Plugins → Installed Plugins**

3. Visit **Settings → Content Writing Tips** to configure options

---

## Dashboard Widget

Once activated, a **Content Writing Tips** widget appears on the main WordPress Dashboard.

The widget:

- Displays a slider of tips selected from the daily cache  
- Includes:
  - Tip title  
  - Tip content  
  - Category label  
  - “Learn more” link  
  - “More tips” link  

---

## Shortcode Usage

Use the shortcode to display tips anywhere shortcodes are supported.

### Single tip (default)

```
[vact_tip_of_the_day]
```

### Slider with multiple tips

```
[vact_tip_of_the_day mode="slider" count="5"]
```

### Shortcode Attributes

| Attribute | Description | Default |
|--------|------------|---------|
| mode | `single` or `slider` | `single` |
| count | Number of tips to display (1–20) | `5` |

---

## Gutenberg Block

### Block Name

**Content Writing Tips**

- Block API version: **v3**  
- Server-rendered via PHP  
- Live preview in the editor using `ServerSideRender`

### Block Settings

In the block sidebar:

- **Display mode**
  - Single tip  
  - Slider (multiple tips)  
- **Tip count**
  - 1–20 tips (slider mode only)  

The block uses the same daily cache and category filters defined in the plugin settings.

---

## Admin Settings Page

**Location:**  
Settings → Content Writing Tips

### Available Options

- **Number of tips per day**
  - 5, 10, 15, or 20  
- **Categories to include**
  - Pulled dynamically from the remote API  
  - If none are selected, all categories are included  
- **Flush tip cache**
  - Clears today’s cached tips and forces a fresh API fetch  

### Additional Resources

The settings page also includes:

- Usage instructions for the dashboard widget, shortcode, and block  
- Link to additional guidance 
- Email link to suggest new tips

---

## Caching Behavior

- Tips are fetched once per day and cached using WordPress transients  
- Cache keys are date-based (UTC)  
- Cache can be manually flushed from the settings page  
- Designed to minimize remote API calls  

---

## Assets & Architecture

The plugin is intentionally modular:

```
content-writing-tips/
├── assets/
│   ├── css/
│   │   └── vact-tip.css
│   └── js/
│       ├── vact-tip-slider.js
│       └── vact-tip-block.js
├── blocks/
│   └── content-writing-tips/
│       └── block.json
├── inc/
│   ├── assets.php
│   ├── block.php
│   ├── core.php
│   ├── dashboard-widget.php
│   ├── settings-page.php
│   └── shortcode.php
└── content-writing-tips.php
```

---

## Accessibility

- Semantic HTML output  
- ARIA labels passed through from content  
- Keyboard-accessible slider controls  
- Screen-reader-friendly status text  
- Designed to align with Section 508 and WCAG expectations  

---

## Compatibility

- WordPress: 6.8+  
- PHP: 7.2+  
- Works on:
  - WordPress VIP  
  - Pressable  
  - Standard WordPress hosting  
- No external JavaScript libraries  

---

## License

GPL-2.0-or-later

---

## Maintainer

**U.S. Department of Veterans Affairs**

For suggestions or new content ideas:  
📧 **vawordpressadmin@va.gov**
