# Block Breakpoint

The Block Breakpoint module makes it possible select one or more breakpoints that will
apply as a block condition.

On the rendering of the page an inline script will remove the block, if the selected
breakpoint media query does not match the current browser window dimensions. The
Javascript `MutationObserver` ensures, that this also apply on all dynamically added
elements, like AJAX or BigPipe.

## Installation

Install the Block Breakpoint module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further information.

## Configuration
1. Open the block configuration for your designated block.
2. Check the checkbox "Enable Block Breakpoint"
3. Select a breakpoint group (usually your Frontend theme)
4. Select the breakpoint for which the block should be shown.
5. Save the block.

## Use case: External scripts like advertisement
Usually you can achieve conditional display of blocks simply with hiding or showing
them by CSS. For external scripts like having an embed script for displaying mobile
and desktop ads and using CSS for the visibility will lead to wrong impression
statistics. The mobile banner script might be even loaded, when it is rendered on
desktop and so doubling the impressions in your ad system.

With the inline script and `MutationObserver` you ensure, that a block, that does
not meet the breakpoint's media query will never be processed further and the DOM
and never drawn by the browser. So you will only see the desktop banner impressions
in your statistics.

You have another use-case. Share it with the community and let us know how you're
using the module.

## Layout Builder Support
Layout Builder components (at the time of writing) do not support third party settings,
which this module is based on. You can use the patch of of
[this issue](http://drupal.org/node/3015152). This will make the module work with
Layout Builder components.

## IE Support
The inline script handling is not working for Internet Explorer <= 11. You will need
to add polyfill for `document.currentScript` support or implement a different
logic. The block will be removed also in older Internet Explorer, but external
contents might have been loaded before the removal.

## Manual usage
You can also use the feature completely independent from the Block UI. Simply attach
the block_breakpoint library and wrap your conditional content with the block_breakpoint
classes. Include the inline match template, if you want to ensure that the content of
block never will be loaded, if the media query does not match.

```xml
{{ attach_library('block_breakpoint/block_breakpoint') }}
<div class="block-breakpoint" data-block-breakpoint-media-query="(max-width: 600px)">
 {% include '@block_breakpoint/block-breakpoint-inline-match.html.twig' %}
 <p>My conditional content, that should only show on mobile.</p>
</div>
```

## Requirements

* Block, Breakpoint ([Drupal core](https://drupal.org/project/drupal))
