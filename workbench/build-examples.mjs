import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const bins = (process.env.PATH || '').split(path.delimiter);
const modules = bins.map((bin) => path.resolve(bin, '..')).find((directory) =>
    existsSync(path.join(directory, 'tailwindcss/index.css'))
    && existsSync(path.join(directory, '@tailwindcss/cli/dist/index.mjs')),
);

if (!modules) {
    throw new Error('Run: npm exec --yes --package=@tailwindcss/cli -- node workbench/build-examples.mjs');
}

const cssPath = (value) => value.replaceAll('\\', '/');
mkdirSync(path.join(root, 'build'), { recursive: true });
const input = path.join(root, 'build/examples-input.css');
writeFileSync(input, [
    `@import "${cssPath(path.join(modules, 'tailwindcss/index.css'))}" source(none);`,
    `@import "${cssPath(path.join(root, 'resources/kit/theme.css'))}";`,
    `@source "${cssPath(path.join(root, 'resources/kit'))}";`,
    `@source "${cssPath(path.join(root, 'workbench/resources/views'))}";`,
    '@custom-variant dark (&:where(.dark, .dark *));',
].join('\n'));

execFileSync(process.execPath, [
    path.join(modules, '@tailwindcss/cli/dist/index.mjs'),
    '-i', input,
    '-o', path.join(root, 'build/examples.css'),
], { stdio: 'inherit', cwd: root });
