<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use local_course_banner_builder\hook_callbacks;

/**
 * Covers the public native-banner sizing rules emitted after compiled CSS.
 */
final class local_course_banner_builder_hook_callbacks_runtime_css_test extends advanced_testcase {
    /** Return the protected runtime stylesheet without changing public hooks. */
    protected function runtime_css(): string {
        $reflection = new ReflectionMethod(hook_callbacks::class, 'get_course_banner_runtime_css');
        $reflection->setAccessible(true);
        return $reflection->invoke(null);
    }

    public function test_nonstandard_formats_share_the_public_128px_floor(): void {
        $css = $this->runtime_css();
        $selector = '.local-course-banner-builder-native-course-banner--format-contentwide,' . "\n" .
            '.local-course-banner-builder-native-course-banner--format-fullwidthtop,' . "\n" .
            '.local-course-banner-builder-native-course-banner--format-fullwidthtopcompact,' . "\n" .
            '.local-course-banner-builder-native-course-banner--format-fullwidthtopinset';
        $this->assertStringContainsString($selector . " {\n    min-height: 128px;\n}", $css);
        $this->assertStringNotContainsString('min-height: 136px;', $css);
        $this->assertStringNotContainsString('min-height: 144px;', $css);
        $this->assertStringNotContainsString('min-height: 150px;', $css);
    }

    /**
     * The runtime policy keeps the canonical public ratios and existing caps.
     *
     * @dataProvider nonstandard_format_provider
     */
    public function test_nonstandard_format_ratios_and_caps_are_preserved(
        string $format,
        string $ratio,
        int $maximum
    ): void {
        $css = $this->runtime_css();
        $selector = '.local-course-banner-builder-native-course-banner--format-' . $format;
        $pattern = '/' . preg_quote($selector, '/') . '\\s*\\{[^}]*' .
            'aspect-ratio:\\s*' . preg_quote($ratio, '/') . ';[^}]*' .
            'max-height:\\s*' . $maximum . 'px;/s';
        $this->assertMatchesRegularExpression($pattern, $css);
    }

    /** @return array */
    public static function nonstandard_format_provider(): array {
        return [
            'content wide' => ['contentwide', '5 / 1', 280],
            'full width top' => ['fullwidthtop', '5 / 1', 360],
            'compact' => ['fullwidthtopcompact', '8 / 1', 210],
            'inset' => ['fullwidthtopinset', '6.1 / 1', 300],
        ];
    }

    public function test_standard_runtime_base_rule_is_unchanged(): void {
        $css = $this->runtime_css();
        $base = '.local-course-banner-builder-native-course-banner {' . "\n" .
            '    width: min(100%, 1320px);' . "\n" .
            '    aspect-ratio: 4 / 1;' . "\n" .
            '    container-type: size;' . "\n" .
            '    min-height: 0;';
        $this->assertStringContainsString($base, $css);
        $this->assertStringNotContainsString(
            '.local-course-banner-builder-native-course-banner--format-standard {',
            $css
        );
    }
}
