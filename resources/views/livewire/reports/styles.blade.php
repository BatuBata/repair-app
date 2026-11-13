<style>
.reports-page { overflow-wrap:anywhere; }
.reports-page .period { display:flex; flex-wrap:wrap; align-items:end; gap:16px; margin:0 0 24px; }
.reports-page .report-dates { display:flex; gap:12px; }
.reports-page .report-dates .field { min-width:0; width:162px; }
.reports-page .report-note { margin-top:8px; }
.reports-page .section-head { flex-wrap:wrap; gap:4px 12px; }
.reports-page .statement > div { min-height:44px; }
.reports-page .statement dd { margin:0; text-align:right; min-width:0; overflow-wrap:normal; }
.reports-page .statement dt { flex-shrink:0; max-width:55%; }
.reports-page .report-transactions { margin-top:16px; min-width:0; }
.reports-page .tabs button[aria-pressed=true] { color:var(--text); font-weight:500; border-bottom:2px solid var(--accent); }
.reports-page .report-table { table-layout:fixed; width:100%; }
.reports-page .report-table .act { width:44px; }
.reports-page .report-table th:nth-child(2) { width:90px; }
.reports-page .report-table th:nth-child(3) { width:120px; }
.reports-page .report-table th:nth-child(4) { width:105px; }
.reports-page .report-table th:nth-child(n+6) { width:120px; }
.reports-page .report-table th,.reports-page .report-table td { overflow-wrap:anywhere; white-space:normal; }
.reports-page .report-table tfoot td { border-top:1px solid var(--border); font-weight:500; padding-top:16px; }
.reports-page .report-detail { width:44px; height:44px; padding:0; display:grid; place-items:center; }
.reports-page .report-description .mobile-only,.reports-page .report-line-cost { display:block; }
.reports-page .report-dialog { overflow-wrap:anywhere; }
.reports-page .daily-chart { display:flex; height:300px; padding:20px 0 32px; margin-top:16px; min-width:0; }
.reports-page .chart-axis { position:relative; width:100px; flex:none; color:var(--axis-ink,var(--muted)); font-size:11px; font-variant-numeric:tabular-nums; }
.reports-page .chart-axis span { position:absolute; right:10px; max-width:90px; transform:translateY(50%); text-align:right; }
.reports-page .chart-scroll { flex:1; overflow-x:auto; overflow-y:hidden; padding:0 0 30px; margin-bottom:-30px; min-width:0; }
.reports-page .chart-plot { position:relative; height:100%; min-width:calc(var(--days) * 28px); }
.reports-page .chart-grid { position:absolute; left:0; right:0; border-top:1px solid var(--rule); }
.reports-page .chart-grid:first-child { border-top-color:var(--control); }
.reports-page .chart-average { position:absolute; left:0; right:0; border-top:1px dashed var(--muted); opacity:.55; }
.reports-page .chart-columns { position:absolute; inset:0; display:flex; }
.reports-page .chart-day { position:relative; flex:1; min-width:0; }
.reports-page .chart-hit { display:block; position:relative; width:100%; height:100%; border:0; padding:0; background:transparent; cursor:pointer; }
.reports-page .chart-hit:hover,.reports-page .chart-hit:focus-visible { background:var(--raised); }
.reports-page .daily-bar { position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:50%; max-width:28px; background:#a9cbd3; border-radius:3px 3px 0 0; pointer-events:none; }
.reports-page .daily-bar.today { background:var(--accent); }
.reports-page .day-label { position:absolute; top:100%; padding-top:8px; left:0; right:0; color:var(--axis-ink,var(--muted)); font-size:12px; }
.reports-page .day-label.today { color:var(--text); font-weight:600; }
.reports-page .daily-tip { position:absolute; z-index:3; top:8px; left:0; width:190px; max-width:70vw; padding:8px 10px; background:var(--surface); border:1px solid var(--border); border-radius:6px; font-size:12px; line-height:18px; pointer-events:none; overflow-wrap:anywhere; }
.reports-page .chart-day:nth-last-child(-n+7) .daily-tip { left:auto; right:0; }
.reports-page .daily-tip > * { display:block; }
.reports-page .daily-tip strong { font-size:15px; font-weight:500; }
.reports-page .chart-paging { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:12px; }
.reports-page .report-loading { opacity:.6; }
@media(min-width:1024px) { .reports-page .report-description .mobile-only,.reports-page .report-table .mobile-only { display:none; } .reports-page .report-detail { width:32px; height:32px; } }
@media(max-width:1023.98px) {
.reports-page .period,.reports-page .segmented { width:100%; }
.reports-page .segmented button { flex:1; min-height:44px; }
.reports-page .report-dates { width:100%; }
.reports-page .report-dates .field { flex:1; width:0; }
.reports-page .report-dates input { width:100%; min-width:0; }
.reports-page .daily-chart { height:230px; }
.reports-page .chart-plot { min-width:calc(var(--days) * 44px); }
.reports-page .report-table thead { display:none; }
.reports-page .report-table tbody tr { display:grid; grid-template-columns:44px minmax(0,1fr) minmax(0,auto); gap:2px 8px; padding:12px 0; border-bottom:1px solid var(--rule); }
.reports-page .report-table tbody td { border:0; padding:0; }
.reports-page .report-table td.act { grid-column:1; grid-row:1 / span 2; }
.reports-page .report-number { grid-column:2; grid-row:1; font-weight:500; }
.reports-page .report-amount { grid-column:3; grid-row:1; max-width:48vw; }
.reports-page .report-description { grid-column:2/-1; grid-row:2; color:var(--muted); font-size:14px; }
.reports-page .report-table tbody .report-empty { grid-column:1/-1; }
.reports-page .report-table tfoot tr { display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; border-top:1px solid var(--border); padding-top:16px; }
.reports-page .report-table tfoot td { border:0; padding:0; }
.reports-page .statement .final { flex-wrap:wrap; }
.reports-page .statement .statement-long { flex-direction:column; align-items:stretch; gap:4px; }
.reports-page .statement .statement-long dt { max-width:none; }
.reports-page .statement .statement-long dd { width:100%; font-size:16px; line-height:24px; }
.reports-page .statement .final dd { margin-left:auto; }
}
</style>
