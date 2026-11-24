import { atriumModule } from '@refactor-circus/atrium/vite'

// Roster's Atrium module: `pnpm build` writes ../dist/atrium (committed), `pnpm test` runs Vitest.
export default atriumModule({
  key: 'roster',
  outDir: '../dist/atrium',
  inputs: ['src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml'],
  test: { setupFiles: ['test/setup.ts'] },
})
