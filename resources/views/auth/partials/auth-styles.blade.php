<style>
  body {
    background: transparent;
    height: unset;
    font-family: 'Open Sans', sans-serif;
  }

  .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: 0;
    margin-left: 0;
  }

  .login-layout {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 100vh;
    padding: 2rem 0;
  }

  .login-panel {
    flex: 0 0 auto;
    padding-left: clamp(1.5rem, 11.67vw, 210px);
  }

  .login-container {
    width: 580px;
    max-width: 100%;
    padding: 50px 70px 40px;
    border: none;
    border-radius: 24px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    background: #ffffff;
  }

  .login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 46px;
  }

  .login-logo-custom,
  .login-logo-default {
    width: 100%;
    max-width: 440px;
    height: auto;
  }

  .auth-card-title {
    color: #333333;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-align: center;
  }

  .auth-card-subtitle {
    color: #666666;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    margin-bottom: 1.5rem;
    text-align: center;
  }

  .auth-card-header .auth-card-title,
  .auth-card-header .auth-card-subtitle {
    text-align: left;
  }

  .auth-card-header .auth-card-title {
    margin-bottom: 0.75rem;
  }

  .auth-card-header .auth-card-subtitle {
    margin-bottom: 2rem;
  }

  .auth-card-footer {
    margin-top: 3.25rem;
    text-align: center;
  }

  .form {
    padding: 0;
  }

  .form-group label {
    color: #333333;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .password-field-header {
    align-items: center;
    display: flex;
    gap: 0.75rem;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }

  .password-field-header label {
    margin-bottom: 0;
  }

  .caps-lock-warning {
    align-items: center;
    color: #C66E00;
    display: inline-flex;
    font-size: 0.75rem;
    font-weight: 500;
    gap: 0.375rem;
    line-height: 1.25rem;
    white-space: nowrap;
  }

  .caps-lock-warning[hidden] {
    display: none !important;
  }

  .background-cover {
    background-color: #002D59;
    background-image: url('{{ asset('img/decisions-bg-pattern.svg') }}');
    background-repeat: repeat;
    background-size: auto;
    background-position: center top;
    position: fixed;
    height: 100%;
    top: 0;
    z-index: -1;
    left: 0;
    width: 100%;
  }

  .form-control-login,
  .login-container .form-control {
    height: 49px;
    padding: 0 1rem;
    border-radius: 8px;
    border: 1px solid #999999;
    color: #333333;
    font-size: 0.875rem;
  }

  .form-control-login::placeholder,
  .login-container .form-control::placeholder {
    color: #808080;
    opacity: 1;
  }

  .form-control-login:focus,
  .login-container .form-control:focus {
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .auth-link {
    color: #2563EB;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
  }

  .auth-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
  }

  .btn-primary {
    background-color: #2563EB;
    border-color: #2563EB;
  }

  .btn-primary:hover,
  .btn-primary:focus {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
  }

  .button-login {
    height: 50px;
    border-radius: 9px;
    font-weight: 600;
    font-size: 1rem;
    text-transform: none;
  }

  .button-login.button-login-uppercase {
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }

  .slogan-panel {
    flex: 1;
    align-items: center;
    padding-left: clamp(2rem, 12.6vw, 227px);
    padding-right: clamp(1.5rem, 5vw, 5rem);
    min-height: 100vh;
  }

  .slogan {
    max-width: 560px;
    font-family: 'Open Sans', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
  }

  .slogan .head-text {
    text-transform: uppercase;
    font-weight: 700;
    color: #A6F252;
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
    letter-spacing: 0.28em;
    line-height: 1.4;
  }

  .slogan .display {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-size: clamp(3rem, 5.5vw, 5.5rem);
    line-height: 0.95;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 1.5rem 0;
    max-width: 100%;
  }

  .slogan .display-line {
    display: block;
  }

  .slogan .display-complexity {
    color: #ffffff;
    font-size: clamp(3rem, 5.5vw, 5.5rem);
    font-weight: 800;
    line-height: 0.95;
  }

  .slogan .subtext {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 300;
    line-height: 1.75;
    max-width: 520px;
    margin-top: 0;
  }

  .slogan .display,
  .slogan .subtext {
    text-transform: none;
  }

  .footer {
    margin-left: clamp(1.5rem, 11.67vw, 210px);
  }

  #togglePassword,
  .toggle-password {
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    cursor: pointer;
    color: #51585E;
  }

  .password-container {
    position: relative;
  }

  .h-100-vh {
    height: 100vh;
  }

  .language-button-container {
    position: fixed;
    left: 13.5px;
    bottom: 13.5px;
    z-index: 1041;
  }

  .language-button-container .btn-language-selector-login {
    align-items: center;
    background: transparent;
    border: 1px solid #ffffff;
    border-radius: 15.5px;
    box-shadow: none;
    color: #ffffff;
    height: 31px;
    justify-content: center;
    min-width: 31px;
    padding: 0 0.5rem;
    text-transform: uppercase;
    width: 31px;
  }

  .language-button-container .btn-language-selector-login > div {
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1;
    text-transform: uppercase;
  }

  .language-button-container .btn-language-selector-login:hover {
    background: rgba(255, 255, 255, 0.12);
  }

  .language-button-container .btn-language-selector-login:active,
  .language-button-container .btn-language-selector-login:focus,
  .language-button-container .btn-language-selector-login:focus-within {
    box-shadow: none;
    outline: none;
  }

  .auth-success-message {
    color: #333333;
    font-size: 0.875rem;
    line-height: 1.5;
    margin-bottom: 1.5rem;
    text-align: center;
  }

  .auth-success-message strong {
    color: #2563EB;
    display: block;
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }

  @media (max-width: 991px) {
    .login-layout {
      justify-content: center;
      padding: 1.5rem;
    }

    .login-panel {
      padding-left: 0;
      width: 100%;
      display: flex;
      justify-content: center;
    }

    .login-container {
      width: 100%;
      max-width: 580px;
      padding: 2rem 1.5rem;
    }
  }

  @media (max-width: 767px) {
    .small-screen {
      border: 0;
      background: transparent;
    }

    .login-container {
      max-width: 100%;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }
  }
</style>
