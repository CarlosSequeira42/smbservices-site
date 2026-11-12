/**
 * Banner de consentimento de cookies + carregamento condicional do
 * Google Analytics (GA4). O GA4 só é carregado depois de o visitante
 * aceitar explicitamente; a recusa é guardada e o banner não volta a
 * aparecer. Texto em PT ou EN consoante o atributo lang da página.
 */
(function () {
  'use strict';

  var GA_MEASUREMENT_ID = 'G-8E5NED34V1';
  var STORAGE_KEY = 'smb_cookie_consent';

  var isEnglish = document.documentElement.lang.indexOf('en') === 0;

  var strings = {
    pt: {
      message: 'Usamos cookies de análise (Google Analytics) para perceber como o site é utilizado e melhorá-lo. Só são ativados com o seu consentimento.',
      accept: 'Aceitar',
      decline: 'Recusar'
    },
    en: {
      message: 'We use analytics cookies (Google Analytics) to understand how the site is used and improve it. They are only activated with your consent.',
      accept: 'Accept',
      decline: 'Decline'
    }
  };

  var t = isEnglish ? strings.en : strings.pt;

  function loadGoogleAnalytics() {
    if (window.__smbGaLoaded) {
      return;
    }
    window.__smbGaLoaded = true;

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_MEASUREMENT_ID;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    function gtag() {
      window.dataLayer.push(arguments);
    }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', GA_MEASUREMENT_ID, { anonymize_ip: true });
  }

  function saveConsent(value) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ value: value, ts: Date.now() }));
    } catch (e) {
      /* localStorage indisponível (ex.: modo privado); o banner volta a aparecer na próxima visita */
    }
  }

  function readConsent() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return null;
      }
      var parsed = JSON.parse(raw);
      return parsed && parsed.value ? parsed.value : null;
    } catch (e) {
      return null;
    }
  }

  function showBanner() {
    var banner = document.createElement('div');
    banner.className = 'cookie-banner';
    banner.setAttribute('role', 'region');
    banner.setAttribute('aria-label', 'Cookies');

    var message = document.createElement('p');
    message.textContent = t.message;

    var actions = document.createElement('div');
    actions.className = 'cookie-actions';

    var declineBtn = document.createElement('button');
    declineBtn.type = 'button';
    declineBtn.className = 'btn-decline';
    declineBtn.textContent = t.decline;

    var acceptBtn = document.createElement('button');
    acceptBtn.type = 'button';
    acceptBtn.className = 'btn-accept';
    acceptBtn.textContent = t.accept;

    actions.appendChild(declineBtn);
    actions.appendChild(acceptBtn);
    banner.appendChild(message);
    banner.appendChild(actions);
    document.body.appendChild(banner);

    acceptBtn.addEventListener('click', function () {
      saveConsent('accepted');
      loadGoogleAnalytics();
      banner.remove();
    });

    declineBtn.addEventListener('click', function () {
      saveConsent('declined');
      banner.remove();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var consent = readConsent();
    if (consent === 'accepted') {
      loadGoogleAnalytics();
    } else if (consent === null) {
      showBanner();
    }
    // consent === 'declined': não faz nada, não volta a perguntar.
  });
})();
