    /* Message table and pagination look like /school: bold header over a 2px rule, one thin rule per row,
       "< Prev 1 2 3 ... Next >" with the current page on a #e9e6e3 square. */
    #emails-messages-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        font-size: 0.875rem;
        color: #151b1e;
        background: #fff;
    }
    #emails-messages-table thead th {
        padding: 1rem;
        background: #fff;
        border: 0;
        border-bottom: 2px solid #d4d4d4;
        font-weight: 700;
        color: #151b1e;
        text-align: left;
        vertical-align: middle;
    }
    #emails-messages-table tbody td {
        padding: 1rem;
        background: #fff;
        border: 0;
        border-bottom: 1px solid #E6E7E8;
        vertical-align: middle;
    }
    /* "..." actions menu like /users (grey dots, hover #f0eeec; white rounded panel, w-44, shadow).
       The panel is position: fixed and placed by script so the table's scroll wrapper cannot clip it. */
    #emails-messages-table .emails-actions-cell {
        width: 40px;
        text-align: center;
    }
    .emails-dots-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 0.5rem;
        border: 0;
        background: transparent;
        color: #525252;
        cursor: pointer;
        transition: all .15s;
    }
    .emails-dots-btn:hover,
    .emails-dots-btn[aria-expanded="true"] { background: #f0eeec; }
    .emails-dots-btn:focus { outline: none; }
    .emails-menu {
        display: none;
        position: fixed;
        z-index: 1050;
        width: 11rem;
        margin: 0;
        padding: 0.5rem 0;
        list-style: none;
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
        text-align: left;
    }
    .emails-menu.is-open { display: block; }
    .emails-menu a {
        display: block;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #374151;
        text-decoration: none;
    }
    .emails-menu a:hover { background: #f3f4f6; color: #374151; text-decoration: none; }

    /* Row height like /users (60px). */
    #emails-messages-table tbody tr {
        height: 60px;
    }
    #emails-messages-table .btn-link {
        text-decoration: none;
    }
    #emails-messages-table .btn-link:hover {
        text-decoration: underline;
    }
    .emails-pager {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem 0 0.25rem;
    }
    .emails-page-btn,
    .emails-page-gap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        height: 2.25rem;
        padding: 0 1rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #374151;
        background: transparent;
        text-decoration: none;
    }
    a.emails-page-btn:hover { color: #9ca3af; text-decoration: none; }
    .emails-page-nav { color: #6b7280; }
    .emails-page-btn.is-disabled { cursor: default; pointer-events: none; }
    .emails-page-btn.is-current {
        background: #e9e6e3;
        color: #2563eb;
        cursor: default;
        pointer-events: none;
    }

    /* Filter card header (Keyword Search, Limit, Order By): white rounded fields like the search box. */
    .emails-filter-head {
        padding: 0.75rem 1.25rem;
        background: #f0f3f5;
        border-bottom: 1px solid #c2cfd6;
    }
    .emails-filter-head label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #151b1e;
        display: block;
        line-height: 1.25rem;
        margin-bottom: 0.5rem !important;
    }
    .emails-filter-head select.form-control {
        height: 2.5rem;
        padding: 0 0.75rem;
        border: 0;
        border-radius: 0.5rem;
        background-color: #fff;
        color: #111827;
        font-size: 0.875rem;
        box-shadow: none;
    }
    .emails-filter-head .emails-search-box {
        position: relative;
        height: 36px;
    }
    .emails-filter-head .emails-search-icon {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0.75rem;
        display: flex;
        align-items: center;
        color: #000;
        pointer-events: none;
    }
    .emails-filter-head .emails-search-input {
        width: 216px;
        height: 36px;
        padding: 0 2.5rem;
        border: 0;
        border-radius: 0.5rem;
        background: #fff;
        color: #111827;
        font-size: 0.875rem;
        box-shadow: none;
    }
    .emails-filter-head .emails-search-input::-webkit-search-cancel-button { display: none; }
    .emails-filter-head select.form-control:focus,
    .emails-filter-head .emails-search-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px #005890;
    }
    .emails-filter-head .emails-search-clear {
        position: absolute;
        top: 0;
        bottom: 0;
        right: 0;
        display: flex;
        align-items: center;
        padding: 0 0.75rem;
        color: #6b7280;
        background: transparent;
        border: 0;
        cursor: pointer;
    }
    .emails-filter-head .emails-search-clear:hover { color: #111827; }
    .emails-filter-head .emails-search-clear svg { width: 1rem; height: 1rem; }

    /* Card box (same as the franchise dashboard boxes) and even spacing inside the filter header. */
    .card {
        border: 1px solid #c2cfd6;
        border-radius: 0.25rem;
        overflow: hidden;
    }
    .emails-filter-head .row {
        margin-top: 0;
        margin-bottom: 0;
    }
    #emails-messages-table .btn-link {
        font-size: inherit;
        line-height: inherit;
        vertical-align: baseline;
    }

    /* Back button sits outside the card, right-aligned below it. */
    .emails-back-row {
        display: flex;
        justify-content: flex-end;
        padding-top: 1rem;
    }
    .emails-back-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25rem;
        color: #fff;
        background: #005890;
        border-radius: 0.5rem;
        text-decoration: none;
        transition: filter 0.15s;
    }
    .emails-back-btn:hover { color: #fff; text-decoration: none; filter: brightness(0.92); }

    /* Sortable headers: clickable, arrow before the label on the sorted column (like /school). */
    #emails-messages-table thead th.emails-sortable {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    #emails-messages-table thead th.emails-sortable:hover {
        background: #f5f7fa;
    }

    /* Card header: title on the left (same as the franchise dashboard "Overview" title), search on the right. */
    .emails-filter-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .emails-filter-head .emails-head-title {
        display: inline;
        margin: 0;
        font-size: 1.09375rem;
        font-weight: 600;
        line-height: 1.1;
        color: #000;
    }
    /* The job / school name sits under the tab title ("Proofing", "User Invitation", ...). */
    .emails-filter-head .emails-head-sub {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.25rem;
        color: #536c79;
    }
    .emails-filter-head .emails-search-box {
        width: 216px;
        margin-left: auto;
    }

    /* Show pages look like /emails: no box around the table, the tab title and the job / school name
       sit under the page heading. */
    .emails-list { background: #fff; }
    /* Page title with the job / school name directly under it: the same look as the franchise dashboard's
       "Resource Centre" title + subtitle (text-2xl title with no margin, 0.875rem #7A8699 line, 0.25rem gap). */
    .emails-show-title h3 {
        margin: 0;
        font-size: 1.75rem;
        line-height: 2rem;
        font-weight: 500;
        color: #212529;
    }
    .emails-show-title .emails-head-sub {
        margin: 0.25rem 0 0;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #7A8699;
    }
