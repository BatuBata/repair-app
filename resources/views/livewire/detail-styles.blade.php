<style>
    .record-detail { overflow-wrap:anywhere; }
    .record-detail .title-row { align-items:center; margin-bottom:20px; flex-wrap:nowrap; }
    .record-detail .title-row > div { min-width:0; }
    .record-detail .title-row h1 { font-size:24px; line-height:32px; }
    .record-detail .title-row .detail-number { font:500 26px/34px var(--mono); }
    .record-detail .detail-heading { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .record-detail .act-btn { flex:none; background:var(--primary); color:var(--primary-ink); border-color:var(--primary); }
    .record-detail .act-btn:hover,.record-detail .act-btn[aria-expanded="true"] { background:var(--primary-hover); }
    .record-detail .detail-overview { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); border:1px solid var(--border); border-radius:var(--radius-card); margin-bottom:24px; }
    .record-detail .detail-overview.three-facts { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .record-detail .detail-fact { padding:20px 24px; min-width:0; }
    .record-detail .detail-fact + .detail-fact { border-left:1px solid var(--border); }
    .record-detail .detail-fact h2 { display:flex; align-items:center; gap:8px; font-size:14px; line-height:22px; font-weight:500; color:var(--muted); }
    .record-detail .detail-fact h2 .icon { width:18px; height:18px; flex:none; }
    .record-detail .detail-value { margin-top:8px; font-size:20px; line-height:28px; font-weight:600; font-variant-numeric:tabular-nums; }
    .record-detail .detail-meta { display:block; margin-top:6px; color:var(--muted); font-size:14px; line-height:20px; }
    .record-detail .detail-layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:24px; align-items:start; }
    .record-detail .detail-layout.stock-layout { grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr); }
    .record-detail .detail-panel { min-width:0; padding:20px; border:1px solid var(--border); border-radius:var(--radius-card); }
    .record-detail .detail-panel + .detail-panel { margin-top:24px; }
    .record-detail .detail-layout > .detail-panel + .detail-panel { margin-top:0; }
    .record-detail .detail-panel > h2,.record-detail .section-head h2 { font-size:16px; line-height:24px; font-weight:600; }
    .record-detail .detail-panel > h2 { margin-bottom:12px; }
    .record-detail .detail-panel .section-head { align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .record-detail .detail-totals { display:grid; gap:12px; font-variant-numeric:tabular-nums; }
    .record-detail .detail-totals > div { display:flex; align-items:baseline; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .record-detail .detail-totals dt { color:var(--muted); }
    .record-detail .detail-totals dd { margin-left:auto; text-align:right; max-width:100%; }
    .record-detail .detail-total { padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid var(--border); }
    .record-detail .detail-total dt { margin-bottom:6px; color:var(--muted); }
    .record-detail .detail-total dd { font-size:28px; line-height:36px; font-weight:600; font-variant-numeric:tabular-nums; }
    .record-detail .detail-warranty { margin-top:20px; padding-top:16px; border-top:1px solid var(--border); }
    .record-detail .detail-warranty h3 { font-size:14px; font-weight:500; margin-bottom:6px; }
    .record-detail .detail-view-link { width:44px; height:44px; padding:0; }
    .record-detail .line-items { table-layout:fixed; }
    .record-detail .line-items th:first-child { width:40%; }
    .record-detail .line-items .n { white-space:normal; }
    .record-detail .line-items td { vertical-align:top; }
    .record-detail .line-items .detail-meta { font-size:var(--fs-small); }
    .record-detail .history { margin:0; }
    .record-detail .stock-form { max-width:none; gap:16px; }
    .record-detail .restock-panel { background:var(--raised); }
    .record-detail .customer-note { margin-bottom:16px; }
    @media(max-width:1199.98px) { .record-detail .detail-layout,.record-detail .detail-layout.stock-layout { grid-template-columns:minmax(0,1fr); } }
    @media(max-width:1023.98px) {
        .record-detail .detail-panel,.record-detail .detail-fact { padding:18px; }
        .record-detail .line-items tbody tr { grid-template-columns:minmax(0,1fr) auto; }
        .record-detail .line-items .detail-item-name { grid-column:1 / -1; }
        .record-detail .line-items .detail-item-prices { grid-column:1; text-align:left; }
        .record-detail .line-items .detail-item-subtotal { grid-column:2; }
        .record-detail .history-orders .history-number { grid-column:2; grid-row:1; }
        .record-detail .history-orders .history-state { grid-column:3; grid-row:1; justify-self:end; }
        .record-detail .history-orders .history-body,.record-detail .history-sales .history-body { grid-column:2 / -1; grid-row:2; }
        .record-detail .history-sales .history-number { grid-column:2 / -1; grid-row:1; }
    }
    @media(max-width:639.98px) {
        .record-detail .detail-overview,.record-detail .detail-overview.three-facts { grid-template-columns:minmax(0,1fr); }
        .record-detail .detail-fact + .detail-fact { border-left:0; border-top:1px solid var(--border); }
        .record-detail .detail-value { font-size:18px; line-height:26px; }
    }
</style>
