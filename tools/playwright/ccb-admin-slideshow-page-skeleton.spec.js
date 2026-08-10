// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

const {test, expect} = require('@playwright/test');

const moodleUrl = process.env.CCB_MOODLE_URL;
const username = process.env.CCB_MOODLE_USERNAME;
const password = process.env.CCB_MOODLE_PASSWORD;

const moodlePath = (path) => {
    const base = moodleUrl.endsWith('/') ? moodleUrl : `${moodleUrl}/`;
    return new URL(path.replace(/^\//, ''), base).toString();
};

test.describe('CCB admin Slideshow page skeleton', () => {
    test('releases the live Slideshow page without leaving inert controls', async ({page}) => {
        test.skip(
            !moodleUrl || !username || !password,
            'Set process-local CCB Moodle credentials before running this leased scenario.'
        );

        await page.addInitScript(() => {
            window.__ccbSlideshowSkeletonTimeline = [];

            const recordShell = (shell) => {
                if (!shell) {
                    return;
                }
                const state = shell.getAttribute(
                    'data-local-course-banner-builder-slideshow-skeleton-state'
                );
                const timeline = window.__ccbSlideshowSkeletonTimeline;
                const previous = timeline[timeline.length - 1];
                if (!previous || previous.state !== state) {
                    timeline.push({state, time: performance.now()});
                }
            };

            const observer = new MutationObserver(() => {
                recordShell(document.querySelector(
                    '[data-local-course-banner-builder-slideshow-skeleton="1"]'
                ));
            });
            observer.observe(document, {
                attributes: true,
                attributeFilter: ['data-local-course-banner-builder-slideshow-skeleton-state'],
                childList: true,
                subtree: true,
            });
            document.addEventListener('DOMContentLoaded', () => {
                recordShell(document.querySelector(
                    '[data-local-course-banner-builder-slideshow-skeleton="1"]'
                ));
            }, {once: true});
        });

        await page.goto(moodlePath('/login/index.php'));
        await page.locator('#username').fill(username);
        await page.locator('#password').fill(password);
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login/index.php')),
            page.locator('#loginbtn').click(),
        ]);

        await page.goto(moodlePath('/local/course_banner_builder/admin_slideshow.php'));
        const shell = page.locator('[data-local-course-banner-builder-slideshow-skeleton="1"]');
        const live = shell.locator('[data-local-course-banner-builder-slideshow-skeleton-live="1"]');
        const skeleton = shell.locator('[data-local-course-banner-builder-slideshow-skeleton-placeholder="1"]');
        const status = shell.locator('[data-local-course-banner-builder-slideshow-skeleton-status="1"]');

        await expect(shell).toHaveAttribute(
            'data-local-course-banner-builder-slideshow-skeleton-state',
            'ready',
            {timeout: 3000}
        );
        await expect(shell).toHaveAttribute('aria-busy', 'false');
        await expect(live).not.toHaveAttribute('aria-hidden');
        await expect(live).not.toHaveAttribute('inert');
        await expect(skeleton).toBeHidden();
        await expect(status).toBeHidden();

        const headerOrder = await shell.evaluate((pageShell) => {
            const identity = pageShell.querySelector(
                '.local-course-banner-builder-slideshow-page-identity'
            );
            const navigation = pageShell.querySelector('[data-easyedu-navigation]');
            const skeletonHeading = pageShell.querySelector(
                '.local-course-banner-builder-slideshow-page-skeleton__heading'
            );
            const skeletonNavigation = pageShell.querySelector(
                '.local-course-banner-builder-slideshow-page-skeleton__navigation'
            );
            const appearsBefore = (first, second) => Boolean(first && second) &&
                Boolean(first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING);

            return {
                liveIdentityBeforeNavigation: appearsBefore(identity, navigation),
                skeletonIdentityBeforeNavigation: appearsBefore(skeletonHeading, skeletonNavigation),
            };
        });
        expect(headerOrder.liveIdentityBeforeNavigation).toBe(true);
        expect(headerOrder.skeletonIdentityBeforeNavigation).toBe(true);

        const visualContract = await skeleton.locator(
            '.local-course-banner-builder-slideshow-page-skeleton__title'
        ).evaluate((surface) => {
            const baseStyle = window.getComputedStyle(surface);
            const shimmerStyle = window.getComputedStyle(surface, '::after');
            return {
                animationDirection: shimmerStyle.animationDirection,
                animationDuration: shimmerStyle.animationDuration,
                animationName: shimmerStyle.animationName,
                backgroundColor: baseStyle.backgroundColor,
                backgroundImage: shimmerStyle.backgroundImage,
            };
        });
        expect(visualContract.backgroundColor).toBe('rgb(220, 231, 240)');
        expect(visualContract.backgroundImage).toContain('108deg');
        expect(visualContract.animationName).toContain('easyedu-skeleton-shimmer');
        expect(visualContract.animationDuration).toBe('2s');
        expect(visualContract.animationDirection).toBe('normal');

        const previewMotion = await skeleton.locator(
            '.local-course-banner-builder-slideshow-page-skeleton__preview'
        ).evaluate((preview) => ({
            frame: window.getComputedStyle(preview, '::after').animationName,
            cue: window.getComputedStyle(
                preview.querySelector('.local-course-banner-builder-slideshow-page-skeleton__preview-line'),
                '::after'
            ).animationName,
        }));
        expect(previewMotion.frame).toBe('none');
        expect(previewMotion.cue).toContain('easyedu-skeleton-shimmer');

        const navigationMotion = await skeleton.locator(
            '.local-course-banner-builder-slideshow-page-skeleton__navigation'
        ).evaluate((navigation) => ({
            frame: window.getComputedStyle(navigation, '::after').animationName,
            cue: window.getComputedStyle(
                navigation.querySelector(
                    '.local-course-banner-builder-slideshow-page-skeleton__navigation-mark'
                ),
                '::after'
            ).animationName,
        }));
        expect(navigationMotion.frame).toBe('none');
        expect(navigationMotion.cue).toContain('easyedu-skeleton-shimmer');

        const timeline = await page.evaluate(() => window.__ccbSlideshowSkeletonTimeline);
        expect(timeline[0].state).toBe('loading');
        expect(timeline.some((entry) => entry.state === 'ready')).toBe(true);
        expect(timeline.find((entry) => entry.state === 'ready').time - timeline[0].time).toBeGreaterThanOrEqual(1100);
    });
});
