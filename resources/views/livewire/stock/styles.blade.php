<style>
    .stock-page { overflow-wrap:anywhere; }
    .stock-page .stock-form { display:grid; gap:20px; max-width:560px; }
    .stock-page .form-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .stock-page .error-text { margin:4px 0 0; }
    .stock-page .notice { margin:0 0 20px; }
    .stock-page .stock-summary { display:flex; flex-wrap:wrap; gap:8px 24px; margin:0 0 24px; color:var(--muted); }
    .stock-page .stock-summary strong { color:var(--text); font-weight:500; font-variant-numeric:tabular-nums; }
    .stock-page .history { margin-top:40px; }
    .stock-page .history h2 { font-size:16px; line-height:22px; font-weight:600; margin-bottom:8px; }
    .stock-page .history-note { display:block; color:var(--muted); font-size:var(--fs-small); margin-top:4px; }
    .stock-page .stock-list { table-layout:fixed; }
    .stock-page .stock-list .act { width:48px; }
    .stock-page .stock-list .price-col { width:150px; }
    .stock-page .stock-list .qty-col { width:88px; }
    .stock-page .stock-list .status-col { width:96px; }
    .stock-page .stock-name { font-weight:500; }
    .stock-page .stock-mobile { display:none; }
    @media(max-width:1023.98px) {
        .stock-page .stock-mobile { display:block; font-size:14px; line-height:20px; color:var(--muted); font-weight:400; margin-top:4px; }
        .stock-page .stock-list .desktop-cell { display:none; }
        .stock-page .stock-list td.stock-name { grid-column:2; grid-row:1; }
        .stock-page .stock-list td.stock-count { grid-column:3; grid-row:1; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .stock-page .stock-list td.stock-state { grid-column:2 / -1; grid-row:2; margin-top:4px; }
        .stock-page .stock-list .empty-cell { grid-column:1 / -1; }
        .stock-page .history .table tbody tr { grid-template-columns:minmax(0,1fr) auto; }
        .stock-page .history .history-date { grid-column:1; }
        .stock-page .history .history-qty { grid-column:2; grid-row:1; }
        .stock-page .history .history-cost, .stock-page .history .history-by { grid-column:1 / -1; }
    }
</style>
