<!-- Fruit & icon library. Included once per page; draw with <svg><use href="#i-orange"/></svg> -->
<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true">
  <defs>
    <radialGradient id="gr-mango" cx="35%" cy="35%" r="70%">
      <stop offset="0" stop-color="#FFE066"/>
      <stop offset=".6" stop-color="#FFB000"/>
      <stop offset="1" stop-color="#F0533B"/>
    </radialGradient>
    <radialGradient id="gr-straw" cx="40%" cy="35%" r="70%">
      <stop offset="0" stop-color="#FF6B6B"/>
      <stop offset="1" stop-color="#C81D3A"/>
    </radialGradient>
    <radialGradient id="gr-berry" cx="35%" cy="30%" r="70%">
      <stop offset="0" stop-color="#7C8CE0"/>
      <stop offset="1" stop-color="#2C3576"/>
    </radialGradient>
    <linearGradient id="gr-glass-shine" x1="0" x2="1">
      <stop offset="0" stop-color="#fff" stop-opacity=".55"/>
      <stop offset=".25" stop-color="#fff" stop-opacity="0"/>
      <stop offset=".8" stop-color="#fff" stop-opacity="0"/>
      <stop offset="1" stop-color="#fff" stop-opacity=".25"/>
    </linearGradient>
    <linearGradient id="gr-liquid-depth" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#fff" stop-opacity=".28"/>
      <stop offset=".5" stop-color="#fff" stop-opacity="0"/>
      <stop offset="1" stop-color="#000" stop-opacity=".12"/>
    </linearGradient>
  </defs>

  <symbol id="i-orange" viewBox="0 0 100 100">
    <circle cx="50" cy="50" r="48" fill="#F57C00"/>
    <circle cx="50" cy="50" r="42" fill="#FFF3E0"/>
    <circle cx="50" cy="50" r="38" fill="#FFA726"/>
    <g stroke="#FFE0B2" stroke-width="3" stroke-linecap="round">
      <path d="M50 14V86M14 50H86M24.5 24.5L75.5 75.5M75.5 24.5L24.5 75.5"/>
    </g>
    <circle cx="50" cy="50" r="5" fill="#FFE0B2"/>
  </symbol>

  <symbol id="i-lemon" viewBox="0 0 100 100">
    <circle cx="50" cy="50" r="48" fill="#C0CA33"/>
    <circle cx="50" cy="50" r="42" fill="#FFFDE7"/>
    <circle cx="50" cy="50" r="38" fill="#FFEB3B"/>
    <g stroke="#FFF9C4" stroke-width="3" stroke-linecap="round">
      <path d="M50 14V86M14 50H86M24.5 24.5L75.5 75.5M75.5 24.5L24.5 75.5"/>
    </g>
    <circle cx="50" cy="50" r="5" fill="#FFF9C4"/>
  </symbol>

  <symbol id="i-kiwi" viewBox="0 0 100 100">
    <circle cx="50" cy="50" r="48" fill="#8D6E63"/>
    <circle cx="50" cy="50" r="43" fill="#8BC34A"/>
    <circle cx="50" cy="50" r="30" fill="#AED581"/>
    <circle cx="50" cy="50" r="13" fill="#F1F8E9"/>
    <g fill="#212121">
      <ellipse cx="50" cy="28" rx="2" ry="3.5"/><ellipse cx="50" cy="72" rx="2" ry="3.5"/>
      <ellipse cx="28" cy="50" rx="3.5" ry="2"/><ellipse cx="72" cy="50" rx="3.5" ry="2"/>
      <ellipse cx="34" cy="34" rx="2" ry="3.5" transform="rotate(-45 34 34)"/>
      <ellipse cx="66" cy="66" rx="2" ry="3.5" transform="rotate(-45 66 66)"/>
      <ellipse cx="66" cy="34" rx="2" ry="3.5" transform="rotate(45 66 34)"/>
      <ellipse cx="34" cy="66" rx="2" ry="3.5" transform="rotate(45 34 66)"/>
    </g>
  </symbol>

  <symbol id="i-mint" viewBox="0 0 100 60">
    <path d="M4 30 C20 4 64 -2 96 22 C70 52 26 60 4 30 Z" fill="#43A047"/>
    <path d="M4 30 C20 4 64 -2 96 22 C70 30 34 34 4 30 Z" fill="#66BB6A"/>
    <path d="M6 30 C36 26 66 26 94 22" fill="none" stroke="#2E7D32" stroke-width="2.5" stroke-linecap="round"/>
    <path d="M30 28 L42 14 M52 26 L64 12 M42 30 L52 44 M64 27 L74 40" stroke="#2E7D32" stroke-width="1.6" stroke-linecap="round" opacity=".7"/>
  </symbol>

  <symbol id="i-strawberry" viewBox="0 0 100 110">
    <path d="M50 104 C22 90 6 60 12 40 C18 22 36 24 50 30 C64 24 82 22 88 40 C94 60 78 90 50 104 Z" fill="url(#gr-straw)"/>
    <g fill="#FFE082">
      <ellipse cx="34" cy="48" rx="2" ry="3"/><ellipse cx="50" cy="44" rx="2" ry="3"/><ellipse cx="66" cy="48" rx="2" ry="3"/>
      <ellipse cx="28" cy="62" rx="2" ry="3"/><ellipse cx="44" cy="60" rx="2" ry="3"/><ellipse cx="58" cy="62" rx="2" ry="3"/><ellipse cx="72" cy="62" rx="2" ry="3"/>
      <ellipse cx="38" cy="76" rx="2" ry="3"/><ellipse cx="52" cy="78" rx="2" ry="3"/><ellipse cx="64" cy="76" rx="2" ry="3"/>
      <ellipse cx="48" cy="92" rx="2" ry="3"/>
    </g>
    <path d="M50 32 L34 18 L46 24 L42 6 L52 22 L62 8 L58 24 L72 18 Z" fill="#43A047"/>
  </symbol>

  <symbol id="i-berry" viewBox="0 0 100 100">
    <circle cx="50" cy="52" r="44" fill="url(#gr-berry)"/>
    <path d="M50 20 l5 8 9 -1 -5 8 5 8 -9 -1 -5 8 -5 -8 -9 1 5 -8 -5 -8 9 1 z" fill="#1A1F4D" opacity=".75"/>
    <ellipse cx="34" cy="40" rx="8" ry="5" fill="#fff" opacity=".25" transform="rotate(-30 34 40)"/>
  </symbol>

  <symbol id="i-watermelon" viewBox="0 0 120 70">
    <path d="M4 6 H116 A56 56 0 0 1 4 6 Z" fill="#2E7D32"/>
    <path d="M10 6 H110 A50 50 0 0 1 10 6 Z" fill="#E8F5E9"/>
    <path d="M14 6 H106 A46 46 0 0 1 14 6 Z" fill="#FF4D6D"/>
    <g fill="#212121">
      <ellipse cx="40" cy="22" rx="2.2" ry="3.5"/><ellipse cx="60" cy="30" rx="2.2" ry="3.5"/>
      <ellipse cx="80" cy="22" rx="2.2" ry="3.5"/><ellipse cx="50" cy="40" rx="2.2" ry="3.5"/><ellipse cx="70" cy="40" rx="2.2" ry="3.5"/>
    </g>
  </symbol>

  <symbol id="i-mango" viewBox="0 0 100 100">
    <path d="M30 22 C58 4 94 26 88 60 C82 92 40 100 20 78 C4 60 8 36 30 22 Z" fill="url(#gr-mango)"/>
    <path d="M58 16 C62 8 70 4 78 4" fill="none" stroke="#6D4C41" stroke-width="4" stroke-linecap="round"/>
    <path d="M70 6 C84 -2 98 6 96 16 C84 20 74 14 70 6 Z" fill="#43A047"/>
    <ellipse cx="38" cy="38" rx="10" ry="6" fill="#fff" opacity=".3" transform="rotate(-35 38 38)"/>
  </symbol>

  <symbol id="i-splash" viewBox="0 0 200 200">
    <path d="M100 30 C120 30 130 52 150 50 C176 48 186 76 168 92 C156 104 176 122 162 140 C148 160 124 146 110 162 C96 178 70 170 66 150 C62 132 34 136 30 112 C26 88 50 82 48 64 C46 42 78 30 100 30 Z"/>
    <circle cx="182" cy="40" r="8"/><circle cx="24" cy="160" r="10"/><circle cx="170" cy="170" r="6"/><circle cx="36" cy="44" r="5"/>
  </symbol>

  <!-- UI icons (stroke) -->
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-cart" viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20.5" r="1.4" fill="currentColor"/><circle cx="17" cy="20.5" r="1.4" fill="currentColor"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
  <symbol id="i-leaf" viewBox="0 0 24 24"><path d="M5 19C5 10 10 5 20 4c0 10-5 15-14 15M5 19l7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-drop" viewBox="0 0 24 24"><path d="M12 3s6 6.6 6 11a6 6 0 0 1-12 0c0-4.4 6-11 6-11z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-bolt" viewBox="0 0 24 24"><path d="M13 3L5 13h6l-1 8 8-10h-6l1-8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-truck" viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="7" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="18" r="2" fill="#fff" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-star" viewBox="0 0 24 24"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" fill="currentColor"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-grid" viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-cup" viewBox="0 0 24 24"><path d="M6 7h12l-1.5 13h-9zM5 7h14M14 3l-2 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="7.5" cy="8.5" r="1.5" fill="currentColor"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 7l9 6 9-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-store" viewBox="0 0 24 24"><path d="M4 10v10h16V10M3 4h18l-1 6H4zM9 20v-6h6v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 21c1-4 4.5-6 8-6s7 2 8 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-external" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16zM13 7l4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.8M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a9.6 9.6 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-image" viewBox="0 0 24 24"><path d="M3 5h18v14H3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="8.5" cy="10" r="1.8" fill="currentColor"/><path d="M3 17l5-5 4 4 3-3 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
</svg>
