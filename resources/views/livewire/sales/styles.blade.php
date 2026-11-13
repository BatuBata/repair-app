<style>
    .sales-page { overflow-wrap:anywhere; }
    .sales-page .notice { margin:0 0 20px; }
    .sales-page .error-text { margin:4px 0 0; }
    .sales-page .customer-fields { display:grid; grid-template-columns:1fr 1fr; gap:20px 24px; max-width:760px; }
    .sales-page .sales-form { display:grid; gap:32px; }
    .sales-page .sales-form h2, .sales-page .sale-summary h2 { font-size:16px; font-weight:600; line-height:22px; margin:0; }
    .sales-page .line-heading { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:12px; }
    .sales-page .sale-line { display:grid; grid-template-columns:44px minmax(0,1fr) 88px 168px; align-items:start; gap:12px; padding:20px 0; border-bottom:1px solid var(--rule); }
    .sales-page .sale-line > .remove { padding-top:28px; }
    .sales-page .sale-line .field > span { font-weight:500; }
    .sales-page .stock-hint { font-size:var(--fs-small); color:var(--muted); margin-top:4px; }
    .sales-page .sale-summary { display:grid; gap:12px; max-width:440px; margin-left:auto; width:100%; }
    .sales-page .summary-line { display:flex; flex-wrap:wrap; gap:4px 20px; justify-content:space-between; font-variant-numeric:tabular-nums; }
    .sales-page .summary-line strong { font-weight:500; }
    .sales-page .summary-line > :last-child { flex-shrink:0; max-width:100%; white-space:nowrap; margin-left:auto; }
    .sales-page .summary-line.total { border-top:1px solid var(--border); padding-top:12px; font-size:20px; line-height:28px; }
    .sales-page .form-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .sales-page .sale-information { display:grid; gap:8px; max-width:760px; margin-bottom:24px; }
    .sales-page .sale-information p { margin:0; }
    .sales-page .sale-number { font-family:var(--font-mono); font-weight:500; font-variant-numeric:tabular-nums; }
    .sales-page .detail-actions { display:flex; flex-wrap:wrap; gap:8px; margin:32px 0; }
    .sales-page .item-name { font-weight:500; }
    .sales-page .mobile-label { display:none; }
    .sales-page dialog .dialog { display:grid; gap:16px; }
    .sales-page dialog h2 { font-size:16px; font-weight:600; }
    .sales-page dialog .dialog-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; }
    @media(min-width:1024px) {
        .sales-page .table .n, .sales-page .sale-number, .sales-page .table .c-meta { white-space:nowrap; }
    }
    @media(max-width:1023.98px) {
        .sales-page .sale-line { grid-template-columns:minmax(0,1fr) minmax(0,1fr) 44px; padding:16px; border:1px solid var(--border); border-radius:8px; margin-bottom:16px; }
        .sales-page .sale-line .product-field { grid-column:1 / 3; grid-row:1; }
        .sales-page .sale-line .remove { grid-column:3; grid-row:1; padding-top:28px; }
        .sales-page .sale-line .quantity-field { grid-column:1; }
        .sales-page .sale-line .price-field { grid-column:2 / -1; }
        .sales-page .sale-summary { max-width:none; margin:0; }
        .sales-page .table.money-first td.c-amt { max-width:45vw; white-space:normal; overflow-wrap:anywhere; }
        .sales-page .sale-items tbody tr { grid-template-columns:minmax(0,1fr) auto; }
        .sales-page .sale-items .item-name { grid-column:1 / -1; }
        .sales-page .sale-items .item-qty { grid-column:1; grid-row:2; text-align:left; }
        .sales-page .sale-items .item-price, .sales-page .sale-items .item-cost { grid-column:1 / -1; display:flex; justify-content:space-between; gap:16px; }
        .sales-page .sale-items .item-subtotal { grid-column:1 / -1; display:flex; justify-content:space-between; gap:16px; font-weight:500; }
        .sales-page .mobile-label { display:inline; color:var(--muted); font-weight:400; }
        .sales-page .empty-cell { grid-column:1 / -1; }
    }
    @media(max-width:639.98px) { .sales-page .customer-fields { grid-template-columns:minmax(0,1fr); } }
</style>
