<style>
  .login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .login-remember {
    align-items: center;
    cursor: pointer;
    display: inline-flex;
    gap: 0.5rem;
    margin: 0;
  }

  .login-toggle {
    display: inline-flex;
    flex-shrink: 0;
    position: relative;
  }

  .login-remember-input {
    cursor: pointer;
    height: 17px;
    margin: 0;
    opacity: 0;
    position: absolute;
    width: 27px;
    z-index: 1;
  }

  .login-toggle-track {
    background: #ffffff;
    border: 1px solid #666666;
    border-radius: 8.5px;
    box-sizing: border-box;
    display: block;
    height: 17px;
    position: relative;
    transition: background-color 0.2s ease, border-color 0.2s ease;
    width: 27px;
  }

  .login-toggle-knob {
    background: #666666;
    border-radius: 50%;
    height: 12px;
    left: 2.5px;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    transition: left 0.2s ease, background-color 0.2s ease;
    width: 12px;
  }

  .login-remember-input:checked + .login-toggle-track {
    background: {{ color('primary') }};
    border-color: {{ color('primary') }};
  }

  .login-remember-input:checked + .login-toggle-track .login-toggle-knob {
    background: #ffffff;
    left: calc(100% - 14.5px);
  }

  .login-toggle:focus-within .login-toggle-track {
    box-shadow: 0 0 0 3px rgba({{ color_rgb('primary') }}, 0.1);
  }

  .login-remember-text {
    color: #333333;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.25rem;
    user-select: none;
  }

  .forgot-password-link {
    color: {{ color('primary') }};
    font-size: 0.875rem;
    font-weight: 400;
    text-decoration: none;
    white-space: nowrap;
  }

  .forgot-password-link:hover {
    color: color-mix(in srgb, {{ color('primary') }} 80%, black);
    text-decoration: underline;
  }

  .login-addons {
    margin-top: 0;
  }

  @media (max-width: 767px) {
    .login-options {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }

    .login-remember {
      width: 100%;
    }
  }
</style>
