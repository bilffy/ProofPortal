{{-- Heading-row search + season select shared by /emails and the /emails/{key} pages (plain CSS). --}}
    /* Heading-row search: the /school search (grey #E6E7E8 rounded field, icon left, clear "X" right).
       Plain CSS, because this older layout does not apply all the Tailwind utilities that page uses. */
    .emails-page-tools {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-right: 0.25rem;
    }
    .emails-page-search {
        position: relative;
        width: 256px;
        height: 36px;
    }
    /* Season dropdown, same grey rounded look as the search box. */
    .emails-page-select {
        height: 36px;
        min-width: 11rem;
        margin: 0;
        padding: 0 2rem 0 0.75rem;
        border: 0;
        border-radius: 0.5rem;
        background-color: #E6E7E8;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23374151' d='M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        color: #111827;
        font-size: 0.875rem;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }
    .emails-page-select:focus { outline: none; box-shadow: 0 0 0 2px #005890; }
    .emails-page-search input {
        display: block;
        width: 100%;
        height: 36px;
        padding: 0 2.25rem 0 2.5rem;
        margin: 0;
        border: 0;
        border-radius: 0.5rem;
        background: #E6E7E8;
        color: #111827;
        font-size: 0.875rem;
        line-height: 1.25rem;
        box-shadow: none;
        box-sizing: border-box;
    }
    .emails-page-search input::placeholder { color: #6b7280; }
    .emails-page-search input:focus { outline: none; box-shadow: 0 0 0 2px #005890; }
    .emails-page-search-icon {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0.75rem;
        display: flex;
        align-items: center;
        color: #374151;
        pointer-events: none;
    }
    .emails-page-search-clear {
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
    .emails-page-search-clear:hover { color: #111827; }
    .emails-page-search-clear svg { width: 1rem; height: 1rem; }
