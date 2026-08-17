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
const AxeBuilder = require('@axe-core/playwright').default;

const moodleUrl = process.env.CCB_MOODLE_URL;
const username = process.env.CCB_MOODLE_USERNAME;
const password = process.env.CCB_MOODLE_PASSWORD;
const targetPath = process.env.CCB_ACCESSIBILITY_PATH ||
    '/local/course_banner_builder/admin_manage.php';
const pluginSelector = process.env.CCB_ACCESSIBILITY_SELECTOR ||
    '.local-course-banner-builder-admin';

/**
 * Build a Moodle URL without assuming whether Moodle is installed at /.
 *
 * @param {string} path Site-relative path.
 * @returns {string}
 */
const moodlePath = (path) => {
    const base = moodleUrl.endsWith('/') ? moodleUrl : `${moodleUrl}/`;
    return new URL(path.replace(/^\//, ''), base).toString();
};

test.describe('Course Banner Builder accessibility smoke', () => {
    test.skip(
        !moodleUrl || !username || !password,
        'Set CCB_MOODLE_URL, CCB_MOODLE_USERNAME, and CCB_MOODLE_PASSWORD.'
    );

    test('course banner administration has no blocking axe violations', async ({page}) => {
        await page.goto(moodlePath('/login/index.php'));
        await page.locator('#username').fill(username);
        await page.locator('#password').fill(password);
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login/index.php')),
            page.locator('#loginbtn').click(),
        ]);

        await page.goto(moodlePath(targetPath));
        const pluginRegion = page.locator(pluginSelector);
        await expect(pluginRegion).toBeVisible();

        const results = await new AxeBuilder({page})
            .include(pluginSelector)
            .withTags([
                'wcag2a',
                'wcag2aa',
                'wcag21a',
                'wcag21aa',
                'wcag22aa',
                'best-practice',
            ])
            .analyze();

        const blockingViolations = results.violations.filter((violation) =>
            ['critical', 'serious'].includes(violation.impact)
        );
        const report = blockingViolations.map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            help: violation.help,
            targets: violation.nodes.map((node) => node.target),
        }));

        expect(blockingViolations, JSON.stringify(report, null, 2)).toEqual([]);
    });
});
