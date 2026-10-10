import { useState, type FormEvent } from 'react'
import {
  ArrowRight, Check, ClipboardList, Database, FileSpreadsheet, FileText, Menu, MessageCircle, Plus, Receipt, RotateCcw, Search, Sparkles, Timer, X, Zap,
} from 'lucide-react'
import { Gauge, Radar, ScoreBars, usePlay } from './charts'

export const PROPOSALS = [
  { id: 'p1', n: '提案 1', name: '診斷儀表板' },
  { id: 'p2', n: '提案 2', name: '流程動畫' },
  { id: 'p3', n: '提案 3', name: '效益試算' },
]

export function Nav({ links }: { links: { href: string; label: string }[] }) {
  const [menu, setMenu] = useState(false)
  return (
    <header className="nav">
      <div className="nav-inner">
        <a href="#top" className="nav-logo"><span className="nav-dot">多</span>多明哥 AI 筆記</a>
        <nav className="nav-links" aria-label="主選單">
          {links.map((n) => <a key={n.href} href={n.href}>{n.label}</a>)}
        </nav>
        <a href="#book" className="nav-cta">預約免費評估</a>
        <button type="button" className="nav-mob-btn" aria-label={menu ? '關閉選單' : '開啟選單'} aria-expanded={menu} onClick={() => setMenu(!menu)}>
          {menu ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>
      {menu && (
        <div className="nav-mob-menu">
          {links.map((n) => <a key={n.href} href={n.href} onClick={() => setMenu(false)}>{n.label}</a>)}
          <a href="#book" className="nav-mob-cta" onClick={() => setMenu(false)}>預約免費評估</a>
        </div>
      )}
    </header>
  )
}

const STATS = [
  { n: '2024.12', u: '', l: '開始開設 AI 實戰工作坊' },
  { n: '5', u: '梯', l: '公開工作坊已完成' },
  { n: '30', u: '分鐘', l: '免費線上適性評估' },
  { n: '14', u: '天', l: '第一個流程上線' },
]
export function StatsBar() {
  return (
    <section className="stats" aria-label="多明哥 AI 筆記數據">
      <div className="wrap stats-grid">
        {STATS.map((s) => (
          <div className="stat" key={s.l}>
            <div className="stat-n">{s.n}{s.u && <small>{s.u}</small>}</div>
            <div className="stat-l">{s.l}</div>
          </div>
        ))}
      </div>
    </section>
  )
}

const JOBS = [
  { icon: MessageCircle, t: 'LINE 詢問分流', s: '常見問題先回，需要真人才轉給同事' },
  { icon: FileText, t: '報價單自動產生', s: '依產品表與規則算好，業務確認後寄出' },
  { icon: Database, t: '內部知識小幫手', s: '新人問 SOP，直接在 LINE 得到答案' },
  { icon: ClipboardList, t: '會議紀錄與待辦', s: '會後整理重點，分派負責人與期限' },
  { icon: FileSpreadsheet, t: '報表自動彙整', s: '各處資料每天自動整理成一張表' },
]
export function Jobs() {
  return (
    <section className="light" id="jobs" aria-labelledby="jobs-h">
      <div className="sec-head">
        <span className="eyebrow">DIGITAL EMPLOYEES</span>
        <h2 id="jobs-h">數位員工可以接手的工作</h2>
        <p>需要人判斷的時候，才會叫你。</p>
      </div>
      <div className="svc-grid">
        {JOBS.map((j) => (
          <div className="svc-card" key={j.t}>
            <div className="svc-icon"><j.icon size={24} /></div>
            <h3>{j.t}</h3>
            <div className="sub">{j.s}</div>
          </div>
        ))}
      </div>
    </section>
  )
}

/* ── Quiz: five questions, live radar, gauge result ── */
const AXES = ['重複度', '規則清楚', '資料數位化', '團隊意願', '決策速度']
const QS = [
  { q: '同事每天花多少時間在重複工作？', o: [['不到 1 小時', 3], ['1–3 小時', 6], ['3 小時以上', 9]] },
  { q: '這些工作有固定的處理規則嗎？', o: [['大部分有 SOP', 9], ['大約一半', 6], ['多半靠經驗', 3]] },
  { q: '工作需要的資料放在哪裡？', o: [['都在電腦或雲端', 9], ['一半紙本', 6], ['多在老員工腦中', 3]] },
  { q: '能撥一位同事每週 2–3 小時學習嗎？', o: [['可以，人選想好了', 9], ['可以試試', 6], ['很難抽出人力', 3]] },
  { q: '導入預算由誰決定？', o: [['我自己就能決定', 9], ['要和合夥人討論', 6], ['要層層簽核', 4]] },
] as const
const GHOST = [7, 6, 5, 6, 8]

export function Quiz({ variant = 'full' }: { variant?: 'full' | 'compact' }) {
  const [ans, setAns] = useState<number[]>(Array(5).fill(-1))
  const [ref, on] = usePlay<HTMLDivElement>()
  const scores = ans.map((a, i) => (a >= 0 ? (QS[i].o[a][1] as number) : 0))
  const answered = ans.filter((a) => a >= 0).length
  const done = answered === 5
  const total = done ? Math.round((scores.reduce((x, y) => x + y, 0) / 50) * 100) : 0
  const level = total >= 70 ? '高' : total >= 50 ? '中' : '待準備'
  const advice = total >= 70
    ? '你們有明顯的重複工作，規則和資料也清楚，很適合從一個小流程開始。'
    : total >= 50
      ? '有適合自動化的工作，但規則或資料要先整理一部分。'
      : '先整理 SOP 和資料，會比直接導入 AI 更有效。'
  const pick = (qi: number, oi: number) => setAns((cur) => cur.map((v, i) => (i === qi ? oi : v)))

  return (
    <section className="quiz-sec" id="quiz" aria-labelledby="quiz-h">
      <div className="sec-head dark-head">
        <span className="eyebrow lime-eyebrow">1 MINUTE CHECK</span>
        <h2 id="quiz-h">1 分鐘 AI 適性診斷</h2>
        <p>點選答案，右邊的雷達圖會即時長出來。</p>
      </div>
      <div className={`quiz-grid ${variant}`} ref={ref}>
        <div className="quiz-qs">
          {QS.map((q, qi) => (
            <fieldset key={q.q} className="quiz-q">
              <legend><span className="qn num">{qi + 1}</span>{q.q}</legend>
              <div className="chips">
                {q.o.map(([label], oi) => (
                  <button key={label} type="button" className="chip" aria-pressed={ans[qi] === oi} onClick={() => pick(qi, oi)}>{label}</button>
                ))}
              </div>
            </fieldset>
          ))}
        </div>
        <div className="quiz-viz">
          <div className="viz-card">
            <div className="viz-top">
              <span>你的 AI 導入輪廓</span>
              <span className="num">{answered} / 5</span>
            </div>
            <Radar labels={AXES} values={scores} ghost={GHOST} />
            <div className="legend center"><span><i style={{ background: 'var(--lime)' }} />你的回答</span><span><i className="dash" />範例輪廓</span></div>
            {done ? (
              <div className="quiz-result" aria-live="polite">
                <Gauge value={total} on={on} caption="導入適性分數" />
                <div className="qr-level">適性：<b>{level}</b></div>
                <p>{advice}</p>
                <div className="qr-btns">
                  <a href="#book" className="btn-teal">帶著結果預約評估 <ArrowRight size={18} /></a>
                  <button type="button" className="btn-ghost-sm" onClick={() => setAns(Array(5).fill(-1))}><RotateCcw size={15} />重測</button>
                </div>
              </div>
            ) : (
              <div className="quiz-wait">
                <ScoreBars on={on} items={AXES.slice(0, 3).map((l, i) => ({ label: l, value: scores[i] }))} />
                <p>還差 {5 - answered} 題就能看到分數。</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  )
}

const PLANS = [
  { label: '先看清楚', name: 'AI 導入適性評估', price: 'NT$0', unit: '', sub: '線上 30 分鐘，不需準備資料',
    feats: ['盤點最花時間的 3 件事', '排出最該先做的流程', '工具與月費級距建議', '不適合會直接告訴你'], cta: '預約免費評估', featured: false },
  { label: '第一位數位員工', name: '14 天陪跑上線', price: 'NT$24,800', unit: '起 / 一個流程', sub: '依實際導入的工作流程報價',
    feats: ['兩次工作會議，14 天上線', '同事親手做，我在旁邊帶', '交付流程說明與維護方式', '上線後 [天數] 天問題排除'], cta: '先預約評估', featured: true },
  { label: '全員上手', name: '企業內訓與工作坊', price: '專案報價', unit: '', sub: '半天或全天，依部門需求規劃',
    feats: ['依部門情境客製案例', '實作為主，課後就能用', '可延伸為種子講師培訓', '課後提供操作講義'], cta: '詢問內訓', featured: false },
]
export function Plans() {
  return (
    <section className="pricing" id="plans" aria-labelledby="plans-h">
      <div className="sec-head dark-head">
        <span className="eyebrow lime-eyebrow">THREE WAYS</span>
        <h2 id="plans-h">三種合作方式，從你需要的開始</h2>
        <p>不用照順序走完。可以只做免費評估，也可以直接找我陪跑或內訓。</p>
      </div>
      <div className="price-grid">
        {PLANS.map((p) => (
          <div className={`price-card${p.featured ? ' featured' : ''}`} key={p.name}>
            {p.featured && <div className="pc-badge">最多人選擇</div>}
            <div className="pc-label">{p.label}</div>
            <h3 className="pc-name">{p.name}</h3>
            <div className="pc-price"><span className="pc-amount">{p.price}</span>{p.unit && <span className="pc-unit">{p.unit}</span>}</div>
            <div className="pc-sub">{p.sub}</div>
            <div className="pc-divider" />
            <ul className="pc-features">{p.feats.map((f) => <li key={f}><Check size={15} strokeWidth={3} />{f}</li>)}</ul>
            <a href="#book" className={p.featured ? 'pc-btn-primary' : 'pc-btn-ghost'}>{p.cta}</a>
          </div>
        ))}
      </div>
      <p className="price-note">方括號為待確認內容，上線前替換。價格未含第三方工具月費。</p>
    </section>
  )
}

const FAQS = [
  { q: '評估真的免費嗎？會被推銷嗎？', a: '不收費。結束時若你想繼續，我會說明方案；不需要就到此為止，不會追著聯絡。' },
  { q: '我不懂技術，可以預約嗎？', a: '可以。評估只談你們每天在做什麼、資料放在哪、哪裡最卡。' },
  { q: '公司資料會外流嗎？', a: '評估階段不需要任何內部檔案。正式導入時會先說明資料放哪、誰看得到，再由你決定。' },
  { q: '為什麼要同事自己操作？', a: '系統最後是你們在用、在改。同事親手做過一次，之後調整或複製到下個部門都不必再請人。' },
]
export function Faq() {
  return (
    <section className="cream" id="faq" aria-labelledby="faq-h">
      <div className="faq-grid">
        <div className="faq-left">
          <h2 id="faq-h">預約前，<br />你可能想問</h2>
          <p>沒找到你的問題？寫在預約表單的備註，會談時優先回答。</p>
          <a href="#book" className="btn-teal">預約免費評估 <ArrowRight size={18} /></a>
        </div>
        <div className="faq-list">
          {FAQS.map((f) => (
            <details key={f.q}>
              <summary>{f.q}<span className="faq-icon" aria-hidden="true"><Plus size={16} /></span></summary>
              <p className="faq-answer">{f.a}</p>
            </details>
          ))}
        </div>
      </div>
    </section>
  )
}

const SIZES = ['1–10 人', '11–40 人', '41–100 人', '100 人以上']
const FIELDS = ['製造／工程', '批發／經銷', '醫美／診所', '教育／補習', '服務業', '其他']
const NEEDS = ['客戶詢問回覆', '報價單製作', '內部資料查詢', '會議紀錄整理', '報表彙整', '還不確定']

function BookingForm() {
  const [step, setStep] = useState(1)
  const [size, setSize] = useState('')
  const [field, setField] = useState('')
  const [needs, setNeeds] = useState<string[]>([])
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [company, setCompany] = useState('')
  const [err, setErr] = useState('')
  const [day, setDay] = useState(-1)
  const [slot, setSlot] = useState('')
  const days = nextWeekdays(5)
  const toggleNeed = (n: string) => setNeeds((cur) => (cur.includes(n) ? cur.filter((x) => x !== n) : [...cur, n]))
  const next = () => {
    if (step === 1 && (!size || !field)) { setErr('請選擇公司規模與產業。'); return }
    if (step === 2 && needs.length === 0) { setErr('至少選一項；還沒想清楚就選「還不確定」。'); return }
    if (step === 3 && (!name.trim() || !/^\S+@\S+\.\S+$/.test(email))) { setErr('請填寫稱呼與正確的 Email，會議連結會寄到這裡。'); return }
    setErr(''); setStep(step + 1)
  }
  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (day < 0 || !slot) { setErr('請選一個日期和時段。'); return }
    setErr(''); setStep(5)
  }
  if (step === 5) {
    return (
      <div className="form-card" aria-live="polite">
        <div className="form-done">
          <div className="ok"><Check size={30} strokeWidth={3} /></div>
          <h3>預約完成，{name}</h3>
          <p className="done-when num">{days[day].label} {slot}</p>
          <p><span className="br-sentence">Google Meet 連結和行事曆邀請已寄到 {email}（示意）。</span><span className="br-sentence">會前我會先讀過你的回答。</span></p>
          <a href="#top" className="btn-outline done-back">回首頁</a>
        </div>
      </div>
    )
  }
  return (
    <form className="form-card" onSubmit={submit} noValidate>
      <div className="form-top"><span>步驟 {step} / 4</span><span>約 2 分鐘</span></div>
      <div className="form-bar"><i style={{ width: `${(step / 4) * 100}%` }} /></div>
      {step === 1 && (
        <div>
          <div className="form-q" id="q-size">公司大約多少人？</div>
          <div className="chips" role="group" aria-labelledby="q-size">{SIZES.map((s) => <button type="button" className="chip" key={s} aria-pressed={size === s} onClick={() => setSize(s)}>{s}</button>)}</div>
          <div className="form-q" id="q-field">屬於哪個產業？</div>
          <div className="chips" role="group" aria-labelledby="q-field">{FIELDS.map((s) => <button type="button" className="chip" key={s} aria-pressed={field === s} onClick={() => setField(s)}>{s}</button>)}</div>
        </div>
      )}
      {step === 2 && (
        <div>
          <div className="form-q" id="q-need">最想先解決哪些事？<span className="form-hint">可複選</span></div>
          <div className="chips" role="group" aria-labelledby="q-need">{NEEDS.map((s) => <button type="button" className="chip" key={s} aria-pressed={needs.includes(s)} onClick={() => toggleNeed(s)}>{s}</button>)}</div>
          <div className="field"><label htmlFor="f-note">最花時間的工作（選填）</label><textarea id="f-note" rows={3} placeholder="例如：每天要回 50 則 LINE 詢價" /></div>
        </div>
      )}
      {step === 3 && (
        <div>
          <div className="field"><label htmlFor="f-name">您的稱呼</label><input id="f-name" autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} /></div>
          <div className="field"><label htmlFor="f-company">公司名稱</label><input id="f-company" autoComplete="organization" value={company} onChange={(e) => setCompany(e.target.value)} /></div>
          <div className="field"><label htmlFor="f-email">Email（寄送會議連結）</label><input id="f-email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} /></div>
        </div>
      )}
      {step === 4 && (
        <div>
          <div className="form-q" id="q-day">選一天</div>
          <div className="day-row" role="group" aria-labelledby="q-day">
            {days.map((d, i) => (
              <button type="button" key={d.label} className="day-btn" aria-pressed={day === i} onClick={() => { setDay(i); setSlot('') }}>
                <span>{d.week}</span><b className="num">{d.date}</b>
              </button>
            ))}
          </div>
          <div className="form-q" id="q-slot">選時段<span className="form-hint">每段 30 分鐘・線上</span></div>
          <div className="slot-grid" role="group" aria-labelledby="q-slot">
            {SLOTS.map((s) => (
              <button type="button" key={s} className="slot-btn num" disabled={day < 0} aria-pressed={slot === s} onClick={() => setSlot(s)}>{s}</button>
            ))}
          </div>
          <p className="form-legal">時段為示意。正式版會同步 Google 日曆，只顯示還空著的時間。</p>
        </div>
      )}
      {err && <p className="field-err" role="alert">{err}</p>}
      <div className="form-nav">
        {step > 1 && <button type="button" className="btn-outline" onClick={() => { setErr(''); setStep(step - 1) }}>上一步</button>}
        {step < 3 && <button type="button" className="btn-teal" onClick={next}>下一步 <ArrowRight size={18} /></button>}
        {step === 3 && <button type="button" className="btn-teal" onClick={next}>下一步：選時間 <ArrowRight size={18} /></button>}
        {step === 4 && <button type="submit" className="btn-teal">確認預約 <ArrowRight size={18} /></button>}
      </div>
      {step === 3 && <p className="form-legal">這是設計預覽，資料不會真的送出。</p>}
    </form>
  )
}

const SLOTS = ['10:00', '11:00', '14:00', '15:30', '19:30', '20:30']
const WEEK = ['週日', '週一', '週二', '週三', '週四', '週五', '週六']
function nextWeekdays(n: number) {
  const out: { week: string; date: string; label: string }[] = []
  const d = new Date()
  while (out.length < n) {
    d.setDate(d.getDate() + 1)
    if (d.getDay() === 0 || d.getDay() === 6) continue
    const date = `${d.getMonth() + 1}/${d.getDate()}`
    out.push({ week: WEEK[d.getDay()], date, label: `${date}（${WEEK[d.getDay()].slice(1)}）` })
  }
  return out
}

export function Book() {
  return (
    <section className="book book-page" id="book" aria-labelledby="book-h">
      <div className="book-grid">
        <div className="book-left">
          <a href="#top" className="book-back">← 回首頁</a>
          <span className="eyebrow lime-eyebrow">FREE 30-MIN ASSESSMENT</span>
          <h1 id="book-h">先花 30 分鐘，<br />看清楚第一步</h1>
          <p><span className="br-sentence">先填幾個問題，再選一個方便的時間。</span><span className="br-sentence">會前我會先讀過你的回答。</span></p>
          <div className="book-steps">
            <div className="book-step"><b>1</b><span>填表單：規模、產業、想解決的事</span></div>
            <div className="book-step"><b>2</b><span>選一個方便的日期和時段</span></div>
            <div className="book-step"><b>3</b><span>30 分鐘線上會談，會後收到評估摘要</span></div>
          </div>
          <p className="book-note"><Timer size={16} />通常 1 個工作天內回覆</p>
        </div>
        <BookingForm />
      </div>
    </section>
  )
}

export function Footer() {
  return (
    <footer className="footer" id="contact">
      <div className="wrap footer-grid">
        <div className="ft-brand">
          <a href="#top" className="nav-logo"><span className="nav-dot">多</span>多明哥 AI 筆記</a>
          <p>中小企業 AI 自動化導入顧問。<br />從一個流程開始，讓同事自己接得住。</p>
          <a href="#book" className="btn-teal ft-cta">預約免費評估 <ArrowRight size={16} /></a>
        </div>
        <div className="ft-col">
          <h2>聯絡方式</h2>
          <ul>
            <li><span className="ft-k">Email</span><span className="ft-v">[Email]</span></li>
          </ul>
        </div>
        <div className="ft-col">
          <h2>網站導覽</h2>
          <ul className="ft-links">
            <li><a href="#quiz">適性診斷</a></li>
            <li><a href="#jobs">數位員工</a></li>
            <li><a href="#plans">方案</a></li>
            <li><a href="#notes">AI 筆記</a></li>
            <li><a href="#faq">常見問題</a></li>
          </ul>
        </div>
        <div className="ft-col">
          <h2>追蹤我</h2>
          <ul className="ft-links">
            <li><a href="https://www.threads.net/@domingo.notes" target="_blank" rel="noopener">Threads @domingo.notes</a></li>
            <li><a href="#contact">[Instagram]</a></li>
            <li><a href="#contact">[Facebook 粉專]</a></li>
            <li><a href="#contact">[YouTube]</a></li>
          </ul>
        </div>
      </div>
      <div className="wrap ft-bottom">
        <span>© 2026 多明哥 AI 筆記</span>
        <nav aria-label="法律資訊"><a href="#top">隱私權政策</a><a href="#top">服務條款</a></nav>
      </div>
    </footer>
  )
}

/* 手機版右下角 LINE 浮動按鈕（預約頁不顯示） */
export function LineFab() {
  return (
    <a href="#contact" className="line-fab" aria-label="用 LINE 問問題">
      <MessageCircle size={20} /><span>LINE 問問題</span>
    </a>
  )
}

/* ── 痛點卡片（接在診斷之後） ── */
const PAINS = [
  { icon: MessageCircle, t: 'LINE 詢問回不完', d: '同樣的價格、規格、營業時間，一天回幾十次。晚上的訊息隔天才回，客人早就問別家。' },
  { icon: Receipt, t: '報價單一直重做', d: '產品表在 Excel、條件在業務腦中，每張報價都要人工對一次，一忙就出錯。' },
  { icon: Search, t: '新人什麼都要問', d: 'SOP 散在群組和雲端資料夾，老員工一天被打斷十幾次，新人還是學很慢。' },
  { icon: ClipboardList, t: '會議開完沒下文', d: '討論很熱烈，會後沒人整理重點和待辦，下週又從頭講一遍。' },
  { icon: Sparkles, t: '上過 AI 課，回公司沒用上', d: '課堂上很厲害，回到公司不知道套在哪個流程，最後又回到老方法。' },
]
export function Pains() {
  return (
    <section className="light" aria-labelledby="pain-h">
      <div className="sec-head">
        <h2 id="pain-h">你是不是也卡在這幾件事？</h2>
        <p>多數老闆不是不想用 AI，而是不知道第一步該放在哪。</p>
      </div>
      <div className="pain-grid">
        {PAINS.map((p) => (
          <div className="pain-card" key={p.t}>
            <div className="pain-icon"><p.icon size={22} /></div>
            <h3>{p.t}</h3>
            <p>{p.d}</p>
          </div>
        ))}
        <div className="pain-card lime">
          <div className="pain-icon" style={{ background: '#000', color: 'var(--lime)', borderColor: '#000' }}><Zap size={22} /></div>
          <h3>中了兩項以上？</h3>
          <p>代表你的公司已經有適合自動化的流程。先花 30 分鐘，看清楚從哪一個開始。</p>
          <a href="#book" className="lime-btn">預約免費評估 <ArrowRight size={16} /></a>
        </div>
      </div>
    </section>
  )
}

/* ── 工具與產業跑馬燈（接在數位員工之後） ── */
const TOOLS = [
  { n: 'LINE 官方帳號', c: '#06C755', a: 'L' }, { n: 'Google 試算表', c: '#0F9D58', a: 'G' }, { n: 'Gmail', c: '#EA4335', a: 'M' },
  { n: 'Google 日曆', c: '#4285F4', a: 'Ca' }, { n: 'Make', c: '#6D00CC', a: 'Mk' }, { n: 'n8n', c: '#EA4B71', a: 'n8' },
  { n: 'ChatGPT', c: '#10A37F', a: 'AI' }, { n: 'Claude', c: '#C96442', a: 'C' }, { n: 'Notion', c: '#3a3a3a', a: 'N' }, { n: 'Excel', c: '#217346', a: 'X' },
]
const INDUSTRIES_A = ['工程行', '設備經銷', '醫美診所', '補習班', '婚禮顧問', '房仲團隊', '室內設計', '貿易商']
const INDUSTRIES_B = ['牙醫診所', '健身教室', '汽車保養', '印刷廠', '電商小品牌', '物流車隊', '會計事務所', '餐飲連鎖']
const HOT = new Set(['工程行', '醫美診所', '補習班', '設備經銷', '房仲團隊'])
export function Marquees() {
  return (
    <>
      <section className="logos" aria-label="可串接的工具">
        <div className="mq-label">串接你已經在用的工具</div>
        <div className="marquee-wrap">
          <div className="marquee-track">
            {[...TOOLS, ...TOOLS].map((t, i) => (
              <div className="logo-pill" key={i} aria-hidden={i >= TOOLS.length}>
                <span className="logo-icon" style={{ background: t.c }}>{t.a}</span>{t.n}
              </div>
            ))}
          </div>
        </div>
      </section>
      <section className="tags" aria-label="適合的產業">
        <div className="tags-label">這些產業的老闆，最常來預約評估</div>
        <div className="marquee-wrap" style={{ marginBottom: 10 }}>
          <div className="tag-track fwd">
            {[...INDUSTRIES_A, ...INDUSTRIES_A].map((t, i) => <span key={i} className={`tag${HOT.has(t) ? ' hot' : ''}`} aria-hidden={i >= INDUSTRIES_A.length}>{t}</span>)}
          </div>
        </div>
        <div className="marquee-wrap">
          <div className="tag-track rev">
            {[...INDUSTRIES_B, ...INDUSTRIES_B].map((t, i) => <span key={i} className="tag" aria-hidden={i >= INDUSTRIES_B.length}>{t}</span>)}
          </div>
        </div>
      </section>
    </>
  )
}

/* ── 評論跑馬燈（接在合作方式之後） ── */
const TESTI = [
  { tag: '工作坊學員', text: '[學員回饋原文，取得同意後刊登。建議挑「上完課回公司做了什麼」的具體描述。]', name: '[學員姓名]', role: '[職稱・產業]' },
  { tag: '企業內訓', text: '[內訓窗口回饋：同事課後實際用在哪個流程、省下多少時間。]', name: '[窗口姓名]', role: '[公司・部門]' },
  { tag: '陪跑導入', text: '[陪跑客戶回饋：導入前後的差別，最好附一個數字。]', name: '[客戶姓名]', role: '[公司・人數]' },
  { tag: '工作坊學員', text: '[學員回饋原文。避免只寫「很棒」，選能說明改變的句子。]', name: '[學員姓名]', role: '[職稱・產業]' },
  { tag: '企業內訓', text: '[主管回饋：團隊對 AI 的態度前後有什麼不同。]', name: '[主管姓名]', role: '[公司・職稱]' },
]
export function Testimonials() {
  return (
    <section className="testi" aria-labelledby="testi-h">
      <div className="sec-head" style={{ paddingInline: 24 }}>
        <h2 id="testi-h">上過課、導入過的人怎麼說</h2>
        <p>以下為版位示意，會換成取得同意的真實回饋。</p>
      </div>
      <div className="marquee-wrap">
        <div className="testi-track">
          {[...TESTI, ...TESTI].map((t, i) => (
            <div className="testi-card" key={i} aria-hidden={i >= TESTI.length}>
              <span className="testi-tag">{t.tag}</span>
              <p className="testi-text">{t.text}</p>
              <div className="testi-name">{t.name}</div>
              <div className="testi-role">{t.role}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}


/* ── AI 筆記列表（正式版由 WordPress 文章自動產生） ── */
const CATS = ['全部', '自動化案例', '工具教學', '導入觀念']
const POSTS = [
  { c: '自動化案例', t: '[文章標題：例如 LINE 詢價怎麼交給數位員工]', m: 6 },
  { c: '導入觀念', t: '[文章標題：中小企業導入 AI 的第一步]', m: 5 },
  { c: '工具教學', t: '[文章標題：Make 串接 LINE 官方帳號]', m: 8 },
  { c: '自動化案例', t: '[文章標題：報價單自動產生實作]', m: 7 },
  { c: '工具教學', t: '[文章標題：用 Claude 整理會議紀錄]', m: 4 },
  { c: '導入觀念', t: '[文章標題：為什麼要讓同事自己動手]', m: 5 },
]
export function Notes() {
  const [cat, setCat] = useState('全部')
  const list = POSTS.filter((p) => cat === '全部' || p.c === cat)
  return (
    <>
      <section className="notes-head" aria-labelledby="notes-h">
        <div className="wrap">
          <span className="eyebrow lime-eyebrow">AI NOTES</span>
          <h1 id="notes-h">AI 筆記</h1>
          <p>把 AI 寫成老闆看得懂、用得上的筆記。</p>
          <div className="chips notes-cats" role="group" aria-label="文章分類">
            {CATS.map((c) => <button key={c} type="button" className="chip" aria-pressed={cat === c} onClick={() => setCat(c)}>{c}</button>)}
          </div>
        </div>
      </section>
      <section className="light notes-list" aria-label="文章列表">
        <div className="notes-grid">
          {list.map((p, i) => (
            <article className="note-card" key={i}>
              <div className="note-cover" aria-hidden="true"><span>{p.c}</span></div>
              <div className="note-body">
                <div className="note-meta"><span className="note-cat">{p.c}</span><span className="num">[日期]・{p.m} 分鐘閱讀</span></div>
                <h2>{p.t}</h2>
                <p>[文章摘要，約 60 字。正式版會自動抓文章前段或你在後台填的摘要。]</p>
                <a href="#notes" className="note-more">閱讀全文 <ArrowRight size={15} /></a>
              </div>
            </article>
          ))}
        </div>
        <p className="notes-note">以上為版位示意。正式版在 WordPress 後台「文章」發佈後，會自動出現在這裡，分類也在後台管理。</p>
        <div className="notes-cta">
          <div><h2>看完想直接問？</h2><p>30 分鐘免費線上評估，帶著你的問題來。</p></div>
          <a href="#book" className="lime-btn">預約免費評估 <ArrowRight size={16} /></a>
        </div>
      </section>
    </>
  )
}
