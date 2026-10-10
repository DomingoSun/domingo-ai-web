import { useEffect, useState } from 'react'
import { Book, Footer, Nav, Notes } from './shared'
import { P1 } from './proposals'

const LINKS = [
  { href: '#quiz', label: '適性診斷' },
  { href: '#jobs', label: '數位員工' },
  { href: '#plans', label: '方案' },
  { href: '#notes', label: 'AI 筆記' },
  { href: '#faq', label: '常見問題' },
]

const readHash = () => {
  try { return window.location.hash.replace('#', '') } catch { return '' }
}

/* 「#book」= 預約頁（正式站為獨立頁面 /booking）；其他錨點 = 首頁區塊 */
export default function App() {
  const [hash, setHash] = useState(readHash)
  useEffect(() => {
    const on = () => setHash(readHash())
    window.addEventListener('hashchange', on)
    return () => window.removeEventListener('hashchange', on)
  }, [])
  const booking = hash === 'book'
  const notes = hash === 'notes'

  useEffect(() => {
    if (booking || notes || !hash || hash === 'top') { window.scrollTo({ top: 0 }); return }
    const el = document.getElementById(hash)
    if (el) requestAnimationFrame(() => el.scrollIntoView())
  }, [booking, notes, hash])

  return (
    <>
      <Nav links={LINKS} />
      <main>{booking ? <Book /> : notes ? <Notes /> : <P1 />}</main>
      <Footer />
    </>
  )
}
