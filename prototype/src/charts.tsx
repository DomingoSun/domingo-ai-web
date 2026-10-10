import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react'

const reduced = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

/** Final values at rest; replays from zero the first time the element enters the viewport. */
export function usePlay<T extends Element>() {
  const ref = useRef<T>(null)
  const [on, setOn] = useState(true)
  useEffect(() => {
    const el = ref.current
    if (!el || !('IntersectionObserver' in window) || reduced()) return
    let done = false
    const io = new IntersectionObserver((ents) => {
      if (done || !ents.some((e) => e.isIntersecting)) return
      done = true
      setOn(false)
      requestAnimationFrame(() => requestAnimationFrame(() => setOn(true)))
      io.disconnect()
    }, { threshold: 0.01 })
    io.observe(el)
    return () => io.disconnect()
  }, [])
  return [ref, on] as const
}

/** Tweens an array of numbers toward its target; zero when off. */
export function useTween(target: number[], on: boolean, dur = 900) {
  const [cur, setCur] = useState(target)
  const curRef = useRef(target)
  const key = target.join(',')
  useEffect(() => {
    if (!on) { const z = target.map(() => 0); curRef.current = z; setCur(z); return }
    if (reduced()) { curRef.current = target; setCur(target); return }
    const from = curRef.current.slice()
    const t0 = performance.now()
    let raf = 0
    const step = (t: number) => {
      const p = Math.min(1, (t - t0) / dur)
      const e = 1 - Math.pow(1 - p, 3)
      const nv = target.map((g, i) => (from[i] ?? 0) + (g - (from[i] ?? 0)) * e)
      curRef.current = nv
      setCur(nv)
      if (p < 1) raf = requestAnimationFrame(step)
    }
    raf = requestAnimationFrame(step)
    return () => cancelAnimationFrame(raf)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [on, key, dur])
  return cur
}

export function CountUp({ to, on = true, fmt = (n: number) => Math.round(n).toLocaleString('zh-TW') }: { to: number; on?: boolean; fmt?: (n: number) => string }) {
  const [v] = useTween([to], on, 1100)
  return <span className="num">{fmt(v)}</span>
}

/* ── Semicircle gauge ── */
export function Gauge({ value, on = true, caption }: { value: number; on?: boolean; caption: string }) {
  const [v] = useTween([value], on, 1200)
  return (
    <div className="gauge">
      <svg viewBox="0 0 200 118" role="img" aria-label={`${caption} ${Math.round(value)} 分`}>
        <path d="M18 104 A82 82 0 0 1 182 104" pathLength={100} className="gauge-track" />
        <path d="M18 104 A82 82 0 0 1 182 104" pathLength={100} className="gauge-fill" style={{ strokeDasharray: 100, strokeDashoffset: 100 - Math.max(0, Math.min(100, v)) }} />
        <text x="100" y="92" textAnchor="middle" className="gauge-num">{Math.round(v)}</text>
        <text x="100" y="112" textAnchor="middle" className="gauge-cap">{caption}</text>
      </svg>
    </div>
  )
}

/* ── Labelled score bars (0–10) ── */
export function ScoreBars({ items, on = true }: { items: { label: string; value: number }[]; on?: boolean }) {
  const vals = useTween(items.map((i) => i.value), on, 1000)
  return (
    <div className="scorebars">
      {items.map((it, i) => (
        <div key={it.label}>
          <div className="sb-row"><span>{it.label}</span><span className="num">{vals[i].toFixed(0)} / 10</span></div>
          <div className="sb-track"><div className="sb-fill" style={{ width: `${vals[i] * 10}%` }} /></div>
        </div>
      ))}
    </div>
  )
}

/* ── Radar (0–10 per axis) ── */
export function Radar({ labels, values, ghost }: { labels: string[]; values: number[]; ghost?: number[] }) {
  const v = useTween(values, true, 600)
  const R = 104
  const n = labels.length
  const pt = (i: number, r: number) => {
    const a = (-90 + (360 / n) * i) * (Math.PI / 180)
    return [Math.cos(a) * r, Math.sin(a) * r]
  }
  const poly = (arr: number[]) => arr.map((x, i) => pt(i, (Math.max(0, x) / 10) * R).join(',')).join(' ')
  return (
    <svg className="radar" viewBox="-175 -140 350 275" role="img" aria-label={labels.map((l, i) => `${l} ${values[i]}`).join('、')}>
      {[2.5, 5, 7.5, 10].map((r) => (
        <polygon key={r} points={labels.map((_, i) => pt(i, (r / 10) * R).join(',')).join(' ')} className="radar-ring" />
      ))}
      {labels.map((_, i) => { const [x, y] = pt(i, R); return <line key={i} x1="0" y1="0" x2={x} y2={y} className="radar-axis" /> })}
      {ghost && <polygon points={poly(ghost)} className="radar-ghost" />}
      <polygon points={poly(v)} className="radar-shape" />
      {v.map((x, i) => { const [px, py] = pt(i, (x / 10) * R); return x > 0.05 ? <circle key={i} cx={px} cy={py} r="4.5" className="radar-dot" /> : null })}
      {labels.map((l, i) => {
        const [x, y] = pt(i, R + 20)
        return <text key={l} x={x} y={y + 4} textAnchor={Math.abs(x) < 8 ? 'middle' : x > 0 ? 'start' : 'end'} className="radar-label">{l}</text>
      })}
    </svg>
  )
}

/* ── Live feed: rolling sparkline + event log (illustrative) ── */
const EVENTS = ['回覆 LINE 詢價', '產生報價單草稿', '轉給業務：客製需求', '回答營業時間', '寫入客戶名單', '整理會議待辦', '回答新人 SOP 問題']
export function LiveFeed() {
  const [tick, setTick] = useState(0)
  useEffect(() => {
    if (reduced()) return
    const id = setInterval(() => setTick((t) => t + 1), 1400)
    return () => clearInterval(id)
  }, [])
  const series = useMemo(() => Array.from({ length: 24 }, (_, i) => {
    const k = i + tick
    return 4 + Math.round(3 * Math.sin(k / 2.3) + 2 * Math.sin(k / 1.1) + ((k * 7) % 5) / 2)
  }), [tick])
  const max = 12
  const W = 300, H = 70
  const pts = series.map((y, i) => [(i / (series.length - 1)) * W, H - (y / max) * (H - 6) - 3])
  const line = pts.map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)},${p[1].toFixed(1)}`).join(' ')
  const area = `${line} L${W},${H} L0,${H} Z`
  const last = pts[pts.length - 1]
  const log = Array.from({ length: 4 }, (_, i) => {
    const k = tick + 3 - i
    const min = (12 + k * 3) % 60
    const hr = 9 + Math.floor((12 + k * 3) / 60) % 12
    return { t: `${String(hr).padStart(2, '0')}:${String(min).padStart(2, '0')}`, e: EVENTS[k % EVENTS.length], k }
  })
  return (
    <div className="feed">
      <div className="feed-head"><span className="live-dot" />數位員工處理中<span className="feed-tag">示意畫面</span></div>
      <svg viewBox={`0 0 ${W} ${H}`} className="feed-spark" preserveAspectRatio="none" role="img" aria-label="每分鐘處理件數示意走勢">
        <path d={area} className="spark-area" />
        <path d={line} className="spark-line" />
        <circle cx={last[0] - 3} cy={last[1]} r="4" className="spark-end" />
      </svg>
      <ul className="feed-log" aria-live="off">
        {log.map((l, i) => (
          <li key={l.k} className={i === 0 ? 'fresh' : ''}><span className="num">{l.t}</span>{l.e}<span className="ok">完成</span></li>
        ))}
      </ul>
    </div>
  )
}

/* ── Workflow pipeline with travelling packets ── */
export function Pipeline({ steps }: { steps: { icon: ReactNode; t: string; d: string }[] }) {
  const [active, setActive] = useState(0)
  useEffect(() => {
    if (reduced()) return
    const id = setInterval(() => setActive((a) => (a + 1) % steps.length), 1100)
    return () => clearInterval(id)
  }, [steps.length])
  return (
    <ol className="pipe">
      {steps.map((s, i) => (
        <li key={s.t} className={`pipe-step${i === active ? ' on' : ''}${i < active ? ' past' : ''}`}>
          <div className="pipe-node">{s.icon}</div>
          <div className="pipe-txt"><b>{s.t}</b><span>{s.d}</span></div>
          {i < steps.length - 1 && <div className="pipe-link" aria-hidden="true"><i /><i /><i /></div>}
        </li>
      ))}
    </ol>
  )
}

/* ── 24-hour bar chart (illustrative distribution) ── */
const HOURS = [1, 0, 0, 0, 0, 0, 1, 3, 5, 8, 8, 7, 5, 7, 8, 8, 7, 6, 6, 8, 10, 9, 6, 3]
const offHours = (h: number) => h < 9 || h >= 18
export function HourChart() {
  const [ref, on] = usePlay<HTMLDivElement>()
  const vals = useTween(HOURS, on, 1100)
  const [hover, setHover] = useState<number | null>(null)
  const W = 720, H = 220, pad = { l: 28, r: 8, t: 12, b: 26 }
  const max = 10
  const bw = (W - pad.l - pad.r) / 24
  const y = (v: number) => pad.t + (H - pad.t - pad.b) * (1 - v / max)
  const total = HOURS.reduce((a, b) => a + b, 0)
  const off = HOURS.reduce((a, b, h) => a + (offHours(h) ? b : 0), 0)
  return (
    <div className="chart-card" ref={ref}>
      <div className="chart-top">
        <div>
          <div className="chart-title">一天之中，客人什麼時候傳 LINE？</div>
          <div className="chart-sub">示意資料，評估時改用你們 LINE 官方帳號後台的真實數據重畫</div>
        </div>
        <div className="chart-kpi"><span className="kpi-n"><CountUp to={(off / total) * 100} on={on} />%</span><span className="kpi-l">落在下班時間（示意）</span></div>
      </div>
      <div className="legend"><span><i style={{ background: 'var(--teal-bright)' }} />上班時間 09–18</span><span><i style={{ background: 'var(--lime)' }} />下班後</span></div>
      <div className="chart-scroll">
        <div className="chart-box">
          <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label="每小時 LINE 詢問數示意">
            {[0, 5, 10].map((t) => (
              <g key={t}>
                <line x1={pad.l} x2={W - pad.r} y1={y(t)} y2={y(t)} className="grid" />
                <text x={pad.l - 6} y={y(t) + 4} textAnchor="end" className="axis">{t}</text>
              </g>
            ))}
            {vals.map((v, h) => {
              const x = pad.l + h * bw + 2
              const top = y(v)
              const hgt = Math.max(0, y(0) - top)
              return (
                <g key={h}>
                  <path d={barPath(x, top, bw - 4, hgt)} fill={offHours(h) ? 'var(--lime)' : 'var(--teal-bright)'} opacity={hover === null || hover === h ? 1 : 0.45} />
                  {h % 3 === 0 && <text x={x + (bw - 4) / 2} y={H - 8} textAnchor="middle" className="axis">{String(h).padStart(2, '0')}</text>}
                  <rect x={pad.l + h * bw} y={pad.t} width={bw} height={H - pad.t - pad.b} fill="transparent"
                    onMouseEnter={() => setHover(h)} onMouseLeave={() => setHover(null)} onFocus={() => setHover(h)} onBlur={() => setHover(null)} tabIndex={0}
                    aria-label={`${h} 點 ${HOURS[h]} 則`} />
                </g>
              )
            })}
          </svg>
          {hover !== null && (
            <div className="tip" style={{ left: `${((pad.l + hover * bw + bw / 2) / W) * 100}%`, top: `${(y(HOURS[hover]) / H) * 100}%` }}>
              <b>{String(hover).padStart(2, '0')}:00–{String((hover + 1) % 24).padStart(2, '0')}:00</b>
              <span>{HOURS[hover]} 則詢問（示意）</span>
              <span>{offHours(hover) ? '下班後，沒人回' : '上班時間'}</span>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

function barPath(x: number, top: number, w: number, h: number) {
  if (h <= 0.5) return ''
  const r = Math.min(4, w / 2, h)
  return `M${x},${top + h} V${top + r} Q${x},${top} ${x + r},${top} H${x + w - r} Q${x + w},${top} ${x + w},${top + r} V${top + h} Z`
}

/* ── Cumulative hours line chart (two series) ── */
export function CumulativeChart({ manual, after, on }: { manual: number; after: number; on: boolean }) {
  const months = Array.from({ length: 12 }, (_, i) => i + 1)
  const a = useTween(months.map((m) => m * manual), on, 1000)
  const b = useTween(months.map((m) => m * after), on, 1000)
  const [hover, setHover] = useState<number | null>(null)
  const W = 640, H = 260, pad = { l: 46, r: 78, t: 16, b: 30 }
  const maxRaw = Math.max(12 * manual, 1)
  const step = niceStep(maxRaw / 4)
  const max = Math.ceil(maxRaw / step) * step
  const x = (i: number) => pad.l + (i / 11) * (W - pad.l - pad.r)
  const y = (v: number) => pad.t + (H - pad.t - pad.b) * (1 - v / max)
  const path = (arr: number[]) => arr.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(' ')
  const ticks = Array.from({ length: Math.round(max / step) + 1 }, (_, i) => i * step)
  return (
    <div className="chart-box">
      <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label={`一年累積工時：全人工 ${Math.round(12 * manual)} 小時，導入後 ${Math.round(12 * after)} 小時`}
        onMouseLeave={() => setHover(null)}>
        {ticks.map((t) => (
          <g key={t}>
            <line x1={pad.l} x2={W - pad.r} y1={y(t)} y2={y(t)} className="grid" />
            <text x={pad.l - 8} y={y(t) + 4} textAnchor="end" className="axis">{t.toLocaleString('zh-TW')}</text>
          </g>
        ))}
        {months.map((m, i) => (i % 2 === 1 || i === 0) && <text key={m} x={x(i)} y={H - 8} textAnchor="middle" className="axis">{m}月</text>)}
        <path d={`${path(a)} L${x(11)},${y(0)} L${x(0)},${y(0)} Z`} className="cum-area" />
        <path d={path(a)} className="cum-line manual" />
        <path d={path(b)} className="cum-line after" />
        <circle cx={x(11)} cy={y(a[11])} r="5" className="cum-dot manual" />
        <circle cx={x(11)} cy={y(b[11])} r="5" className="cum-dot after" />
        <text x={x(11) + 10} y={y(a[11]) + 4} className="cum-lab">全人工</text>
        <text x={x(11) + 10} y={y(b[11]) + 4} className="cum-lab">導入後</text>
        {hover !== null && <line x1={x(hover)} x2={x(hover)} y1={pad.t} y2={y(0)} className="crosshair" />}
        {months.map((_, i) => (
          <rect key={i} x={x(i) - (W - pad.l - pad.r) / 22} y={pad.t} width={(W - pad.l - pad.r) / 11} height={H - pad.t - pad.b}
            fill="transparent" onMouseEnter={() => setHover(i)} onFocus={() => setHover(i)} tabIndex={0} aria-label={`第 ${i + 1} 月`} />
        ))}
      </svg>
      {hover !== null && (
        <div className="tip" style={{ left: `${(x(hover) / W) * 100}%`, top: `${(y(12 * manual * ((hover + 1) / 12)) / H) * 100}%` }}>
          <b>第 {hover + 1} 個月累積</b>
          <span><i className="sw" style={{ background: 'var(--teal-bright)' }} />全人工 {Math.round((hover + 1) * manual).toLocaleString('zh-TW')} 小時</span>
          <span><i className="sw" style={{ background: 'var(--lime)' }} />導入後 {Math.round((hover + 1) * after).toLocaleString('zh-TW')} 小時</span>
        </div>
      )}
    </div>
  )
}

function niceStep(raw: number) {
  const p = Math.pow(10, Math.floor(Math.log10(Math.max(raw, 1))))
  const f = raw / p
  return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * p
}

/* ── Donut: share automatable ── */
export function Donut({ share, on = true }: { share: number; on?: boolean }) {
  const [s] = useTween([share], on, 1000)
  const C = 100
  const gap = 1.2
  const a = Math.max(0, s - gap)
  const b = Math.max(0, 100 - s - gap)
  return (
    <svg viewBox="0 0 120 120" className="donut" role="img" aria-label={`可交給數位員工 ${Math.round(share)}%`}>
      <circle cx="60" cy="60" r="46" pathLength={C} className="donut-b" style={{ strokeDasharray: `${b} ${C - b}`, strokeDashoffset: -(s + gap / 2) }} transform="rotate(-90 60 60)" />
      <circle cx="60" cy="60" r="46" pathLength={C} className="donut-a" style={{ strokeDasharray: `${a} ${C - a}`, strokeDashoffset: -gap / 2 }} transform="rotate(-90 60 60)" />
      <text x="60" y="60" textAnchor="middle" className="donut-n">{Math.round(s)}%</text>
      <text x="60" y="76" textAnchor="middle" className="donut-l">可交給 AI</text>
    </svg>
  )
}
