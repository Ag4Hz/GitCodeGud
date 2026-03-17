// eslint-disable-next-line @typescript-eslint/no-require-imports
const langs = require('linguist-languages');
// eslint-disable-next-line @typescript-eslint/no-require-imports
const fs    = require('fs');
// eslint-disable-next-line @typescript-eslint/no-require-imports
const path  = require('path');

function score(data, ext) {
    let s = 0;
    if (data.group)                                   s += 10_000_000;
    if ((data.aliases || []).includes(ext))           s -= 10_000_000;
    if (!data.color)                                  s += 1_000_000;
    if (!data.aceMode || data.aceMode === 'text')     s += 100_000;
    if (!data.codemirrorMode)                         s += 100_000;
    const id = data.languageId ?? 999999;
    s += id > 1_000_000 ? 50_000 : 0;
    s += id;
    return s;
}

const extMap      = {};
const filenameMap = {};

for (const [name, data] of Object.entries(langs)) {
    for (const rawExt of (data.extensions || [])) {
        const ext = rawExt.replace(/^\./, '').toLowerCase();
        const s   = score(data, ext);
        if (!extMap[ext] || s < extMap[ext].score) {
            extMap[ext] = { lang: name, score: s };
        }
    }
    for (const filename of (data.filenames || [])) {
        const fn = filename.toLowerCase();
        const s  = score(data, '');
        if (!filenameMap[fn] || s < filenameMap[fn].score) {
            filenameMap[fn] = { lang: name, score: s };
        }
    }
}

for (const [ext, lang] of Object.entries(heuristics)) {
    extMap[ext] = { lang, score: -999_999_999 };
}

let php = '<?php\n\n';
php += '// Auto-generated from github-linguist via linguist-languages npm package.\n';
php += '// Scoring: alias match > color+editor support > languageId.\n';
php += '// heuristics entries mirror linguist heuristics.yml defaults (content-based).\n';
php += '// To regenerate: node resources/js/linguist-generate.js\n\n';
php += 'return [\n';
php += "    'extensions' => [\n";
for (const [ext, info] of Object.entries(extMap)) {
    const e = ext.replace(/'/g, "\\'");
    const l = info.lang.replace(/'/g, "\\'");
    php += `        '${e}' => '${l}',\n`;
}
php += '    ],\n\n';
php += "    'filenames' => [\n";
for (const [fn, info] of Object.entries(filenameMap)) {
    const f = fn.replace(/'/g, "\\'");
    const l = info.lang.replace(/'/g, "\\'");
    php += `        '${f}' => '${l}',\n`;
}
php += '    ],\n];\n';

const outPath = path.resolve(__dirname, '../../config/linguist.php');
fs.writeFileSync(outPath, php);
console.log(`Written ${php.split('\n').length} lines to ${outPath}`);
