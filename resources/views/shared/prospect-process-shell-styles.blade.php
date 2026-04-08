<style>
  .prospect-process-shell {
    --prospect-shell-bg: linear-gradient(180deg, #eef5ff 0%, #f8fbff 100%);
    --prospect-surface: #ffffff;
    --prospect-surface-muted: #f8fbff;
    --prospect-border: #d9e6f5;
    --prospect-border-strong: #c5d6ec;
    --prospect-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    --prospect-shadow-soft: 0 14px 30px rgba(15, 23, 42, 0.06);
    --prospect-text: #1f2937;
    --prospect-muted: #64748b;
    --prospect-accent: #1d4ed8;
    --prospect-accent-soft: #dbeafe;
  }

  .prospect-process-shell .prospect-screen-shell {
    background: var(--prospect-shell-bg);
    border: 1px solid var(--prospect-border);
    border-radius: 28px;
    box-shadow: var(--prospect-shadow-soft);
    padding: clamp(16px, 2vw, 28px);
  }

  .prospect-process-shell .prospect-screen-shell + .prospect-screen-shell {
    margin-top: 1rem;
  }

  .prospect-process-shell .prospect-screen-frame {
    max-width: 1080px;
    margin: 0 auto;
    padding: clamp(20px, 3vw, 36px);
    background: var(--prospect-surface);
    border: 1px solid rgba(197, 214, 236, 0.8);
    border-radius: 24px;
    box-shadow: var(--prospect-shadow);
  }

  .prospect-process-shell .prospect-screen-frame--compact {
    max-width: 980px;
  }

  .prospect-process-shell .prospect-screen-toolbar {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .prospect-process-shell .prospect-screen-toolbar .btn.btn-secondary {
    color: var(--prospect-accent);
    background: var(--prospect-accent-soft);
    border-color: #bfdbfe;
    border-radius: 999px;
    font-weight: 600;
    padding-inline: 1rem;
  }

  .prospect-process-shell .prospect-screen-toolbar .btn.btn-secondary:hover,
  .prospect-process-shell .prospect-screen-toolbar .btn.btn-secondary:focus {
    background: #bfdbfe;
    border-color: #93c5fd;
    color: #1e3a8a;
  }

  .prospect-process-shell .prospect-process-header.card-custom {
    background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid var(--prospect-border);
    border-radius: 24px;
    box-shadow: var(--prospect-shadow-soft);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
  }

  .prospect-process-shell .prospect-process-header .title,
  .prospect-process-shell .prospect-process-header-mobile .title {
    color: var(--prospect-text);
    font-weight: 600;
    letter-spacing: -0.02em;
  }

  .prospect-process-shell .prospect-process-header .custom-color {
    color: var(--prospect-accent);
  }

  .prospect-process-shell .prospect-process-header .info-button {
    background-color: #dbeafe;
  }

  .prospect-process-shell .prospect-process-header .info-button span {
    color: var(--prospect-accent);
  }

  .prospect-process-shell .prospect-process-header .info-button-active {
    background-color: var(--prospect-accent) !important;
  }

  .prospect-process-shell .prospect-process-header .info-button-active span {
    color: #ffffff;
  }

  .prospect-process-shell .prospect-process-header-mobile {
    background: var(--prospect-surface);
    border: 1px solid var(--prospect-border);
    border-radius: 18px;
    box-shadow: var(--prospect-shadow-soft);
    margin-bottom: 1rem;
  }

  .prospect-process-shell .prospect-task-renderer,
  .prospect-process-shell .prospect-summary-renderer,
  .prospect-process-shell .prospect-form-renderer {
    background: transparent;
  }

  .prospect-process-shell .prospect-task-renderer.card,
  .prospect-process-shell .prospect-summary-renderer.card,
  .prospect-process-shell .prospect-screen-page.card {
    border: 0;
    box-shadow: none;
    background: transparent;
  }

  .prospect-process-shell .prospect-screen-page + .prospect-screen-page {
    margin-top: 1rem;
  }

  .prospect-process-shell .prospect-task-shell {
    padding-bottom: 1.5rem;
  }

  .prospect-process-shell .prospect-summary-section {
    padding: 0.5rem 0 1.5rem;
  }

  .prospect-process-shell .prospect-screen-frame .card-text,
  .prospect-process-shell .prospect-screen-frame p,
  .prospect-process-shell .prospect-screen-frame dd,
  .prospect-process-shell .prospect-screen-frame dt {
    color: var(--prospect-text);
  }

  .prospect-process-shell .prospect-screen-frame .text-muted {
    color: var(--prospect-muted) !important;
  }

  .prospect-process-shell .prospect-screen-frame .table {
    border-collapse: separate;
    border-spacing: 0;
  }

  .prospect-process-shell .prospect-screen-frame .table thead th {
    background: var(--prospect-surface-muted);
    color: var(--prospect-muted);
    border-top: 0;
    border-bottom: 1px solid var(--prospect-border);
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .prospect-process-shell .prospect-screen-frame .table td {
    border-top-color: #e2e8f0;
    vertical-align: middle;
  }

  .prospect-process-shell .prospect-screen-frame .btn-primary,
  .prospect-process-shell .prospect-screen-frame .btn.btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-color: #1d4ed8;
    border-radius: 999px;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
    font-weight: 600;
    padding-inline: 1.25rem;
  }

  .prospect-process-shell .prospect-screen-frame .btn-outline-secondary,
  .prospect-process-shell .prospect-screen-frame .btn.btn-secondary {
    border-radius: 999px;
  }

  @media (max-width: 768px) {
    .prospect-process-shell .prospect-screen-shell {
      border-radius: 20px;
      padding: 12px;
    }

    .prospect-process-shell .prospect-screen-frame {
      border-radius: 18px;
      padding: 16px;
    }

    .prospect-process-shell .prospect-screen-toolbar {
      justify-content: stretch;
    }

    .prospect-process-shell .prospect-screen-toolbar .btn {
      width: 100%;
    }
  }

  @media print {
    .prospect-process-shell .prospect-screen-shell,
    .prospect-process-shell .prospect-screen-frame {
      background: #ffffff;
      border: 0;
      box-shadow: none;
      padding: 0;
    }
  }
</style>
