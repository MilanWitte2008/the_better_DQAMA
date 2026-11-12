/* Eén rustig woordmerk, met voldoende contrast op elke achtergrond. */
.firda-wordmark {
    display:inline-block; flex-shrink:0;
    font-size:42px; line-height:1;
    letter-spacing:0; color:inherit; text-decoration:none;
}
.firda-wordmark-shape {
    display:inline-block;width:2.868421em;height:1em;max-width:100%;vertical-align:middle;
    background:currentColor;
    -webkit-mask:url('{{ asset('images/landing/firda-wordmark.svg') }}') center / contain no-repeat;
    mask:url('{{ asset('images/landing/firda-wordmark.svg') }}') center / contain no-repeat;
}
.firda-header {color:#fff;font-size:46px;}
.firda-preview {color:#fff;font-size:32px;}
.firda-login {color:#fff;font-size:54px;}
.login-mobile-brand .firda-login {color:#183c3c;}
.firda-sidebar {color:#fff;font-size:36px;}
body.dashboard-navigation .sidebar .brand {gap:10px;flex-wrap:wrap;}
body.dashboard-navigation .sidebar .brand>span:last-child {flex:1;min-width:110px;}
.firda-hero {
    position:absolute;z-index:1;inset:auto 0 36%;text-align:center;
    font-size:132px;line-height:1.2;color:#f4faff;
    filter:drop-shadow(0 12px 16px #0005);
}
@media(prefers-reduced-motion:no-preference) {
    .firda-hero {animation:firda-breathe 7s ease-in-out infinite;}
    a:hover>.firda-wordmark {animation:firda-greeting .6s ease-out;}
    @keyframes firda-breathe {0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
    @keyframes firda-greeting {0%,100%{transform:translateY(0)}50%{transform:translateY(-2px)}}
}
@media(min-width:701px) and (max-width:1100px) {
    .firda-hero {font-size:100px;}
}
@media(max-width:700px) {
    .firda-header {font-size:38px;}
    .firda-sidebar {font-size:22px;}
    body.dashboard-navigation .sidebar .brand {flex-wrap:nowrap;}
    .firda-hero {font-size:88px;}
}
@media(max-width:380px) {
    .firda-hero {font-size:72px;}
}
