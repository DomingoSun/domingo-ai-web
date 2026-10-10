import { useState } from 'react'
import {
  ArrowRight, Bell, Bot, Brain, Calendar, Database, FileText, GitBranch, MessageCircle, Zap,
} from 'lucide-react'
import { CountUp, CumulativeChart, Donut, Gauge, HourChart, LiveFeed, Pipeline, ScoreBars, usePlay } from './charts'
import { Book, Faq, Jobs, Marquees, Pains, Plans, Quiz, StatsBar, Testimonials } from './shared'

/* ═════════ 提案 1：診斷儀表板 ═════════ */
export function P1() {
  const [ref, on] = usePlay<HTMLDivElement>()
  return (
    <>
      <section className="hero hero-split" id="top">
        <div className="split-inner">
          <div className="split-copy">
            <div className="hero-badge"><span className="hero-badge-dot" />AI 導入適性診斷</div>
            <h1 className="hero-h1 h1-ask">
              <span className="h1-line">你的公司，</span>
              <span className="h1-line"><span className="h1-chunk">準備好讓 AI </span><span className="h1-chunk"><em className="h1-lime">上班</em>了嗎？</span></span>
            </h1>
            <p className="hero-sub sub-ask">
              <span className="h1-chunk">回答 5 題，馬上看到「AI 導入適性」結果。</span>
              <span className="h1-chunk">想要完整解讀，再預約 30 分鐘免費線上評估。</span>
            </p>
            <div className="hero-btns">
              <a href="#quiz" className="btn-teal">開始 1 分鐘診斷 <ArrowRight size={18} /></a>
              <a href="#book" className="btn-outline">直接預約評估</a>
            </div>
          </div>
          <div className="dash" ref={ref}>
            <div className="dash-head"><span>AI 適性診斷報告</span><span className="feed-tag">範例</span></div>
            <div className="dash-grid">
              <div className="dash-cell"><Gauge value={78} on={on} caption="導入適性分數" /></div>
              <div className="dash-cell">
                <ScoreBars on={on} items={[{ label: '流程重複度', value: 8 }, { label: '規則清楚度', value: 6 }, { label: '資料可取得', value: 7 }]} />
                <div className="dash-rec">建議先做：<b>LINE 詢價分流</b></div>
              </div>
            </div>
            <LiveFeed />
          </div>
        </div>
      </section>
      <StatsBar />
      <Quiz />
      <Pains />
      <Jobs />
      <Marquees />
      <Plans />
      <Testimonials />
      <Faq />
    </>
  )
}

