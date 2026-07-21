import nene2 from '@hideyukimori/nene2-standards'
import eslintConfigPrettier from 'eslint-config-prettier'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import globals from 'globals'
import tseslint from 'typescript-eslint'

export default tseslint.config(
  {
    ignores: [
      'dist',
      'node_modules',
      'coverage',
      'storybook-static',
      'src/shared/api/schema.gen.ts',
      'public/mockServiceWorker.js',
      // Build/config files live outside tsconfig; base enables the typed
      // projectService, which errors on files it can't find in a project.
      '*.config.{ts,js,mjs}',
      'tools/**',
      '.storybook/**',
      '**/*.mjs',
    ],
  },
  // base enables the typed projectService (auto-discovers tsconfig), so we only
  // supply browser globals here — no explicit parserOptions.project.
  {
    files: ['src/**/*.{ts,tsx}', 'tests/**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
    },
  },
  // Shared synthesized form (README canonical order). fsd/api/i18n/testing carry
  // the FSD boundaries, transport bans, a11y, and testing-library rules that were
  // previously hand-rolled or missing. styling uses the no-arg FSD-canonical entry.
  ...nene2.base,
  ...nene2.fsd,
  ...nene2.api,
  ...nene2.stylingWith(),
  ...nene2.i18n,
  ...nene2.testing,
  // React hygiene is not part of the fleet form; keep it as a repo-local addition.
  {
    files: ['src/**/*.{ts,tsx}'],
    plugins: { 'react-hooks': reactHooks, 'react-refresh': reactRefresh },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
    },
  },
  {
    // Tests and stories: jsdom/node globals and looser type-aware rules.
    files: ['tests/**/*.{ts,tsx}', 'src/**/*.test.{ts,tsx}', 'src/**/*.stories.{ts,tsx}'],
    languageOptions: {
      globals: { ...globals.browser, ...globals.node },
    },
    rules: {
      '@typescript-eslint/no-non-null-assertion': 'off',
      // Test render helpers and stories legitimately export non-components.
      'react-refresh/only-export-components': 'off',
    },
  },

  // ── Registered exceptions (hub 裁定 07-21・playbook §7 判例15/16/19) ───────────
  // Each is a files×rule override with a reason and a removal condition. Scope-off,
  // never inline disable. New files get full enforcement (the ratchet form).

  // Styling-coupled (判例21/27). Removal: deal C5 (W3 意匠再生成 complete) — the
  // same event that drains the stylelint baseline 尻尾12 to 0. deal's legacy class
  // vocabulary (topnav-link/btn/shell …) and inline styles are rewritten there;
  // polishing them before the regeneration is exactly what 施主裁定 07-16 rejected.
  {
    files: ['src/**/*.{ts,tsx}'],
    rules: {
      'better-tailwindcss/no-unknown-classes': 'off',
      'nene2/style-prop-css-vars-only': 'off',
    },
  },

  // Supply-coupled (判例15). Removal: C4 nene2-i18n adoption (Phase B B-2) — these
  // call Intl directly for money/date formatting; they migrate to nene2-i18n/format.
  {
    files: [
      'src/shared/lib/format-money.ts',
      'src/features/deal-detail/ui/ActivityTimeline.tsx',
      'src/features/kanban-board/ui/KanbanBoardView.tsx',
    ],
    rules: { 'no-restricted-syntax': 'off' },
  },

  // False-positive on the sole legitimate site (判例16). The I18nProvider is the
  // one place allowed to set the lang attribute (AM-18); the selector flags its
  // own implementation. Tracked by fleet-tooling#118; removal when its shared-config
  // fix ships.
  {
    files: ['src/shared/i18n/i18n-context.tsx'],
    rules: { 'no-restricted-syntax': 'off' },
  },

  // False-positive on the sole legitimate site (判例16). shared/theme is the
  // registered theme controller — the one place allowed to set/read data-theme
  // (会議R2⑥/R5); the selector flags its own implementation. Tracked by
  // fleet-tooling#130 (data-theme analogue of #118); removal when its fix ships.
  {
    files: ['src/shared/theme/index.ts'],
    rules: { 'no-restricted-syntax': 'off' },
  },

  // Registered exception — endonym (判例19). The locale selector labels
  // ('日本語'/'EN') are each language's own name; 固定表示するのが i18n の標準作法
  // ＝意図的な非翻訳. Permanent 公認差異.
  {
    files: ['src/shared/i18n/locales.ts'],
    rules: { 'no-restricted-syntax': 'off' },
  },

  // Registered exception — bilingual fallback above the i18n provider. The root
  // error boundary is a class component that renders ABOVE <I18nProvider> (it
  // catches provider errors too), so it cannot call t(); the bilingual fallback
  // text is the intentional, correct behaviour. Permanent 公認差異.
  {
    files: ['src/app/root-error-boundary.tsx'],
    rules: { 'no-restricted-syntax': 'off' },
  },

  // Architecture-coupled (documented follow-up). The audit CSV download uses a raw
  // fetch because the transport adapter is barred from features/ and deal has no
  // entities/audit home yet — an architecture decision. Removal: board +nene-deal
  // #35 (W1 audit CSV transport 移行 = entities/audit 新設).
  {
    files: ['src/features/audit-export/download-audit-csv.ts'],
    rules: { 'no-restricted-globals': 'off' },
  },

  // Config-context false-positive. deal's vitest runs without `globals: true`, so
  // React Testing Library cannot auto-register an afterEach cleanup; the explicit
  // cleanup() is required for test isolation. Removal: if/when vitest globals
  // auto-cleanup is adopted fleet-wide.
  {
    files: ['tests/setup/vitest.setup.ts'],
    rules: { 'testing-library/no-manual-cleanup': 'off' },
  },

  eslintConfigPrettier,
)
