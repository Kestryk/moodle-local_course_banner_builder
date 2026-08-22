const {test, expect} = require('@playwright/test');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const environment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_PRIMARY_ACCORDION_SOURCE_CATEGORY_ID',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, `Missing primary-accordion environment values: ${missing.join(', ')}`);
    const categoryId = String(process.env.EASYEDU_CCB_PRIMARY_ACCORDION_SOURCE_CATEGORY_ID);
    ensure(/^\d+$/.test(categoryId), 'Primary-accordion source category must be numeric.');
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        categoryId,
    };
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

const inspectAccordion = async accordion => accordion.evaluate(details => {
    const summary = details.querySelector(':scope > summary');
    const heading = summary?.querySelector(':scope > .local-course-banner-builder-section-heading');
    const chevron = summary?.querySelector(':scope > .local-course-banner-builder-primary-accordion-chevron');
    const legacyChevron = summary?.querySelector('.local-course-banner-builder-collapse-icon');
    const settings = summary?.querySelector(':scope > .local-course-banner-builder-settings-action');
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, right: box.right, width: box.width, height: box.height};
    };
    const style = getComputedStyle(summary);
    const chevronStyle = chevron ? getComputedStyle(chevron) : null;
    const settingsStyle = settings ? getComputedStyle(settings) : null;
    const chevronRect = chevron ? rect(chevron) : null;
    const headingRect = heading ? rect(heading) : null;
    return {
        isDetails: details.tagName === 'DETAILS',
        isOpen: details.open,
        hasSummary: summary?.tagName === 'SUMMARY',
        minHeight: style.minHeight,
        summaryHeight: summary?.getBoundingClientRect().height || null,
        borderBottomWidth: style.borderBottomWidth,
        marginBottom: style.marginBottom,
        visiblePrimaryChevronCount: chevron && chevronStyle.display !== 'none' ? 1 : 0,
        primaryChevronFontSize: chevronStyle?.fontSize || null,
        primaryChevronOrder: chevronStyle?.order || null,
        primaryChevronTransform: chevronStyle?.transform || null,
        primaryChevronTransition: chevronStyle?.transitionProperty || null,
        primaryChevronLeftOfTitle: Boolean(chevronRect && headingRect && chevronRect.right <= headingRect.left + 1),
        legacyChevronDisplay: legacyChevron ? getComputedStyle(legacyChevron).display : null,
        settingsHeight: settings ? rect(settings).height : null,
        settingsMinHeight: settingsStyle?.minHeight || null,
        settingsBorderStyle: settingsStyle?.borderStyle || null,
    };
});

const assertPrimarySurface = (surface, referenceSurface) => {
    expect(surface.isDetails).toBe(true);
    expect(surface.hasSummary).toBe(true);
    expect(surface.isOpen).toBe(true);
    expect(surface.visiblePrimaryChevronCount).toBe(1);
    expect(surface.primaryChevronLeftOfTitle).toBe(true);
    expect(surface.primaryChevronOrder).toBe('-1');
    expect(surface.legacyChevronDisplay).toBe('none');
    expect(surface.minHeight).toBe(referenceSurface.minHeight);
    expect(Math.abs(surface.summaryHeight - referenceSurface.summaryHeight)).toBeLessThanOrEqual(1);
    expect(surface.borderBottomWidth).toBe(referenceSurface.borderBottomWidth);
    expect(surface.marginBottom).toBe(referenceSurface.marginBottom);
    expect(surface.primaryChevronFontSize).toBe('12.48px');
    expect(surface.primaryChevronTransform).not.toBe('none');
    expect(surface.primaryChevronTransition).toContain('transform');
};

const assertNativeSummaryToggles = async(page, accordion) => {
    const summary = accordion.locator(':scope > summary');
    const openSurface = await inspectAccordion(accordion);
    await summary.focus();
    await expect(summary).toBeFocused();
    await summary.press('Enter');
    await expect(accordion).not.toHaveAttribute('open', 'open');
    await page.waitForTimeout(200);
    const closedSurface = await inspectAccordion(accordion);
    expect(closedSurface.primaryChevronTransform).not.toBe(openSurface.primaryChevronTransform);
    await summary.press('Enter');
    await expect(accordion).toHaveAttribute('open', 'open');
    await page.waitForTimeout(200);
    const reopenedSurface = await inspectAccordion(accordion);
    expect(reopenedSurface.primaryChevronTransform).toBe(openSurface.primaryChevronTransform);
};

test('CCB primary accordions keep parity, left chevrons and native disclosure state', async({page}) => {
    const env = environment();
    await page.setViewportSize({width: 1440, height: 1024});
    await login(page, env);
    await page.goto(
        `${env.baseUrl}/local/course_banner_builder/admin_manage.php?categoryid=${encodeURIComponent(env.categoryId)}`,
        {waitUntil: 'domcontentloaded'}
    );

    const selected = page.locator('details[data-primary-accordion="selected-source"]');
    const configured = page.locator('details[data-primary-accordion="configured-sources"]');
    const reference = page.locator('details.local-course-banner-builder-options-panel').first();
    const settings = selected.locator(':scope > summary .local-course-banner-builder-settings-action');

    await expect(selected).toHaveCount(1);
    await expect(configured).toHaveCount(1);
    await expect(reference).toHaveCount(1);
    await expect(settings).toHaveCount(1);
    await expect(selected).toHaveAttribute('open', 'open');
    await expect(configured).toHaveAttribute('open', 'open');
    await expect(settings).toHaveAttribute('type', 'button');
    await expect(settings).toHaveAttribute('data-action', 'local-course-banner-builder-summary-action');
    await expect(settings).toHaveAttribute('data-bs-target', '#local-course-banner-builder-source-settings-modal');
    expect(await settings.evaluate(button => (button.getAttribute('aria-label') || button.textContent || '').trim())).not.toBe('');
    await settings.focus();
    await expect(settings).toBeFocused();

    await reference.evaluate(details => {
        details.open = true;
    });
    const [referenceSurface, selectedSurface, configuredSurface] = await Promise.all([
        inspectAccordion(reference),
        inspectAccordion(selected),
        inspectAccordion(configured),
    ]);
    assertPrimarySurface(selectedSurface, referenceSurface);
    assertPrimarySurface(configuredSurface, referenceSurface);
    expect(selectedSurface.settingsHeight).toBeGreaterThanOrEqual(24);
    expect(selectedSurface.settingsHeight).toBeLessThanOrEqual(referenceSurface.summaryHeight);
    expect(selectedSurface.settingsMinHeight).toBe('31.2px');
    expect(selectedSurface.settingsBorderStyle).toBe('solid');

    await assertNativeSummaryToggles(page, selected);
    await assertNativeSummaryToggles(page, configured);

    await page.setViewportSize({width: 390, height: 844});
    const [mobileSelected, mobileConfigured] = await Promise.all([
        inspectAccordion(selected),
        inspectAccordion(configured),
    ]);
    for (const surface of [mobileSelected, mobileConfigured]) {
        expect(surface.visiblePrimaryChevronCount).toBe(1);
        expect(surface.primaryChevronLeftOfTitle).toBe(true);
        expect(surface.primaryChevronTransition).toContain('transform');
    }
});
