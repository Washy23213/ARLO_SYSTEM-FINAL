@extends('admin.layout')

@section('title', 'Analytics & Reports')

@section('content')
<style>
    .admin-analytics-page .analytics-report-layout {
        display: grid;
        gap: 1.25rem;
        align-items: start;
    }

    .admin-analytics-page .analytics-command-surface {
        position: relative;
        overflow: visible;
        z-index: 12;
        isolation: isolate;
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 1rem;
        align-items: center;
        padding: 1rem 1.15rem;
        border: 1px solid rgba(208, 219, 210, 0.92);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 247, 0.97)),
            radial-gradient(circle at right center, rgba(16, 185, 129, 0.08), transparent 42%);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
    }

    .admin-analytics-page .analytics-command-surface__actions {
        position: relative;
        z-index: 2;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .admin-analytics-page .analytics-export-trigger {
        display: flex;
        border: 1px solid rgba(209, 220, 211, 0.96);
        border-radius: 1rem;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-export-trigger__main,
    .admin-analytics-page .analytics-export-trigger__toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #0f172a;
        background: transparent;
        font-size: 0.9rem;
        font-weight: 700;
        transition: background-color 0.18s ease, color 0.18s ease;
    }

    .admin-analytics-page .analytics-export-trigger__main {
        min-height: 2.95rem;
        padding: 0.8rem 1.05rem;
    }

    .admin-analytics-page .analytics-export-trigger__toggle {
        min-width: 2.8rem;
        border-left: 1px solid rgba(209, 220, 211, 0.96);
    }

    .admin-analytics-page .analytics-export-trigger__main:hover,
    .admin-analytics-page .analytics-export-trigger__toggle:hover {
        color: #0f6a42;
        background: #f4fbf7;
    }

    .admin-analytics-page .analytics-export-menu {
        width: 18rem;
        margin-top: 0.7rem;
        z-index: 40;
        border: 1px solid rgba(209, 220, 211, 0.96);
        border-radius: 1rem;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }

    .admin-analytics-page .analytics-export-menu__header {
        padding: 0.8rem 1rem;
        background: #f8faf9;
        border-bottom: 1px solid rgba(229, 235, 231, 0.95);
    }

    .admin-analytics-page .analytics-export-overview {
        position: fixed;
        inset: 0;
        width: 100vw;
        min-height: 100dvh;
        z-index: 70;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
        padding: clamp(0.9rem, 2.8vh, 1.5rem);
    }

    .admin-analytics-page .analytics-export-overview.hidden {
        display: none;
    }

    .admin-analytics-page .analytics-export-overview__backdrop {
        position: fixed;
        inset: 0;
        background: rgba(7, 18, 31, 0.68);
        backdrop-filter: blur(8px);
    }

    .admin-analytics-page .analytics-export-overview__dialog {
        position: relative;
        z-index: 1;
        width: min(100%, 56rem);
        max-height: calc(100dvh - 1.8rem);
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) auto;
        overflow: hidden;
        border: 1px solid rgba(209, 220, 211, 0.96);
        border-radius: 1.5rem;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(245, 249, 246, 0.98)),
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.08), transparent 34%),
            radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.08), transparent 30%);
        box-shadow: 0 30px 70px rgba(15, 23, 42, 0.22);
    }

    .admin-analytics-page .analytics-export-overview__header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.2rem 1.25rem 1rem;
        border-bottom: 1px solid rgba(210, 219, 213, 0.92);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 248, 0.94));
    }

    .admin-analytics-page .analytics-export-overview__body {
        min-height: 0;
        overflow: auto;
        padding: 1rem 1.25rem 0;
        scrollbar-gutter: stable;
    }

    .admin-analytics-page .analytics-export-overview__eyebrow {
        color: #0f6a42;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-size: 0.68rem;
        font-weight: 800;
    }

    .admin-analytics-page .analytics-export-overview__title {
        margin-top: 0.35rem;
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .admin-analytics-page .analytics-export-overview__description {
        margin-top: 0.45rem;
        color: #4b5563;
        font-size: 0.92rem;
        line-height: 1.6;
        max-width: 42rem;
    }

    .admin-analytics-page .analytics-export-overview__close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.65rem;
        height: 2.65rem;
        border: 1px solid rgba(209, 220, 211, 0.96);
        border-radius: 999px;
        background: #fff;
        color: #0f5132;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .analytics-export-overview__close:hover {
        transform: translateY(-1px);
        background: #f8fbf9;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .admin-analytics-page .analytics-export-overview__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .admin-analytics-page .analytics-export-overview__chip {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.58rem 0.82rem;
        border: 1px solid rgba(209, 220, 211, 0.95);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    .admin-analytics-page .analytics-export-overview__chip i {
        color: #0f6a42;
        font-size: 1rem;
    }

    .admin-analytics-page .analytics-export-overview__grid {
        display: grid;
        gap: 1rem;
        margin-top: 1rem;
    }

    .admin-analytics-page .analytics-export-overview__panel {
        padding: 1rem 1.05rem;
        border: 1px solid rgba(209, 220, 211, 0.9);
        border-radius: 1.2rem;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.88), 0 12px 24px rgba(15, 23, 42, 0.05);
    }

    .admin-analytics-page .analytics-export-overview__panel-title {
        color: #0f172a;
        font-size: 0.94rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .admin-analytics-page .analytics-export-overview__panel-copy {
        margin-top: 0.25rem;
        color: #6b7280;
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .admin-analytics-page .analytics-export-overview__list {
        display: grid;
        gap: 0.6rem;
        margin-top: 0.85rem;
    }

    .admin-analytics-page .analytics-export-overview__selection-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.7rem;
        margin-top: 0.9rem;
    }

    .admin-analytics-page .analytics-export-overview__selection-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .admin-analytics-page .analytics-export-overview__selection-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 2.2rem;
        padding: 0.48rem 0.78rem;
        border: 1px solid rgba(209, 220, 211, 0.94);
        border-radius: 999px;
        background: #fff;
        color: #0f5132;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
    }

    .admin-analytics-page .analytics-export-overview__selection-button:hover {
        transform: translateY(-1px);
        background: #f8fbf9;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-export-overview__selection-hint {
        color: #4b5563;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1.5;
    }

    .admin-analytics-page .analytics-export-overview__choice-list {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    .admin-analytics-page .analytics-export-overview__choice {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        width: 100%;
        padding: 0.72rem 0.78rem;
        border: 1px solid rgba(209, 220, 211, 0.9);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.96);
        text-align: left;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.9);
    }

    .admin-analytics-page .analytics-export-overview__choice:hover {
        transform: translateY(-1px);
        border-color: rgba(148, 163, 184, 0.42);
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.06);
    }

    .admin-analytics-page .analytics-export-overview__choice.is-selected {
        border-color: rgba(14, 165, 233, 0.3);
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.08), rgba(14, 165, 233, 0.05));
        box-shadow: 0 14px 28px rgba(15, 118, 110, 0.09);
    }

    .admin-analytics-page .analytics-export-overview__choice.is-summary {
        cursor: default;
    }

    .admin-analytics-page .analytics-export-overview__choice.is-summary:hover {
        transform: none;
        border-color: rgba(14, 165, 233, 0.3);
        box-shadow: 0 14px 28px rgba(15, 118, 110, 0.09);
    }

    .admin-analytics-page .analytics-export-overview__choice-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.2rem;
        height: 1.2rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
        border: 1.5px solid #c6d4c9;
        border-radius: 0.35rem;
        background: #fff;
        color: transparent;
        font-size: 0.8rem;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .admin-analytics-page .analytics-export-overview__choice.is-selected .analytics-export-overview__choice-indicator {
        border-color: #02681e;
        background: linear-gradient(135deg, #02681e, #ffc600);
        color: #fff;
    }

    .admin-analytics-page .analytics-export-overview__choice-copy {
        min-width: 0;
        flex: 1 1 auto;
    }

    .admin-analytics-page .analytics-export-overview__choice-title {
        color: #0f172a;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .admin-analytics-page .analytics-export-overview__choice-meta {
        margin-top: 0.15rem;
        color: #6b7280;
        font-size: 0.74rem;
        line-height: 1.45;
    }

    .admin-analytics-page .analytics-export-overview__list-item {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        color: #1f2937;
        font-size: 0.86rem;
        line-height: 1.55;
    }

    .admin-analytics-page .analytics-export-overview__list-item::before {
        content: "";
        width: 0.62rem;
        height: 0.62rem;
        flex-shrink: 0;
        margin-top: 0.38rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #02681e, #ffc600);
        box-shadow: 0 0 0 0.22rem rgba(15, 157, 111, 0.1);
    }

    .admin-analytics-page .analytics-export-overview__metrics {
        display: grid;
        gap: 0.75rem;
        margin-top: 0.9rem;
        grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr));
    }

    .admin-analytics-page .analytics-export-overview__preview-grid {
        display: grid;
        gap: 0.85rem;
        margin-top: 0.9rem;
        grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
    }

    .admin-analytics-page .analytics-export-overview__preview-card {
        appearance: none;
        display: grid;
        cursor: pointer;
        font: inherit;
        gap: 0.7rem;
        width: 100%;
        padding: 0.8rem;
        border: 1px solid rgba(209, 220, 211, 0.9);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(244, 248, 245, 0.96));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 10px 20px rgba(15, 23, 42, 0.05);
        text-align: left;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .analytics-export-overview__preview-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 26px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-export-overview__preview-card.is-selected {
        border-color: rgba(14, 165, 233, 0.3);
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.08), rgba(14, 165, 233, 0.05));
        box-shadow: 0 16px 30px rgba(15, 118, 110, 0.09);
    }

    .admin-analytics-page .analytics-export-overview__preview-card:not(.is-selected) {
        opacity: 0.74;
        border-style: dashed;
    }

    .admin-analytics-page .analytics-export-overview__preview-card:not(.is-selected) .analytics-export-overview__preview-figure,
    .admin-analytics-page .analytics-export-overview__preview-card:not(.is-selected) .analytics-export-overview__preview-data {
        opacity: 0.68;
    }

    .admin-analytics-page .analytics-export-overview__preview-card:focus-visible {
        outline: 2px solid rgba(14, 165, 233, 0.34);
        outline-offset: 2px;
    }

    .admin-analytics-page .analytics-export-overview__preview-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .admin-analytics-page .analytics-export-overview__preview-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 1.8rem;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        border: 1px solid rgba(209, 220, 211, 0.94);
        background: #fff;
        color: #4b5563;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .admin-analytics-page .analytics-export-overview__preview-card.is-selected .analytics-export-overview__preview-status {
        border-color: rgba(14, 165, 233, 0.24);
        background: rgba(255, 255, 255, 0.96);
        color: #0f6a42;
    }

    .admin-analytics-page .analytics-export-overview__preview-title {
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .admin-analytics-page .analytics-export-overview__preview-note {
        color: #6b7280;
        font-size: 0.72rem;
        line-height: 1.45;
    }

    .admin-analytics-page .analytics-export-overview__preview-figure {
        position: relative;
        aspect-ratio: 16 / 10;
        overflow: hidden;
        border: 1px solid rgba(209, 220, 211, 0.92);
        border-radius: 0.9rem;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 249, 247, 0.98)),
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.08), transparent 40%);
    }

    .admin-analytics-page .analytics-export-overview__preview-image {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .admin-analytics-page .analytics-export-overview__preview-image[hidden] {
        display: none;
    }

    .admin-analytics-page .analytics-export-overview__preview-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 0.9rem;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        line-height: 1.55;
    }

    .admin-analytics-page .analytics-export-overview__preview-data {
        display: grid;
        gap: 0.5rem;
    }

    .admin-analytics-page .analytics-export-overview__preview-sparkline {
        padding: 0.58rem 0.7rem;
        border: 1px solid rgba(209, 220, 211, 0.92);
        border-radius: 0.85rem;
        background: rgba(255, 255, 255, 0.92);
        color: #0f5132;
        font-family: var(--portal-font-mono);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-analytics-page .analytics-export-overview__preview-rows {
        display: grid;
        gap: 0.35rem;
    }

    .admin-analytics-page .analytics-export-overview__preview-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.55rem;
        color: #334155;
        font-size: 0.74rem;
        line-height: 1.4;
    }

    .admin-analytics-page .analytics-export-overview__preview-row-label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-analytics-page .analytics-export-overview__preview-row-value {
        flex-shrink: 0;
        color: #0f172a;
        font-weight: 700;
    }

    .admin-analytics-page .analytics-export-overview__preview-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 8rem;
        padding: 1rem;
        border: 1px dashed rgba(148, 163, 184, 0.5);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.82);
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.55;
        text-align: center;
    }

    .admin-analytics-page .analytics-export-overview__metric {
        padding: 0.9rem 0.95rem;
        border: 1px solid rgba(209, 220, 211, 0.9);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(243, 247, 244, 0.96));
    }

    .admin-analytics-page .analytics-export-overview__metric-label {
        color: #0f5132;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-size: 0.64rem;
        font-weight: 800;
    }

    .admin-analytics-page .analytics-export-overview__metric-value {
        margin-top: 0.4rem;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .admin-analytics-page .analytics-export-overview__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 0;
        padding: 1rem 1.25rem 1.2rem;
        border-top: 1px solid rgba(210, 219, 213, 0.95);
        background: linear-gradient(180deg, rgba(248, 251, 249, 0.9), rgba(255, 255, 255, 0.98));
    }

    .admin-analytics-page .analytics-export-overview__secondary,
    .admin-analytics-page .analytics-export-overview__primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 2.9rem;
        padding: 0.78rem 1rem;
        border-radius: 1rem;
        font-size: 0.88rem;
        font-weight: 700;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .analytics-export-overview__secondary {
        color: #0f5132;
        border: 1px solid rgba(209, 220, 211, 0.95);
        background: #fff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
    }

    .admin-analytics-page .analytics-export-overview__primary {
        color: #fff;
        border: 1px solid rgba(11, 59, 31, 0.14);
        background: linear-gradient(135deg, #00491e, #02681e);
        box-shadow: 0 16px 30px rgba(11, 59, 31, 0.26);
    }

    .admin-analytics-page .analytics-export-overview__secondary:hover,
    .admin-analytics-page .analytics-export-overview__primary:hover {
        transform: translateY(-1px);
    }

    .analytics-export-overview-open {
        overflow: hidden;
    }

    .admin-analytics-page .analytics-filter-panel {
        position: relative;
        overflow: hidden;
        padding: 1.15rem;
        border: 1px solid rgba(208, 219, 210, 0.92);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(244, 249, 246, 0.98)),
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.08), transparent 38%),
            radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.08), transparent 32%);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.09);
    }

    .admin-analytics-page .analytics-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image: linear-gradient(rgba(15, 23, 42, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
        background-size: 1.2rem 1.2rem;
        mask-image: linear-gradient(180deg, rgba(255, 255, 255, 0.28), transparent 92%);
    }

    .admin-analytics-page .analytics-filter-panel__header,
    .admin-analytics-page .analytics-filter-panel__summary,
    .admin-analytics-page .analytics-filter-form {
        position: relative;
        z-index: 1;
    }

    .admin-analytics-page .analytics-filter-panel__header {
        display: grid;
        gap: 1rem;
        align-items: flex-start;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(210, 219, 213, 0.95);
    }

    .admin-analytics-page .analytics-filter-panel__title {
        display: flex;
        gap: 0.9rem;
        align-items: flex-start;
    }

    .admin-analytics-page .analytics-filter-panel__glyph {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.7rem;
        height: 2.7rem;
        flex-shrink: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, #00491e, #02681e 55%, #ffc600);
        color: #fff;
        box-shadow: 0 14px 28px rgba(21, 128, 61, 0.18);
        font-size: 1.12rem;
    }

    .admin-analytics-page .analytics-filter-panel__eyebrow {
        color: #0f5132;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .admin-analytics-page .analytics-filter-panel__intro {
        margin-top: 0.35rem;
        color: #4b5563;
        font-size: 0.86rem;
        line-height: 1.6;
    }

    .admin-analytics-page .analytics-filter-panel__status {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        min-width: 0;
        padding: 0.8rem 0.9rem;
        border: 1px solid rgba(209, 220, 211, 0.95);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(248, 251, 249, 0.98), rgba(238, 246, 241, 0.98));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .admin-analytics-page .analytics-filter-panel__status-label {
        color: #0f5132;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-size: 0.62rem;
        font-weight: 800;
    }

    .admin-analytics-page .analytics-filter-panel__status-value {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 800;
        line-height: 1;
    }

    .admin-analytics-page .analytics-filter-panel__status-note {
        display: none;
        margin-top: 0.45rem;
        color: #4b5563;
        font-size: 0.85rem;
        line-height: 1.55;
    }

    .admin-analytics-page .analytics-filter-feedback {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.55rem 0.78rem;
        border-radius: 0.85rem;
        border: 1px solid #d7e7dc;
        background: #f4fbf7;
        color: #0f6a42;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .admin-analytics-page .analytics-filter-feedback::before {
        content: "";
        width: 0.6rem;
        height: 0.6rem;
        flex-shrink: 0;
        border-radius: 999px;
        background: currentColor;
        box-shadow: 0 0 0 0.25rem rgba(21, 128, 61, 0.12);
    }

    .admin-analytics-page .analytics-filter-feedback.text-red-600 {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .admin-analytics-page .analytics-filter-panel__summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .admin-analytics-page .analytics-filter-chip,
    .admin-analytics-page .analytics-filter-panel__empty {
        display: inline-flex;
        align-items: center;
        gap: 0.58rem;
        padding: 0.52rem 0.72rem;
        border-radius: 999px;
        border: 1px solid rgba(209, 220, 211, 0.95);
        background: rgba(255, 255, 255, 0.98);
        color: #0f172a;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    }

    .admin-analytics-page button.analytics-filter-chip {
        appearance: none;
        cursor: pointer;
        font: inherit;
        text-align: left;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page button.analytics-filter-chip:hover,
    .admin-analytics-page button.analytics-filter-chip:focus-visible {
        border-color: rgba(20, 87, 38, 0.28);
        background: #f8fbf9;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
        outline: none;
    }

    .admin-analytics-page .analytics-filter-chip i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.7rem;
        height: 1.7rem;
        border-radius: 999px;
        background: rgba(15, 106, 66, 0.08);
        color: #0f6a42;
        font-size: 0.88rem;
    }

    .admin-analytics-page .analytics-filter-chip__label {
        color: #0f5132;
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .admin-analytics-page .analytics-filter-chip__value {
        color: #0f172a;
        font-size: 0.74rem;
        font-weight: 700;
    }

    .admin-analytics-page .analytics-filter-panel__empty {
        color: #4b5563;
        line-height: 1.55;
    }

    .admin-analytics-page .analytics-filter-form {
        display: block;
        margin-top: 1rem;
    }

    .admin-analytics-page .analytics-filter-form__more[hidden] {
        display: none;
    }

    .admin-analytics-page .analytics-filter-form__toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.2rem;
        padding: 0;
        color: #0f5132;
        font-size: 0.82rem;
        font-weight: 700;
        background: transparent;
        border: 0;
    }

    .admin-analytics-page .analytics-filter-form__toggle i {
        font-size: 1rem;
        transition: transform 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-form__toggle[aria-expanded='true'] i {
        transform: rotate(180deg);
    }

    .admin-analytics-page .analytics-filter-group {
        margin: 0;
        padding: 1rem 0;
        border-top: 1px solid rgba(210, 219, 213, 0.95);
        scroll-margin-top: 1.25rem;
        transition: background-color 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .admin-analytics-page .analytics-filter-panel {
        scroll-margin-top: 1.25rem;
    }

    .admin-analytics-page .analytics-filter-group:focus,
    .admin-analytics-page .analytics-filter-panel:focus {
        outline: none;
    }

    .admin-analytics-page .analytics-filter-group.is-jump-target,
    .admin-analytics-page .analytics-filter-panel.is-jump-target {
        border-color: rgba(200, 154, 45, 0.45);
        background: linear-gradient(180deg, rgba(255, 251, 235, 0.8), rgba(241, 248, 242, 0.65));
        box-shadow:
            inset 0 0 0 1px rgba(200, 154, 45, 0.28),
            0 14px 28px rgba(7, 24, 14, 0.1);
        animation: analyticsFilterJump 1.45s ease-out;
    }

    .admin-analytics-page .analytics-filter-panel.is-jump-target {
        border-color: rgba(15, 106, 66, 0.5);
        box-shadow:
            inset 0 0 0 2px rgba(15, 106, 66, 0.24),
            0 0 0 0.32rem rgba(16, 185, 129, 0.14),
            0 22px 42px rgba(7, 24, 14, 0.14);
    }

    .admin-analytics-page .analytics-filter-panel.is-jump-target .analytics-filter-panel__header {
        border-color: rgba(15, 106, 66, 0.32);
        background: linear-gradient(135deg, rgba(236, 253, 245, 0.92), rgba(255, 251, 235, 0.72));
        border-radius: 1rem;
        box-shadow: inset 0 0 0 1px rgba(15, 106, 66, 0.16);
    }

    .admin-analytics-page .analytics-filter-form > .analytics-filter-group:first-of-type {
        padding-top: 0;
        border-top: 0;
    }

    .admin-analytics-page .analytics-filter-group__title {
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.35;
        letter-spacing: -0.01em;
    }

    .admin-analytics-page .analytics-filter-group__hint {
        margin-top: 0.25rem;
        color: #6b7280;
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .admin-analytics-page .analytics-filter-field-grid {
        display: grid;
        gap: 0.75rem;
        margin-top: 0.8rem;
    }

    .admin-analytics-page .analytics-filter-field {
        display: grid;
        gap: 0.42rem;
        min-width: 0;
    }

    .admin-analytics-page .analytics-filter-field > span {
        color: #476354;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .admin-analytics-page .analytics-filter-control {
        width: 100%;
        min-width: 0;
        min-height: 2.85rem;
        border: 1px solid rgba(11, 59, 31, 0.18);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.94);
        color: #102016;
        padding: 0.72rem 0.78rem;
        font-size: 0.84rem;
        font-weight: 650;
        line-height: 1.4;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 8px 18px rgba(15, 23, 42, 0.04);
    }

    .admin-analytics-page select.analytics-filter-control {
        appearance: none;
        background-image:
            linear-gradient(45deg, transparent 50%, #0f5132 50%),
            linear-gradient(135deg, #0f5132 50%, transparent 50%);
        background-position:
            calc(100% - 1.15rem) 50%,
            calc(100% - 0.82rem) 50%;
        background-size: 0.38rem 0.38rem, 0.38rem 0.38rem;
        background-repeat: no-repeat;
        padding-right: 2.4rem;
    }

    .admin-analytics-page .analytics-filter-control:hover {
        background-color: #fff;
        border-color: rgba(11, 59, 31, 0.26);
    }

    .admin-analytics-page .analytics-filter-control:focus {
        outline: none;
        border-color: rgba(20, 87, 38, 0.48);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(30, 122, 52, 0.12), 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-chart-picker {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.8rem;
    }

    .admin-analytics-page .analytics-chart-option {
        display: block;
        cursor: pointer;
    }

    .admin-analytics-page .analytics-chart-option__input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .admin-analytics-page .analytics-chart-option__ui {
        display: flex;
        align-items: center;
        gap: 0.62rem;
        min-height: 2.75rem;
        padding: 0.68rem 0.78rem;
        border: 1px solid rgba(209, 220, 211, 0.82);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.86);
        color: #111827;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.35;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92);
    }

    .admin-analytics-page .analytics-chart-option:hover .analytics-chart-option__ui {
        background: #f7fbf9;
        border-color: rgba(156, 163, 175, 0.34);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    .admin-analytics-page .analytics-chart-option__box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.08rem;
        height: 1.08rem;
        flex-shrink: 0;
        border: 1.5px solid #c6d4c9;
        border-radius: 0.35rem;
        background: #fff;
        color: transparent;
        font-size: 0.72rem;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .admin-analytics-page .analytics-chart-option__input:checked + .analytics-chart-option__ui {
        border-color: rgba(15, 106, 66, 0.28);
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.12), rgba(255, 255, 255, 0.92));
        color: #0f5132;
        box-shadow: 0 14px 28px rgba(15, 118, 110, 0.1);
        transform: translateX(2px);
    }

    .admin-analytics-page .analytics-chart-option__input:checked + .analytics-chart-option__ui .analytics-chart-option__box {
        border-color: #02681e;
        background: linear-gradient(135deg, #02681e, #ffc600);
        color: #fff;
    }

    .admin-analytics-page .analytics-filter-search {
        position: relative;
        margin-top: 0.75rem;
        display: grid;
        gap: 0.45rem;
    }

    .admin-analytics-page .analytics-filter-search__label {
        color: #476354;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .admin-analytics-page .analytics-filter-search__control {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-height: 2.75rem;
        padding: 0 0.82rem;
        border: 1px solid rgba(11, 59, 31, 0.18);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 8px 18px rgba(15, 23, 42, 0.04);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-search__control:focus-within {
        border-color: rgba(20, 87, 38, 0.48);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(30, 122, 52, 0.12), 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-filter-search__control i {
        color: #0f5132;
        font-size: 1.05rem;
    }

    .admin-analytics-page .analytics-filter-search__input {
        width: 100%;
        min-width: 0;
        min-height: auto !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        outline: none !important;
        background: transparent !important;
        box-shadow: none !important;
        transform: none !important;
        appearance: none;
        -webkit-appearance: none;
        color: #102016;
        font-size: 0.84rem;
        font-weight: 600;
    }

    .admin-analytics-page .analytics-filter-search__input:hover,
    .admin-analytics-page .analytics-filter-search__input:focus {
        border: 0 !important;
        outline: none !important;
        background: transparent !important;
        box-shadow: none !important;
        transform: none !important;
    }

    .admin-analytics-page .analytics-filter-search__input::-webkit-search-cancel-button {
        display: none;
    }

    .admin-analytics-page .analytics-filter-search__input::placeholder {
        color: #8aa093;
        font-weight: 500;
    }

    .admin-analytics-page .analytics-filter-search__clear[hidden] {
        display: none;
    }

    .admin-analytics-page .analytics-filter-search__clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.65rem;
        height: 1.65rem;
        flex: 0 0 1.65rem;
        border: 1px solid rgba(15, 81, 50, 0.14);
        border-radius: 999px;
        background: rgba(239, 248, 241, 0.92);
        color: #0f5132;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-search__clear:hover,
    .admin-analytics-page .analytics-filter-search__clear:focus-visible {
        border-color: rgba(15, 81, 50, 0.28);
        background: #dcfce7;
        color: #063f25;
        outline: none;
        transform: scale(1.04);
    }

    .admin-analytics-page .analytics-filter-search__clear i {
        color: currentColor;
        font-size: 1rem;
    }

    .admin-analytics-page .analytics-filter-search__suggestions[hidden] {
        display: none;
    }

    .admin-analytics-page .analytics-filter-search__suggestions {
        display: grid;
        gap: 0.35rem;
        padding: 0.4rem;
        border: 1px solid rgba(15, 81, 50, 0.16);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-filter-search__suggestion {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 0.55rem;
        border: 0;
        border-radius: 0.78rem;
        background: transparent;
        padding: 0.58rem 0.65rem;
        color: #102016;
        text-align: left;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.35;
        transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-search__suggestion:hover,
    .admin-analytics-page .analytics-filter-search__suggestion:focus-visible,
    .admin-analytics-page .analytics-filter-search__suggestion.is-active {
        color: #0f5132;
        background: #eef8f1;
        outline: none;
        transform: translateX(2px);
    }

    .admin-analytics-page .analytics-filter-search__suggestion.is-active {
        box-shadow: inset 0 0 0 1px rgba(15, 157, 111, 0.22);
    }

    .admin-analytics-page .analytics-filter-search__suggestion.is-selected {
        color: #0f5132;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.12), rgba(14, 165, 233, 0.06));
    }

    .admin-analytics-page .analytics-filter-search__suggestion i {
        color: #02681e;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .admin-analytics-page .analytics-filter-search__empty {
        color: #b45309;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .admin-analytics-page .analytics-filter-group__options {
        display: grid;
        gap: 0.5rem;
        margin-top: 0.7rem;
    }

    .admin-analytics-page .analytics-filter-group__more[hidden] {
        display: none;
    }

    .admin-analytics-page .analytics-filter-group__more {
        display: grid;
        gap: 0.5rem;
        margin-top: 0.4rem;
    }

    .admin-analytics-page .analytics-filter-option {
        display: block;
        cursor: pointer;
    }

    .admin-analytics-page .analytics-filter-option__input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .admin-analytics-page .analytics-filter-option__ui {
        display: flex;
        align-items: flex-start;
        gap: 0.62rem;
        padding: 0.72rem 0.78rem;
        border: 1px solid rgba(209, 220, 211, 0.82);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.84);
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92);
    }

    .admin-analytics-page .analytics-filter-option:hover .analytics-filter-option__ui {
        background: #f7fbf9;
        border-color: rgba(156, 163, 175, 0.34);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }

    .admin-analytics-page .analytics-filter-option__box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.05rem;
        height: 1.05rem;
        margin-top: 0.08rem;
        flex-shrink: 0;
        border: 1.5px solid #c6d4c9;
        border-radius: 999px;
        background: #fff;
        color: transparent;
        font-size: 0.72rem;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-option__label {
        color: #111827;
        font-size: 0.84rem;
        line-height: 1.5;
        font-weight: 600;
    }

    .admin-analytics-page .analytics-filter-option__input:checked + .analytics-filter-option__ui {
        border-color: rgba(14, 165, 233, 0.24);
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.1), rgba(14, 165, 233, 0.06));
        box-shadow: 0 14px 28px rgba(15, 118, 110, 0.1);
        transform: translateX(2px);
    }

    .admin-analytics-page .analytics-filter-option__input:checked + .analytics-filter-option__ui .analytics-filter-option__box {
        border-color: #02681e;
        background: linear-gradient(135deg, #02681e, #ffc600);
        color: #fff;
    }

    .admin-analytics-page .analytics-filter-option__input:checked + .analytics-filter-option__ui .analytics-filter-option__label {
        color: #0f5132;
        font-weight: 700;
    }

    .admin-analytics-page .analytics-filter-group__toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.3rem;
        padding: 0;
        color: #0f5132;
        font-size: 0.8rem;
        font-weight: 700;
        background: transparent;
        border: 0;
    }

    .admin-analytics-page .analytics-filter-group__toggle i {
        font-size: 1rem;
        transition: transform 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-group__toggle[aria-expanded='true'] i {
        transform: rotate(180deg);
    }

    .admin-analytics-page .analytics-filter-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        align-items: center;
        gap: 0.65rem;
        margin-top: 0.55rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(210, 219, 213, 0.95);
    }

    .admin-analytics-page .analytics-filter-actions__title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .admin-analytics-page .analytics-filter-actions__body {
        margin-top: 0.35rem;
        color: #6b7280;
        font-size: 0.88rem;
        line-height: 1.55;
        max-width: 38rem;
    }

    .admin-analytics-page .analytics-filter-actions__buttons {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    .admin-analytics-page .analytics-filter-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 2.65rem;
        padding: 0.72rem 0.95rem;
        border-radius: 0.95rem;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .admin-analytics-page .analytics-filter-reset {
        color: #0f5132;
        border: 1px solid #d7e7dc;
        background: #fff;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .analytics-filter-reset:hover {
        transform: translateY(-1px);
        background: #f8fbf9;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
    }

    @media (min-width: 1280px) {
        .admin-analytics-page .analytics-report-layout {
            grid-template-columns: minmax(17rem, 19rem) minmax(0, 1fr);
        }

        .admin-analytics-page .analytics-filter-panel {
            position: sticky;
            top: 1.4rem;
            max-height: calc(100vh - 2.8rem);
            overflow: auto;
        }
    }

    @media (max-width: 1279px) {
        .admin-analytics-page .analytics-report-layout {
            gap: 1.5rem;
        }

        .admin-analytics-page .analytics-filter-panel__header {
            gap: 1.15rem;
            padding-bottom: 1.15rem;
        }

        .admin-analytics-page .analytics-filter-panel__summary {
            margin-top: 1.1rem;
        }

        .admin-analytics-page .analytics-filter-form {
            margin-top: 1.15rem;
        }

        .admin-analytics-page .analytics-filter-group {
            padding-top: 1.1rem;
            padding-bottom: 1.1rem;
        }

        .admin-analytics-page .analytics-filter-group__options {
            gap: 0.65rem;
            margin-top: 0.8rem;
        }

        .admin-analytics-page .analytics-filter-actions {
            margin-top: 0.85rem;
            padding-top: 1.15rem;
        }
    }

    .admin-analytics-page .analytics-report-layout__main {
        min-width: 0;
        display: grid;
        gap: 1.5rem;
    }

    .admin-analytics-page .analytics-chart-grid {
        min-width: 0;
        display: grid;
        gap: 1.5rem;
        align-items: start;
    }

    .admin-analytics-page .analytics-chart-card {
        scroll-margin-top: 1.25rem;
        min-width: 0;
        align-self: start;
    }

    .admin-analytics-page .analytics-chart-card.is-jump-target {
        border-color: rgba(200, 154, 45, 0.55);
        box-shadow:
            inset 0 0 0 1px rgba(200, 154, 45, 0.32),
            0 18px 34px rgba(7, 24, 14, 0.12);
        animation: analyticsFilterJump 1.45s ease-out;
    }

    .admin-analytics-page .analytics-chart-card--span-full {
        grid-column: 1 / -1;
    }

    .admin-analytics-page .analytics-chart-card__header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 1rem;
    }

    .admin-analytics-page .analytics-chart-card__heading {
        min-width: 0;
        flex: 1 1 14rem;
    }

    .admin-analytics-page .analytics-chart-card__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .admin-analytics-page .analytics-chart-area {
        width: 100%;
        min-width: 0;
    }

    .admin-analytics-page .analytics-chart-area--radial {
        max-width: min(100%, 31rem);
        margin-inline: auto;
    }

    .admin-analytics-page .analytics-chart-area--radial-balanced {
        max-width: min(100%, 25rem);
    }

    .admin-analytics-page .analytics-chart-area--bar-balanced {
        height: clamp(18rem, 28vw, 24rem);
    }

    .admin-analytics-page .analytics-chart-area--bar-balanced canvas {
        height: 100% !important;
    }

    .admin-analytics-page .analytics-chart-card canvas {
        display: block;
        width: 100% !important;
        max-width: 100%;
    }

    .admin-analytics-page {
        --arlo-green: #02681e;
        --arlo-green-dark: #00491e;
        --arlo-green-ink: #06391d;
        --arlo-gold: #ffc600;
        --arlo-gold-dark: #8f6917;
        --arlo-line: #dbe5dd;
        --arlo-line-strong: #b8c8bd;
        --arlo-ink: #111827;
        --arlo-muted: #627168;
        --arlo-soft: #f6faf7;
        --arlo-paper: #fffdf8;
        --arlo-blue: #02681e;
        --arlo-cyan: #0e7490;
        --arlo-gray: #64748b;
        color: var(--arlo-ink);
    }

    .admin-analytics-page .analytics-command-surface {
        border-radius: 0.75rem !important;
        border-color: #cbd8ce;
        background: #fff;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-export-trigger {
        border-radius: 0.45rem;
        box-shadow: none;
    }

    .admin-analytics-page .analytics-export-trigger__main,
    .admin-analytics-page .analytics-export-trigger__toggle {
        min-height: 2.55rem;
        color: var(--arlo-green-ink);
    }

    .admin-analytics-page .analytics-filter-panel {
        border-radius: 0.75rem !important;
        border: 1px solid #cbd8ce;
        background: #fff;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
    }

    .admin-analytics-page .analytics-filter-panel__eyebrow,
    .admin-analytics-page .analytics-filter-chip__label {
        color: var(--arlo-green-ink);
    }

    .admin-analytics-page .analytics-filter-option__ui {
        border-radius: 0.5rem;
        background: #fff;
    }

    .admin-analytics-page .analytics-filter-option__input:checked + .analytics-filter-option__ui {
        border-color: rgba(47, 111, 34, 0.36);
        background: #f1f8ef;
        box-shadow: inset 4px 0 0 var(--arlo-green-dark);
        transform: none;
    }

    .admin-analytics-page .analytics-filter-option__input:checked + .analytics-filter-option__ui .analytics-filter-option__box {
        border-color: var(--arlo-green-dark);
        background: var(--arlo-green-dark);
    }

    .admin-analytics-page .analytics-report-layout__main {
        padding: clamp(0.9rem, 1.8vw, 1.35rem);
        border-radius: 0.9rem;
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.12), transparent 30%),
            linear-gradient(135deg, #26352d, #18231d);
        overflow-x: auto;
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, 0.06),
            0 18px 40px rgba(7, 24, 14, 0.18);
    }

    .admin-analytics-page .arlo-report-sheet {
        box-sizing: border-box;
        width: 100%;
        max-width: 82rem;
        min-width: 0;
        margin-inline: auto;
        padding: clamp(1.15rem, 2vw, 1.75rem);
        border: 1px solid var(--arlo-line);
        border-radius: 0.55rem;
        background: var(--arlo-paper);
        color: var(--arlo-ink);
        font-family: var(--portal-font-family);
        box-shadow: 0 28px 58px rgba(0, 0, 0, 0.24);
    }

    .admin-analytics-page .arlo-report-masthead {
        display: grid;
        gap: 0.8rem;
    }

    .admin-analytics-page .arlo-report-masthead__brand {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 3.35rem;
        padding: 0 0.15rem 0.75rem;
        border-bottom: 1px solid var(--arlo-line-strong);
    }

    .admin-analytics-page .arlo-report-masthead__identity {
        display: flex;
        align-items: center;
        min-width: 0;
        gap: 0.75rem;
    }

    .admin-analytics-page .arlo-report-masthead__logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 2.95rem;
        height: 2.95rem;
        border: 1px solid rgba(20, 87, 38, 0.16);
        border-radius: 0.45rem;
        background: #fff;
        padding: 0.28rem;
    }

    .admin-analytics-page .arlo-report-masthead__logo img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .admin-analytics-page .arlo-report-masthead__university {
        margin: 0;
        color: var(--arlo-green-ink);
        font-size: clamp(1rem, 1.5vw, 1.22rem);
        font-weight: 800;
        letter-spacing: 0.02em;
        line-height: 1.08;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-masthead__address,
    .admin-analytics-page .arlo-report-masthead__office,
    .admin-analytics-page .arlo-report-masthead__office-note {
        margin: 0;
        color: var(--arlo-muted);
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .admin-analytics-page .arlo-report-masthead__office-block {
        max-width: 22rem;
        text-align: right;
    }

    .admin-analytics-page .arlo-report-masthead__office {
        color: var(--arlo-green-ink);
    }

    .admin-analytics-page .arlo-report-masthead__office-note {
        margin-top: 0.15rem;
        font-weight: 600;
    }

    .admin-analytics-page .arlo-report-titlebar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 3.35rem;
        padding: 0.8rem 1rem;
        border-radius: 0.45rem;
        background:
            linear-gradient(135deg, rgba(11, 59, 31, 0.98), rgba(20, 87, 38, 0.94)),
            radial-gradient(circle at right, rgba(200, 154, 45, 0.18), transparent 34%);
        color: #fff;
        overflow: hidden;
    }

    .admin-analytics-page .arlo-report-titlebar::after {
        content: "";
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        height: 0.28rem;
        background: linear-gradient(90deg, var(--arlo-gold), rgba(244, 230, 189, 0.75));
    }

    .admin-analytics-page .arlo-report-titlebar h1 {
        position: relative;
        z-index: 1;
        margin: 0;
        font-size: clamp(1.05rem, 1.6vw, 1.45rem);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: 0.01em;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-titlebar span {
        position: relative;
        z-index: 1;
        flex: 0 0 auto;
        color: rgba(255, 255, 255, 0.86);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .admin-analytics-page .arlo-report-scope {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--arlo-line);
    }

    .admin-analytics-page .arlo-report-scope__eyebrow {
        margin: 0;
        color: var(--arlo-gold-dark);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-scope h2 {
        margin: 0.28rem 0 0;
        color: var(--arlo-ink);
        font-size: clamp(1.05rem, 1.55vw, 1.32rem);
        font-weight: 800;
        line-height: 1.18;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-scope__meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.6rem;
        min-width: min(100%, 34rem);
        margin: 0;
    }

    .admin-analytics-page .arlo-report-scope__meta-card {
        appearance: none;
        display: block;
        text-align: left;
        cursor: pointer;
        font: inherit;
        width: 100%;
        min-width: 0;
        padding: 0.65rem 0.7rem;
        border: 1px solid var(--arlo-line);
        border-radius: 0.45rem;
        background: #fff;
        text-decoration: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
    }

    .admin-analytics-page .arlo-report-scope__meta-card:hover,
    .admin-analytics-page .arlo-report-scope__meta-card:focus-visible {
        border-color: rgba(20, 87, 38, 0.26);
        background: #f8fbf9;
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
        outline: none;
    }

    .admin-analytics-page .arlo-report-scope__meta-label {
        display: block;
        color: var(--arlo-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        line-height: 1.1;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-scope__meta-value {
        display: block;
        margin-top: 0.18rem;
        color: var(--arlo-ink);
        font-size: 0.74rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .admin-analytics-page .arlo-report-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
        gap: 0.8rem;
        margin-top: 0.8rem;
        margin-bottom: 1.25rem;
    }

    .admin-analytics-page .arlo-report-flow-note {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.9rem;
        padding: 0.7rem 0.82rem;
        border: 1px solid var(--arlo-line);
        border-radius: 0.5rem;
        background: linear-gradient(180deg, #ffffff, #f8fbf9);
        color: var(--arlo-muted);
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .admin-analytics-page .arlo-report-flow-note span {
        color: var(--arlo-ink);
    }

    .admin-analytics-page .arlo-report-flow-note i {
        color: var(--arlo-green);
        font-size: 1rem;
    }

    .admin-analytics-page .arlo-report-kpi {
        position: relative;
        min-width: 0;
        min-height: 5.15rem;
        overflow: hidden;
        border: 1px solid var(--tile-border);
        border-radius: 0.48rem;
        background:
            linear-gradient(180deg, #fff, var(--tile-soft)),
            radial-gradient(circle at top right, var(--tile-glow), transparent 34%);
    }

    .admin-analytics-page .arlo-report-kpi::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 0.28rem;
        background: var(--tile-strong);
    }

    .admin-analytics-page .arlo-report-kpi__body {
        display: block;
        min-height: 3.45rem;
        padding: 0.78rem 0.85rem 0.45rem 1rem;
    }

    .admin-analytics-page .arlo-report-kpi__body span {
        display: block;
        color: var(--arlo-muted);
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        line-height: 1.25;
        text-transform: uppercase;
        overflow-wrap: anywhere;
    }

    .admin-analytics-page .arlo-report-kpi__body .arlo-report-kpi__stage {
        width: fit-content;
        margin-bottom: 0.35rem;
        padding: 0.18rem 0.42rem;
        border: 1px solid color-mix(in srgb, var(--tile-strong) 24%, transparent);
        border-radius: 999px;
        background: color-mix(in srgb, var(--tile-strong) 8%, #fff);
        color: var(--tile-strong);
        font-size: 0.52rem;
        line-height: 1;
        letter-spacing: 0.06em;
    }

    .admin-analytics-page .arlo-report-kpi__body strong {
        display: block;
        margin-top: 0.38rem;
        color: var(--arlo-ink);
        font-size: 1.72rem;
        font-weight: 800;
        line-height: 1;
        overflow-wrap: anywhere;
    }

    .admin-analytics-page .arlo-report-kpi__footer {
        display: flex;
        align-items: center;
        gap: 0.28rem;
        min-height: 1.35rem;
        margin: 0;
        padding: 0.28rem 0.85rem 0.36rem 1rem;
        border-top: 1px solid rgba(15, 23, 42, 0.07);
        color: var(--arlo-muted);
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .admin-analytics-page .arlo-report-kpi__footer span {
        color: var(--tile-strong);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .admin-analytics-page .arlo-report-kpi__scope-link {
        appearance: none;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--tile-strong);
        cursor: pointer;
        font: inherit;
        font-weight: 800;
        line-height: inherit;
        text-align: left;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 2px;
    }

    .admin-analytics-page .arlo-report-kpi__scope-link:hover,
    .admin-analytics-page .arlo-report-kpi__scope-link:focus-visible {
        color: var(--arlo-green-ink);
        outline: none;
    }

    .admin-analytics-page .arlo-report-kpi--yellow {
        --tile-soft: #fffbeb;
        --tile-glow: rgba(200, 154, 45, 0.12);
        --tile-strong: #b7791f;
        --tile-border: #f2dfae;
    }

    .admin-analytics-page .arlo-report-kpi--green {
        --tile-soft: #f1f8f2;
        --tile-glow: rgba(20, 87, 38, 0.12);
        --tile-strong: var(--arlo-green);
        --tile-border: #cfe0d2;
    }

    .admin-analytics-page .arlo-report-kpi--orange {
        --tile-soft: #fff7ed;
        --tile-glow: rgba(234, 88, 12, 0.1);
        --tile-strong: #c2410c;
        --tile-border: #fed7aa;
    }

    .admin-analytics-page .arlo-report-kpi--blue {
        --tile-soft: #eff6ff;
        --tile-glow: rgba(37, 99, 235, 0.1);
        --tile-strong: var(--arlo-blue);
        --tile-border: #bfd4f8;
    }

    .admin-analytics-page .arlo-report-kpi--cyan {
        --tile-soft: #ecfeff;
        --tile-glow: rgba(14, 116, 144, 0.1);
        --tile-strong: var(--arlo-cyan);
        --tile-border: #b7dde4;
    }

    .admin-analytics-page .arlo-report-kpi--gray {
        --tile-soft: #f8fafc;
        --tile-glow: rgba(100, 116, 139, 0.1);
        --tile-strong: var(--arlo-gray);
        --tile-border: #d5dce5;
    }

    .admin-analytics-page .arlo-report-insights {
        margin: -0.2rem 0 1.25rem;
        padding: 0.9rem;
        border: 1px solid var(--arlo-line);
        border-radius: 0.5rem;
        background: linear-gradient(180deg, #ffffff, #f8fbf9);
        break-inside: avoid;
    }

    .admin-analytics-page .arlo-report-insights__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.72rem;
        border-bottom: 1px solid var(--arlo-line);
    }

    .admin-analytics-page .arlo-report-insights__eyebrow {
        margin: 0;
        color: var(--arlo-gold-dark);
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        line-height: 1.1;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-insights__header h3 {
        margin: 0.18rem 0 0;
        color: var(--arlo-ink);
        font-size: 0.95rem;
        font-weight: 800;
        line-height: 1.15;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-insights__scope-link {
        appearance: none;
        flex: 0 0 auto;
        min-height: 2rem;
        padding: 0.42rem 0.68rem;
        border: 1px solid rgba(20, 87, 38, 0.22);
        border-radius: 0.4rem;
        background: #fff;
        color: var(--arlo-green-ink);
        cursor: pointer;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-insights__scope-link:hover,
    .admin-analytics-page .arlo-report-insights__scope-link:focus-visible {
        border-color: rgba(20, 87, 38, 0.36);
        background: #f1f8f2;
        outline: none;
    }

    .admin-analytics-page .arlo-report-insights__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(12.5rem, 1fr));
        gap: 0.72rem;
    }

    .admin-analytics-page .arlo-report-insight-card {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        min-width: 0;
        padding: 0.75rem;
        border: 1px solid #d8e4dc;
        border-radius: 0.45rem;
        background: #fff;
    }

    .admin-analytics-page .arlo-report-insight-card__icon {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 2.05rem;
        height: 2.05rem;
        border-radius: 0.42rem;
        background: #f1f8f2;
        color: var(--arlo-green);
        font-size: 1.05rem;
    }

    .admin-analytics-page .arlo-report-insight-card div {
        min-width: 0;
    }

    .admin-analytics-page .arlo-report-insight-card__label {
        margin: 0;
        color: var(--arlo-muted);
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-insight-card strong {
        display: block;
        margin-top: 0.26rem;
        color: var(--arlo-ink);
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.08;
        overflow-wrap: anywhere;
    }

    .admin-analytics-page .arlo-report-insight-card p:last-child {
        margin: 0.28rem 0 0;
        color: var(--arlo-muted);
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.35;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-grid {
        gap: 1rem;
        margin-top: 1rem;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card {
        border: 1px solid var(--arlo-line) !important;
        border-radius: 0.5rem !important;
        background: #fff !important;
        box-shadow: none !important;
        padding: 0.9rem !important;
        break-inside: avoid;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card__header {
        align-items: flex-start;
        margin-bottom: 0.7rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--arlo-line);
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card__heading h3 {
        color: var(--arlo-ink) !important;
        font-size: clamp(0.9rem, 1.2vw, 1.05rem);
        font-weight: 800;
        line-height: 1.18;
        letter-spacing: 0.01em;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card__heading p {
        margin-top: 0.28rem !important;
        color: var(--arlo-muted) !important;
        font-size: 0.73rem !important;
        font-weight: 600;
        line-height: 1.35;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card__actions {
        gap: 0.35rem;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-card__actions button {
        min-height: 1.75rem;
        border-radius: 0.35rem !important;
        border-color: var(--arlo-line-strong) !important;
        color: var(--arlo-green-ink);
        background: #fff;
        font-size: 0.66rem;
        font-weight: 800;
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-area {
        height: clamp(15rem, 23vw, 20rem);
        padding: 0.4rem;
        border: 1px solid #edf2ee;
        border-radius: 0.45rem;
        background: linear-gradient(180deg, #fff, #fbfdfb);
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-area--radial {
        height: clamp(17rem, 26vw, 23rem);
    }

    .admin-analytics-page .arlo-report-sheet .analytics-chart-area--bar-balanced {
        height: clamp(16rem, 25vw, 22rem);
    }

    .admin-analytics-page .arlo-report-sheet .empty-state-card {
        border-radius: 0.5rem;
        border-style: dashed;
        background: linear-gradient(180deg, #fff, #f8faf9);
    }

    .admin-analytics-page .arlo-report-summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(14rem, 0.85fr) minmax(14rem, 0.85fr);
        gap: 1rem;
        margin-top: 1rem;
        break-inside: avoid;
    }

    .admin-analytics-page .arlo-report-panel {
        min-width: 0;
        padding: 0.95rem;
        border: 1px solid var(--arlo-line);
        border-radius: 0.5rem;
        background: #fff;
    }

    .admin-analytics-page .arlo-report-panel h3 {
        margin: 0 0 0.85rem;
        color: var(--arlo-ink);
        font-size: clamp(0.88rem, 1.1vw, 1rem);
        font-weight: 800;
        letter-spacing: 0.02em;
        line-height: 1.15;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-college-tiles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(9.25rem, 1fr));
        gap: 0.65rem;
    }

    .admin-analytics-page .arlo-college-tile {
        --tile-soft: #f1f8f2;
        --tile-strong: var(--arlo-green);
        --tile-border: #cfe0d2;
        position: relative;
        min-height: 4.45rem;
        border: 1px solid var(--tile-border);
        border-left: 0.28rem solid var(--tile-strong);
        border-radius: 0.45rem;
        background: linear-gradient(180deg, #fff, var(--tile-soft));
        overflow: hidden;
    }

    .admin-analytics-page .arlo-college-tile__percent {
        position: absolute;
        top: 0.45rem;
        right: 0.5rem;
        color: var(--tile-strong);
        font-size: 0.62rem;
        font-weight: 800;
    }

    .admin-analytics-page .arlo-college-tile__body {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: end;
        gap: 0.45rem;
        min-height: 3.05rem;
        padding: 1rem 0.55rem 0.35rem;
    }

    .admin-analytics-page .arlo-college-tile__body p {
        margin: 0;
        color: var(--arlo-ink);
        font-size: 0.6rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        line-height: 1.15;
        text-transform: uppercase;
    }

    .admin-analytics-page .arlo-college-tile__body strong {
        color: var(--arlo-ink);
        font-size: clamp(1.1rem, 1.7vw, 1.45rem);
        font-weight: 800;
        line-height: 1;
    }

    .admin-analytics-page .arlo-college-tile__footer {
        display: flex;
        align-items: center;
        gap: 0.22rem;
        min-height: 1.05rem;
        margin: 0;
        padding: 0.2rem 0.55rem 0.28rem;
        border-top: 1px solid rgba(15, 23, 42, 0.07);
        color: var(--arlo-muted);
        font-size: 0.58rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .admin-analytics-page .arlo-college-tile__footer span {
        color: var(--tile-strong);
        font-weight: 800;
    }

    .admin-analytics-page .arlo-college-tile--green {
        --tile-soft: #f1f8f2;
        --tile-strong: var(--arlo-green);
        --tile-border: #cfe0d2;
    }

    .admin-analytics-page .arlo-college-tile--red {
        --tile-soft: #fff5f5;
        --tile-strong: #b42318;
        --tile-border: #f0c4bf;
    }

    .admin-analytics-page .arlo-college-tile--yellow {
        --tile-soft: #fffbeb;
        --tile-strong: #b7791f;
        --tile-border: #f2dfae;
    }

    .admin-analytics-page .arlo-college-tile--blue {
        --tile-soft: #eff6ff;
        --tile-strong: var(--arlo-blue);
        --tile-border: #bfd4f8;
    }

    .admin-analytics-page .arlo-college-tile--orange {
        --tile-soft: #fff7ed;
        --tile-strong: #c2410c;
        --tile-border: #f3c2a2;
    }

    .admin-analytics-page .arlo-college-tile--amber {
        --tile-soft: #fffbeb;
        --tile-strong: #b45309;
        --tile-border: #f2d38b;
    }

    .admin-analytics-page .arlo-college-tile--rose {
        --tile-soft: #fff1f2;
        --tile-strong: #be123c;
        --tile-border: #f3b7c2;
    }

    .admin-analytics-page .arlo-college-tile--cyan {
        --tile-soft: #ecfeff;
        --tile-strong: var(--arlo-cyan);
        --tile-border: #b7dde4;
    }

    .admin-analytics-page .arlo-college-tile--violet {
        --tile-soft: #f5f3ff;
        --tile-strong: #6d28d9;
        --tile-border: #d6c8fb;
    }

    .admin-analytics-page .arlo-college-tile--teal {
        --tile-soft: #f0fdfa;
        --tile-strong: #0f766e;
        --tile-border: #b9e4dc;
    }

    .admin-analytics-page .arlo-report-signatures {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
        margin-top: 1.35rem;
        padding-inline: 0.5rem;
        color: var(--arlo-muted);
        font-size: 0.72rem;
        font-weight: 700;
    }

    .admin-analytics-page .arlo-report-signatures span::before {
        content: "";
        display: block;
        height: 1px;
        margin-bottom: 0.45rem;
        background: var(--arlo-line-strong);
    }

    @keyframes analyticsFilterJump {
        0% {
            box-shadow:
                inset 0 0 0 1px rgba(200, 154, 45, 0),
                0 0 0 0 rgba(200, 154, 45, 0);
        }

        35% {
            box-shadow:
                inset 0 0 0 1px rgba(200, 154, 45, 0.42),
                0 0 0 6px rgba(200, 154, 45, 0.12),
                0 16px 30px rgba(7, 24, 14, 0.12);
        }

        100% {
            box-shadow:
                inset 0 0 0 1px rgba(200, 154, 45, 0.28),
                0 14px 28px rgba(7, 24, 14, 0.1);
        }
    }

    @media (max-width: 1100px) {
        .admin-analytics-page .arlo-report-sheet {
            min-width: 0;
        }

        .admin-analytics-page .arlo-report-scope {
            align-items: stretch;
            flex-direction: column;
        }

        .admin-analytics-page .arlo-report-scope__meta {
            min-width: 0;
        }

        .admin-analytics-page .arlo-report-kpis {
            grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
        }

        .admin-analytics-page .arlo-report-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .admin-analytics-page .analytics-report-layout__main {
            padding: 0.55rem;
        }

        .admin-analytics-page .arlo-report-sheet {
            padding: 0.75rem;
        }

        .admin-analytics-page .arlo-report-masthead__brand,
        .admin-analytics-page .arlo-report-titlebar {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-analytics-page .arlo-report-masthead__office-block {
            max-width: none;
            text-align: left;
        }

        .admin-analytics-page .arlo-report-titlebar {
            min-height: 4rem;
        }

        .admin-analytics-page .arlo-report-titlebar span {
            white-space: normal;
        }

        .admin-analytics-page .arlo-report-scope__meta,
        .admin-analytics-page .arlo-report-kpis,
        .admin-analytics-page .arlo-report-insights__grid,
        .admin-analytics-page .arlo-report-signatures {
            grid-template-columns: 1fr;
        }

        .admin-analytics-page .arlo-report-kpi__body strong {
            font-size: 1.55rem;
        }

        .admin-analytics-page .arlo-report-insights__header {
            align-items: stretch;
            flex-direction: column;
        }

        .admin-analytics-page .arlo-report-insights__scope-link {
            width: 100%;
        }

        .admin-analytics-page .arlo-report-signatures {
            padding-inline: 0;
        }
    }

    @media print {
        .admin-analytics-page .analytics-command-surface,
        .admin-analytics-page .analytics-filter-panel,
        .admin-analytics-page .analytics-chart-card__actions {
            display: none !important;
        }

        .admin-analytics-page .analytics-report-layout {
            display: block;
        }

        .admin-analytics-page .analytics-report-layout__main {
            padding: 0;
            background: #fff;
            box-shadow: none;
            overflow: visible;
        }

        .admin-analytics-page .arlo-report-sheet {
            width: 100%;
            min-width: 0;
            padding: 0;
            box-shadow: none;
        }
    }

    @media (max-width: 767px) {
        .admin-analytics-page .analytics-export-overview {
            padding: 0.85rem;
        }

        .admin-analytics-page .analytics-export-overview__dialog {
            border-radius: 1.25rem;
            max-height: calc(100dvh - 1rem);
        }

        .admin-analytics-page .analytics-export-overview__header,
        .admin-analytics-page .analytics-export-overview__body,
        .admin-analytics-page .analytics-export-overview__actions {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .admin-analytics-page .analytics-export-overview__actions > * {
            width: 100%;
        }

        .admin-analytics-page .analytics-export-overview__selection-toolbar {
            align-items: stretch;
        }

        .admin-analytics-page .analytics-export-overview__selection-actions {
            width: 100%;
        }

        .admin-analytics-page .analytics-export-overview__selection-button {
            flex: 1 1 0;
        }

        .admin-analytics-page .analytics-command-surface {
            padding: 0.95rem;
        }

        .admin-analytics-page .analytics-command-surface__actions {
            width: 100%;
        }

        .admin-analytics-page .analytics-export-trigger {
            width: 100%;
        }

        .admin-analytics-page .analytics-export-trigger__main {
            flex: 1 1 auto;
        }

        .admin-analytics-page .analytics-filter-panel {
            padding: 1.05rem;
        }

        .admin-analytics-page .analytics-filter-panel__title {
            gap: 0.7rem;
        }

        .admin-analytics-page .analytics-filter-panel__glyph {
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.75rem;
        }

        .admin-analytics-page .analytics-filter-actions__buttons,
        .admin-analytics-page .analytics-filter-reset {
            width: 100%;
        }

        .admin-analytics-page .analytics-filter-panel__summary,
        .admin-analytics-page .analytics-filter-form,
        .admin-analytics-page .analytics-filter-actions {
            margin-top: 1rem;
        }

        .admin-analytics-page .analytics-filter-group {
            padding-top: 1.05rem;
            padding-bottom: 1.05rem;
        }

        .admin-analytics-page .analytics-chart-card__actions {
            width: 100%;
            justify-content: flex-start;
        }

    }
</style>

<div class="admin-analytics-page space-y-6">
    <section class="analytics-command-surface page-section rounded-[1.75rem]">
        <div class="analytics-command-surface__actions">
            <div class="relative">
                <div class="analytics-export-trigger">
                    <button id="analyticsExportBtn" type="button" onclick="toggleExportMenu(event)" class="analytics-export-trigger__main">
                        <i class='bx bx-export text-base'></i>
                        Export
                    </button>
                    <button type="button" onclick="toggleExportMenu(event)" class="analytics-export-trigger__toggle" aria-label="Open export options">
                        <i class='bx bx-chevron-down text-lg'></i>
                    </button>
                </div>
                <div id="analyticsExportMenu" class="analytics-export-menu hidden absolute right-0 mt-2 bg-white z-20">
                    <div class="analytics-export-menu__header">
                        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Export Format</p>
                    </div>
                    <button type="button" data-analytics-export-option="pdf" onclick="openAnalyticsExportOverview('pdf')" class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                <i class='bx bxs-file-pdf'></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Export as PDF</p>
                                <p class="text-xs text-gray-600">Formatted report.</p>
                            </div>
                        </div>
                    </button>
                    <button type="button" data-analytics-export-option="excel" onclick="openAnalyticsExportOverview('excel')" class="w-full text-left px-4 py-3 hover:bg-emerald-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <i class='bx bxs-spreadsheet'></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Export as Excel</p>
                                <p class="text-xs text-gray-600">Workbook report.</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div id="analyticsExportOverviewPortal" class="admin-analytics-page">
        <div id="analyticsExportOverviewModal" class="analytics-export-overview hidden" role="dialog" aria-modal="true" aria-labelledby="analyticsExportOverviewTitle">
            <button type="button" class="analytics-export-overview__backdrop" onclick="closeAnalyticsExportOverview()" aria-label="Close export overview"></button>
            <div class="analytics-export-overview__dialog">
                <div class="analytics-export-overview__header">
                    <div>
                        <p class="analytics-export-overview__eyebrow">Export Overview</p>
                        <h2 id="analyticsExportOverviewTitle" class="analytics-export-overview__title">Confirm export</h2>
                        <p id="analyticsExportOverviewDescription" class="analytics-export-overview__description"></p>
                    </div>
                    <button type="button" class="analytics-export-overview__close" data-analytics-export-close onclick="closeAnalyticsExportOverview()" aria-label="Close export overview">
                        <i class='bx bx-x text-xl'></i>
                    </button>
                </div>

                <div class="analytics-export-overview__body">
                    <div class="analytics-export-overview__meta">
                        <span class="analytics-export-overview__chip">
                            <i class='bx bx-file'></i>
                            <span id="analyticsExportOverviewFormat">PDF</span>
                        </span>
                        <span class="analytics-export-overview__chip">
                            <i class='bx bx-copy-alt'></i>
                            <span id="analyticsExportOverviewFileName">arlo-tracer-report.pdf</span>
                        </span>
                        <span class="analytics-export-overview__chip">
                            <i class='bx bx-bar-chart-alt-2'></i>
                            <span id="analyticsExportOverviewChartCount">0 charts</span>
                        </span>
                    </div>

                    <div class="analytics-export-overview__grid md:grid-cols-2">
                        <section class="analytics-export-overview__panel">
                            <h3 class="analytics-export-overview__panel-title">Current Scope</h3>
                            <p class="analytics-export-overview__panel-copy">These filters define what will be included in the exported report.</p>
                            <div id="analyticsExportOverviewScope" class="analytics-export-overview__list"></div>
                        </section>

                        <section class="analytics-export-overview__panel">
                            <h3 class="analytics-export-overview__panel-title">Selected Charts</h3>
                            <p class="analytics-export-overview__panel-copy">Use the graph cards below as the main selector. This summary updates live so the admin can confirm faster.</p>
                            <div class="analytics-export-overview__selection-toolbar">
                                <div class="analytics-export-overview__selection-actions">
                                    <button type="button" class="analytics-export-overview__selection-button" onclick="selectAllAnalyticsExportCharts()">
                                        <i class='bx bx-select-multiple'></i>
                                        Select all
                                    </button>
                                    <button type="button" class="analytics-export-overview__selection-button" onclick="clearAnalyticsExportCharts()">
                                        <i class='bx bx-minus-circle'></i>
                                        Clear all
                                    </button>
                                </div>
                                <p id="analyticsExportOverviewSelectionHint" class="analytics-export-overview__selection-hint"></p>
                            </div>
                            <div id="analyticsExportOverviewCharts" class="analytics-export-overview__choice-list"></div>
                        </section>
                    </div>

                    <section class="analytics-export-overview__panel mt-4">
                        <h3 class="analytics-export-overview__panel-title">Detailed CSV Reports</h3>
                        <p class="analytics-export-overview__panel-copy">Download uniform name lists for the current scope. Each CSV includes tracer numbers, alumni names, course, year, employment status, company, address, occupation, and a names-included summary below the records.</p>
                        <div class="analytics-export-overview__selection-actions mt-4">
                            <button type="button" class="analytics-export-overview__selection-button" onclick="exportAnalyticsCsvReport('employability')">
                                <i class='bx bx-table'></i>
                                Employability CSV
                            </button>
                            <button type="button" class="analytics-export-overview__selection-button" onclick="exportAnalyticsCsvReport('satisfaction')">
                                <i class='bx bx-comment-detail'></i>
                                Satisfaction CSV
                            </button>
                        </div>
                    </section>

                    <section class="analytics-export-overview__panel mt-4">
                        <h3 class="analytics-export-overview__panel-title">What This Export Includes</h3>
                        <p class="analytics-export-overview__panel-copy">A quick summary of the sections that will be generated for this format.</p>
                        <div id="analyticsExportOverviewDeliverables" class="analytics-export-overview__list"></div>
                    </section>

                    <section class="analytics-export-overview__panel mt-4">
                        <h3 id="analyticsExportOverviewPreviewTitle" class="analytics-export-overview__panel-title">Chart Preview Samples</h3>
                        <p id="analyticsExportOverviewPreviewDescription" class="analytics-export-overview__panel-copy">Click any graph card to include or exclude it before confirming the export.</p>
                        <div id="analyticsExportOverviewPreviewGrid" class="analytics-export-overview__preview-grid"></div>
                    </section>

                    <section class="analytics-export-overview__panel mt-4">
                        <h3 class="analytics-export-overview__panel-title">Overview Notes</h3>
                        <p class="analytics-export-overview__panel-copy">This is the summary narrative that frames the export before chart-level details.</p>
                        <div id="analyticsExportOverviewNarrative" class="analytics-export-overview__list"></div>
                    </section>

                    <section class="analytics-export-overview__panel mt-4">
                        <h3 class="analytics-export-overview__panel-title">Key Metrics Snapshot</h3>
                        <p class="analytics-export-overview__panel-copy">These headline values will be part of the exported report summary.</p>
                        <div id="analyticsExportOverviewMetrics" class="analytics-export-overview__metrics"></div>
                    </section>
                </div>

                <div class="analytics-export-overview__actions">
                    <button type="button" class="analytics-export-overview__secondary" data-analytics-export-cancel onclick="closeAnalyticsExportOverview()">
                        Cancel
                    </button>
                    <button id="analyticsExportConfirmBtn" type="button" class="analytics-export-overview__primary" data-analytics-export-confirm onclick="confirmAnalyticsExport()">
                        <i class='bx bxs-file-export text-lg'></i>
                        Confirm Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div data-admin-analytics-content>
        @include('admin.partials.analytics-content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
(() => {
    const initializeAdminAnalyticsPage = () => {
    if (!window.Chart || !document.querySelector('[data-admin-analytics-content]')) {
        return;
    }

    const cleanupController = new AbortController();
    const analyticsEndpoint = @json(route('admin.analytics'));
    const analyticsCsvExportEndpoint = @json(route('admin.analytics.export', ['report' => '__REPORT__']));
    const chartOrder = [
        'employmentTrendsChart',
        'tracedGraduatesChart',
        'industryChart',
        'employabilityChart',
        'globalDistributionChart',
        'userGrowthChart',
        'jobApplicationsChart',
        'collegeDistributionChart',
        'employmentStatusChart',
        'feedbacksChart',
        'curriculumRelevanceChart',
        'firstJobTimeChart',
        'presentEmploymentStatusChart',
        'teachingFeedbackChart',
        'facilitiesSatisfactionChart',
        'overallExperienceChart',
        'reasonsChart',
        'graduationYearChart',
        'industryGrowthChart'
    ];
    const defaultChartOrder = [
        'employmentTrendsChart',
        'tracedGraduatesChart',
        'industryChart',
        'employabilityChart',
        'globalDistributionChart',
        'reasonsChart',
        'collegeDistributionChart',
        'employmentStatusChart',
        'graduationYearChart'
    ];
    const chartTitles = {
        employmentTrendsChart: 'Employment Trends',
        tracedGraduatesChart: 'Traced Graduates',
        industryChart: 'Industry Distribution',
        employabilityChart: 'Employability Rate',
        globalDistributionChart: 'Global Distribution',
        userGrowthChart: 'User Growth',
        jobApplicationsChart: 'Job Opportunity Submissions',
        collegeDistributionChart: 'Alumni by College',
        employmentStatusChart: 'Employment Status',
        feedbacksChart: 'Feedback Categories',
        curriculumRelevanceChart: 'Curriculum Relevance to First Job',
        firstJobTimeChart: 'Time to First Job',
        presentEmploymentStatusChart: 'Present Employment Status',
        teachingFeedbackChart: 'Teaching Feedback',
        facilitiesSatisfactionChart: 'University Facilities',
        overallExperienceChart: 'Overall Experience',
        reasonsChart: 'Reasons for Unemployment',
        graduationYearChart: 'Graduation Year Trends',
        industryGrowthChart: 'Industry Growth'
    };
    const exportFormatConfig = {
        pdf: {
            label: 'OARL PDF report',
            shortLabel: 'PDF',
            extension: 'pdf',
            icon: 'bxs-file-pdf',
            description: 'A formatted OARL tracer report with the current scope, overview notes, chart images, and generated insights.',
            confirmLabel: 'Confirm PDF Export',
            busyLabel: 'Generating PDF...'
        },
        excel: {
            label: 'OARL Excel workbook',
            shortLabel: 'Excel',
            extension: 'xlsx',
            icon: 'bxs-spreadsheet',
            description: 'A workbook export with an OARL summary sheet, chart insights sheet, and one worksheet for each available chart.',
            confirmLabel: 'Confirm Excel Export',
            busyLabel: 'Exporting Excel...'
        },
        csv: {
            label: 'OARL CSV report',
            shortLabel: 'CSV',
            extension: 'csv',
            icon: 'bxs-file',
            description: 'A uniform CSV export where each selected chart uses the same layout as the individual graph CSV buttons.',
            confirmLabel: 'Confirm CSV Export',
            busyLabel: 'Exporting CSV...'
        }
    };
    const analyticsPalette = {
        coral: { solid: 'rgb(0, 73, 30)', soft: 'rgba(0, 73, 30, 0.12)' },
        teal: { solid: 'rgb(2, 104, 30)', soft: 'rgba(2, 104, 30, 0.14)' },
        amber: { solid: 'rgb(255, 198, 0)', soft: 'rgba(255, 198, 0, 0.2)' },
        indigo: { solid: 'rgb(0, 73, 30)', soft: 'rgba(0, 73, 30, 0.16)' },
        rose: { solid: 'rgb(2, 104, 30)', soft: 'rgba(2, 104, 30, 0.12)' },
        cyan: { solid: 'rgb(255, 198, 0)', soft: 'rgba(255, 198, 0, 0.18)' },
        lime: { solid: 'rgb(0, 73, 30)', soft: 'rgba(0, 73, 30, 0.1)' },
        violet: { solid: 'rgb(2, 104, 30)', soft: 'rgba(2, 104, 30, 0.16)' }
    };
    const analyticsSeriesPalette = [
        analyticsPalette.coral,
        analyticsPalette.teal,
        analyticsPalette.amber,
        analyticsPalette.indigo,
        analyticsPalette.rose,
        analyticsPalette.cyan,
        analyticsPalette.lime,
        analyticsPalette.violet
    ];
    const reportFontFamily = getComputedStyle(document.documentElement)
        .getPropertyValue('--portal-font-family')
        .trim() || '"Plus Jakarta Sans", system-ui, sans-serif';
    const arloValueLabelsPlugin = {
        id: 'arloValueLabels',
        afterDatasetsDraw(chart, args, options) {
            if (!options || options.display !== true) {
                return;
            }

            const { ctx } = chart;
            const chartType = chart.config.type;
            const hideZero = options.hideZero !== false;
            const minArcPercent = Number(options.minArcPercent ?? 0);

            ctx.save();
            ctx.font = `${options.fontWeight || 700} ${options.fontSize || 10}px ${reportFontFamily}`;
            ctx.fillStyle = options.color || '#00491e';

            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);

                if (!meta || meta.hidden) {
                    return;
                }

                const values = (dataset.data || []).map((value) => Number(value) || 0);
                const total = values.reduce((sum, value) => sum + value, 0);

                meta.data.forEach((element, index) => {
                    const value = values[index] || 0;

                    if (hideZero && value === 0) {
                        return;
                    }

                    let label = Number.isInteger(value)
                        ? value.toLocaleString()
                        : value.toLocaleString(undefined, { maximumFractionDigits: 1 });

                    if (options.format === 'valuePercent' && total > 0) {
                        const percent = (value / total) * 100;

                        if (percent < minArcPercent) {
                            return;
                        }

                        const percentLabel = percent >= 10 ? percent.toFixed(0) : percent.toFixed(1);
                        label = `${label} (${percentLabel}%)`;
                    }

                    const position = typeof element.tooltipPosition === 'function'
                        ? element.tooltipPosition()
                        : { x: element.x, y: element.y };

                    let x = position.x;
                    let y = position.y;
                    const defaultColor = options.color || '#00491e';

                    ctx.fillStyle = defaultColor;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    if (chartType === 'bar') {
                        const isHorizontalBar = chart.options?.indexAxis === 'y';

                        if (isHorizontalBar) {
                            const chartArea = chart.chartArea || { left: 0, right: chart.width };
                            const baseX = Number.isFinite(element.base) ? element.base : position.x;
                            const endX = Number.isFinite(element.x) ? element.x : position.x;
                            const isPositive = endX >= baseX;
                            const labelPadding = Number(options.padding ?? 8);
                            const labelWidth = ctx.measureText(label).width;
                            const availableWidth = Math.abs(endX - baseX);
                            const fitsInside = availableWidth >= labelWidth + (labelPadding * 2);

                            y = element.y;
                            ctx.textBaseline = 'middle';

                            if (fitsInside) {
                                x = isPositive ? endX - labelPadding : endX + labelPadding;
                                ctx.textAlign = isPositive ? 'right' : 'left';
                                ctx.fillStyle = options.insideColor || '#ffffff';
                            } else {
                                x = isPositive ? endX + labelPadding : endX - labelPadding;
                                ctx.textAlign = isPositive ? 'left' : 'right';
                                ctx.fillStyle = options.outsideColor || defaultColor;

                                if (isPositive && x + labelWidth > chartArea.right) {
                                    x = chartArea.right - 2;
                                    ctx.textAlign = 'right';
                                } else if (!isPositive && x - labelWidth < chartArea.left) {
                                    x = chartArea.left + 2;
                                    ctx.textAlign = 'left';
                                }
                            }
                        } else {
                            y = Math.min(element.y, element.base ?? element.y) - 4;
                        }
                    } else if (chartType === 'line') {
                        y -= 8;
                    } else if (chartType === 'doughnut' || chartType === 'pie') {
                        ctx.textBaseline = 'middle';
                    }

                    const outlineWidth = Number(options.outlineWidth ?? 0);

                    if (outlineWidth > 0) {
                        ctx.lineWidth = outlineWidth;
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = options.outlineColor || 'rgba(15, 23, 42, 0.72)';
                        ctx.strokeText(label, x, y);
                    }

                    ctx.fillText(label, x, y);
                });
            });

            ctx.restore();
        }
    };

    if (!window.__arloValueLabelsPluginRegistered) {
        Chart.register(arloValueLabelsPlugin);
        Chart.defaults.font.family = reportFontFamily;
        Chart.defaults.color = '#334155';
        window.__arloValueLabelsPluginRegistered = true;
    }

    const reportTooltip = {
        backgroundColor: 'rgba(17, 24, 39, 0.94)',
        titleFont: { family: reportFontFamily, size: 11, weight: '700' },
        bodyFont: { family: reportFontFamily, size: 10 },
        padding: 10
    };

    function reportLegend(position = 'bottom') {
        return {
            position,
            labels: {
                boxWidth: 9,
                boxHeight: 9,
                color: '#334155',
                usePointStyle: true,
                pointStyle: 'circle',
                font: {
                    family: reportFontFamily,
                    size: 10
                }
            }
        };
    }

    function reportPlugins({ legend = false, valueLabels = {} } = {}) {
        return {
            legend: legend ? reportLegend(typeof legend === 'string' ? legend : legend.position || 'bottom') : { display: false },
            tooltip: reportTooltip,
            arloValueLabels: {
                display: true,
                color: '#00491e',
                fontSize: 10,
                hideZero: true,
                ...valueLabels
            }
        };
    }

    function reportAxisTitle(text) {
        return {
            display: Boolean(text),
            text: text || '',
            color: '#334155',
            font: {
                family: reportFontFamily,
                size: 11,
                weight: '700'
            },
            padding: {
                top: 8,
                bottom: 4
            }
        };
    }

    function reportScales({ xTitle = 'Category', yTitle = 'Value' } = {}) {
        return {
            x: {
                grid: {
                    display: false
                },
                title: reportAxisTitle(xTitle),
                ticks: {
                    color: '#334155',
                    font: {
                        family: reportFontFamily,
                        size: 10
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(100, 116, 139, 0.16)',
                    drawBorder: false
                },
                title: reportAxisTitle(yTitle),
                ticks: {
                    color: '#334155',
                    font: {
                        family: reportFontFamily,
                        size: 10
                    }
                }
            }
        };
    }

    function reportLinearOptions(config = {}) {
        const scales = reportScales({
            xTitle: config.xTitle,
            yTitle: config.yTitle
        });

        Object.entries(config.scales || {}).forEach(([axis, axisConfig]) => {
            const baseScale = scales[axis] || {};

            scales[axis] = {
                ...baseScale,
                ...axisConfig,
                grid: {
                    ...(baseScale.grid || {}),
                    ...(axisConfig.grid || {}),
                },
                ticks: {
                    ...(baseScale.ticks || {}),
                    ...(axisConfig.ticks || {}),
                },
                title: {
                    ...(baseScale.title || {}),
                    ...(axisConfig.title || {}),
                },
            };
        });

        return {
            responsive: true,
            maintainAspectRatio: false,
            ...(config.indexAxis ? { indexAxis: config.indexAxis } : {}),
            ...(config.layout ? { layout: config.layout } : {}),
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: reportPlugins(config),
            scales
        };
    }

    function reportRadialOptions(config = {}) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            cutout: config.cutout || '52%',
            plugins: reportPlugins({
                legend: config.legend ?? 'bottom',
                valueLabels: {
                    format: 'valuePercent',
                    color: '#ffffff',
                    outlineColor: 'rgba(15, 23, 42, 0.72)',
                    outlineWidth: 3,
                    fontSize: 10,
                    fontWeight: 800,
                    minArcPercent: config.minArcPercent ?? 6
                }
            })
        };
    }

    let analyticsCharts = {};
    let analyticsState = {};
    let activeRequestController = null;
    let activeRequestToken = 0;
    let filterChangeTimeoutId = null;
    let pendingAnalyticsExportFormat = null;
    let analyticsExportInProgress = false;
    let selectedAnalyticsExportChartIds = new Set();
    let hasAnalyticsExportChartSelectionState = false;

    const analyticsContentContainer = document.querySelector('[data-admin-analytics-content]');
    const analyticsExportOverviewPortal = document.getElementById('analyticsExportOverviewPortal');

    function mountAnalyticsExportOverviewPortal() {
        if (!analyticsExportOverviewPortal || analyticsExportOverviewPortal.parentElement === document.body) {
            return;
        }

        document.body.appendChild(analyticsExportOverviewPortal);
    }

    mountAnalyticsExportOverviewPortal();

    function syncAnalyticsExportChartSelection(chartEntries, reset = false) {
        const availableIds = new Set(chartEntries.map((entry) => entry.id));

        if (reset || !hasAnalyticsExportChartSelectionState) {
            selectedAnalyticsExportChartIds = new Set(chartEntries.map((entry) => entry.id));
            hasAnalyticsExportChartSelectionState = true;
            return;
        }

        selectedAnalyticsExportChartIds = new Set(
            Array.from(selectedAnalyticsExportChartIds).filter((chartId) => availableIds.has(chartId))
        );
    }

    function getSelectedExportChartIds(chartEntries = getExportChartEntries()) {
        const availableIds = new Set(chartEntries.map((entry) => entry.id));

        return chartOrder.filter((chartId) => availableIds.has(chartId) && selectedAnalyticsExportChartIds.has(chartId));
    }

    function readAnalyticsState() {
        const stateElement = document.getElementById('adminAnalyticsState');

        if (!stateElement) {
            return {};
        }

        try {
            return JSON.parse(stateElement.textContent || '{}');
        } catch (error) {
            console.error('Failed to parse admin analytics state.', error);
            return {};
        }
    }

    function destroyAnalyticsCharts() {
        Object.values(analyticsCharts).forEach((chart) => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });

        analyticsCharts = {};
    }

    function buildLineDataset(series, index) {
        const palette = analyticsSeriesPalette[index % analyticsSeriesPalette.length];

        return {
            label: series.label,
            data: series.data,
            borderColor: palette.solid,
            backgroundColor: palette.soft,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 4,
            pointBackgroundColor: palette.solid,
            tension: 0.28
        };
    }

    function visibleChartCanvas(chartId) {
        const canvas = document.getElementById(chartId);

        if (!canvas || canvas.closest('.hidden, [hidden]')) {
            return null;
        }

        return canvas;
    }

    function initializeAnalyticsCharts() {
        if (!window.Chart || !analyticsContentContainer) {
            return;
        }

        destroyAnalyticsCharts();
        analyticsState = readAnalyticsState();

        const employmentTrendsCanvas = visibleChartCanvas('employmentTrendsChart');
        if (employmentTrendsCanvas) {
            analyticsCharts.employmentTrendsChart = new Chart(employmentTrendsCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: analyticsState.employmentTrends?.labels || [],
                    datasets: [{
                        label: 'Employed',
                        data: analyticsState.employmentTrends?.employed || [],
                        borderColor: analyticsPalette.coral.solid,
                        backgroundColor: analyticsPalette.coral.soft,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        pointBackgroundColor: analyticsPalette.coral.solid,
                        tension: 0.32
                    }, {
                        label: 'Self-Employed',
                        data: analyticsState.employmentTrends?.selfEmployed || [],
                        borderColor: analyticsPalette.amber.solid,
                        backgroundColor: analyticsPalette.amber.soft,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        pointBackgroundColor: analyticsPalette.amber.solid,
                        tension: 0.32
                    }, {
                        label: 'Unemployed',
                        data: analyticsState.employmentTrends?.unemployed || [],
                        borderColor: analyticsPalette.rose.solid,
                        backgroundColor: analyticsPalette.rose.soft,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        pointBackgroundColor: analyticsPalette.rose.solid,
                        tension: 0.32
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Months',
                    yTitle: 'Alumni',
                    legend: 'top',
                    valueLabels: {
                        display: false
                    }
                })
            });
        }

        const tracedGraduatesCanvas = visibleChartCanvas('tracedGraduatesChart');
        if (tracedGraduatesCanvas) {
            analyticsCharts.tracedGraduatesChart = new Chart(tracedGraduatesCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.tracedGraduates?.labels || [],
                    datasets: [{
                        label: 'Traced Graduates',
                        data: analyticsState.tracedGraduates?.data || [],
                        backgroundColor: analyticsPalette.teal.solid,
                        borderColor: analyticsPalette.teal.solid,
                        borderWidth: 1,
                        borderRadius: 2,
                        maxBarThickness: 58
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Graduation Year',
                    yTitle: 'Traced Alumni'
                })
            });
        }

        const industryCanvas = visibleChartCanvas('industryChart');
        if (industryCanvas) {
            analyticsCharts.industryChart = new Chart(industryCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.industryData?.labels || [],
                    datasets: [{
                        data: analyticsState.industryData?.data || [],
                        backgroundColor: analyticsSeriesPalette.map((color) => color.solid),
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const employabilityCanvas = visibleChartCanvas('employabilityChart');
        if (employabilityCanvas) {
            analyticsCharts.employabilityChart = new Chart(employabilityCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: analyticsState.employabilityData?.labels || [],
                    datasets: [{
                        label: 'Employability Rate (%)',
                        data: analyticsState.employabilityData?.data || [],
                        borderColor: analyticsPalette.indigo.solid,
                        backgroundColor: analyticsPalette.indigo.soft,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        pointBackgroundColor: analyticsPalette.indigo.solid,
                        tension: 0.32
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Graduation Year',
                    yTitle: 'Employability Rate (%)',
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    valueLabels: {
                        color: analyticsPalette.indigo.solid
                    }
                })
            });
        }

        const globalDistributionCanvas = visibleChartCanvas('globalDistributionChart');
        if (globalDistributionCanvas) {
            analyticsCharts.globalDistributionChart = new Chart(globalDistributionCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.globalData?.labels || [],
                    datasets: [{
                        label: 'Alumni Distribution',
                        data: analyticsState.globalData?.data || [],
                        backgroundColor: analyticsPalette.cyan.solid,
                        borderColor: analyticsPalette.cyan.solid,
                        borderWidth: 1,
                        borderRadius: 2,
                        maxBarThickness: 58
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Location',
                    yTitle: 'Alumni'
                })
            });
        }

        const reasonsCanvas = visibleChartCanvas('reasonsChart');
        if (reasonsCanvas) {
            analyticsCharts.reasonsChart = new Chart(reasonsCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.reasonsData?.labels || [],
                    datasets: [{
                        label: 'Number of Alumni',
                        data: analyticsState.reasonsData?.data || [],
                        backgroundColor: analyticsPalette.rose.solid,
                        borderColor: analyticsPalette.rose.solid,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 34
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Alumni',
                    yTitle: 'Reasons',
                    indexAxis: 'y',
                    scales: {
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    valueLabels: {
                        color: analyticsPalette.rose.solid,
                        insideColor: '#ffffff',
                        outsideColor: analyticsPalette.rose.solid,
                        fontSize: 11,
                        padding: 9
                    }
                })
            });
        }

        const userGrowthCanvas = visibleChartCanvas('userGrowthChart');
        if (userGrowthCanvas) {
            analyticsCharts.userGrowthChart = new Chart(userGrowthCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: analyticsState.userGrowth?.labels || [],
                    datasets: [{
                        label: 'New Users',
                        data: analyticsState.userGrowth?.data || [],
                        borderColor: analyticsPalette.coral.solid,
                        backgroundColor: analyticsPalette.coral.soft,
                        borderWidth: 2,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        pointBackgroundColor: analyticsPalette.coral.solid,
                        tension: 0.28
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Months',
                    yTitle: 'New Users',
                    valueLabels: {
                        color: analyticsPalette.coral.solid
                    }
                })
            });
        }

        const jobApplicationsCanvas = visibleChartCanvas('jobApplicationsChart');
        if (jobApplicationsCanvas) {
            analyticsCharts.jobApplicationsChart = new Chart(jobApplicationsCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.jobApplicationsTrend?.labels || [],
                    datasets: [{
                        label: 'Job submissions',
                        data: analyticsState.jobApplicationsTrend?.data || [],
                        backgroundColor: analyticsPalette.teal.solid,
                        borderColor: analyticsPalette.teal.solid,
                        borderWidth: 1,
                        borderRadius: 2,
                        maxBarThickness: 56
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Period',
                    yTitle: 'Submissions'
                })
            });
        }

        const collegeDistributionCanvas = visibleChartCanvas('collegeDistributionChart');
        if (collegeDistributionCanvas) {
            analyticsCharts.collegeDistributionChart = new Chart(collegeDistributionCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.collegeDistribution?.labels || [],
                    datasets: [{
                        data: analyticsState.collegeDistribution?.data || [],
                        backgroundColor: analyticsSeriesPalette.map((color) => color.solid),
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const employmentStatusCanvas = visibleChartCanvas('employmentStatusChart');
        if (employmentStatusCanvas) {
            analyticsCharts.employmentStatusChart = new Chart(employmentStatusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.employmentStatus?.labels || [],
                    datasets: [{
                        data: analyticsState.employmentStatus?.data || [],
                        backgroundColor: [
                            analyticsPalette.lime.solid,
                            analyticsPalette.rose.solid,
                            analyticsPalette.cyan.solid,
                            analyticsPalette.amber.solid
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const feedbacksCanvas = visibleChartCanvas('feedbacksChart');
        if (feedbacksCanvas) {
            analyticsCharts.feedbacksChart = new Chart(feedbacksCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.feedbacksData?.labels || [],
                    datasets: [{
                        data: analyticsState.feedbacksData?.data || [],
                        backgroundColor: analyticsSeriesPalette.map((color) => color.solid),
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const curriculumRelevanceCanvas = visibleChartCanvas('curriculumRelevanceChart');
        if (curriculumRelevanceCanvas) {
            analyticsCharts.curriculumRelevanceChart = new Chart(curriculumRelevanceCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.curriculumRelevanceData?.labels || [],
                    datasets: [{
                        data: analyticsState.curriculumRelevanceData?.data || [],
                        backgroundColor: [
                            analyticsPalette.teal.solid,
                            analyticsPalette.rose.solid,
                            analyticsPalette.amber.solid
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const firstJobTimeCanvas = visibleChartCanvas('firstJobTimeChart');
        if (firstJobTimeCanvas) {
            analyticsCharts.firstJobTimeChart = new Chart(firstJobTimeCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.firstJobTimeData?.labels || [],
                    datasets: [{
                        label: 'Alumni',
                        data: analyticsState.firstJobTimeData?.data || [],
                        backgroundColor: analyticsPalette.teal.solid,
                        borderColor: analyticsPalette.teal.solid,
                        borderWidth: 1,
                        borderRadius: 2,
                        maxBarThickness: 58
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Time to First Job',
                    yTitle: 'Alumni'
                })
            });
        }

        const presentEmploymentStatusCanvas = visibleChartCanvas('presentEmploymentStatusChart');
        if (presentEmploymentStatusCanvas) {
            analyticsCharts.presentEmploymentStatusChart = new Chart(presentEmploymentStatusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.presentEmploymentStatusData?.labels || [],
                    datasets: [{
                        data: analyticsState.presentEmploymentStatusData?.data || [],
                        backgroundColor: analyticsSeriesPalette.map((color) => color.solid),
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const teachingFeedbackCanvas = visibleChartCanvas('teachingFeedbackChart');
        if (teachingFeedbackCanvas) {
            analyticsCharts.teachingFeedbackChart = new Chart(teachingFeedbackCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.teachingFeedbackData?.labels || [],
                    datasets: [{
                        label: 'Average Rating',
                        data: analyticsState.teachingFeedbackData?.data || [],
                        backgroundColor: analyticsPalette.teal.solid,
                        borderColor: analyticsPalette.teal.solid,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 34
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Average Rating',
                    yTitle: 'Teaching Area',
                    indexAxis: 'y',
                    layout: {
                        padding: {
                            right: 8
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            min: 0,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    valueLabels: {
                        color: analyticsPalette.teal.solid,
                        insideColor: '#ffffff',
                        outsideColor: analyticsPalette.teal.solid,
                        fontSize: 11,
                        padding: 9
                    }
                })
            });
        }

        const facilitiesSatisfactionCanvas = visibleChartCanvas('facilitiesSatisfactionChart');
        if (facilitiesSatisfactionCanvas) {
            analyticsCharts.facilitiesSatisfactionChart = new Chart(facilitiesSatisfactionCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: analyticsState.facilitiesSatisfactionData?.labels || [],
                    datasets: [{
                        data: analyticsState.facilitiesSatisfactionData?.data || [],
                        backgroundColor: [
                            analyticsPalette.lime.solid,
                            analyticsPalette.amber.solid,
                            analyticsPalette.rose.solid,
                            analyticsPalette.indigo.solid
                        ],
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: reportRadialOptions()
            });
        }

        const overallExperienceCanvas = visibleChartCanvas('overallExperienceChart');
        if (overallExperienceCanvas) {
            analyticsCharts.overallExperienceChart = new Chart(overallExperienceCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.overallExperienceData?.labels || [],
                    datasets: [{
                        label: 'Average Rating',
                        data: analyticsState.overallExperienceData?.data || [],
                        backgroundColor: analyticsPalette.indigo.solid,
                        borderColor: analyticsPalette.indigo.solid,
                        borderWidth: 1,
                        borderRadius: 3,
                        maxBarThickness: 34
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Average Rating',
                    yTitle: 'Experience Area',
                    indexAxis: 'y',
                    layout: {
                        padding: {
                            right: 8
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            min: 0,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    valueLabels: {
                        color: analyticsPalette.indigo.solid,
                        insideColor: '#ffffff',
                        outsideColor: analyticsPalette.indigo.solid,
                        fontSize: 11,
                        padding: 9
                    }
                })
            });
        }

        const graduationYearCanvas = visibleChartCanvas('graduationYearChart');
        if (graduationYearCanvas) {
            analyticsCharts.graduationYearChart = new Chart(graduationYearCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: analyticsState.graduationYearTrends?.labels || [],
                    datasets: [{
                        label: 'Graduates',
                        data: analyticsState.graduationYearTrends?.data || [],
                        backgroundColor: analyticsPalette.teal.solid,
                        borderColor: analyticsPalette.teal.solid,
                        borderWidth: 1,
                        borderRadius: 2,
                        maxBarThickness: 58
                    }]
                },
                options: reportLinearOptions({
                    xTitle: 'Graduation Year',
                    yTitle: 'Graduates'
                })
            });
        }

        const industryGrowthCanvas = visibleChartCanvas('industryGrowthChart');
        if (industryGrowthCanvas) {
            analyticsCharts.industryGrowthChart = new Chart(industryGrowthCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: analyticsState.industryGrowth?.labels || [],
                    datasets: (analyticsState.industryGrowth?.datasets || []).map(buildLineDataset)
                },
                options: reportLinearOptions({
                    xTitle: 'Months',
                    yTitle: 'Alumni',
                    legend: 'top',
                    valueLabels: {
                        display: false
                    }
                })
            });
        }
    }

    function downloadTextFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function downloadBlobFile(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    window.exportChartAsPng = function (chartId, filenameBase) {
        const chart = analyticsCharts[chartId];
        if (!chart) {
            return;
        }

        const imageData = getHighResChartImage(chartId) || buildFallbackChartPng(chart);
        if (!imageData) {
            return;
        }

        const link = document.createElement('a');
        link.href = imageData;
        link.download = filenameBase + '.png';
        link.click();
    };

    window.exportChartAsPdf = async function (chartId, filenameBase) {
        const chart = analyticsCharts[chartId];
        if (!chart) {
            return;
        }

        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF exporter failed to load. Please refresh and try again.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const now = new Date();
        await waitForNextFrame();
        chart.update();

        const imageData = getHighResChartImage(chartId) || buildFallbackChartPng(chart);
        if (!imageData) {
            alert('Unable to generate the chart image for PDF export right now.');
            return;
        }

        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 14;
        const contentWidth = pageWidth - (margin * 2);
        const title = chartTitles[chartId] || chartId;
        const scopeLine = getExportScopeLine();
        let y = 12;

        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(6, 57, 29);
        pdf.setFontSize(11);
        pdf.text('Central Mindanao University', margin, y);
        pdf.setTextColor(98, 113, 104);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        pdf.text('Office of the Alumni Relations and Linkages (OARL)', pageWidth - margin, y, { align: 'right' });

        y += 7;
        pdf.setDrawColor(184, 200, 189);
        pdf.line(margin, y, pageWidth - margin, y);

        y += 8;
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(16);
        pdf.text(title, margin, y);

        y += 6;
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(98, 113, 104);
        pdf.setFontSize(8.5);
        pdf.text(`Generated ${now.toLocaleString()}`, margin, y);

        y += 5;
        pdf.setTextColor(51, 65, 85);
        y = addWrappedText(pdf, [`Scope: ${scopeLine}`], margin, y, contentWidth, 4.2);

        y += 3;
        const chartHeight = 102;
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(207, 224, 210);
        pdf.roundedRect(margin, y, contentWidth, chartHeight, 2, 2, 'FD');
        pdf.addImage(imageData, 'PNG', margin + 3, y + 3, contentWidth - 6, chartHeight - 6);

        y += chartHeight + 9;
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(12);
        pdf.text('Generated Insights', margin, y);

        y += 6;
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(9.5);
        pdf.setTextColor(51, 65, 85);
        const insights = buildChartNarrative(chartId, chart).map((line) => `- ${line}`);
        addWrappedText(pdf, insights, margin, y, contentWidth, 4.5);

        pdf.setDrawColor(226, 232, 240);
        pdf.line(margin, pageHeight - 12, pageWidth - margin, pageHeight - 12);
        pdf.setFontSize(7.5);
        pdf.setTextColor(100, 116, 139);
        pdf.text('OARL Tracer Analytics', margin, pageHeight - 7);
        pdf.text(title, pageWidth - margin, pageHeight - 7, { align: 'right' });

        pdf.save(`${filenameBase}.pdf`);
    };

    function escapeCsvValue(value) {
        return `"${String(value ?? '').replace(/"/g, '""')}"`;
    }

    function buildNamesSummaryCsvRows(summaryRows = []) {
        const rows = [
            [],
            ['Names Summary'],
            ['Total Names', summaryRows.length],
            [],
            [
                'Tracer No.',
                'Name',
                'Course',
                'Year',
                'Employment Status',
                'Name of Company',
                'Address',
                'Occupation',
                'If unemployed, reason for unemployment'
            ]
        ];

        if (!summaryRows.length) {
            rows.push(['', 'No names matched the current filters.', '', '', '', '', '', '', '']);
            return rows;
        }

        summaryRows.forEach((row) => {
            rows.push([
                row.tracerNumber || '',
                row.name || '',
                row.course || '',
                row.year || '',
                row.employmentStatus || '',
                row.company || '',
                row.address || '',
                row.occupation || '',
                row.unemploymentReason || ''
            ]);
        });

        return rows;
    }

    function buildNamesSummaryWorksheetRows(summaryRows = []) {
        const rows = [
            [],
            [{ value: 'Names Summary', styleId: 'Section', mergeAcross: 8 }],
            [
                { value: 'Total Names', styleId: 'Label' },
                { value: summaryRows.length, styleId: 'Body' }
            ],
            [],
            [
                'Tracer No.',
                'Name',
                'Course',
                'Year',
                'Employment Status',
                'Name of Company',
                'Address',
                'Occupation',
                'If unemployed, reason for unemployment'
            ].map((value) => ({ value, styleId: 'Header' }))
        ];

        if (!summaryRows.length) {
            rows.push([
                { value: '', styleId: 'Body' },
                { value: 'No names matched the current filters.', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' }
            ]);
            return rows;
        }

        summaryRows.forEach((row) => {
            rows.push([
                { value: row.tracerNumber || '', styleId: 'Body' },
                { value: row.name || '', styleId: 'Body' },
                { value: row.course || '', styleId: 'Body' },
                { value: row.year || '', styleId: 'Body' },
                { value: row.employmentStatus || '', styleId: 'Body' },
                { value: row.company || '', styleId: 'Body' },
                { value: row.address || '', styleId: 'Body' },
                { value: row.occupation || '', styleId: 'Body' },
                { value: row.unemploymentReason || '', styleId: 'Body' }
            ]);
        });

        return rows;
    }

    function getExportScopeLine(filterSummary = getCurrentFilterSummary()) {
        return filterSummary.length
            ? filterSummary.join(' | ')
            : 'Default analytics view with no extra filters applied.';
    }

    function buildChartCsvExportRows(chartId, chart, now = new Date(), scopeLine = getExportScopeLine()) {
        const labels = chart.data.labels || [];
        const datasets = chart.data.datasets || [];
        const rows = [
            [chartTitles[chartId] || chartId],
            ['Generated', now.toLocaleString()],
            ['Scope', scopeLine],
            ['Preview', buildChartDataPreview(extractChartPreviewValues(chart))],
            [],
            ['Label', ...datasets.map((dataset) => dataset.label || 'Value')]
        ];

        labels.forEach((label, index) => {
            const values = datasets.map((dataset) => (Array.isArray(dataset.data) ? (dataset.data[index] ?? '') : ''));

            rows.push([label, ...values]);
        });

        rows.push(...buildNamesSummaryCsvRows(analyticsState.csvNameSummary || []));

        return rows;
    }

    function buildWorksheetRowsFromCsvRows(rows = []) {
        const maxColumns = Math.max(1, ...rows.map((row) => row.length));
        const metadataLabels = new Set(['Generated', 'Scope', 'Preview']);
        let isNamesSummarySection = false;

        return rows.map((row, rowIndex) => {
            if (!row.length) {
                return [];
            }

            const firstCell = String(row[0] ?? '');

            if (rowIndex === 0) {
                return [{ value: firstCell, styleId: 'Title', mergeAcross: maxColumns - 1 }];
            }

            if (metadataLabels.has(firstCell)) {
                return [
                    { value: firstCell, styleId: 'Label' },
                    {
                        value: row[1] ?? '',
                        styleId: firstCell === 'Generated' ? 'Subtle' : 'Body',
                        mergeAcross: Math.max(0, maxColumns - 2)
                    }
                ];
            }

            if (firstCell === 'Names Summary') {
                isNamesSummarySection = true;
                return [{ value: firstCell, styleId: 'Section', mergeAcross: maxColumns - 1 }];
            }

            if (firstCell === 'Total Names') {
                return row.map((value, index) => ({
                    value,
                    styleId: index === 0 ? 'Label' : 'Body'
                }));
            }

            if (firstCell === 'Label' || firstCell === 'Tracer No.') {
                return row.map((value) => ({ value, styleId: 'Header' }));
            }

            return row.map((value, index) => ({
                value,
                styleId: !isNamesSummarySection && index === 0 ? 'Label' : 'Body'
            }));
        });
    }

    window.exportChartAsCsv = function (chartId, filenameBase) {
        const chart = analyticsCharts[chartId];
        if (!chart) {
            return;
        }

        const now = new Date();
        const workbook = buildAnalyticsExcelWorkbook(now, [chartId]);
        const blob = new Blob([workbook], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

        downloadBlobFile(blob, `${filenameBase}.xlsx`);
    };

    function escapeSpreadsheetXml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function sanitizeWorksheetName(name, usedNames) {
        const fallback = 'Sheet';
        const baseName = String(name || fallback)
            .replace(/[\\/:?*\[\]]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 31) || fallback;

        let candidate = baseName;
        let suffix = 2;

        while (usedNames.has(candidate)) {
            const suffixLabel = ` ${suffix}`;
            candidate = `${baseName.slice(0, Math.max(0, 31 - suffixLabel.length))}${suffixLabel}`;
            suffix += 1;
        }

        usedNames.add(candidate);
        return candidate;
    }

    function mergeWorksheetColumnWidths(...widthSets) {
        const maxLength = Math.max(...widthSets.map((widths) => widths.length));

        return Array.from({ length: maxLength }, (_, index) => Math.max(
            ...widthSets.map((widths) => Number(widths[index]) || 0),
            90
        ));
    }

    const xlsxStyleIndexes = {
        Default: 0,
        Title: 1,
        Section: 2,
        Header: 3,
        Label: 4,
        Body: 5,
        Subtle: 6
    };

    function normalizeSpreadsheetCell(cell) {
        if (cell === null || typeof cell === 'undefined') {
            return { value: '', styleId: 'Body' };
        }

        return typeof cell === 'object' && !Array.isArray(cell)
            ? cell
            : { value: cell, styleId: 'Body' };
    }

    function xlsxColumnName(index) {
        let columnName = '';
        let value = index;

        while (value > 0) {
            const remainder = (value - 1) % 26;
            columnName = String.fromCharCode(65 + remainder) + columnName;
            value = Math.floor((value - 1) / 26);
        }

        return columnName;
    }

    function buildXlsxWorksheetXml(rows, columnWidths = []) {
        const mergeRefs = [];
        const columns = columnWidths.map((width, index) => {
            const excelWidth = Math.max(10, Math.min(36, Number(width) / 7));
            const columnIndex = index + 1;

            return `<col min="${columnIndex}" max="${columnIndex}" width="${excelWidth.toFixed(2)}" customWidth="1"/>`;
        }).join('');

        const sheetRows = rows.map((row, rowIndex) => {
            const rowNumber = rowIndex + 1;
            let columnIndex = 1;
            const cells = (row || []).map((cell) => {
                const normalized = normalizeSpreadsheetCell(cell);
                const styleIndex = xlsxStyleIndexes[normalized.styleId || 'Body'] ?? xlsxStyleIndexes.Body;
                const cellRef = `${xlsxColumnName(columnIndex)}${rowNumber}`;
                const mergeAcross = Number.isInteger(normalized.mergeAcross) && normalized.mergeAcross > 0
                    ? normalized.mergeAcross
                    : 0;

                if (mergeAcross > 0) {
                    mergeRefs.push(`${cellRef}:${xlsxColumnName(columnIndex + mergeAcross)}${rowNumber}`);
                }

                columnIndex += mergeAcross + 1;

                const value = normalized.value ?? '';
                if (typeof value === 'number' && Number.isFinite(value)) {
                    return `<c r="${cellRef}" s="${styleIndex}"><v>${value}</v></c>`;
                }

                return `<c r="${cellRef}" s="${styleIndex}" t="inlineStr"><is><t xml:space="preserve">${escapeSpreadsheetXml(value)}</t></is></c>`;
            }).join('');

            return `<row r="${rowNumber}">${cells}</row>`;
        }).join('');

        const mergeCells = mergeRefs.length
            ? `<mergeCells count="${mergeRefs.length}">${mergeRefs.map((ref) => `<mergeCell ref="${ref}"/>`).join('')}</mergeCells>`
            : '';

        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheetViews>
        <sheetView workbookViewId="0">
            <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
        </sheetView>
    </sheetViews>
    ${columns ? `<cols>${columns}</cols>` : ''}
    <sheetData>${sheetRows}</sheetData>
    ${mergeCells}
</worksheet>`;
    }

    function buildXlsxStylesXml() {
        return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="7">
        <font><sz val="11"/><color rgb="FF172033"/><name val="Calibri"/></font>
        <font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FF166534"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FF172033"/><name val="Calibri"/></font>
        <font><sz val="11"/><color rgb="FF172033"/><name val="Calibri"/></font>
        <font><i/><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/></font>
    </fonts>
    <fills count="7">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFEEF7F0"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFEAF7ED"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF1B7F3A"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF8FBF8"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFD6E0D4"/></left>
            <right style="thin"><color rgb="FFD6E0D4"/></right>
            <top style="thin"><color rgb="FFD6E0D4"/></top>
            <bottom style="thin"><color rgb="FFD6E0D4"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="2" borderId="1"/></cellStyleXfs>
    <cellXfs count="7">
        <xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="5" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="6" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>`;
    }

    function buildXlsxZip(files, now = new Date()) {
        const encoder = new TextEncoder();
        const crcTable = buildXlsxCrcTable();
        const dosTime = ((now.getHours() & 31) << 11) | ((now.getMinutes() & 63) << 5) | Math.floor((now.getSeconds() & 63) / 2);
        const dosDate = (((now.getFullYear() - 1980) & 127) << 9) | (((now.getMonth() + 1) & 15) << 5) | (now.getDate() & 31);
        const localParts = [];
        const centralParts = [];
        let offset = 0;

        files.forEach((file) => {
            const nameBytes = encoder.encode(file.name);
            const data = file.data instanceof Uint8Array ? file.data : encoder.encode(String(file.data ?? ''));
            const crc = xlsxCrc32(data, crcTable);
            const localHeader = new Uint8Array(30 + nameBytes.length);
            const localView = new DataView(localHeader.buffer);

            localView.setUint32(0, 0x04034b50, true);
            localView.setUint16(4, 20, true);
            localView.setUint16(6, 0x0800, true);
            localView.setUint16(8, 0, true);
            localView.setUint16(10, dosTime, true);
            localView.setUint16(12, dosDate, true);
            localView.setUint32(14, crc, true);
            localView.setUint32(18, data.length, true);
            localView.setUint32(22, data.length, true);
            localView.setUint16(26, nameBytes.length, true);
            localHeader.set(nameBytes, 30);

            localParts.push(localHeader, data);

            const centralHeader = new Uint8Array(46 + nameBytes.length);
            const centralView = new DataView(centralHeader.buffer);

            centralView.setUint32(0, 0x02014b50, true);
            centralView.setUint16(4, 20, true);
            centralView.setUint16(6, 20, true);
            centralView.setUint16(8, 0x0800, true);
            centralView.setUint16(10, 0, true);
            centralView.setUint16(12, dosTime, true);
            centralView.setUint16(14, dosDate, true);
            centralView.setUint32(16, crc, true);
            centralView.setUint32(20, data.length, true);
            centralView.setUint32(24, data.length, true);
            centralView.setUint16(28, nameBytes.length, true);
            centralView.setUint32(42, offset, true);
            centralHeader.set(nameBytes, 46);

            centralParts.push(centralHeader);
            offset += localHeader.length + data.length;
        });

        const centralOffset = offset;
        const centralSize = centralParts.reduce((size, part) => size + part.length, 0);
        const endRecord = new Uint8Array(22);
        const endView = new DataView(endRecord.buffer);

        endView.setUint32(0, 0x06054b50, true);
        endView.setUint16(8, files.length, true);
        endView.setUint16(10, files.length, true);
        endView.setUint32(12, centralSize, true);
        endView.setUint32(16, centralOffset, true);

        return concatUint8Arrays([...localParts, ...centralParts, endRecord]);
    }

    function buildXlsxCrcTable() {
        const table = new Uint32Array(256);

        for (let index = 0; index < 256; index += 1) {
            let value = index;
            for (let bit = 0; bit < 8; bit += 1) {
                value = (value & 1) ? (0xedb88320 ^ (value >>> 1)) : (value >>> 1);
            }
            table[index] = value >>> 0;
        }

        return table;
    }

    function xlsxCrc32(data, table) {
        let crc = 0xffffffff;

        for (let index = 0; index < data.length; index += 1) {
            crc = table[(crc ^ data[index]) & 0xff] ^ (crc >>> 8);
        }

        return (crc ^ 0xffffffff) >>> 0;
    }

    function concatUint8Arrays(parts) {
        const length = parts.reduce((size, part) => size + part.length, 0);
        const output = new Uint8Array(length);
        let offset = 0;

        parts.forEach((part) => {
            output.set(part, offset);
            offset += part.length;
        });

        return output;
    }

    function buildXlsxWorkbook(sheets, now = new Date()) {
        const worksheetOverrides = sheets.map((_, index) => (
            `<Override PartName="/xl/worksheets/sheet${index + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`
        )).join('');
        const workbookSheetsXml = sheets.map((sheet, index) => (
            `<sheet name="${escapeSpreadsheetXml(sheet.name)}" sheetId="${index + 1}" r:id="rId${index + 1}"/>`
        )).join('');
        const workbookRelationships = sheets.map((_, index) => (
            `<Relationship Id="rId${index + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${index + 1}.xml"/>`
        )).join('');
        const files = [
            {
                name: '[Content_Types].xml',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    ${worksheetOverrides}
</Types>`
            },
            {
                name: '_rels/.rels',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>`
            },
            {
                name: 'docProps/app.xml',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>OARL Analytics</Application>
</Properties>`
            },
            {
                name: 'docProps/core.xml',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>OARL Tracer Report</dc:title>
    <dc:creator>OARL Analytics</dc:creator>
    <cp:lastModifiedBy>OARL Analytics</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">${now.toISOString()}</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">${now.toISOString()}</dcterms:modified>
</cp:coreProperties>`
            },
            {
                name: 'xl/workbook.xml',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>${workbookSheetsXml}</sheets>
</workbook>`
            },
            {
                name: 'xl/_rels/workbook.xml.rels',
                data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    ${workbookRelationships}
    <Relationship Id="rId${sheets.length + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`
            },
            {
                name: 'xl/styles.xml',
                data: buildXlsxStylesXml()
            },
            ...sheets.map((sheet, index) => ({
                name: `xl/worksheets/sheet${index + 1}.xml`,
                data: buildXlsxWorksheetXml(sheet.rows, sheet.columnWidths)
            }))
        ];

        return buildXlsxZip(files, now);
    }

    function buildAnalyticsExcelWorkbook(now, selectedChartIds = chartOrder) {
        const filterSummary = getCurrentFilterSummary();
        const scopeLine = getExportScopeLine(filterSummary);
        const overviewNarrative = buildExecutiveOverviewNarrative(filterSummary);
        const metrics = [
            ['Metric', 'Value'],
            ['Total Users', analyticsState.totalUsers ?? 0],
            ['Active Sessions', analyticsState.activeSessions ?? 0],
            ['Job Submissions', analyticsState.jobApplications ?? 0],
            ['Data Completeness', `${analyticsState.dataCompleteness ?? 0}%`],
            ['Employment Rate', `${analyticsState.employmentRate ?? 0}%`],
            ['Self-Employment Rate', `${analyticsState.selfEmploymentRate ?? 0}%`],
            ['Unemployment Rate', `${analyticsState.unemploymentRate ?? 0}%`],
        ];

        const workbookSheets = [];
        const usedSheetNames = new Set();

        const summaryRows = [
            [{ value: 'CMU-F-A-ARL-003 Tracer Form Report', styleId: 'Title', mergeAcross: 3 }],
            [{ value: `Generated ${now.toLocaleString()}`, styleId: 'Subtle', mergeAcross: 3 }],
            [],
            [{ value: 'Active Scope', styleId: 'Section', mergeAcross: 3 }],
            [{ value: scopeLine, styleId: 'Body', mergeAcross: 3 }],
            [],
            [{ value: 'Key Metrics', styleId: 'Section', mergeAcross: 3 }],
            metrics[0].map((value) => ({ value, styleId: 'Header' })),
            ...metrics.slice(1).map(([label, value]) => ([
                { value: label, styleId: 'Label' },
                { value, styleId: 'Body' }
            ])),
            [],
            [{ value: 'Overview Notes', styleId: 'Section', mergeAcross: 3 }],
            ...overviewNarrative.map((line) => ([{ value: line, styleId: 'Body', mergeAcross: 3 }]))
        ];

        workbookSheets.push({
            name: sanitizeWorksheetName('Report Summary', usedSheetNames),
            rows: summaryRows,
            columnWidths: [180, 120, 120, 120]
        });

        const insightsRows = [
            [{ value: 'Chart Insights', styleId: 'Title', mergeAcross: 4 }],
            [{ value: `Generated ${now.toLocaleString()}`, styleId: 'Subtle', mergeAcross: 4 }],
            [],
            [
                { value: 'Chart', styleId: 'Header' },
                { value: 'Preview', styleId: 'Header' },
                { value: 'Insight 1', styleId: 'Header' },
                { value: 'Insight 2', styleId: 'Header' },
                { value: 'Insight 3', styleId: 'Header' }
            ]
        ];

        selectedChartIds.forEach((chartId) => {
            const chart = analyticsCharts[chartId];
            if (!chart) {
                return;
            }

            const insights = buildChartNarrative(chartId, chart);
            insightsRows.push([
                { value: chartTitles[chartId] || chartId, styleId: 'Label' },
                { value: buildChartDataPreview(extractChartPreviewValues(chart)), styleId: 'Body' },
                { value: insights[0] || '', styleId: 'Body' },
                { value: insights[1] || '', styleId: 'Body' },
                { value: insights[2] || '', styleId: 'Body' }
            ]);

            const datasets = chart.data.datasets || [];
            const chartRows = buildWorksheetRowsFromCsvRows(
                buildChartCsvExportRows(chartId, chart, now, scopeLine)
            );

            const chartColumnWidths = [140, ...datasets.map(() => 100)];
            const namesSummaryColumnWidths = [140, 180, 160, 90, 150, 170, 190, 170, 220];

            workbookSheets.push({
                name: sanitizeWorksheetName(chartTitles[chartId] || chartId, usedSheetNames),
                rows: chartRows,
                columnWidths: mergeWorksheetColumnWidths(chartColumnWidths, namesSummaryColumnWidths)
            });
        });

        if (selectedChartIds.length === 0) {
            insightsRows.push([
                { value: 'No charts selected', styleId: 'Label' },
                { value: 'Summary only', styleId: 'Body' },
                { value: 'No chart insights were included in this export.', styleId: 'Body' },
                { value: '', styleId: 'Body' },
                { value: '', styleId: 'Body' }
            ]);
        }

        workbookSheets.splice(1, 0, {
            name: sanitizeWorksheetName('Chart Insights', usedSheetNames),
            rows: insightsRows,
            columnWidths: [140, 110, 220, 220, 220]
        });

        return buildXlsxWorkbook(workbookSheets, now);
    }

    function applyReadablePngTheme(options = {}, chartType = '') {
        options.color = '#334155';
        options.layout = {
            ...(options.layout || {}),
            padding: {
                top: 28,
                right: 36,
                bottom: 26,
                left: 28,
                ...((options.layout || {}).padding || {})
            }
        };

        if (options.plugins?.legend?.labels) {
            const legendFont = options.plugins.legend.labels.font || {};
            options.plugins.legend.labels.color = '#334155';
            options.plugins.legend.labels.font = {
                ...legendFont,
                family: reportFontFamily,
                size: Math.max(12, Number(legendFont.size) || 12),
                weight: legendFont.weight || '600'
            };
        }

        if (options.plugins?.arloValueLabels) {
            const isRadialChart = chartType === 'doughnut' || chartType === 'pie';

            if (isRadialChart) {
                options.plugins.arloValueLabels.color = '#ffffff';
                options.plugins.arloValueLabels.outlineColor = 'rgba(15, 23, 42, 0.78)';
                options.plugins.arloValueLabels.outlineWidth = Math.max(4, Number(options.plugins.arloValueLabels.outlineWidth) || 0);
            } else {
                options.plugins.arloValueLabels.color = '#00491e';
                options.plugins.arloValueLabels.outsideColor = '#00491e';
                options.plugins.arloValueLabels.insideColor = '#ffffff';
            }

            options.plugins.arloValueLabels.fontSize = Math.max(12, Number(options.plugins.arloValueLabels.fontSize) || 12);
            options.plugins.arloValueLabels.fontWeight = options.plugins.arloValueLabels.fontWeight || 800;
        }

        Object.values(options.scales || {}).forEach((scale) => {
            const tickFont = scale.ticks?.font || {};
            scale.grid = {
                ...(scale.grid || {}),
                color: 'rgba(100, 116, 139, 0.18)',
                drawBorder: false
            };
            scale.ticks = {
                ...(scale.ticks || {}),
                color: '#334155',
                font: {
                    ...tickFont,
                    family: reportFontFamily,
                    size: Math.max(12, Number(tickFont.size) || 12),
                    weight: tickFont.weight || '500'
                }
            };
        });

        return options;
    }

    function buildFallbackChartPng(sourceChart) {
        const sourceCanvas = sourceChart?.canvas;
        if (!sourceCanvas) {
            return null;
        }

        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = Math.max(1280, sourceCanvas.width || 0);
        exportCanvas.height = Math.max(720, sourceCanvas.height || 0);
        const exportContext = exportCanvas.getContext('2d');

        if (!exportContext) {
            return null;
        }

        exportContext.fillStyle = '#ffffff';
        exportContext.fillRect(0, 0, exportCanvas.width, exportCanvas.height);

        const padding = 48;
        const availableWidth = exportCanvas.width - (padding * 2);
        const availableHeight = exportCanvas.height - (padding * 2);
        const scale = Math.min(
            availableWidth / Math.max(1, sourceCanvas.width),
            availableHeight / Math.max(1, sourceCanvas.height)
        );
        const drawWidth = sourceCanvas.width * scale;
        const drawHeight = sourceCanvas.height * scale;
        const offsetX = (exportCanvas.width - drawWidth) / 2;
        const offsetY = (exportCanvas.height - drawHeight) / 2;

        exportContext.drawImage(sourceCanvas, offsetX, offsetY, drawWidth, drawHeight);
        return exportCanvas.toDataURL('image/png', 1.0);
    }

    function getHighResChartImage(chartId) {
        const sourceChart = analyticsCharts[chartId];
        if (!sourceChart) {
            return null;
        }

        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = 1280;
        tempCanvas.height = 720;
        const tempCtx = tempCanvas.getContext('2d');
        if (!tempCtx) {
            return null;
        }

        tempCtx.fillStyle = '#ffffff';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

        const clonedData = JSON.parse(JSON.stringify(sourceChart.data || {}));
        const clonedOptions = applyReadablePngTheme(JSON.parse(JSON.stringify(sourceChart.options || {})), sourceChart.config.type);
        clonedOptions.responsive = false;
        clonedOptions.maintainAspectRatio = false;
        clonedOptions.animation = false;
        clonedOptions.devicePixelRatio = 2;

        const tempChart = new Chart(tempCtx, {
            type: sourceChart.config.type,
            data: clonedData,
            options: clonedOptions
        });

        tempChart.update('none');
        tempCtx.save();
        tempCtx.globalCompositeOperation = 'destination-over';
        tempCtx.fillStyle = '#ffffff';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        tempCtx.restore();

        const imageData = tempCanvas.toDataURL('image/png', 1.0);
        tempChart.destroy();
        return imageData;
    }

    function waitForNextFrame() {
        return new Promise((resolve) => {
            window.requestAnimationFrame(() => resolve());
        });
    }

    function setAnalyticsExportBusyState(isBusy, format = 'pdf') {
        const exportButton = document.getElementById('analyticsExportBtn');
        const exportOptions = Array.from(document.querySelectorAll('[data-analytics-export-option]'));
        const confirmButton = document.getElementById('analyticsExportConfirmBtn');
        const dismissButtons = Array.from(document.querySelectorAll('[data-analytics-export-cancel], [data-analytics-export-close]'));
        const formatConfig = exportFormatConfig[format] || exportFormatConfig.pdf;
        analyticsExportInProgress = isBusy;

        if (exportButton) {
            exportButton.disabled = isBusy;
            exportButton.classList.toggle('opacity-70', isBusy);
            exportButton.classList.toggle('cursor-wait', isBusy);
            exportButton.innerHTML = isBusy
                ? `<i class='bx bx-loader-alt bx-spin text-base'></i>${formatConfig.busyLabel}`
                : `<i class='bx bx-export text-base'></i>Export`;
        }

        exportOptions.forEach((option) => {
            option.disabled = isBusy;
            option.classList.toggle('opacity-70', isBusy);
            option.classList.toggle('cursor-wait', isBusy);
        });

        if (confirmButton) {
            confirmButton.disabled = isBusy;
            confirmButton.classList.toggle('opacity-70', isBusy);
            confirmButton.classList.toggle('cursor-wait', isBusy);
            confirmButton.innerHTML = isBusy
                ? `<i class='bx bx-loader-alt bx-spin text-lg'></i>${formatConfig.busyLabel}`
                : `<i class='bx ${formatConfig.icon} text-lg'></i>${formatConfig.confirmLabel}`;
        }

        dismissButtons.forEach((button) => {
            button.disabled = isBusy;
            button.classList.toggle('opacity-70', isBusy);
            button.classList.toggle('cursor-not-allowed', isBusy);
        });
    }

    function exportAnalyticsAsExcel(now, selectedChartIds = chartOrder) {
        const workbook = buildAnalyticsExcelWorkbook(now, selectedChartIds);
        const blob = new Blob([workbook], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        downloadBlobFile(blob, `arlo-tracer-report-${now.toISOString().slice(0, 10)}.xlsx`);
    }

    function exportAnalyticsAsCsv(now, selectedChartIds = chartOrder) {
        const scopeLine = getExportScopeLine();
        const rows = [];

        selectedChartIds.forEach((chartId) => {
            const chart = analyticsCharts[chartId];
            if (!chart) {
                return;
            }

            if (rows.length) {
                rows.push([], []);
            }

            rows.push(...buildChartCsvExportRows(chartId, chart, now, scopeLine));
        });

        if (!rows.length) {
            rows.push(
                ['OARL CSV Report'],
                ['Generated', now.toLocaleString()],
                ['Scope', scopeLine],
                [],
                ['No charts selected for export.']
            );
        }

        const csv = rows
            .map((row) => row.map(escapeCsvValue).join(','))
            .join('\n');

        downloadTextFile(`\uFEFF${csv}`, `arlo-tracer-report-${now.toISOString().slice(0, 10)}.csv`, 'text/csv;charset=utf-8;');
    }

    function formatInsightValue(value) {
        if (typeof value === 'number' && Number.isFinite(value)) {
            return Number.isInteger(value) ? String(value) : value.toFixed(1);
        }

        return String(value ?? '0');
    }

    function getCurrentFilterSummary() {
        if (!analyticsState?.ui) {
            return [];
        }

        const filterSummary = [];
        const ui = analyticsState.ui;

        if (ui.selectedCollegeLabel && ui.selectedCollegeLabel !== 'All Colleges') {
            filterSummary.push(`College: ${ui.selectedCollegeLabel}`);
        }
        if (ui.selectedGraduationYearLabel && ui.selectedGraduationYearLabel !== 'All Years') {
            filterSummary.push(`Graduation year: ${ui.selectedGraduationYearLabel}`);
        }
        if (ui.selectedDegreeLabel && ui.selectedDegreeLabel !== 'All Degrees') {
            filterSummary.push(`Degree: ${ui.selectedDegreeLabel}`);
        }
        if (ui.selectedSexLabel && ui.selectedSexLabel !== 'All Sexes') {
            filterSummary.push(`Sex: ${ui.selectedSexLabel}`);
        }
        if (ui.selectedAgeRangeLabel && ui.selectedAgeRangeLabel !== 'All Age Ranges') {
            filterSummary.push(`Age: ${ui.selectedAgeRangeLabel}`);
        }
        if (ui.selectedEmploymentStatusLabel && ui.selectedEmploymentStatusLabel !== 'All Employment Statuses') {
            filterSummary.push(`Employment: ${ui.selectedEmploymentStatusLabel}`);
        }
        if (ui.selectedIndustryLabel && ui.selectedIndustryLabel !== 'All Industries') {
            filterSummary.push(`Industry: ${ui.selectedIndustryLabel}`);
        }
        if (ui.selectedLocationLabel && ui.selectedLocationLabel !== 'All Locations') {
            filterSummary.push(`Location: ${ui.selectedLocationLabel}`);
        }
        if (ui.selectedSalaryRangeLabel && ui.selectedSalaryRangeLabel !== 'All Salary Ranges') {
            filterSummary.push(`Salary: ${ui.selectedSalaryRangeLabel}`);
        }

        return filterSummary;
    }

    function calculatePercentage(part, whole) {
        if (!whole) {
            return 0;
        }

        return (Number(part || 0) / Number(whole || 0)) * 100;
    }

    function describeVolatility(maxValue, minValue, averageValue) {
        if (averageValue <= 0) {
            return 'stable';
        }

        const spreadRatio = (maxValue - minValue) / averageValue;

        if (spreadRatio >= 1.25) {
            return 'high';
        }

        if (spreadRatio >= 0.55) {
            return 'moderate';
        }

        return 'low';
    }

    function buildExecutiveOverviewNarrative(filterSummary = getCurrentFilterSummary()) {
        const totalUsers = Number(analyticsState.totalUsers ?? 0);
        const activeSessions = Number(analyticsState.activeSessions ?? 0);
        const jobApplications = Number(analyticsState.jobApplications ?? 0);
        const dataCompleteness = Number(analyticsState.dataCompleteness ?? 0);
        const employmentRate = Number(analyticsState.employmentRate ?? 0);
        const selfEmploymentRate = Number(analyticsState.selfEmploymentRate ?? 0);
        const unemploymentRate = Number(analyticsState.unemploymentRate ?? 0);
        const workforceParticipation = employmentRate + selfEmploymentRate;

        const scopeNarrative = filterSummary.length
            ? `The current export reflects a filtered analytics scope focused on ${filterSummary.join(', ')}.`
            : 'The current export reflects the full analytics view with no additional filter restrictions applied.';

        let dataQualityNarrative = `Data completeness is currently ${formatInsightValue(dataCompleteness)}%, which gives the report a reliable baseline for trend reading.`;
        if (dataCompleteness < 75) {
            dataQualityNarrative = `Data completeness is currently ${formatInsightValue(dataCompleteness)}%, so the report should be read with caution because some alumni outcomes may still be underrepresented.`;
        } else if (dataCompleteness < 90) {
            dataQualityNarrative = `Data completeness is currently ${formatInsightValue(dataCompleteness)}%, providing a usable view of outcomes while still leaving room to strengthen record coverage.`;
        }

        let employmentNarrative = `Employment conditions remain balanced, with ${formatInsightValue(employmentRate)}% employed, ${formatInsightValue(selfEmploymentRate)}% self-employed, and ${formatInsightValue(unemploymentRate)}% unemployed.`;
        if (workforceParticipation >= 75 && unemploymentRate <= 15) {
            employmentNarrative = `Employment conditions appear strong, with ${formatInsightValue(workforceParticipation)}% of visible alumni either employed or self-employed and unemployment limited to ${formatInsightValue(unemploymentRate)}%.`;
        } else if (workforceParticipation < 60 || unemploymentRate >= 25) {
            employmentNarrative = `Employment conditions appear softer in the current view, with workforce participation at ${formatInsightValue(workforceParticipation)}% and unemployment at ${formatInsightValue(unemploymentRate)}%, indicating a need for closer follow-up on placement outcomes.`;
        }

        return [
            `This report captures ${formatInsightValue(totalUsers)} visible users, ${formatInsightValue(jobApplications)} alumni job opportunity submissions, and ${formatInsightValue(activeSessions)} active sessions for the selected analytics view.`,
            scopeNarrative,
            dataQualityNarrative,
            employmentNarrative
        ];
    }

    function getExportChartEntries() {
        return chartOrder
            .map((chartId) => ({
                id: chartId,
                title: chartTitles[chartId] || chartId,
                chart: analyticsCharts[chartId] || null,
            }))
            .filter((entry) => Boolean(entry.chart));
    }

    function getSelectedExportChartEntries(chartEntries = getExportChartEntries()) {
        const selectedIds = new Set(getSelectedExportChartIds(chartEntries));

        return chartEntries.filter((entry) => selectedIds.has(entry.id));
    }

    function buildExportFileName(format, now = new Date()) {
        const formatConfig = exportFormatConfig[format] || exportFormatConfig.pdf;
        return `arlo-tracer-report-${now.toISOString().slice(0, 10)}.${formatConfig.extension}`;
    }

    function buildExportDeliverables(format, chartEntries = getExportChartEntries()) {
        const chartCount = chartEntries.length;
        const chartLabel = `${formatInsightValue(chartCount)} chart ${chartCount === 1 ? 'section' : 'sections'}`;

        if (chartCount === 0) {
            return [
                'A report summary section with the active scope, key metrics, and overview notes.',
                'No charts are currently selected, so the export will contain summary information only.',
                'Select one or more charts above if you want graph pages or chart worksheets included.'
            ];
        }

        if (format === 'excel') {
            return [
                'A report summary sheet with the active scope, headline metrics, and overview notes.',
                'A chart insights sheet with generated talking points for every available visualization.',
                `${chartLabel} exported as separate worksheets containing the chart labels and dataset values.`
            ];
        }

        if (format === 'csv') {
            return [
                `${chartLabel} exported in one CSV file using the same layout as the individual graph CSV buttons.`,
                'Each chart section includes generation time, active scope, chart preview, data rows, and names summary.',
                'Blank rows separate chart sections so the file stays easy to scan in Excel.'
            ];
        }

        return [
            'A report opening page with generation time, active scope, key metrics, and overview notes.',
            `${chartLabel} exported as high-resolution pages with the live visualization and generated insights.`,
            'A formatted PDF layout that is ready for sharing or printing.'
        ];
    }

    function extractChartPreviewValues(chart) {
        const labels = chart?.data?.labels || [];
        const datasets = Array.isArray(chart?.data?.datasets) ? chart.data.datasets : [];

        if (!labels.length || !datasets.length) {
            return [];
        }

        if (datasets.length === 1) {
            return (Array.isArray(datasets[0]?.data) ? datasets[0].data : [])
                .map((value) => Number(value) || 0);
        }

        return labels.map((_, index) => datasets.reduce((sum, dataset) => {
            const rawValues = Array.isArray(dataset?.data) ? dataset.data : [];
            return sum + (Number(rawValues[index]) || 0);
        }, 0));
    }

    function buildChartDataPreview(values, maxPoints = 12) {
        const series = Array.isArray(values) ? values.filter((value) => Number.isFinite(value)) : [];

        if (!series.length) {
            return 'No data preview';
        }

        const sampled = series.length <= maxPoints
            ? series
            : Array.from({ length: maxPoints }, (_, index) => {
                const position = Math.round((index / Math.max(1, maxPoints - 1)) * (series.length - 1));
                return series[position] ?? 0;
            });

        const minValue = Math.min(...sampled);
        const maxValue = Math.max(...sampled);
        const latestValue = series[series.length - 1] ?? 0;

        return `${series.length} chart items | Lowest count: ${formatInsightValue(minValue)} | Highest count: ${formatInsightValue(maxValue)} | Last listed count: ${formatInsightValue(latestValue)}`;
    }

    function getChartPreviewRows(chart, maxRows = 3) {
        const labels = chart?.data?.labels || [];
        const datasets = Array.isArray(chart?.data?.datasets) ? chart.data.datasets : [];

        if (!labels.length || !datasets.length) {
            return [];
        }

        return labels.slice(0, maxRows).map((label, index) => {
            const total = datasets.reduce((sum, dataset) => {
                const rawValues = Array.isArray(dataset?.data) ? dataset.data : [];
                return sum + (Number(rawValues[index]) || 0);
            }, 0);

            return {
                label: String(label),
                value: formatInsightValue(total),
            };
        });
    }

    function buildChartThumbnailImage(chartId) {
        const chart = analyticsCharts[chartId];
        const sourceCanvas = chart?.canvas;

        if (!sourceCanvas) {
            return null;
        }

        try {
            const targetWidth = 360;
            const targetHeight = 220;
            const previewCanvas = document.createElement('canvas');
            previewCanvas.width = targetWidth;
            previewCanvas.height = targetHeight;
            const previewContext = previewCanvas.getContext('2d');

            if (!previewContext) {
                return sourceCanvas.toDataURL('image/png', 0.92);
            }

            previewContext.fillStyle = '#ffffff';
            previewContext.fillRect(0, 0, targetWidth, targetHeight);

            const widthRatio = targetWidth / sourceCanvas.width;
            const heightRatio = targetHeight / sourceCanvas.height;
            const scale = Math.min(widthRatio, heightRatio);
            const drawWidth = sourceCanvas.width * scale;
            const drawHeight = sourceCanvas.height * scale;
            const offsetX = (targetWidth - drawWidth) / 2;
            const offsetY = (targetHeight - drawHeight) / 2;

            previewContext.drawImage(sourceCanvas, offsetX, offsetY, drawWidth, drawHeight);

            return previewCanvas.toDataURL('image/png', 0.92);
        } catch (error) {
            try {
                return sourceCanvas.toDataURL('image/png', 0.92);
            } catch (fallbackError) {
                return null;
            }
        }
    }

    function renderExportOverviewList(targetId, items, emptyMessage) {
        const container = document.getElementById(targetId);
        if (!container) {
            return;
        }

        container.innerHTML = '';
        const normalizedItems = Array.isArray(items) && items.length ? items : [emptyMessage];

        normalizedItems.forEach((item) => {
            const line = document.createElement('div');
            line.className = 'analytics-export-overview__list-item';
            line.textContent = item;
            container.appendChild(line);
        });
    }

    function updateAnalyticsExportSelectionHint(selectedCount, totalCount) {
        const hint = document.getElementById('analyticsExportOverviewSelectionHint');
        if (!hint) {
            return;
        }

        if (totalCount === 0) {
            hint.textContent = 'No charts are available in the current view.';
            return;
        }

        if (selectedCount === 0) {
            hint.textContent = 'No charts selected yet. Click any graph card below to include it, or continue with a summary-only export.';
            return;
        }

        hint.textContent = `${selectedCount} of ${totalCount} charts selected for export.`;
    }

    function renderExportOverviewChartChoicesLegacy(chartEntries = []) {
        const container = document.getElementById('analyticsExportOverviewCharts');
        if (!container) {
            return;
        }

        container.innerHTML = '';

        if (!chartEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No chart visualizations are currently available for selection.';
            container.appendChild(emptyState);
            updateAnalyticsExportSelectionHint(0, 0);
            return;
        }

        const selectedIds = new Set(getSelectedExportChartIds(chartEntries));
        const selectedEntries = chartEntries.filter((entry) => selectedIds.has(entry.id));

        if (!selectedEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No charts selected yet. Click any graph card below to include it in the export.';
            container.appendChild(emptyState);
            updateAnalyticsExportSelectionHint(0, chartEntries.length);
            return;
        }

        selectedEntries.forEach((entry) => {
            const item = document.createElement('div');
            item.className = 'analytics-export-overview__choice is-selected is-summary';

            const indicator = document.createElement('span');
            indicator.className = 'analytics-export-overview__choice-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.innerHTML = "<i class='bx bx-check'></i>";

            const copy = document.createElement('span');
            copy.className = 'analytics-export-overview__choice-copy';

            const title = document.createElement('span');
            title.className = 'analytics-export-overview__choice-title';
            title.textContent = entry.title;

            const meta = document.createElement('span');
            meta.className = 'analytics-export-overview__choice-meta';
            const chartType = String(entry.chart?.config?.type || 'chart').toUpperCase();
            const dataPointCount = Array.isArray(entry.chart?.data?.labels) ? entry.chart.data.labels.length : 0;
            meta.textContent = `${chartType} - ${dataPointCount} data point${dataPointCount === 1 ? '' : 's'}`;

            copy.append(title, meta);
            item.append(indicator, copy);
            container.appendChild(item);
        });

        updateAnalyticsExportSelectionHint(selectedEntries.length, chartEntries.length);
    }

    function renderExportOverviewMetrics(metrics = []) {
        const container = document.getElementById('analyticsExportOverviewMetrics');
        if (!container) {
            return;
        }

        container.innerHTML = '';

        metrics.forEach(([label, value]) => {
            const card = document.createElement('div');
            card.className = 'analytics-export-overview__metric';

            const metricLabel = document.createElement('p');
            metricLabel.className = 'analytics-export-overview__metric-label';
            metricLabel.textContent = label;

            const metricValue = document.createElement('p');
            metricValue.className = 'analytics-export-overview__metric-value';
            metricValue.textContent = value;

            card.append(metricLabel, metricValue);
            container.appendChild(card);
        });
    }

    function updateAnalyticsExportConfirmLabel(format, selectedCount) {
        const confirmButton = document.getElementById('analyticsExportConfirmBtn');
        const formatConfig = exportFormatConfig[format] || exportFormatConfig.pdf;

        if (!confirmButton || analyticsExportInProgress) {
            return;
        }

        const label = selectedCount === 0
            ? `Confirm ${formatConfig.shortLabel} Summary Export`
            : formatConfig.confirmLabel;

        confirmButton.innerHTML = `<i class='bx ${formatConfig.icon} text-lg'></i>${label}`;
    }

    function renderExportOverviewPreviewsLegacy(format, chartEntries = []) {
        const container = document.getElementById('analyticsExportOverviewPreviewGrid');
        const title = document.getElementById('analyticsExportOverviewPreviewTitle');
        const description = document.getElementById('analyticsExportOverviewPreviewDescription');

        if (!container || !title || !description) {
            return;
        }

        const isSpreadsheetPreview = format === 'excel' || format === 'csv';
        title.textContent = isSpreadsheetPreview ? 'Spreadsheet-style Preview Samples' : 'Chart Preview Samples';
        description.textContent = isSpreadsheetPreview
            ? 'Spreadsheet and CSV exports are data-first, so these cards show compact count summaries and row previews instead of image thumbnails.'
            : 'These small thumbnails show the chart visuals that will be included in the PDF report.';

        container.innerHTML = '';

        if (!chartEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No chart previews are available for the current export scope.';
            container.appendChild(emptyState);
            return;
        }

        chartEntries.forEach((entry) => {
            const card = document.createElement('article');
            card.className = 'analytics-export-overview__preview-card';

            const heading = document.createElement('div');

            const titleLine = document.createElement('p');
            titleLine.className = 'analytics-export-overview__preview-title';
            titleLine.textContent = entry.title;

            const note = document.createElement('p');
            note.className = 'analytics-export-overview__preview-note';
            note.textContent = isSpreadsheetPreview
                ? 'Compact data preview'
                : 'Small image preview';

            heading.append(titleLine, note);
            card.appendChild(heading);

            if (isSpreadsheetPreview) {
                const previewData = document.createElement('div');
                previewData.className = 'analytics-export-overview__preview-data';

                const sparkline = document.createElement('div');
                sparkline.className = 'analytics-export-overview__preview-sparkline';
                sparkline.textContent = buildChartDataPreview(extractChartPreviewValues(entry.chart));

                const previewRows = document.createElement('div');
                previewRows.className = 'analytics-export-overview__preview-rows';

                getChartPreviewRows(entry.chart).forEach((row) => {
                    const rowLine = document.createElement('div');
                    rowLine.className = 'analytics-export-overview__preview-row';

                    const label = document.createElement('span');
                    label.className = 'analytics-export-overview__preview-row-label';
                    label.textContent = row.label;

                    const value = document.createElement('span');
                    value.className = 'analytics-export-overview__preview-row-value';
                    value.textContent = row.value;

                    rowLine.append(label, value);
                    previewRows.appendChild(rowLine);
                });

                previewData.append(sparkline, previewRows);
                card.appendChild(previewData);
            } else {
                const figure = document.createElement('div');
                figure.className = 'analytics-export-overview__preview-figure';

                const image = document.createElement('img');
                image.className = 'analytics-export-overview__preview-image';
                image.alt = `${entry.title} preview`;

                const imageData = buildChartThumbnailImage(entry.id);
                if (imageData) {
                    image.src = imageData;
                } else {
                    image.hidden = true;
                }

                const fallback = document.createElement('div');
                fallback.className = 'analytics-export-overview__preview-fallback';
                fallback.textContent = imageData ? '' : 'Preview unavailable for this chart.';
                fallback.hidden = Boolean(imageData);

                figure.append(image, fallback);
                card.appendChild(figure);
            }

            container.appendChild(card);
        });
    }

    function updateAnalyticsExportOverview(format) {
        const formatConfig = exportFormatConfig[format] || exportFormatConfig.pdf;
        const filterSummary = getCurrentFilterSummary();
        const chartEntries = getExportChartEntries();
        const selectedChartEntries = getSelectedExportChartEntries(chartEntries);
        const metrics = [
            ['Total Users', String(analyticsState.totalUsers ?? 0)],
            ['Active Sessions', String(analyticsState.activeSessions ?? 0)],
            ['Job Submissions', String(analyticsState.jobApplications ?? 0)],
            ['Employment Rate', `${analyticsState.employmentRate ?? 0}%`],
            ['Data Completeness', `${analyticsState.dataCompleteness ?? 0}%`],
            ['Unemployment Rate', `${analyticsState.unemploymentRate ?? 0}%`],
        ];

        const title = document.getElementById('analyticsExportOverviewTitle');
        const description = document.getElementById('analyticsExportOverviewDescription');
        const formatLabel = document.getElementById('analyticsExportOverviewFormat');
        const filename = document.getElementById('analyticsExportOverviewFileName');
        const chartCount = document.getElementById('analyticsExportOverviewChartCount');

        if (title) {
            title.textContent = `Confirm ${formatConfig.label} export`;
        }

        if (description) {
            description.textContent = formatConfig.description;
        }

        if (formatLabel) {
            formatLabel.textContent = formatConfig.shortLabel;
        }

        if (filename) {
            filename.textContent = buildExportFileName(format);
        }

        if (chartCount) {
            chartCount.textContent = selectedChartEntries.length === 0
                ? 'Summary only'
                : `${selectedChartEntries.length} of ${chartEntries.length} charts`;
        }

        renderExportOverviewList(
            'analyticsExportOverviewScope',
            filterSummary,
            'Default analytics view with no extra filters applied.'
        );
        renderExportOverviewChartChoices(chartEntries);
        renderExportOverviewList(
            'analyticsExportOverviewDeliverables',
            buildExportDeliverables(format, selectedChartEntries),
            'The export summary is not available right now.'
        );
        renderExportOverviewList(
            'analyticsExportOverviewNarrative',
            buildExecutiveOverviewNarrative(filterSummary),
            'No overview narrative is available right now.'
        );
        setAnalyticsExportBusyState(false, format);
        renderExportOverviewPreviews(format, chartEntries);
        renderExportOverviewMetrics(metrics);
        updateAnalyticsExportConfirmLabel(format, selectedChartEntries.length);
    }

    function renderExportOverviewChartChoices(chartEntries = []) {
        const container = document.getElementById('analyticsExportOverviewCharts');
        if (!container) {
            return;
        }

        container.innerHTML = '';

        if (!chartEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No chart visualizations are currently available for selection.';
            container.appendChild(emptyState);
            updateAnalyticsExportSelectionHint(0, 0);
            return;
        }

        const selectedIds = new Set(getSelectedExportChartIds(chartEntries));
        const selectedEntries = chartEntries.filter((entry) => selectedIds.has(entry.id));

        if (!selectedEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No charts selected yet. Click any graph card below to include it in the export.';
            container.appendChild(emptyState);
            updateAnalyticsExportSelectionHint(0, chartEntries.length);
            return;
        }

        selectedEntries.forEach((entry) => {
            const item = document.createElement('div');
            item.className = 'analytics-export-overview__choice is-selected is-summary';

            const indicator = document.createElement('span');
            indicator.className = 'analytics-export-overview__choice-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.innerHTML = "<i class='bx bx-check'></i>";

            const copy = document.createElement('span');
            copy.className = 'analytics-export-overview__choice-copy';

            const title = document.createElement('span');
            title.className = 'analytics-export-overview__choice-title';
            title.textContent = entry.title;

            const meta = document.createElement('span');
            meta.className = 'analytics-export-overview__choice-meta';
            const chartType = String(entry.chart?.config?.type || 'chart').toUpperCase();
            const dataPointCount = Array.isArray(entry.chart?.data?.labels) ? entry.chart.data.labels.length : 0;
            meta.textContent = `${chartType} - ${dataPointCount} data point${dataPointCount === 1 ? '' : 's'}`;

            copy.append(title, meta);
            item.append(indicator, copy);
            container.appendChild(item);
        });

        updateAnalyticsExportSelectionHint(selectedEntries.length, chartEntries.length);
    }

    function renderExportOverviewPreviews(format, chartEntries = []) {
        const container = document.getElementById('analyticsExportOverviewPreviewGrid');
        const title = document.getElementById('analyticsExportOverviewPreviewTitle');
        const description = document.getElementById('analyticsExportOverviewPreviewDescription');

        if (!container || !title || !description) {
            return;
        }

        const isSpreadsheetPreview = format === 'excel' || format === 'csv';
        const selectedIds = new Set(getSelectedExportChartIds(chartEntries));
        title.textContent = isSpreadsheetPreview ? 'Spreadsheet-style Preview Samples' : 'Chart Preview Samples';
        description.textContent = isSpreadsheetPreview
            ? 'Click any graph card to include or exclude it. Spreadsheet and CSV exports stay data-first, so these cards show compact count summaries and row previews.'
            : 'Click any graph card to include or exclude it before confirming the PDF export.';

        container.innerHTML = '';

        if (!chartEntries.length) {
            const emptyState = document.createElement('div');
            emptyState.className = 'analytics-export-overview__preview-empty';
            emptyState.textContent = 'No chart previews are available for the current export scope.';
            container.appendChild(emptyState);
            return;
        }

        chartEntries.forEach((entry) => {
            const isSelected = selectedIds.has(entry.id);
            const card = document.createElement('button');
            card.type = 'button';
            card.className = `analytics-export-overview__preview-card${isSelected ? ' is-selected' : ''}`;
            card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            card.setAttribute('title', isSelected ? 'Click to exclude this chart from export' : 'Click to include this chart in export');
            card.onclick = () => toggleAnalyticsExportChartSelection(entry.id);

            const heading = document.createElement('div');
            heading.className = 'analytics-export-overview__preview-header';

            const headingCopy = document.createElement('div');

            const titleLine = document.createElement('p');
            titleLine.className = 'analytics-export-overview__preview-title';
            titleLine.textContent = entry.title;

            const note = document.createElement('p');
            note.className = 'analytics-export-overview__preview-note';
            note.textContent = isSpreadsheetPreview
                ? 'Compact data preview'
                : 'Small image preview';

            const status = document.createElement('span');
            status.className = 'analytics-export-overview__preview-status';
            status.textContent = isSelected ? 'Included' : 'Excluded';

            headingCopy.append(titleLine, note);
            heading.append(headingCopy, status);
            card.appendChild(heading);

            if (isSpreadsheetPreview) {
                const previewData = document.createElement('div');
                previewData.className = 'analytics-export-overview__preview-data';

                const sparkline = document.createElement('div');
                sparkline.className = 'analytics-export-overview__preview-sparkline';
                sparkline.textContent = buildChartDataPreview(extractChartPreviewValues(entry.chart));

                const previewRows = document.createElement('div');
                previewRows.className = 'analytics-export-overview__preview-rows';

                getChartPreviewRows(entry.chart).forEach((row) => {
                    const rowLine = document.createElement('div');
                    rowLine.className = 'analytics-export-overview__preview-row';

                    const label = document.createElement('span');
                    label.className = 'analytics-export-overview__preview-row-label';
                    label.textContent = row.label;

                    const value = document.createElement('span');
                    value.className = 'analytics-export-overview__preview-row-value';
                    value.textContent = row.value;

                    rowLine.append(label, value);
                    previewRows.appendChild(rowLine);
                });

                previewData.append(sparkline, previewRows);
                card.appendChild(previewData);
            } else {
                const figure = document.createElement('div');
                figure.className = 'analytics-export-overview__preview-figure';

                const image = document.createElement('img');
                image.className = 'analytics-export-overview__preview-image';
                image.alt = `${entry.title} preview`;

                const imageData = buildChartThumbnailImage(entry.id);
                if (imageData) {
                    image.src = imageData;
                } else {
                    image.hidden = true;
                }

                const fallback = document.createElement('div');
                fallback.className = 'analytics-export-overview__preview-fallback';
                fallback.textContent = imageData ? '' : 'Preview unavailable for this chart.';
                fallback.hidden = Boolean(imageData);

                figure.append(image, fallback);
                card.appendChild(figure);
            }

            container.appendChild(card);
        });
    }

    window.toggleAnalyticsExportChartSelection = function (chartId) {
        const chartEntries = getExportChartEntries();
        const availableIds = new Set(chartEntries.map((entry) => entry.id));

        if (!availableIds.has(chartId)) {
            return;
        }

        hasAnalyticsExportChartSelectionState = true;

        if (selectedAnalyticsExportChartIds.has(chartId)) {
            selectedAnalyticsExportChartIds.delete(chartId);
        } else {
            selectedAnalyticsExportChartIds.add(chartId);
        }

        updateAnalyticsExportOverview(pendingAnalyticsExportFormat || 'pdf');
    };

    window.selectAllAnalyticsExportCharts = function () {
        const chartEntries = getExportChartEntries();
        selectedAnalyticsExportChartIds = new Set(chartEntries.map((entry) => entry.id));
        hasAnalyticsExportChartSelectionState = true;
        updateAnalyticsExportOverview(pendingAnalyticsExportFormat || 'pdf');
    };

    window.clearAnalyticsExportCharts = function () {
        selectedAnalyticsExportChartIds = new Set();
        hasAnalyticsExportChartSelectionState = true;
        updateAnalyticsExportOverview(pendingAnalyticsExportFormat || 'pdf');
    };

    function setAnalyticsExportOverviewOpen(isOpen) {
        const modal = document.getElementById('analyticsExportOverviewModal');
        if (!modal) {
            return;
        }

        modal.classList.toggle('hidden', !isOpen);
        document.body.classList.toggle('analytics-export-overview-open', isOpen);
    }

    function summarizeSingleDatasetTrend(chart) {
        const labels = chart?.data?.labels || [];
        const dataset = chart?.data?.datasets?.[0] || null;
        const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];

        if (!labels.length || !values.length) {
            return ['No data points are available for this chart under the current filter set.'];
        }

        const total = values.reduce((sum, value) => sum + value, 0);
        const maxValue = Math.max(...values);
        const minValue = Math.min(...values);
        const latestValue = values[values.length - 1];
        const earliestValue = values[0];
        const averageValue = total / values.length;
        const maxIndex = values.indexOf(maxValue);
        const minIndex = values.indexOf(minValue);
        const delta = latestValue - earliestValue;
        const volatility = describeVolatility(maxValue, minValue, averageValue);
        const percentDelta = earliestValue > 0
            ? calculatePercentage(Math.abs(delta), earliestValue)
            : null;

        const insights = [
            `Across ${labels.length} reported periods, the series records ${formatInsightValue(total)} total activity with an average of ${formatInsightValue(averageValue)} per period.`
        ];

        insights.push(`Performance peaks at ${formatInsightValue(maxValue)} in ${labels[maxIndex] ?? 'the strongest period'} and falls to ${formatInsightValue(minValue)} in ${labels[minIndex] ?? 'the softest period'}, indicating ${volatility} volatility across the selected range.`);

        if (delta > 0) {
            insights.push(`The latest period closes ${formatInsightValue(delta)} above the opening level${percentDelta !== null ? `, an improvement of ${formatInsightValue(percentDelta)}%` : ''}, which signals positive momentum at the end of the selected range.`);
        } else if (delta < 0) {
            insights.push(`The latest period closes ${formatInsightValue(Math.abs(delta))} below the opening level${percentDelta !== null ? `, a decline of ${formatInsightValue(percentDelta)}%` : ''}, showing a softer finish that may need closer monitoring.`);
        } else {
            insights.push('The latest period finishes at the same level as the opening period, suggesting a stable overall trajectory despite movement inside the range.');
        }

        return insights;
    }

    function summarizeDistributionChart(chart) {
        const labels = chart?.data?.labels || [];
        const dataset = chart?.data?.datasets?.[0] || null;
        const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];
        const total = values.reduce((sum, value) => sum + value, 0);

        if (!labels.length || !values.length || total === 0) {
            return ['No distribution data is available for this chart under the current filter set.'];
        }

        const ranked = labels.map((label, index) => ({
            label,
            value: values[index] ?? 0,
        })).sort((left, right) => right.value - left.value);

        const top = ranked[0];
        const second = ranked[1] || null;
        const topShare = calculatePercentage(top.value, total);
        const secondShare = second ? calculatePercentage(second.value, total) : 0;
        const topTwoShare = calculatePercentage(top.value + (second?.value ?? 0), total);
        const insights = [
            `This distribution covers ${formatInsightValue(total)} records across ${ranked.length} categories, giving a direct view of where the alumni base is most concentrated.`
        ];

        insights.push(`${top.label} leads the distribution with ${formatInsightValue(top.value)} records or ${formatInsightValue(topShare)}% of the total${second ? `, ahead of ${second.label} at ${formatInsightValue(second.value)} records or ${formatInsightValue(secondShare)}%` : ''}.`);

        insights.push(topTwoShare >= 70
            ? `The top categories account for ${formatInsightValue(topTwoShare)}% of the distribution, so the current population is concentrated in a relatively narrow set of segments.`
            : `The top categories account for ${formatInsightValue(topTwoShare)}% of the distribution, which suggests a comparatively broad spread across the available segments.`);

        return insights;
    }

    function summarizeEmploymentChart(chart) {
        const labels = chart?.data?.labels || [];
        const dataset = chart?.data?.datasets?.[0] || null;
        const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];
        const total = values.reduce((sum, value) => sum + value, 0);

        if (!labels.length || !values.length || total === 0) {
            return ['No employment status records are available for this chart under the current filter set.'];
        }

        const ranked = labels.map((label, index) => ({
            label,
            value: values[index] ?? 0,
        })).sort((left, right) => right.value - left.value);

        const top = ranked[0];
        const topShare = calculatePercentage(top.value, total);
        const employedValue = ranked.find((item) => item.label.toLowerCase() === 'employed')?.value ?? 0;
        const selfEmployedValue = ranked.find((item) => item.label.toLowerCase() === 'self-employed')?.value ?? 0;
        const unemployedValue = ranked.find((item) => item.label.toLowerCase() === 'unemployed')?.value ?? 0;
        const unknownValue = ranked.find((item) => item.label.toLowerCase() === 'unknown')?.value ?? 0;
        const participationShare = calculatePercentage(employedValue + selfEmployedValue, total);
        const unemploymentShare = calculatePercentage(unemployedValue, total);
        const unknownShare = calculatePercentage(unknownValue, total);

        return [
            `The employment snapshot summarizes ${formatInsightValue(total)} alumni records, with workforce participation at ${formatInsightValue(participationShare)}% when employed and self-employed outcomes are combined.`,
            `${top.label} is currently the largest segment at ${formatInsightValue(top.value)} records or ${formatInsightValue(topShare)}% of the visible population, while unemployment stands at ${formatInsightValue(unemploymentShare)}%.`,
            unknownShare >= 15
                ? `Unknown employment outcomes account for ${formatInsightValue(unknownShare)}% of the total, so the employment picture would benefit from additional record updates before strategic decisions are finalized.`
                : `Unknown employment outcomes account for ${formatInsightValue(unknownShare)}%, which keeps the chart usable for planning while still encouraging continued record completion.`
        ];
    }

    function summarizeMultiDatasetTrend(chart) {
        const labels = chart?.data?.labels || [];
        const datasets = Array.isArray(chart?.data?.datasets) ? chart.data.datasets : [];

        if (!labels.length || !datasets.length) {
            return ['No segmented trend data is available for this chart under the current filter set.'];
        }

        const ranked = datasets.map((dataset) => {
            const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];
            const total = values.reduce((sum, value) => sum + value, 0);
            const peak = values.length ? Math.max(...values) : 0;
            const peakIndex = values.length ? values.indexOf(peak) : -1;

            return {
                label: dataset.label || 'Series',
                total,
                peak,
                peakLabel: peakIndex >= 0 ? labels[peakIndex] : null,
            };
        }).sort((left, right) => right.total - left.total);

        const top = ranked[0];
        const second = ranked[1] || null;
        const combinedTotal = ranked.reduce((sum, item) => sum + item.total, 0);
        const leaderShare = calculatePercentage(top.total, combinedTotal);
        const aggregateByPeriod = labels.map((label, index) => ({
            label,
            value: datasets.reduce((sum, dataset) => {
                const values = Array.isArray(dataset?.data) ? dataset.data : [];
                return sum + (Number(values[index]) || 0);
            }, 0),
        }));
        const peakPeriod = aggregateByPeriod.sort((left, right) => right.value - left.value)[0] || null;
        const insights = [
            `This chart compares ${ranked.length} tracked series across ${labels.length} periods and records a combined activity total of ${formatInsightValue(combinedTotal)}.`
        ];

        insights.push(`${top.label} contributes the strongest overall activity with a cumulative value of ${formatInsightValue(top.total)}${second ? `, ahead of ${second.label} at ${formatInsightValue(second.total)}` : ''}, representing ${formatInsightValue(leaderShare)}% of all tracked activity.`)

        insights.push(peakPeriod
            ? `Overall activity reaches its busiest point in ${peakPeriod.label} with ${formatInsightValue(peakPeriod.value)} combined records${top.peakLabel ? `, while the leading series itself peaks at ${formatInsightValue(top.peak)} in ${top.peakLabel}` : ''}.`
            : 'A clear aggregate peak period is not available for this comparison.');

        return insights;
    }

    function summarizeAverageRatingsChart(chart) {
        const labels = chart?.data?.labels || [];
        const dataset = chart?.data?.datasets?.[0] || null;
        const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];
        const validEntries = labels
            .map((label, index) => ({ label, value: values[index] ?? 0 }))
            .filter((entry) => entry.value > 0)
            .sort((left, right) => right.value - left.value);

        if (!validEntries.length) {
            return ['No rating data is available for this chart under the current filter set.'];
        }

        const average = validEntries.reduce((sum, entry) => sum + entry.value, 0) / validEntries.length;
        const top = validEntries[0];
        const lowest = validEntries[validEntries.length - 1];
        const spread = top.value - lowest.value;

        return [
            `This chart compares ${validEntries.length} rating items on a 1-5 scale, with an average of ${formatInsightValue(average)}.`,
            `${top.label} is the strongest rated area at ${formatInsightValue(top.value)}, while ${lowest.label} is the lowest rated area at ${formatInsightValue(lowest.value)}.`,
            spread >= 1
                ? `The ${formatInsightValue(spread)}-point gap suggests a clear priority area for review among the lower-rated satisfaction items.`
                : `The ${formatInsightValue(spread)}-point gap suggests the satisfaction ratings are relatively consistent across the measured areas.`
        ];
    }

    function summarizeRateTrend(chart) {
        const labels = chart?.data?.labels || [];
        const dataset = chart?.data?.datasets?.[0] || null;
        const values = Array.isArray(dataset?.data) ? dataset.data.map((value) => Number(value) || 0) : [];

        if (!labels.length || !values.length) {
            return ['No rate data is available for this chart under the current filter set.'];
        }

        const average = values.reduce((sum, value) => sum + value, 0) / values.length;
        const highest = Math.max(...values);
        const lowest = Math.min(...values);
        const highestIndex = values.indexOf(highest);
        const lowestIndex = values.indexOf(lowest);
        const latest = values[values.length - 1];
        const earliest = values[0];
        const change = latest - earliest;

        return [
            `This rate chart covers ${labels.length} graduation-year points, with an average rate of ${formatInsightValue(average)}%.`,
            `The strongest rate is ${formatInsightValue(highest)}% in ${labels[highestIndex] ?? 'the leading year'}, while the lowest rate is ${formatInsightValue(lowest)}% in ${labels[lowestIndex] ?? 'the softest year'}.`,
            change > 0
                ? `The latest listed year is ${formatInsightValue(change)} percentage points above the opening year, showing improvement across the selected range.`
                : change < 0
                    ? `The latest listed year is ${formatInsightValue(Math.abs(change))} percentage points below the opening year, so this area may need closer review.`
                    : 'The latest listed year matches the opening year, showing a stable rate across the selected range.'
        ];
    }

    function buildChartNarrative(chartId, chart) {
        switch (chartId) {
            case 'employmentTrendsChart':
                return summarizeMultiDatasetTrend(chart);
            case 'tracedGraduatesChart':
            case 'industryChart':
            case 'globalDistributionChart':
            case 'reasonsChart':
                return summarizeDistributionChart(chart);
            case 'employabilityChart':
                return summarizeRateTrend(chart);
            case 'userGrowthChart':
            case 'jobApplicationsChart':
            case 'graduationYearChart':
            case 'firstJobTimeChart':
                return summarizeSingleDatasetTrend(chart);
            case 'collegeDistributionChart':
            case 'feedbacksChart':
            case 'curriculumRelevanceChart':
            case 'presentEmploymentStatusChart':
            case 'facilitiesSatisfactionChart':
                return summarizeDistributionChart(chart);
            case 'employmentStatusChart':
                return summarizeEmploymentChart(chart);
            case 'teachingFeedbackChart':
            case 'overallExperienceChart':
                return summarizeAverageRatingsChart(chart);
            case 'industryGrowthChart':
                return summarizeMultiDatasetTrend(chart);
            default:
                return ['A live chart summary is not available for this visualization yet.'];
        }
    }

    function addWrappedText(pdf, textLines, startX, startY, maxWidth, lineHeight = 5) {
        let y = startY;

        textLines.forEach((line) => {
            const wrapped = pdf.splitTextToSize(line, maxWidth);
            pdf.text(wrapped, startX, y);
            y += wrapped.length * lineHeight;
        });

        return y;
    }

    async function exportAnalyticsAsPdf(now, selectedChartIds = chartOrder) {
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF exporter failed to load. Please refresh and try again.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const pageWidth = pdf.internal.pageSize.getWidth();
        const margin = 14;
        const contentWidth = pageWidth - (margin * 2);
        const reportDate = now.toLocaleDateString(undefined, {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });

        function drawPdfHeader() {
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(6, 57, 29);
            pdf.setFontSize(11);
            pdf.text('Central Mindanao University', margin, 10);
            pdf.setFont('helvetica', 'normal');
            pdf.setTextColor(98, 113, 104);
            pdf.setFontSize(8);
            pdf.text('University Town, Musuan, Bukidnon', margin, 15);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(6, 57, 29);
            pdf.text('Office of the Alumni Relations and Linkages (OARL)', pageWidth - margin, 15, { align: 'right' });

            pdf.setDrawColor(184, 200, 189);
            pdf.line(margin, 18, pageWidth - margin, 18);
            pdf.setFillColor(11, 59, 31);
            pdf.roundedRect(margin, 22, contentWidth, 12, 1.6, 1.6, 'F');
            pdf.setFillColor(200, 154, 45);
            pdf.rect(margin, 33.1, contentWidth, 0.9, 'F');
            pdf.setTextColor(255, 255, 255);
            pdf.setFontSize(14);
            pdf.text('CMU-F-A-ARL-003 TRACER FORM REPORT', margin + 4, 29.5);
            pdf.setTextColor(230, 238, 233);
            pdf.setFontSize(7);
            pdf.text(`DISSEMINATION DATE: ${reportDate}`, pageWidth - margin - 4, 29.5, { align: 'right' });
            pdf.setTextColor(17, 24, 39);
            pdf.setFont('helvetica', 'normal');
        }

        drawPdfHeader();

        const filterSummary = getCurrentFilterSummary();
        const metrics = [
            ['Total Users', String(analyticsState.totalUsers ?? 0)],
            ['Active Sessions', String(analyticsState.activeSessions ?? 0)],
            ['Job Submissions', String(analyticsState.jobApplications ?? 0)],
            ['Data Completeness', `${analyticsState.dataCompleteness ?? 0}%`],
            ['Employment Rate', `${analyticsState.employmentRate ?? 0}%`],
            ['Self-Employment Rate', `${analyticsState.selfEmploymentRate ?? 0}%`],
            ['Unemployment Rate', `${analyticsState.unemploymentRate ?? 0}%`]
        ];

        let y = 45;
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(13);
        pdf.text('GRADUATES TRACER DATA', margin, y);
        pdf.setFont('helvetica', 'normal');
        y += 8;

        const metricTiles = metrics.slice(0, 5);
        const tileGap = 8;
        const tileWidth = (contentWidth - (tileGap * (metricTiles.length - 1))) / metricTiles.length;
        const tileColors = [
            { soft: [255, 251, 235], strong: [183, 121, 31], border: [242, 223, 174] },
            { soft: [241, 248, 242], strong: [20, 87, 38], border: [207, 224, 210] },
            { soft: [239, 246, 255], strong: [37, 99, 235], border: [191, 212, 248] },
            { soft: [236, 254, 255], strong: [14, 116, 144], border: [183, 221, 228] },
            { soft: [248, 250, 252], strong: [100, 116, 139], border: [213, 220, 229] },
        ];

        metricTiles.forEach(([label, value], index) => {
            const x = margin + (index * (tileWidth + tileGap));
            const color = tileColors[index] || tileColors[0];
            pdf.setFillColor(...color.soft);
            pdf.setDrawColor(...color.border);
            pdf.roundedRect(x, y, tileWidth, 19, 1.4, 1.4, 'FD');
            pdf.setFillColor(...color.strong);
            pdf.rect(x, y, 1.4, 19, 'F');
            pdf.setTextColor(98, 113, 104);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(7);
            pdf.text(label.toUpperCase(), x + 4, y + 6);
            pdf.setTextColor(17, 24, 39);
            pdf.setFontSize(16);
            pdf.text(String(value), x + 4, y + 13);
            pdf.setTextColor(...color.strong);
            pdf.setFontSize(6);
            pdf.text('CURRENT FILTERED SCOPE', x + 4, y + 17);
        });

        y += 30;
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(12);
        pdf.text('Active Scope', margin, y);
        y += 6;
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(10);
        if (filterSummary.length) {
            y = addWrappedText(pdf, filterSummary.map((item) => `- ${item}`), margin, y, contentWidth, 4.6);
        } else {
            pdf.text('- Default analytics view with no extra filters applied.', margin, y);
            y += 5;
        }

        y += 3;
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(12);
        pdf.text('Overview', margin, y);
        y += 6;
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(10);
        const overviewNarrative = buildExecutiveOverviewNarrative(filterSummary);
        addWrappedText(pdf, overviewNarrative, margin, y, contentWidth, 4.8);

        for (const chartId of selectedChartIds) {
            const chart = analyticsCharts[chartId];
            if (!chart) {
                continue;
            }

            await waitForNextFrame();
            chart.update();
            const imageData = getHighResChartImage(chartId);
            if (!imageData) {
                continue;
            }

            pdf.addPage();
            drawPdfHeader();
            y = 45;

            const chartWidth = contentWidth;
            const chartHeight = 105;
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(17, 24, 39);
            pdf.setFontSize(13);
            pdf.text(chartTitles[chartId] || chartId, margin, y);
            y += 8;
            pdf.addImage(imageData, 'PNG', margin, y, chartWidth, chartHeight);
            y += chartHeight + 8;

            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(11);
            pdf.text('Generated Insights', margin, y);
            y += 5;
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(10);
            const insights = buildChartNarrative(chartId, chart).map((line) => `- ${line}`);
            addWrappedText(pdf, insights, margin, y, contentWidth, 4.8);
        }

        pdf.save(`arlo-tracer-report-${now.toISOString().slice(0, 10)}.pdf`);
    }

    window.toggleExportMenu = function (event) {
        event.stopPropagation();
        const menu = document.getElementById('analyticsExportMenu');
        if (!menu) {
            return;
        }

        menu.classList.toggle('hidden');
    };

    window.openAnalyticsExportOverview = function (format) {
        if (!exportFormatConfig[format] || analyticsExportInProgress) {
            return;
        }

        const menu = document.getElementById('analyticsExportMenu');
        if (menu) {
            menu.classList.add('hidden');
        }

        pendingAnalyticsExportFormat = format;
        syncAnalyticsExportChartSelection(getExportChartEntries(), true);
        updateAnalyticsExportOverview(format);
        setAnalyticsExportOverviewOpen(true);
    };

    window.closeAnalyticsExportOverview = function () {
        if (analyticsExportInProgress) {
            return;
        }

        pendingAnalyticsExportFormat = null;
        setAnalyticsExportOverviewOpen(false);
    };

    async function runAnalyticsExport(format) {
        const now = new Date();
        const menu = document.getElementById('analyticsExportMenu');
        if (menu) {
            menu.classList.add('hidden');
        }

        const chartEntries = getExportChartEntries();
        syncAnalyticsExportChartSelection(chartEntries);
        const selectedChartIds = getSelectedExportChartIds(chartEntries);

        setAnalyticsExportBusyState(true, format);

        try {
            if (format === 'excel') {
                exportAnalyticsAsExcel(now, selectedChartIds);
                return;
            }

            if (format === 'csv') {
                exportAnalyticsAsCsv(now, selectedChartIds);
                return;
            }

            await exportAnalyticsAsPdf(now, selectedChartIds);
        } finally {
            setAnalyticsExportBusyState(false, format);
        }
    }

    window.confirmAnalyticsExport = async function () {
        if (!pendingAnalyticsExportFormat || analyticsExportInProgress) {
            return;
        }

        const format = pendingAnalyticsExportFormat;

        try {
            await runAnalyticsExport(format);
            pendingAnalyticsExportFormat = null;
            setAnalyticsExportOverviewOpen(false);
        } catch (error) {
            console.error('Analytics export failed:', error);
            updateAnalyticsExportOverview(format);
            alert('Unable to complete the export right now. Please try again.');
        }
    };

    window.exportAnalyticsReport = runAnalyticsExport;

    window.exportAnalyticsCsvReport = function (report) {
        if (!['employability', 'satisfaction'].includes(report)) {
            return;
        }

        const menu = document.getElementById('analyticsExportMenu');
        if (menu) {
            menu.classList.add('hidden');
        }

        const exportUrl = new URL(
            analyticsCsvExportEndpoint.replace('__REPORT__', report),
            window.location.origin
        );
        const form = currentFilterForm();
        const filters = form
            ? collectFilters(form)
            : (analyticsState?.filters || {});

        serializeFilters(filters).forEach((value, key) => exportUrl.searchParams.set(key, value));
        window.location.href = exportUrl.toString();
    };

    document.addEventListener('click', function (event) {
        const menu = document.getElementById('analyticsExportMenu');
        const button = document.getElementById('analyticsExportBtn');
        if (!menu || !button) {
            return;
        }

        if (!menu.contains(event.target) && !button.contains(event.target)) {
            menu.classList.add('hidden');
        }
    }, { signal: cleanupController.signal });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        const exportOverviewModal = document.getElementById('analyticsExportOverviewModal');
        if (exportOverviewModal && !exportOverviewModal.classList.contains('hidden')) {
            closeAnalyticsExportOverview();
            return;
        }

        const menu = document.getElementById('analyticsExportMenu');
        if (menu) {
            menu.classList.add('hidden');
        }
    }, { signal: cleanupController.signal });

    function currentFilterForm() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-filter-form]') ?? null;
    }

    function currentFilterFeedback() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-filter-feedback]') ?? null;
    }

    function currentVisualizations() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-visualizations]') ?? null;
    }

    function currentApplyButton() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-apply-button]') ?? null;
    }

    function currentApplyLabel() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-apply-label]') ?? null;
    }

    function currentApplyIcon() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-apply-icon]') ?? null;
    }

    function currentFilterInputs() {
        return analyticsContentContainer
            ? Array.from(analyticsContentContainer.querySelectorAll('[data-admin-analytics-filter-input], [data-filter-more-toggle], [data-filter-groups-more-toggle], [data-filter-option-search], [data-filter-option-clear], [data-filter-option-suggestion]'))
            : [];
    }

    function currentResetLink() {
        return analyticsContentContainer?.querySelector('[data-admin-analytics-reset-link]') ?? null;
    }

    function currentFilterPanel() {
        return analyticsContentContainer?.querySelector('#analytics-filter-panel') ?? null;
    }

    function setFilterGroupsExpanded(expanded) {
        const form = currentFilterForm();
        const moreContent = form?.querySelector('[data-filter-groups-more-content]');
        const toggle = form?.querySelector('[data-filter-groups-more-toggle]');
        const label = toggle?.querySelector('[data-filter-groups-more-label]');

        if (!moreContent || !toggle || !label) {
            return;
        }

        moreContent.hidden = !expanded;
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        label.textContent = expanded ? 'Less filters' : 'More filters';
    }

    function setFilterGroupExpanded(group, expanded) {
        const moreContent = group?.querySelector('[data-filter-more-content]');
        const toggle = group?.querySelector('[data-filter-more-toggle]');
        const label = toggle?.querySelector('[data-filter-more-label]');

        if (!group || !moreContent || !toggle || !label) {
            return;
        }

        moreContent.hidden = !expanded;
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        label.textContent = expanded ? 'Less' : 'More';
    }

    function normalizeFilterOptionSearch(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function filterOptionData(option) {
        const input = option?.querySelector('input[type="radio"]');
        const label = option?.getAttribute('data-filter-option-label')
            || option?.querySelector('.analytics-filter-option__label')?.textContent
            || option?.textContent
            || '';

        return {
            option,
            input,
            label: String(label).trim(),
            value: String(input?.value || ''),
            name: String(input?.name || option?.getAttribute('data-filter-option-name') || ''),
            normalizedLabel: normalizeFilterOptionSearch(label),
            isAllOption: !String(input?.value || '').trim(),
            isSelected: Boolean(input?.checked),
        };
    }

    function renderFilterOptionSuggestions(group, searchInput, matches) {
        const suggestions = group?.querySelector('[data-filter-option-suggestions]');

        if (!suggestions) {
            return;
        }

        suggestions.replaceChildren();

        if (!searchInput || matches.length === 0) {
            suggestions.hidden = true;
            searchInput?.setAttribute('aria-expanded', 'false');
            searchInput?.removeAttribute('aria-activedescendant');
            return;
        }

        let selectedIndex = -1;

        matches.slice(0, 6).forEach((match, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.id = `${searchInput.id || 'analyticsFilterSearch'}Suggestion${index}`;
            button.className = `analytics-filter-search__suggestion${match.isSelected ? ' is-selected' : ''}`;
            button.dataset.filterOptionSuggestion = 'true';
            button.dataset.filterOptionValue = match.value;
            button.dataset.filterOptionLabel = match.label;
            button.dataset.filterOptionName = match.name;
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', match.isSelected ? 'true' : 'false');

            if (match.isSelected) {
                selectedIndex = index;
            }

            const icon = document.createElement('i');
            icon.className = match.isSelected ? 'bx bx-check-circle' : 'bx bx-list-check';
            icon.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.textContent = match.label;

            button.append(icon, label);
            suggestions.append(button);
        });

        suggestions.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');

        if (selectedIndex >= 0) {
            setActiveFilterOptionSuggestion(group, searchInput, selectedIndex);
        } else {
            searchInput.removeAttribute('aria-activedescendant');
        }
    }

    function filterOptionSuggestionButtons(group) {
        return Array.from(group?.querySelectorAll('[data-filter-option-suggestion]') || []);
    }

    function setActiveFilterOptionSuggestion(group, searchInput, nextIndex) {
        const suggestions = filterOptionSuggestionButtons(group);

        if (!searchInput || suggestions.length === 0) {
            searchInput?.removeAttribute('aria-activedescendant');
            return null;
        }

        const normalizedIndex = ((nextIndex % suggestions.length) + suggestions.length) % suggestions.length;

        suggestions.forEach((suggestion, index) => {
            const isActive = index === normalizedIndex;
            const isSelected = suggestion.classList.contains('is-selected');

            suggestion.classList.toggle('is-active', isActive);
            suggestion.setAttribute('aria-selected', isActive || isSelected ? 'true' : 'false');
        });

        const activeSuggestion = suggestions[normalizedIndex];
        searchInput.setAttribute('aria-activedescendant', activeSuggestion.id);
        activeSuggestion.scrollIntoView({ block: 'nearest', inline: 'nearest' });

        return activeSuggestion;
    }

    function activeFilterOptionSuggestion(group) {
        return group?.querySelector('[data-filter-option-suggestion].is-active')
            || group?.querySelector('[data-filter-option-suggestion].is-selected')
            || null;
    }

    function closeFilterOptionSuggestions(searchInput) {
        const group = searchInput?.closest('[data-filter-group]');
        const suggestions = group?.querySelector('[data-filter-option-suggestions]');

        if (!searchInput || !suggestions || suggestions.hidden) {
            return false;
        }

        suggestions.hidden = true;
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.removeAttribute('aria-activedescendant');
        suggestions.querySelectorAll('[data-filter-option-suggestion]').forEach((suggestion) => {
            suggestion.classList.remove('is-active');
        });

        return true;
    }

    function syncFilterOptionClearButton(searchInput) {
        const clearButton = searchInput
            ?.closest('[data-filter-search-wrap]')
            ?.querySelector('[data-filter-option-clear]');

        if (!clearButton) {
            return;
        }

        clearButton.hidden = !String(searchInput.value || '').trim();
    }

    function applyFilterOptionSearch(searchInput) {
        const group = searchInput?.closest('[data-filter-group]');
        if (!group) {
            return;
        }

        syncFilterOptionClearButton(searchInput);

        const searchTerm = normalizeFilterOptionSearch(searchInput.value);
        const options = Array.from(group.querySelectorAll('[data-filter-option]'));
        const moreContent = group.querySelector('[data-filter-more-content]');
        const toggle = group.querySelector('[data-filter-more-toggle]');
        const emptyState = group.querySelector('[data-filter-option-empty]');
        const suggestionMatches = [];

        if (searchTerm && moreContent) {
            moreContent.hidden = false;
        } else if (moreContent && toggle) {
            moreContent.hidden = toggle.getAttribute('aria-expanded') !== 'true';
        }

        if (toggle) {
            toggle.hidden = Boolean(searchTerm);
        }

        options.forEach((option) => {
            const optionData = filterOptionData(option);
            const isMatch = !searchTerm || optionData.isAllOption || optionData.normalizedLabel.includes(searchTerm);
            const isSuggestionMatch = Boolean(searchTerm) && optionData.normalizedLabel.includes(searchTerm);

            option.hidden = !isMatch;

            if (isSuggestionMatch) {
                suggestionMatches.push(optionData);
            }
        });

        renderFilterOptionSuggestions(group, searchInput, suggestionMatches);

        if (emptyState) {
            emptyState.hidden = !searchTerm || suggestionMatches.length > 0;
        }
    }

    function selectFilterOptionSuggestion(suggestion) {
        const group = suggestion?.closest('[data-filter-group]');
        const searchInput = group?.querySelector('[data-filter-option-search]');
        const suggestions = group?.querySelector('[data-filter-option-suggestions]');
        const value = String(suggestion?.getAttribute('data-filter-option-value') || '');
        const label = String(suggestion?.getAttribute('data-filter-option-label') || '');
        const name = String(suggestion?.getAttribute('data-filter-option-name') || '');
        const input = Array.from(group?.querySelectorAll('input[type="radio"]') || [])
            .find((candidate) => String(candidate.name || '') === name && String(candidate.value || '') === value);

        if (!input) {
            return;
        }

        input.checked = true;

        if (searchInput) {
            searchInput.value = value ? label : '';
            applyFilterOptionSearch(searchInput);
            searchInput.setAttribute('aria-expanded', 'false');
            searchInput.focus({ preventScroll: true });
        }

        if (suggestions) {
            suggestions.replaceChildren();
            suggestions.hidden = true;
        }

        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function captureFilterViewportState(trigger = null) {
        const form = currentFilterForm();
        const panel = currentFilterPanel();
        const activeElement = document.activeElement;
        const activeFilterInput = activeElement?.matches?.('[data-admin-analytics-filter-input]')
            ? activeElement
            : (trigger?.matches?.('[data-admin-analytics-filter-input]') ? trigger : null);
        const groupExpansionState = {};

        form?.querySelectorAll('[data-filter-group]').forEach((group) => {
            const targetName = group.getAttribute('data-filter-jump-target') || group.id || '';
            const toggle = group.querySelector('[data-filter-more-toggle]');

            if (!targetName || !toggle) {
                return;
            }

            groupExpansionState[targetName] = toggle.getAttribute('aria-expanded') === 'true';
        });

        const groupsToggle = form?.querySelector('[data-filter-groups-more-toggle]');

        return {
            windowTop: Math.max(0, Math.round(window.scrollY || 0)),
            panelTop: Math.max(0, Math.round(panel?.scrollTop || 0)),
            panelLeft: Math.max(0, Math.round(panel?.scrollLeft || 0)),
            hasGroupsToggle: Boolean(groupsToggle),
            groupsExpanded: groupsToggle?.getAttribute('aria-expanded') === 'true',
            groupExpansionState,
            activeFilter: activeFilterInput
                ? {
                    name: activeFilterInput.name,
                    value: activeFilterInput.value,
                }
                : null,
        };
    }

    function applyFilterViewportState(state) {
        if (!state || !analyticsContentContainer) {
            return;
        }

        const form = currentFilterForm();

        if (state.hasGroupsToggle) {
            setFilterGroupsExpanded(Boolean(state.groupsExpanded));
        }

        Object.entries(state.groupExpansionState || {}).forEach(([targetName, expanded]) => {
            const group = Array.from(form?.querySelectorAll('[data-filter-group]') || [])
                .find((candidate) => (candidate.getAttribute('data-filter-jump-target') || candidate.id || '') === targetName);

            setFilterGroupExpanded(group, Boolean(expanded));
        });

        const nextPanel = currentFilterPanel();

        if (nextPanel) {
            nextPanel.scrollTop = Number(state.panelTop) || 0;
            nextPanel.scrollLeft = Number(state.panelLeft) || 0;
        }

        if (Number.isFinite(Number(state.windowTop))) {
            window.scrollTo({ top: Number(state.windowTop) || 0, left: 0, behavior: 'auto' });
        }

        if (!state.activeFilter) {
            return;
        }

        const nextActiveInput = Array.from(currentFilterForm()?.querySelectorAll('[data-admin-analytics-filter-input]') || [])
            .find((input) => input.name === state.activeFilter.name && input.value === state.activeFilter.value);

        nextActiveInput?.focus({ preventScroll: true });
    }

    function restoreFilterViewportState(state) {
        applyFilterViewportState(state);
        window.requestAnimationFrame(() => applyFilterViewportState(state));
        window.setTimeout(() => applyFilterViewportState(state), 60);
        window.setTimeout(() => applyFilterViewportState(state), 180);
    }

    function highlightFilterTarget(target) {
        target.classList.remove('is-jump-target');
        void target.offsetWidth;
        target.classList.add('is-jump-target');

        window.setTimeout(() => {
            target.classList.remove('is-jump-target');
        }, 1800);
    }

    function scrollFilterPanelIntoView(panel) {
        const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
        const behavior = motionPreference.matches ? 'auto' : 'smooth';
        const panelRect = panel.getBoundingClientRect();
        const panelIsVisible = panelRect.bottom > 0 && panelRect.top < window.innerHeight;

        if (!panelIsVisible) {
            window.scrollTo({
                top: Math.max(0, window.scrollY + panelRect.top - 22),
                behavior,
            });
        }

        if (typeof panel.scrollTo === 'function') {
            panel.scrollTo({
                top: 0,
                behavior,
            });
        } else {
            panel.scrollTop = 0;
        }
    }

    function jumpToFilterTarget(targetName = 'filters') {
        if (!analyticsContentContainer) {
            return;
        }

        const normalizedTargetName = String(targetName || 'filters');
        const target = analyticsContentContainer.querySelector(`[data-filter-jump-target="${normalizedTargetName}"]`);

        if (!target) {
            return;
        }

        if (target.closest('[data-filter-groups-more-content]')) {
            setFilterGroupsExpanded(true);
        }

        window.requestAnimationFrame(() => {
            const targetId = target.id || 'analytics-filter-panel';
            const isFilterPanel = normalizedTargetName === 'filters';

            if (isFilterPanel) {
                scrollFilterPanelIntoView(target);
            } else {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest',
                });
            }

            target.focus({ preventScroll: true });
            highlightFilterTarget(target);
            window.history.replaceState(window.history.state, '', `#${targetId}`);
        });
    }

    function jumpToInitialFilterHash() {
        const hash = window.location.hash.replace(/^#/, '');

        if (!hash.startsWith('analytics-filter-')) {
            return;
        }

        const target = analyticsContentContainer?.querySelector(`#${hash}`);

        if (!target) {
            return;
        }

        jumpToFilterTarget(target.getAttribute('data-filter-jump-target') || 'filters');
    }

    function requestedInitialChartId() {
        const params = new URLSearchParams(window.location.search);
        const targetChart = String(params.get('target_chart') || '').trim();
        const hash = window.location.hash.replace(/^#/, '');

        return targetChart || (hash.startsWith('analytics-filter-') ? '' : hash);
    }

    function jumpToChartTarget(chartId, attempt = 0) {
        const normalizedChartId = String(chartId || '').trim();

        if (!normalizedChartId) {
            return;
        }

        const target = document.getElementById(normalizedChartId);
        const chartCard = target?.closest('.analytics-chart-card');

        if (!chartCard || !analyticsContentContainer?.contains(chartCard)) {
            if (attempt < 8) {
                window.setTimeout(() => jumpToChartTarget(normalizedChartId, attempt + 1), 90);
            }

            return;
        }

        window.setTimeout(() => {
            chartCard.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest',
            });
            highlightFilterTarget(chartCard);
        }, attempt === 0 ? 80 : 0);
    }

    function jumpToInitialChartTarget() {
        jumpToChartTarget(requestedInitialChartId());
    }

    function jumpToInitialHash() {
        jumpToInitialFilterHash();
        jumpToInitialChartTarget();
    }

    function setFilterFeedback(message, isError = false) {
        const feedback = currentFilterFeedback();
        if (!feedback) {
            return;
        }

        const shouldShow = Boolean(message) && isError;
        feedback.textContent = shouldShow ? message : '';
        feedback.classList.toggle('hidden', !shouldShow);
        feedback.classList.toggle('text-red-600', shouldShow);
        feedback.classList.remove('text-emerald-700/80');
    }

    function setLoadingState(isLoading, source = 'filters') {
        const applyButton = currentApplyButton();
        const applyLabel = currentApplyLabel();
        const applyIcon = currentApplyIcon();
        const visualizations = currentVisualizations();

        if (applyButton) {
            applyButton.disabled = isLoading;
        }

        if (applyLabel) {
            applyLabel.textContent = isLoading && source === 'filters'
                ? 'Updating...'
                : 'Apply Filters';
        }

        if (applyIcon) {
            applyIcon.classList.toggle('bx-spin', isLoading && source === 'filters');
        }

        if (visualizations) {
            visualizations.classList.toggle('opacity-60', isLoading);
            visualizations.classList.toggle('pointer-events-none', isLoading);
        }

        currentFilterInputs().forEach((element) => {
            element.disabled = isLoading;
        });

        const resetLink = currentResetLink();
        if (resetLink) {
            resetLink.classList.toggle('pointer-events-none', isLoading);
            resetLink.classList.toggle('opacity-60', isLoading);
            resetLink.setAttribute('aria-disabled', isLoading ? 'true' : 'false');
        }
    }

    function normalizedChartSelection(charts) {
        return (charts || [])
            .map((chart) => String(chart || '').trim())
            .filter((chart, index, list) => chart !== '' && list.indexOf(chart) === index);
    }

    function isDefaultChartSelection(charts) {
        const selectedCharts = normalizedChartSelection(charts);

        return selectedCharts.length === defaultChartOrder.length
            && defaultChartOrder.every((chartId) => selectedCharts.includes(chartId));
    }

    function serializeFilters(filters) {
        const params = new URLSearchParams();

        if (String(filters.date_from || '').trim() !== '') {
            params.set('date_from', String(filters.date_from).trim());
        }

        if (String(filters.date_to || '').trim() !== '') {
            params.set('date_to', String(filters.date_to).trim());
        }

        if (String(filters.college || '').trim() !== '') {
            params.set('college', String(filters.college).trim());
        }

        if (String(filters.degree || '').trim() !== '') {
            params.set('degree', String(filters.degree).trim());
        }

        if (String(filters.sex || '').trim() !== '') {
            params.set('sex', String(filters.sex).trim());
        }

        if (String(filters.civil_status || '').trim() !== '') {
            params.set('civil_status', String(filters.civil_status).trim());
        }

        if (String(filters.age_from || '').trim() !== '') {
            params.set('age_from', String(filters.age_from).trim());
        }

        if (String(filters.age_to || '').trim() !== '') {
            params.set('age_to', String(filters.age_to).trim());
        }

        if (String(filters.graduation_year || '').trim() !== '') {
            params.set('graduation_year', String(filters.graduation_year).trim());
        }

        if (String(filters.graduation_year_from || '').trim() !== '') {
            params.set('graduation_year_from', String(filters.graduation_year_from).trim());
        }

        if (String(filters.graduation_year_to || '').trim() !== '') {
            params.set('graduation_year_to', String(filters.graduation_year_to).trim());
        }

        if (String(filters.employment_status || '').trim() !== '') {
            params.set('employment_status', String(filters.employment_status).trim());
        }

        if (String(filters.industry || '').trim() !== '') {
            params.set('industry', String(filters.industry).trim());
        }

        if (String(filters.location || '').trim() !== '') {
            params.set('location', String(filters.location).trim());
        }

        if (String(filters.salary_range || '').trim() !== '') {
            params.set('salary_range', String(filters.salary_range).trim());
        }

        const selectedCharts = normalizedChartSelection(filters.charts || []);

        if (!isDefaultChartSelection(selectedCharts)) {
            selectedCharts.forEach((chart) => {
                params.append('charts[]', chart);
            });
        }

        return params;
    }

    function collectFilters(form) {
        const formData = new FormData(form);

        return {
            date_from: String(formData.get('date_from') || ''),
            date_to: String(formData.get('date_to') || ''),
            college: String(formData.get('college') || ''),
            degree: String(formData.get('degree') || ''),
            sex: String(formData.get('sex') || ''),
            civil_status: String(formData.get('civil_status') || ''),
            age_from: String(formData.get('age_from') || ''),
            age_to: String(formData.get('age_to') || ''),
            graduation_year: String(formData.get('graduation_year') || ''),
            graduation_year_from: String(formData.get('graduation_year_from') || ''),
            graduation_year_to: String(formData.get('graduation_year_to') || ''),
            employment_status: String(formData.get('employment_status') || ''),
            industry: String(formData.get('industry') || ''),
            location: String(formData.get('location') || ''),
            salary_range: String(formData.get('salary_range') || ''),
            charts: formData.getAll('charts[]').map((value) => String(value || '')),
        };
    }

    function programCatalogForForm(form) {
        if (!form?.dataset?.programCatalog) {
            return {};
        }

        try {
            return JSON.parse(form.dataset.programCatalog);
        } catch (error) {
            return {};
        }
    }

    function allProgramsFromCatalog(catalog) {
        const seen = new Set();
        const programs = [];

        Object.values(catalog || {}).forEach((list) => {
            (Array.isArray(list) ? list : []).forEach((program) => {
                const label = String(program || '').trim();
                const key = label.toLowerCase();
                if (label && !seen.has(key)) {
                    seen.add(key);
                    programs.push(label);
                }
            });
        });

        return programs;
    }

    function updateProgramOptionsForCollege(form, clearSelection = false) {
        const collegeSelect = form?.querySelector('[data-college-filter-select]');
        const programSelect = form?.querySelector('[data-program-filter-select]');

        if (!collegeSelect || !programSelect) {
            return;
        }

        const catalog = programCatalogForForm(form);
        const college = String(collegeSelect.value || '').trim();
        const programs = college ? (catalog[college] || []) : allProgramsFromCatalog(catalog);
        const currentValue = clearSelection ? '' : String(programSelect.value || programSelect.dataset.selectedProgram || '');

        programSelect.replaceChildren(new Option('All Programs', ''));

        programs.forEach((program) => {
            const label = String(program || '').trim();
            if (!label) {
                return;
            }

            programSelect.appendChild(new Option(label, label, false, label === currentValue));
        });

        if (![...programSelect.options].some((option) => option.value === currentValue)) {
            programSelect.value = '';
        } else {
            programSelect.value = currentValue;
        }
    }

    async function fetchAdminAnalytics(filters, options = {}) {
        const form = currentFilterForm();
        if (!analyticsContentContainer || !form) {
            return;
        }

        const {
            forceRefresh = false,
            source = 'filters',
            trigger = null,
        } = options;
        const preserveFilterViewport = source === 'filters' || source === 'reset';
        const filterViewportState = preserveFilterViewport ? captureFilterViewportState(trigger) : null;

        activeRequestToken += 1;
        const requestToken = activeRequestToken;

        if (activeRequestController) {
            activeRequestController.abort();
        }

        activeRequestController = new AbortController();
        const url = new URL(analyticsEndpoint, window.location.origin);
        url.search = serializeFilters(filters).toString();
        if (forceRefresh) {
            url.searchParams.set('force_refresh', '1');
        }

        setLoadingState(true, source);
        setFilterFeedback('');

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeRequestController.signal,
            });

            if (!response.ok) {
                throw new Error(`Admin analytics filter request failed with status ${response.status}.`);
            }

            const payload = await response.json();
            analyticsContentContainer.innerHTML = payload.html || '';
            initializeAnalyticsCharts();
            if (preserveFilterViewport) {
                restoreFilterViewportState(filterViewportState);
            }
            const nextUrl = new URL(analyticsEndpoint, window.location.origin);
            nextUrl.search = serializeFilters(payload.filters || filters).toString();
            const requestedChart = requestedInitialChartId();
            const shouldRestoreChartTarget = !preserveFilterViewport && requestedChart;

            if (shouldRestoreChartTarget) {
                nextUrl.searchParams.set('target_chart', requestedChart);
                nextUrl.hash = requestedChart;
            }

            window.history.replaceState({ filters: payload.filters || filters }, '', nextUrl);
            if (shouldRestoreChartTarget) {
                jumpToChartTarget(requestedChart);
            }
            setFilterFeedback('');
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);
            setFilterFeedback('Could not update admin analytics right now. Please try again.', true);
        } finally {
            if (requestToken === activeRequestToken) {
                setLoadingState(false, source);
                activeRequestController = null;
            }
        }
    }

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-admin-analytics-filter-form]');
        if (!form) {
            return;
        }

        event.preventDefault();
        fetchAdminAnalytics(collectFilters(form), { source: 'filters', trigger: document.activeElement });
    }, { signal: cleanupController.signal });

    document.addEventListener('change', function (event) {
        const input = event.target.closest('[data-admin-analytics-filter-input]');
        if (!input) {
            return;
        }

        const form = input.closest('[data-admin-analytics-filter-form]');
        if (!form) {
            return;
        }

        if (input.matches('[data-college-filter-select]')) {
            updateProgramOptionsForCollege(form, true);
        }

        window.clearTimeout(filterChangeTimeoutId);
        filterChangeTimeoutId = window.setTimeout(() => {
            fetchAdminAnalytics(collectFilters(form), { source: 'filters', trigger: input });
        }, 120);
    }, { signal: cleanupController.signal });

    document.addEventListener('input', function (event) {
        const searchInput = event.target.closest('[data-filter-option-search]');
        if (!searchInput || !analyticsContentContainer?.contains(searchInput)) {
            return;
        }

        applyFilterOptionSearch(searchInput);
    }, { signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const clearButton = event.target.closest('[data-filter-option-clear]');
        if (!clearButton || !analyticsContentContainer?.contains(clearButton)) {
            return;
        }

        event.preventDefault();

        const searchInput = clearButton
            .closest('[data-filter-search-wrap]')
            ?.querySelector('[data-filter-option-search]');

        if (!searchInput) {
            return;
        }

        searchInput.value = '';
        applyFilterOptionSearch(searchInput);
        closeFilterOptionSuggestions(searchInput);
        searchInput.focus({ preventScroll: true });
    }, { signal: cleanupController.signal });

    document.addEventListener('keydown', function (event) {
        const searchInput = event.target.closest('[data-filter-option-search]');
        if (!searchInput || !analyticsContentContainer?.contains(searchInput)) {
            return;
        }

        const navigationKeys = ['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft', 'Enter', 'Escape'];
        if (!navigationKeys.includes(event.key)) {
            return;
        }

        const group = searchInput.closest('[data-filter-group]');
        const suggestionsWrap = group?.querySelector('[data-filter-option-suggestions]');

        if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && suggestionsWrap?.hidden) {
            applyFilterOptionSearch(searchInput);
        }

        const suggestions = filterOptionSuggestionButtons(group);
        const activeSuggestion = activeFilterOptionSuggestion(group);
        const activeIndex = suggestions.indexOf(activeSuggestion);

        if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && suggestions.length > 0) {
            event.preventDefault();
            const nextIndex = event.key === 'ArrowDown'
                ? activeIndex + 1
                : (activeIndex >= 0 ? activeIndex - 1 : suggestions.length - 1);

            setActiveFilterOptionSuggestion(group, searchInput, nextIndex);
            return;
        }

        if (event.key === 'Enter' || event.key === 'ArrowRight') {
            const suggestion = activeSuggestion || suggestions[0];

            if (!suggestion) {
                return;
            }

            event.preventDefault();
            selectFilterOptionSuggestion(suggestion);
            return;
        }

        if (event.key === 'Escape' || event.key === 'ArrowLeft') {
            if (closeFilterOptionSuggestions(searchInput)) {
                event.preventDefault();
            }
        }
    }, { signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const suggestion = event.target.closest('[data-filter-option-suggestion]');
        if (!suggestion || !analyticsContentContainer?.contains(suggestion)) {
            return;
        }

        event.preventDefault();
        selectFilterOptionSuggestion(suggestion);
    }, { signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const jumpTrigger = event.target.closest('[data-filter-jump]');
        if (!jumpTrigger || !analyticsContentContainer?.contains(jumpTrigger)) {
            return;
        }

        event.preventDefault();
        jumpToFilterTarget(jumpTrigger.getAttribute('data-filter-jump'));
    }, { signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const resetLink = event.target.closest('[data-admin-analytics-reset-link]');
        if (!resetLink) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        fetchAdminAnalytics({
            date_from: '',
            date_to: '',
            college: '',
            degree: '',
            sex: '',
            civil_status: '',
            age_from: '',
            age_to: '',
            graduation_year: '',
            graduation_year_from: '',
            graduation_year_to: '',
            employment_status: '',
            industry: '',
            location: '',
            salary_range: '',
            charts: [],
        }, { source: 'reset', trigger: resetLink });
    }, { capture: true, signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-filter-more-toggle]');
        if (!toggle) {
            return;
        }

        const group = toggle.closest('[data-filter-group]');
        const moreContent = group?.querySelector('[data-filter-more-content]');
        const label = toggle.querySelector('[data-filter-more-label]');
        if (!group || !moreContent || !label) {
            return;
        }

        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setFilterGroupExpanded(group, !isExpanded);
    }, { signal: cleanupController.signal });

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-filter-groups-more-toggle]');
        if (!toggle) {
            return;
        }

        const form = toggle.closest('[data-admin-analytics-filter-form]');
        const moreContent = form?.querySelector('[data-filter-groups-more-content]');
        const label = toggle.querySelector('[data-filter-groups-more-label]');
        if (!form || !moreContent || !label) {
            return;
        }

        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setFilterGroupsExpanded(!isExpanded);
    }, { signal: cleanupController.signal });

    window.__portalPageCleanup = () => {
        if (activeRequestController) {
            activeRequestController.abort();
        }

        window.clearTimeout(filterChangeTimeoutId);
        cleanupController.abort();
        destroyAnalyticsCharts();
    };

    initializeAnalyticsCharts();
    window.setTimeout(jumpToInitialHash, 0);
    };

    document.addEventListener('admin:page-ready', initializeAdminAnalyticsPage, { once: true });
})();
</script>
@endsection
