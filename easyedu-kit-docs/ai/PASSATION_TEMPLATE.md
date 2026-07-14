# EasyEdu UI Kit Passation Template

Use this template when handing work from one Codex window to another.

```text
You are working on [PLUGIN NAME].

Before coding:
1. Read the embedded EasyEdu kit docs in the plugin, or the source kit repo:
   - ai/AI_RULES.md
   - ai/IMPLEMENTATION_CHECKLIST.md
   - ai/COMPONENT_CONTRACT.md
   - ai/GUIDE_PARITY_CHECKLIST.md when guide parity is involved
   - ai/MOODLE_PLUGIN_RULES.md when working in a Moodle plugin
   - docs/component-matrix.md
   - docs/components/[RELEVANT COMPONENT].md
2. Identify the exact EasyEdu component/mixin/template/AMD behaviour to reuse.
3. If the behaviour is missing, update easyedu-ui-kit first.

Source of truth:
- Repository: git@github.com:Kestryk/easyedu-ui-kit.git
- Commit/tag to use: [COMMIT OR TAG]
- Component family: [guide/buttons/forms/dropdowns/etc.]

Required behaviour:
- [List exact behaviours, not vague style inspiration.]

Forbidden:
- Do not approximate the design with plugin-local CSS.
- Do not recreate a local selector/highlight/menu/button if the kit has one.
- Do not add native title tooltips where kit custom help is used.

Validation:
- Compile SCSS.
- Check JS syntax.
- Run git diff --check.
- Run the Moodle rule audit when working in a Moodle plugin.
- If visual parity is required, compare against the reference plugin with
  browser/headless only when explicitly allowed.

Final answer must include:
- kit files used;
- kit files changed;
- plugin files changed;
- verification run;
- unresolved differences.
```
