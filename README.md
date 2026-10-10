# 多明哥 AI 筆記｜官方網站

domingoai.com 的 WordPress 主題與設計原型。網站的目的：讓企業主做 AI 導入適性診斷，再預約 30 分鐘免費線上評估。

## 資料夾

| 路徑 | 內容 |
|---|---|
| `themes/domingo/` | WordPress 區塊主題（佈景主題）。部署到 `wp-content/themes/domingo/` |
| `plugins/domingo-core/` | 網站功能外掛：互動區塊、後台「網站設定」與「評論」。部署到 `wp-content/plugins/domingo-core/`，換主題也不會不見 |
| `docs/make-setup.html` | Make 預約自動化設定指南（含 Claude 提示詞與信件範本） |
| `scripts/` | 打包上傳用壓縮檔的小工具 |
| `prototype/` | 設計定稿的 React 原型，只當作設計參考，不會部署到網站 |

## 主題結構（themes/domingo）

- `theme.json`：顏色、字型、字級、間距等全站設定，後台「外觀 → 編輯器」可直接選用
- `parts/`：頁首、頁尾
- `templates/`：首頁、AI 筆記列表、文章內頁、一般頁面、預約頁、404
- `patterns/`：首頁各區塊（可在後台直接編輯文字）
- `assets/css/theme.css`：元件樣式

## 後台可以改的地方（domingo-core）

- **網站設定**：5 題診斷的題目、選項與分數、三種結果的建議文字、工具跑馬燈、產業跑馬燈
- **評論**：新增一筆就會出現在首頁評論跑馬燈（請先取得本人同意）
- **網站設定 → 預約時段**：每週固定時段、每場長度、最快／最遠可預約、不開放的日期、新預約通知信、表單選項
- **預約**：客戶送出的預約清單。可改狀態（新預約／已確認／已完成／已取消），改成「已取消」時段會重新開放
- 診斷做完後，按「預約」會把結果帶到預約頁（例如 `/booking/?quiz=9-9-6-9-9&score=84`）

## 開發進度

- [x] 第 1 階段：主機環境檢查（Cloudways、WordPress 7.1.3）
- [x] 第 2 階段：主題骨架、全站設定、頁首頁尾、首頁靜態區塊、頁面版型
- [x] 第 3 階段：互動區塊（診斷報告卡、5 題診斷＋雷達圖、工具／產業跑馬燈、評論跑馬燈）
- [x] 第 4 階段：預約頁（4 步驟表單、後台時段設定、預約清單）
- [x] 第 5 階段：Make 自動化（建立日曆會議、Claude 會前摘要、確認信）
- [ ] 第 6 階段：SEO／AI 搜尋設定
- [ ] 第 7 階段：Claude Code 串接（WordPress MCP）
- [ ] 第 8 階段：上線前檢查

## 放上 domingoai.com

1. `python3 scripts/build-zips.py` 產生 `dist/domingo-core.zip` 和 `dist/domingo.zip`
2. 後台「外掛 → 安裝外掛 → 上傳外掛」上傳 `domingo-core.zip` 並啟用
3. 後台「外觀 → 佈景主題 → 新增 → 上傳佈景主題」上傳 `domingo.zip` 並啟用
4. 後台「網站設定」按「開始初始設定」（建立頁面、首頁、網址格式、時區）
5. 清除 Breeze 快取

更新時重複 1–3（上傳同名檔案時選「以上傳的版本取代」），再清快取。

## 預約 API

- `GET /wp-json/domingo/v1/slots`：目前還能預約的時段（不快取）
- `POST /wp-json/domingo/v1/bookings`：送出預約。成功後觸發 `do_action( 'domingo_booking_created', $post_id, $data )`，第 5 階段的 Make 從這裡接
- 防垃圾：隱藏欄位、表單至少停留 4 秒、同一來源每小時最多 5 次、同時段上鎖

上線時記得在 Cloudways 的 Varnish 與 Breeze 設定裡，把 `/booking/` 加進「不快取」清單（外掛已送出不快取標頭，這是雙重保險）。

## 預約自動化（第 5 階段）

- 後台「網站設定 → 自動化」貼上 Make 的 Webhook 網址（或在主機設定環境變數 `DOMINGO_MAKE_WEBHOOK_URL`，優先使用）
- 新預約：先回應客戶，再在背景送到 Make；失敗會在 5、10 分鐘後重試
- Make 完成後呼叫 `POST /wp-json/domingo/v1/bookings/{id}/meeting`（標頭 `X-Domingo-Secret`），寫回 Meet 連結與 Claude 會前摘要，狀態改為「已確認」
- 後台改成「已取消」時送出 `event = cancelled`
- 回寫密碼由網站自動產生並隨資料送出；也可用環境變數 `DOMINGO_HOOK_SECRET` 指定

## 安全規則

不要把任何密碼、API 金鑰、權杖寫進這個 repo。一律放在主機或電腦的環境變數。
