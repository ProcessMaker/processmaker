{{--
  Auth-page CSS without app.css.
  Only 2 font files on the critical path (Regular + ExtraBold for LCP).
  Icons are inline SVG — no Font Awesome download (~77 KiB).
--}}
<link rel="preload" href="{{ asset('fonts/OpenSans-ExtraBold.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/OpenSans-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:'Open Sans';font-style:normal;font-weight:400;font-display:swap;src:url('{{ asset('fonts/OpenSans-Regular.woff2') }}') format('woff2')}
@font-face{font-family:'Open Sans';font-style:normal;font-weight:800;font-display:swap;src:url('{{ asset('fonts/OpenSans-ExtraBold.woff2') }}') format('woff2')}

*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;line-height:1.15}
body{margin:0;font-family:'Open Sans',sans-serif;font-size:1rem;font-weight:400;line-height:1.5;color:#212529}
button,input{font-family:inherit;font-size:inherit;line-height:inherit;margin:0}
label{display:inline-block}
a{color:{{ color('primary') }};text-decoration:none;background-color:transparent}
a:hover{text-decoration:underline}
img{vertical-align:middle;border-style:none}
strong{font-weight:800}

.d-flex{display:flex!important}
.flex-column{flex-direction:column!important}
.flex-fill{flex:1 1 auto!important}
.d-none{display:none!important}
@media (min-width:992px){.d-lg-flex{display:flex!important}}
.mb-0{margin-bottom:0!important}
.mb-3{margin-bottom:1rem!important}

.btn{display:inline-block;font-weight:400;color:#212529;text-align:center;vertical-align:middle;user-select:none;background-color:transparent;border:1px solid transparent;padding:.375rem .75rem;font-size:1rem;line-height:1.5;border-radius:.25rem;cursor:pointer;transition:color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out}
.btn:hover{text-decoration:none}
.btn-primary{color:#fff;background-color:{{ color('primary') }};border-color:{{ color('primary') }}}
.btn-block{display:block;width:100%}

.form-group{margin-bottom:1rem}
.form-control{display:block;width:100%;padding:.375rem .75rem;font-size:1rem;font-weight:400;line-height:1.5;color:#495057;background-color:#fff;background-clip:padding-box;border:1px solid #ced4da;border-radius:.25rem;transition:border-color .15s ease-in-out,box-shadow .15s ease-in-out}
.form-control:focus{color:#495057;background-color:#fff;outline:0}
.is-invalid{border-color:#ec5962}
.invalid-feedback{display:none;width:100%;margin-top:.25rem;font-size:80%;color:#ec5962}
.is-invalid~.invalid-feedback{display:block}

.alert{position:relative;padding:.75rem 1.25rem;margin-bottom:1rem;border:1px solid transparent;border-radius:.25rem}
.alert-danger{color:#721c24;background-color:#f8d7da;border-color:#f5c6cb}

.card{position:relative;display:flex;flex-direction:column;min-width:0;word-wrap:break-word;background-color:#fff;background-clip:border-box}
.card-body{flex:1 1 auto}

.password-toggle{display:inline-flex;align-items:center;justify-content:center;padding:0;border:0;background:transparent;color:#51585E;line-height:0}
.password-toggle svg{width:18px;height:18px;display:block}
.caps-lock-warning svg{width:12px;height:12px;flex-shrink:0}
</style>
