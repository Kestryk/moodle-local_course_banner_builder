const {defineConfig} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ARTIFACTS_ROOT_ENV = 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT';
const HISTORICAL_ARTIFACTS_ROOT = 'D:\\EasyEdu\\artifacts';

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const isWithinPath = (target, parent) => {
    const relative = path.relative(parent, target);
    return relative === '' || (!relative.startsWith(`..${path.sep}`) && relative !== '..' && !path.isAbsolute(relative));
};

// This is the single artifact-root resolver for both Playwright's framework output
// and the CCB scenario-owned evidence directories.
const resolveArtifactsRoot = () => {
    const repositoryRoot = fs.realpathSync.native(path.resolve(__dirname, '../..'));
    const configured = process.env[ARTIFACTS_ROOT_ENV];
    let candidate = configured;
    if (configured) {
        ensure(path.isAbsolute(configured), `${ARTIFACTS_ROOT_ENV} must be an absolute path outside the repository.`);
    } else if (fs.existsSync('D:\\')) {
        candidate = HISTORICAL_ARTIFACTS_ROOT;
    } else {
        ensure(process.env.LOCALAPPDATA,
            `D: is unavailable; set ${ARTIFACTS_ROOT_ENV} or provide LOCALAPPDATA for the portable artifacts root.`);
        candidate = path.join(process.env.LOCALAPPDATA, 'EasyEdu', 'artifacts');
    }

    const resolved = path.resolve(candidate);
    ensure(!isWithinPath(resolved, repositoryRoot),
        `${ARTIFACTS_ROOT_ENV} must resolve outside the Git repository; choose an external writable directory.`);
    try {
        fs.mkdirSync(resolved, {recursive: true});
    } catch (error) {
        throw new Error(`Cannot create ${ARTIFACTS_ROOT_ENV}: choose an external writable directory. (${error.code || error.message})`);
    }
    const canonical = fs.realpathSync.native(resolved);
    ensure(!isWithinPath(canonical, repositoryRoot),
        `${ARTIFACTS_ROOT_ENV} resolves through a reparse point into the Git repository; choose another directory.`);
    let writeProbe = '';
    try {
        writeProbe = fs.mkdtempSync(path.join(canonical, '.ccb-artifacts-write-'));
    } catch (error) {
        throw new Error(`${ARTIFACTS_ROOT_ENV} must be writable; choose another external directory. (${error.code || error.message})`);
    } finally {
        if (writeProbe) {
            fs.rmdirSync(writeProbe);
        }
    }
    return canonical;
};

const artifactsRoot = resolveArtifactsRoot();

module.exports = defineConfig({
    metadata: {ccbArtifactsRoot: artifactsRoot},
    outputDir: path.join(artifactsRoot, 'ccb', 'public-title-accessibility', 'playwright-output'),
});
