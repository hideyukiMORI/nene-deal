/** Pixel-diff two screenshot dirs (before/after). Exit 1 if any pixels differ. */
import fs from 'node:fs'
import { PNG } from 'pngjs'
import pixelmatch from 'pixelmatch'

const A = process.env.SMOKE_BEFORE || '/tmp/deal-smoke/before'
const B = process.env.SMOKE_AFTER || '/tmp/deal-smoke/after'
const DIFF = process.env.SMOKE_DIFF || '/tmp/deal-smoke/diff'
fs.mkdirSync(DIFF, { recursive: true })

const files = fs.readdirSync(A).filter((f) => f.endsWith('.png'))
let total = 0
for (const f of files) {
  if (!fs.existsSync(`${B}/${f}`)) {
    console.log('MISSING after:', f)
    total += 1
    continue
  }
  const a = PNG.sync.read(fs.readFileSync(`${A}/${f}`))
  const b = PNG.sync.read(fs.readFileSync(`${B}/${f}`))
  if (a.width !== b.width || a.height !== b.height) {
    console.log(`SIZE DIFF ${f}: ${a.width}x${a.height} vs ${b.width}x${b.height}`)
    total += 1
    continue
  }
  const diff = new PNG({ width: a.width, height: a.height })
  const n = pixelmatch(a.data, b.data, diff.data, a.width, a.height, { threshold: 0.1 })
  if (n > 0) {
    console.log(`${f}: ${n}px (${((n / (a.width * a.height)) * 100).toFixed(4)}%)`)
    fs.writeFileSync(`${DIFF}/${f}`, PNG.sync.write(diff))
  }
  total += n
}
console.log(`TOTAL diff px: ${total} across ${files.length} shots`)
process.exit(total === 0 ? 0 : 1)