/* ═════════ 提案 2：流程動畫 ═════════ */
const STEPS = [
  { icon: <MessageCircle size={24} />, t: '觸發', d: '客人傳 LINE 問價格' },
  { icon: <Brain size={24} />, t: 'AI 理解', d: '讀懂品項、數量、地區' },
  { icon: <GitBranch size={24} />, t: '判斷', d: '一般詢價？還是客製？' },
  { icon: <FileText size={24} />, t: '執行', d: '產生報價、回覆客人' },
  { icon: <Bell size={24} />, t: '通知', d: '客製案才叫業務接手' },
]
export function P2() {
  return (
    <>
      <section className="hero" id="top">
        <span className="hero-float hf1" aria-hidden="true"><MessageCircle size={28} /></span>
        <span className="hero-float hf2" aria-hidden="true"><Bot size={34} /></span>
        <span className="hero-float hf3" aria-hidden="true"><FileText size={24} /></span>
        <span className="hero-float hf4" aria-hidden="true"><Calendar size={22} /></span>
        <div className="hero-inner">
          <div className="hero-badge"><span className="hero-badge-dot" />中小企業 AI 自動化導入顧問</div>
          <h1 className="hero-h1">30 分鐘找出你的<br />AI 數位員工</h1>
          <div className="hero-lime">觸發・判斷・執行</div>
          <p className="hero-h2">24 小時自己跑完</p>
          <p className="hero-sub">不需要寫程式，不需要懂技術。先看一條流程怎麼自己跑完。</p>
          <div className="hero-btns">
            <a href="#book" className="btn-teal">預約 30 分鐘免費評估 <ArrowRight size={18} /></a>
            <a href="#flow" className="btn-outline">看流程怎麼跑</a>
          </div>
        </div>
      </section>
      <StatsBar />

      <section className="flow-sec" id="flow" aria-labelledby="flow-h">
        <div className="sec-head dark-head">
          <span className="eyebrow lime-eyebrow">HOW IT RUNS</span>
          <h2 id="flow-h">一則 LINE 詢價，數位員工這樣處理</h2>
          <p>五個步驟自己跑完，需要人判斷才叫你。</p>
        </div>
        <Pipeline steps={STEPS} />
      </section>

      <section className="light" id="about">
        <div className="about-grid">
          <div className="about-left">
            <span className="eyebrow">ONE EMPLOYEE, MANY JOBS</span>
            <h2>一位數位員工，<br />同時接好幾份工作</h2>
            <p>從一條流程開始。上線後，同一套做法可以複製到報價、知識庫、會議待辦。</p>
            <div className="about-checks">
              {['第一條流程 14 天上線', '同事親手操作，學會就帶得走', '不適合導入的部分，會直接說'].map((t) => (
                <div className="about-check" key={t}><span className="ck-dot"><Zap size={12} strokeWidth={3} /></span>{t}</div>
              ))}
            </div>
            <a href="#book" className="btn-teal">預約免費評估 <ArrowRight size={18} /></a>
          </div>
          <div className="about-right" aria-hidden="true">
            <div className="network-ring nr1 spin" /><div className="network-ring nr2 spin-rev" /><div className="network-ring nr3" />
            <span className="net-tag nt-top">你的第一位數位員工</span>
            <div className="net-center pulse-glow"><span className="big">AI</span><span className="sm">24 HR</span></div>
            <div className="net-node nn1"><MessageCircle size={16} />LINE 詢問</div>
            <div className="net-node nn2"><FileText size={16} />報價單</div>
            <div className="net-node nn3"><Database size={16} />知識庫</div>
            <div className="net-node nn4"><Calendar size={16} />會議待辦</div>
            <span className="net-tag nt-bot">觸發 → 理解 → 判斷 → 執行 → 通知</span>
          </div>
        </div>
      </section>

      <section className="chart-sec" aria-label="詢問時段">
        <div className="wrap"><HourChart /></div>
      </section>
      <Quiz variant="compact" />
      <Plans />
      <Faq />
      <Book />
    </>
  )
}

/* ═════════ 提案 3：效益試算 ═════════ */
const WORKDAYS = 22
function Slider({ id, label, value, min, max, step, unit, onChange }: { id: string; label: string; value: number; min: number; max: number; step: number; unit: string; onChange: (v: number) => void }) {
  const pct = ((value - min) / (max - min)) * 100
  return (
    <div className="slider">
      <div className="slider-top"><label htmlFor={id}>{label}</label><output htmlFor={id} className="num">{value.toLocaleString('zh-TW')} {unit}</output></div>
      <input id={id} type="range" min={min} max={max} step={step} value={value} onChange={(e) => onChange(Number(e.target.value))} style={{ ['--p' as string]: `${pct}%` }} />
    </div>
  )
}

