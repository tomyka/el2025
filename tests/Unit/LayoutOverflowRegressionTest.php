<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guards against a specific regression: giving <body> (.sb-layout) a
 * partial overflow clip (e.g. overflow-x: hidden with overflow-y left
 * unset) forces the browser to also compute overflow-y as "auto" per the
 * CSS spec (an element can't mix overflow-x: non-visible with an actually
 * visible overflow-y). That turns <body> into its own scroll/clipping
 * container, which breaks Bootstrap Popover's container: 'body'
 * positioning math for anything below the very top of the page - popovers
 * render hundreds/thousands of pixels away from the hovered element.
 *
 * The fix moved the horizontal-scroll safety net from .sb-layout (body)
 * onto .sb-main (the content wrapper) instead. These tests pin that
 * invariant so it can't quietly regress.
 */
class LayoutOverflowRegressionTest extends TestCase
{
    private function cssRuleBody(string $selector): string
    {
        $css = file_get_contents(public_path('css/custom.css'));

        $this->assertNotFalse($css, 'Could not read public/css/custom.css');

        $pattern = '/(?<![\.\w-])'.preg_quote($selector, '/').'\s*\{([^}]*)\}/';
        $this->assertMatchesRegularExpression($pattern, $css, "Selector {$selector} not found in custom.css");

        preg_match($pattern, $css, $matches);

        return $matches[1];
    }

    public function test_body_layout_class_does_not_set_any_overflow(): void
    {
        $rule = $this->cssRuleBody('.sb-layout');

        $this->assertDoesNotMatchRegularExpression(
            '/overflow(-x|-y)?\s*:/i',
            $rule,
            '.sb-layout is applied directly to <body>. Setting overflow-x (or '
            .'-y) on it - even just one axis - makes the browser force the '
            .'other axis to compute as "auto" per the CSS overflow spec, '
            .'turning <body> into its own scroll container. That breaks '
            .'Bootstrap Popover\'s container:"body" positioning for any '
            .'element below the top of the page. Put overflow clipping on '
            .'.sb-main (or a narrower wrapper) instead - not on <body>.'
        );
    }

    public function test_main_content_wrapper_still_clips_horizontal_overflow(): void
    {
        $rule = $this->cssRuleBody('.sb-main');

        $this->assertMatchesRegularExpression(
            '/overflow-x\s*:\s*hidden/i',
            $rule,
            '.sb-main should carry the horizontal-scroll safety net that '
            .'used to live on <body> (.sb-layout). If this moved elsewhere, '
            .'update this test to match - just make sure it is not back on '
            .'<body>.'
        );
    }
}
