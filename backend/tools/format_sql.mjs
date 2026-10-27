import { format } from 'sql-formatter';

let input = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', (chunk) => input += chunk);
process.stdin.on('end', () => {
    try {
        const query = JSON.parse(input);
        const formatted = format(query, {
                language: 'mysql',
                tabWidth: 4,
                useTabs: false,
                keywordCase: 'upper',
                linesBetweenQueries: 1,
            });
        process.stdout.write(JSON.stringify({ sql: normalizeLayout(formatted) }));
    } catch (error) {
        process.stdout.write(JSON.stringify({error: String(error.message ?? error)}));
        process.exitCode = 2;
    }
});

function normalizeLayout(sql) {
    const lines = sql.split(/\r?\n/);
    const output = [];
    for (let index = 0; index < lines.length; index += 1) {
        const line = lines[index];
        const trimmed = line.trim();
        if ((trimmed === 'FROM' || trimmed === 'INSERT INTO') && lines[index + 1]) {
            output.push(`${trimmed} ${lines[++index].trim()}`);
            continue;
        }
        if (/^\s+JOIN\b/i.test(line)) {
            output.push(trimmed);
            continue;
        }
        output.push(line);
    }
    return output.join('\n');
}
