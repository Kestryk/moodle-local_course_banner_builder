'use strict';

const {defineConfig} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const artifactRootVariable = 'EASYEDU_CCB_PRIMARY_ACCORDION_ARTIFACT_ROOT';

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const isWithin = (candidate, parent) => {
    const relative = path.relative(parent, candidate);
    return relative === '' || (!relative.startsWith(`..${path.sep}`) && relative !== '..' && !path.isAbsolute(relative));
};

const repositoryRoot = fs.realpathSync.native(path.resolve(__dirname, '../..'));
const configuredArtifactRoot = process.env[artifactRootVariable];

ensure(configuredArtifactRoot && path.isAbsolute(configuredArtifactRoot),
    `${artifactRootVariable} must be an absolute external directory.`);

fs.mkdirSync(configuredArtifactRoot, {recursive: true});
const artifactRoot = fs.realpathSync.native(configuredArtifactRoot);
ensure(!isWithin(artifactRoot, repositoryRoot),
    `${artifactRootVariable} must remain outside the CCB source worktree.`);

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: 'ccb-primary-accordion-parity.spec.js',
    outputDir: path.join(artifactRoot, 'playwright-output'),
    // Covers Moodle login plus the initial CCB render before assertions.
    timeout: 90 * 1000,
    workers: 1,
    retries: 0,
    use: {
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'off',
    },
});
