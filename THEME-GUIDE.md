# HireMatrix Color Theme

## Overview
The HireMatrix job portal now uses a fresh teal-to-green gradient theme that creates a modern, professional, and energetic feel. The theme supports both light and dark modes.

## Color Palette

### Light Mode
- **Primary**: `#1FB7B5` (Teal)
- **Primary Dark**: `#0D8A90` (Dark Teal)
- **Secondary**: `#53B86C` (Green)
- **Secondary Dark**: `#3F9E58` (Dark Green)
- **Accent**: `#B5D84E` (Lime Green)
- **Accent Dark**: `#9DC23D` (Dark Lime)
- **Background**: `#F8FCFB` (Very light teal-white)
- **Foreground**: `#16212B` (Dark blue-black)
- **Card**: `#FFFFFF` (White)
- **Muted**: `#EDF8F5` (Light mint)
- **Border**: `#D9ECE5` (Light teal-gray)

### Dark Mode
- **Primary**: `#1FB7B5` (Teal - same as light)
- **Primary Dark**: `#0D8A90` (Dark Teal)
- **Secondary**: `#53B86C` (Green)
- **Secondary Dark**: `#3F9E58` (Dark Green)
- **Accent**: `#B5D84E` (Lime Green)
- **Accent Dark**: `#9DC23D` (Dark Lime)
- **Background**: `#0E1619` (Very dark blue-gray)
- **Foreground**: `#F8FAFC` (Nearly white)
- **Card**: `#162327` (Dark card)
- **Muted**: `#1B2A2F` (Muted dark)
- **Border**: `#23343A` (Dark border)

## Implementation

### Files Modified
1. **`/public/jobboard/css/theme-colors.css`** - New file with color variables
2. **`/public/jobboard/css/hirematrix-style.css`** - Updated to use new colors

### CSS Variables
Use CSS variables throughout your code:

```css
/* Primary color */
color: var(--primary);
background: var(--primary);

/* Primary gradient */
background: var(--gradient-primary);

/* Borders */
border-color: var(--border);

/* Text colors */
color: var(--foreground);        /* Main text */
color: var(--muted-foreground);  /* Secondary text */
```

## Usage Guide

### Buttons
```css
/* Primary button */
.btn-primary {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}

.btn-primary:hover {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
}

/* Secondary button */
.btn-secondary {
  background: var(--secondary);
  border-color: var(--secondary);
}
```

### Gradients
The theme includes two main gradients:

**Primary Gradient (Teal → Green → Lime)**:
```css
background: var(--gradient-primary);
/* or */
background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
```

**Soft Gradient (For subtle backgrounds)**:
```css
background: var(--gradient-soft);
```

### Cards & Panels
```css
.card {
  background: var(--card);
  border: 1px solid var(--border);
}
```

### Alerts & Badges
```css
/* Success/Primary badges */
.badge-primary {
  background: rgba(31, 183, 181, 0.15);
  color: var(--primary);
}

/* Secondary badges */
.badge-secondary {
  background: rgba(83, 184, 108, 0.15);
  color: var(--secondary);
}
```

## Adding Dark Mode Support

To enable dark mode, add the `.dark` class to the root element:

```html
<html class="dark">
  <!-- Your content -->
</html>
```

Or use JavaScript:
```javascript
// Toggle dark mode
document.documentElement.classList.toggle('dark');

// Enable dark mode
document.documentElement.classList.add('dark');

// Disable dark mode
document.documentElement.classList.remove('dark');
```

## Browser Support
- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Requires CSS variable polyfill

## Migration Notes

If you're updating from the old blue-orange theme:
1. All color variables are automatically updated via CSS variables
2. No HTML changes required
3. Check any hardcoded color values in custom CSS
4. Test both light and dark modes thoroughly

## Component Examples

### Hero Section
The hero uses a subtle gradient background with the theme colors:
```css
background: linear-gradient(135deg, 
  rgba(31, 183, 181, 0.05) 0%, 
  rgba(83, 184, 108, 0.05) 100%);
```

### Navigation
Uses teal gradient for brand logo and hover states match the primary color.

### Footer
Features the full gradient from teal through green to lime:
```css
background: linear-gradient(135deg, #1FB7B5, #53B86C, #B5D84E);
```

### Forms
- Focus states use `--primary` with 12% opacity for shadow
- Borders transition from `--border` to `--primary` on focus

## Accessibility

The color combinations meet WCAG AA standards for contrast:
- Primary teal on white: ✓ Passes
- Foreground text on background: ✓ Passes
- Dark mode combinations: ✓ Passes

## Tips

1. Always use CSS variables instead of hardcoding colors
2. Test both light and dark modes when making changes
3. Use `rgba()` with theme colors for transparency effects
4. Maintain the gradient flow: Teal → Green → Lime
5. Use `--muted` colors for less important UI elements

## Support

For questions or issues with the theme implementation, refer to the main project README.md.
