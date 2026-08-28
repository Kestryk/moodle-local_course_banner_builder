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
    test('releases the live Slideshow page without leaving inert controls', async ({page}, testInfo) => {
        test.setTimeout(90_000);
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
        await page.locator('#loginbtn').click({noWaitAfter: true});
        await expect(page).not.toHaveURL(/\/login\/index\.php$/, {timeout: 30_000});

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
        await page.screenshot({
            path: testInfo.outputPath('slideshow-ready-desktop.png'),
            fullPage: true,
        });

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

        const identityContract = await shell.locator(
            '.local-course-banner-builder-slideshow-page-identity'
        ).evaluate((identity) => ({
            eyebrow: identity.querySelector(
                '.local-course-banner-builder-slideshow-page-identity__eyebrow'
            ).textContent.trim(),
            navigationGap: parseFloat(window.getComputedStyle(identity).marginBlockEnd),
        }));
        expect(identityContract.eyebrow).toBe('Course Banner Builder');
        expect(identityContract.navigationGap).toBeGreaterThan(0);

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
        ).evaluateAll((previews) => previews.map((preview) => ({
            frame: window.getComputedStyle(preview, '::after').animationName,
            cue: window.getComputedStyle(
                preview.querySelector('.local-course-banner-builder-slideshow-page-skeleton__preview-line'),
                '::after'
            ).animationName,
        })));
        expect(previewMotion).toHaveLength(2);
        previewMotion.forEach((preview) => {
            expect(preview.frame).toBe('none');
            expect(preview.cue).toContain('easyedu-skeleton-shimmer');
        });

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

        const navigationContract = await skeleton.locator(
            '.local-course-banner-builder-slideshow-page-skeleton__navigation'
        ).evaluate((navigation) => ({
            minBlockSize: parseFloat(window.getComputedStyle(navigation).minBlockSize),
            guideIsCircle: window.getComputedStyle(
                navigation.querySelector('.local-course-banner-builder-slideshow-page-skeleton__navigation-mark')
            ).borderRadius === '50%',
            cueCount: navigation.querySelectorAll(
                '.local-course-banner-builder-slideshow-page-skeleton__navigation-item'
            ).length,
        }));
        expect(navigationContract.minBlockSize).toBeLessThanOrEqual(64);
        expect(navigationContract.guideIsCircle).toBe(true);
        expect(navigationContract.cueCount).toBe(1);
        await expect(skeleton.locator(
            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )).toHaveCount(0);

        const structuralContracts = await skeleton.locator(
            '.local-course-banner-builder-slideshow-page-skeleton__card'
        ).evaluateAll((cards) => cards.map((card) => ({
            blockAccentWidth: parseFloat(window.getComputedStyle(card).borderBlockStartWidth),
            frameAnimation: window.getComputedStyle(card, '::after').animationName,
            inlineAccentWidth: parseFloat(window.getComputedStyle(card).borderInlineStartWidth),
            cueAnimation: window.getComputedStyle(
                card.querySelector('.local-course-banner-builder-slideshow-page-skeleton__card-title'),
                '::after'
            ).animationName,
        })));
        expect(structuralContracts).toHaveLength(2);
        structuralContracts.forEach((card) => {
            expect(card.blockAccentWidth).toBeGreaterThan(1);
            expect(card.inlineAccentWidth).toBeLessThan(card.blockAccentWidth);
            expect(card.frameAnimation).toBe('none');
            expect(card.cueAnimation).toContain('easyedu-skeleton-shimmer');
        });

        const iconAlignment = await live.locator(
            '.local-course-banner-builder-slideshow-card-header > .fa'
        ).evaluateAll((icons) => icons.map((icon) => {
            const iconBox = icon.getBoundingClientRect();
            const headerBox = icon.parentElement.getBoundingClientRect();
            return Math.abs(
                (iconBox.top + (iconBox.height / 2)) - (headerBox.top + (headerBox.height / 2))
            );
        }));
        expect(iconAlignment).toHaveLength(2);
        iconAlignment.forEach((offset) => expect(offset).toBeLessThanOrEqual(1));

        await page.setViewportSize({width: 390, height: 844});
        await page.screenshot({
            path: testInfo.outputPath('slideshow-ready-mobile-390.png'),
            fullPage: true,
        });
        const mobileOverlap = await page.locator(
            '.local-course-banner-builder-slideshow-page-identity'
        ).evaluate((identity) => {
            const trigger = document.querySelector('[data-easyedu-navigation-open]');
            const triggerRect = trigger?.getBoundingClientRect();
            const content = identity.querySelectorAll(
                '.local-course-banner-builder-slideshow-page-identity__eyebrow, ' +
                '.local-course-banner-builder-slideshow-page-identity__title, ' +
                '.local-course-banner-builder-slideshow-page-identity__description'
            );
            return Boolean(triggerRect) && Array.from(content).some((element) => {
                const contentRect = element.getBoundingClientRect();
                return !(
                    triggerRect.right <= contentRect.left ||
                    triggerRect.left >= contentRect.right ||
                    triggerRect.bottom <= contentRect.top ||
                    triggerRect.top >= contentRect.bottom
                );
            });
        });
        expect(mobileOverlap).toBe(false);
        await shell.evaluate(node => {
            node.setAttribute('data-local-course-banner-builder-slideshow-skeleton-state', 'loading');
        });
        await live.evaluate(node => {
            node.hidden = true;
        });
        await skeleton.evaluate(node => {
            node.hidden = false;
        });
        await page.screenshot({
            path: testInfo.outputPath('slideshow-skeleton-mobile-390.png'),
            fullPage: true,
        });
        await skeleton.evaluate(node => {
            node.hidden = true;
        });
        await live.evaluate(node => {
            node.hidden = false;
        });
        await shell.evaluate(node => {
            node.setAttribute('data-local-course-banner-builder-slideshow-skeleton-state', 'ready');
        });

        const timeline = await page.evaluate(() => window.__ccbSlideshowSkeletonTimeline);
        expect(timeline[0].state).toBe('loading');
        expect(timeline.some((entry) => entry.state === 'ready')).toBe(true);
        expect(timeline.find((entry) => entry.state === 'ready').time - timeline[0].time).toBeGreaterThanOrEqual(1100);
    });
});
