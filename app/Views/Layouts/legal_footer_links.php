<?php $footerVariant = $footer_variant ?? 'compact'; ?>
<style>
    .site-legal-footer {
        border-top: 1px solid #D9ECE5;
        margin-top: 24px;
        padding: 18px 20px 10px;
        background: #ffffff;
    }

    .site-legal-footer__inner {
        max-width: 860px;
        margin: 0 auto;
        display: grid;
        justify-items: center;
        gap: 10px;
        color: #5F7288;
        font-size: 0.92rem;
        line-height: 1.65;
    }

    .site-legal-footer__copy {
        color: #5F7288;
        font-weight: 500;
        text-align: center;
    }

    .site-legal-footer__links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 10px;
    }

    .site-legal-footer__links a {
        display: inline-flex;
        align-items: center;
        min-height: 36px;
        padding: 0 14px;
        border: 1px solid #D9ECE5;
        border-radius: 999px;
        background: #ffffff;
        color: #0D8A90 !important;
        font-weight: 600;
        line-height: 1;
        text-decoration: none !important;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .site-legal-footer__links a:hover {
        background: #EEF9F5;
        border-color: #BFE6DE;
        color: #0B6F75 !important;
        transform: translateY(-1px);
    }

    @media (max-width: 767.98px) {
        .site-legal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }
    }

    @media (prefers-color-scheme: dark) {
        .site-legal-footer {
            border-top-color: #23343A;
<<<<<<< HEAD
            background: #0d1117;
=======
            background: #111111 !important;
>>>>>>> a45b839080c0d98ed1b38aefe937538e7e0d2a9b
        }

        .site-legal-footer__inner {
            color: #94A3B8;
        }

        .site-legal-footer__copy {
            color: #94A3B8;
        }

        .site-legal-footer__links a {
            background: #11161d;
            border-color: #23343A;
            color: #7EE3DD !important;
        }

        .site-legal-footer__links a:hover {
            background: #152027;
            border-color: #23343A;
            color: #7EE3DD !important;
        }
    }

    .site-legal-footer--landing {
        border-top: 0;
        background: transparent;
        padding: 24px 20px 10px;
    }

    .site-legal-footer--landing .site-legal-footer__inner {
        max-width: 1180px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px 24px;
        padding: 14px 18px;
        border-radius: 22px;
<<<<<<< HEAD
        background: linear-gradient(135deg, #ffffff 0%, #f4fbfa 100%);
=======
        background: #ffffff !important;
>>>>>>> a45b839080c0d98ed1b38aefe937538e7e0d2a9b
        border: 1px solid #d9ece5;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
    }

    .site-legal-footer--landing .site-legal-footer__copy {
        color: #16212B;
        text-align: left;
        white-space: nowrap;
    }

    .site-legal-footer--landing .site-legal-footer__links {
        gap: 8px;
    }

    .site-legal-footer--landing .site-legal-footer__links a {
        min-height: 34px;
        padding: 0 12px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #16212B !important;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .site-legal-footer--landing .site-legal-footer__links a:hover {
        background: #e8f7f7;
        color: #0d8a90 !important;
        transform: none;
    }

    @media (prefers-color-scheme: dark) {
<<<<<<< HEAD
        .site-legal-footer--landing .site-legal-footer__inner {
            background: #11161d;
            border-color: #23343A;
            box-shadow: none;
        }
=======
       .site-legal-footer.site-legal-footer--landing {
        background: #111111 !important;
    }

    .site-legal-footer--landing .site-legal-footer__inner {
        background: #111111 !important;
        border-color: #23343A;
        box-shadow: none;
    }
>>>>>>> a45b839080c0d98ed1b38aefe937538e7e0d2a9b

        .site-legal-footer--landing .site-legal-footer__copy {
            color: #E2E8F0;
        }

        .site-legal-footer--landing .site-legal-footer__links a {
            color: #E2E8F0 !important;
        }

        .site-legal-footer--landing .site-legal-footer__links a:hover {
            background: rgba(31, 183, 181, 0.14);
            color: #7EE3DD !important;
        }
    }

    @media (max-width: 767.98px) {
        .site-legal-footer--landing .site-legal-footer__inner {
            flex-direction: column;
            justify-content: center;
            border-radius: 18px;
            padding: 14px;
        }

        .site-legal-footer--landing .site-legal-footer__copy {
            white-space: normal;
            text-align: center;
        }
    }
</style>
<div class="site-legal-footer <?= $footerVariant === 'landing' ? 'site-legal-footer--landing' : '' ?>">
    <div class="site-legal-footer__inner">
        <span class="site-legal-footer__copy">&copy; <?= date('Y') ?> HireMatrix. All rights reserved.</span>
        <div class="site-legal-footer__links">
            <a href="<?= base_url('about') ?>">About</a>
            <a href="<?= base_url('contact') ?>">Contact</a>
            <a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a>
            <a href="<?= base_url('terms-of-service') ?>">Terms of Service</a>
        </div>
    </div>
</div>
