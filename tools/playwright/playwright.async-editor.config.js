const {defineConfig} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const artifactRoot = process.env.EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT;
if (!artifactRoot || !path.isAbsolute(artifactRoot)) {
    throw new Error('EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT must be an absolute external path.');
}
const resolvedArtifacts = path.resolve(artifactRoot);
const repositoryRoot = path.resolve(__dirname, '../..');
if (resolvedArtifacts === repositoryRoot || resolvedArtifacts.startsWith(repositoryRoot + path.sep)) {
    throw new Error('Async editor Playwright artifacts must remain outside the repository.');
}
fs.mkdirSync(resolvedArtifacts, {recursive: true});

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: 'ccb-async-editor-actions.spec.js',
    outputDir: path.join(resolvedArtifacts, 'playwright-output'),
    timeout: 90000,
    use: {screenshot: 'only-on-failure', trace: 'retain-on-failure', video: 'off'},
});
