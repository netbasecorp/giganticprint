/* node plugins/signedge-designer-ui/tests/parse-price.test.js
   Extracts parseFormattedPrice / fmtPct / sgBulkExtra from assets/js/override.js and checks them. */
var fs = require('fs'), path = require('path');
var src = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'override.js'), 'utf8');

function grab(name) {
    var start = src.indexOf('    function ' + name + '(');
    if (start < 0) throw new Error('missing ' + name);
    var end = src.indexOf('\n    }\n', start);
    return src.slice(start, end + 7);
}

var fns = new Function('window',
    grab('parseFormattedPrice') + grab('fmtPct') + grab('sgBulkExtra') +
    'return { parse: parseFormattedPrice, pct: fmtPct, bulk: sgBulkExtra };'
)({ sg_b2b_bulk_rules: [{ minQty: 5, extra: 5 }, { minQty: 10, extra: 7.5 }] });

var fail = 0;
function eq(got, want, label) {
    if (got !== want) { fail++; console.log('FAIL ' + label + ': got ' + got + ' want ' + want); }
    else console.log('PASS ' + label);
}

[
    ['$1,234.56', 1234.56], ['1.234,56 $', 1234.56], ['16,63 $', 16.63], ['$17.68', 17.68],
    ['1.234', 1234], ['1,234', 1234], ['$1,234,567', 1234567], ['1.234.567,8', 1234567.8],
    ['0,125', 0.125], ['0.125', 0.125], ['1234.567', 1234.567], ['6.6', 6.6],
    ['€ 25,00', 25], ['-$3.99', -3.99], ['12.345,67', 12345.67],
    ['', null], ['—', null], ['abc', null]
].forEach(function (c) { eq(fns.parse(c[0]), c[1], 'parse "' + c[0] + '"'); });

eq(fns.pct(10), '10', 'pct 10');
eq(fns.pct(12.5), '12.5', 'pct 12.5');
eq(fns.pct(7.256), '7.26', 'pct 7.256');
eq(fns.pct(0), '0', 'pct 0');
eq(fns.bulk(1), 0, 'bulk qty 1');
eq(fns.bulk(5), 5, 'bulk qty 5');
eq(fns.bulk(12), 7.5, 'bulk qty 12');

process.exit(fail ? 1 : 0);