export function P3() {
  const [msgs, setMsgs] = useState(40)
  const [mins, setMins] = useState(4)
  const [wage, setWage] = useState(250)
  const [ratio, setRatio] = useState(50)
  const manual = (msgs * mins / 60) * WORKDAYS
  const saved = manual * ratio / 100
  const after = manual - saved
  const [heroRef, heroOn] = usePlay<HTMLDivElement>()
  const [calcRef, calcOn] = usePlay<HTMLDivElement>()

  return (
    <>
      <section className="hero hero-split" id="top">
        <div className="split-inner">
          <div className="split-copy">
            <div className="hero-badge"><span className="hero-badge-dot" />AI 自動化效益試算</div>
            <h1 className="hero-h1">先算清楚<br />值不值得做</h1>
            <div className="hero-lime">工時・成本・回本</div>
            <p className="hero-h2">再決定要不要導入</p>
            <p className="hero-sub">拉三個數字，馬上看到每月可省多少工時。想精算，再預約 30 分鐘評估。</p>
            <div className="hero-btns">
              <a href="#calc" className="btn-teal">開始試算 <ArrowRight size={18} /></a>
              <a href="#book" className="btn-outline">直接預約評估</a>
            </div>
          </div>
          <div className="dash" ref={heroRef}>
            <div className="dash-head"><span>每月可省下</span><span className="feed-tag">依下方假設試算</span></div>
            <div className="hero-calc">
              <div>
                <div className="big-kpi"><CountUp to={saved} on={heroOn} /><small>小時</small></div>
                <div className="kpi-l">約 <CountUp to={saved * wage} on={heroOn} /> 元人力成本</div>
                <ul className="assume">
                  <li>每天 {msgs} 則詢問</li><li>每則 {mins} 分鐘</li><li>{ratio}% 可交給 AI</li>
                </ul>
              </div>
              <Donut share={ratio} on={heroOn} />
            </div>
          </div>
        </div>
      </section>
      <StatsBar />

      <section className="calc-sec" id="calc" aria-labelledby="calc-h">
        <div className="sec-head dark-head">
          <span className="eyebrow lime-eyebrow">ROI CALCULATOR</span>
          <h2 id="calc-h">你的數位員工，一年省多少？</h2>
          <p>用你公司的真實數字拉拉看。這是試算，不是承諾。</p>
        </div>
        <div className="calc-grid" ref={calcRef}>
          <div className="calc-inputs">
            <Slider id="s-msgs" label="每天的詢問／重複工作件數" value={msgs} min={10} max={200} step={5} unit="件" onChange={setMsgs} />
            <Slider id="s-mins" label="每件平均處理時間" value={mins} min={1} max={15} step={1} unit="分鐘" onChange={setMins} />
            <Slider id="s-wage" label="處理同事的時薪" value={wage} min={190} max={600} step={10} unit="元" onChange={setWage} />
            <Slider id="s-ratio" label="可交給數位員工的比例" value={ratio} min={20} max={80} step={5} unit="%" onChange={setRatio} />
            <p className="calc-note">以每月 {WORKDAYS} 個工作天計算。可交給 AI 的比例，評估會談時會依實際流程重估。</p>
          </div>
          <div className="calc-out">
            <div className="tiles">
              <div className="tile"><span className="tile-l">每月可省工時</span><span className="tile-n"><CountUp to={saved} on={calcOn} /><small>小時</small></span></div>
              <div className="tile"><span className="tile-l">每月人力成本</span><span className="tile-n"><CountUp to={saved * wage} on={calcOn} /><small>元</small></span></div>
              <div className="tile hot"><span className="tile-l">一年累積省下</span><span className="tile-n"><CountUp to={saved * 12} on={calcOn} /><small>小時</small></span></div>
            </div>
            <div className="chart-card">
              <div className="chart-top">
                <div>
                  <div className="chart-title">一年累積工時（小時）</div>
                  <div className="chart-sub">兩條線之間的距離，就是數位員工幫你做掉的時間</div>
                </div>
              </div>
              <div className="legend"><span><i style={{ background: 'var(--teal-bright)' }} />全人工</span><span><i style={{ background: 'var(--lime)' }} />導入後</span></div>
              <CumulativeChart manual={manual} after={after} on={calcOn} />
            </div>
            <a href="#book" className="btn-teal calc-cta">帶著試算結果預約評估 <ArrowRight size={18} /></a>
          </div>
        </div>
      </section>

      <Jobs />
      <Quiz variant="compact" />
      <Plans />
      <Faq />
      <Book />
    </>
  )
}
