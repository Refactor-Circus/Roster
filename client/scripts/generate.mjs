// Regenerates src/schema.d.ts from the package's openapi.json. Run it through
// `composer client:generate`, which exports openapi.json first. The first line
// carries the document's sha256, which tests/Feature/OpenApiTest.php compares
// with the committed openapi.json.

import { createHash } from 'node:crypto';
import { readFile, writeFile } from 'node:fs/promises';
import openapiTS, { astToString } from 'openapi-typescript';

const source = new URL('../../openapi.json', import.meta.url);
const target = new URL('../src/schema.d.ts', import.meta.url);

const document = await readFile(source);
const hash = createHash('sha256').update(document).digest('hex');
const ast = await openapiTS(JSON.parse(document.toString('utf8')));

const header = [
  `// openapi.json sha256: ${hash}`,
  '// Generated from openapi.json; do not edit. Regenerate with: composer client:generate',
].join('\n');

await writeFile(target, `${header}\n${astToString(ast)}`);
