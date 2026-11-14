/**
 * Calculadora de IP / IP Calculator — cálculo client-side, sem dependências.
 * Idioma detectado automaticamente via <html lang="pt-...">.
 */
(function () {
  'use strict';

  var isPT = document.documentElement.lang.indexOf('pt') === 0;

  var STR = isPT ? {
    invalidIp: 'Endereço IP inválido. Usa o formato 192.168.1.10 (4 números entre 0 e 255).',
    network: 'Endereço de rede',
    broadcast: 'Endereço de broadcast',
    mask: 'Máscara de sub-rede',
    wildcard: 'Máscara wildcard',
    cidr: 'Notação CIDR',
    totalAddresses: 'Total de endereços',
    usableHosts: 'Hosts utilizáveis',
    firstHost: 'Primeiro host utilizável',
    lastHost: 'Último host utilizável',
    ipClass: 'Classe de IP',
    scope: 'Âmbito',
    private: 'Privado (RFC 1918)',
    public: 'Público',
    loopback: 'Loopback',
    linkLocal: 'Link-local (APIPA)',
    multicast: 'Multicast',
    reserved: 'Reservado',
    binary: 'Representação binária',
    note31: 'Sub-rede /31 (RFC 3021): sem endereços de rede/broadcast reservados — os 2 endereços são utilizáveis, tipicamente usados em links ponto a ponto.',
    note32: 'Sub-rede /32: identifica um único host — não existem endereços de rede, broadcast ou hosts adicionais.',
    none: 'Nenhum (sub-rede sem hosts utilizáveis)'
  } : {
    invalidIp: 'Invalid IP address. Use the format 192.168.1.10 (4 numbers between 0 and 255).',
    network: 'Network address',
    broadcast: 'Broadcast address',
    mask: 'Subnet mask',
    wildcard: 'Wildcard mask',
    cidr: 'CIDR notation',
    totalAddresses: 'Total addresses',
    usableHosts: 'Usable hosts',
    firstHost: 'First usable host',
    lastHost: 'Last usable host',
    ipClass: 'IP class',
    scope: 'Scope',
    private: 'Private (RFC 1918)',
    public: 'Public',
    loopback: 'Loopback',
    linkLocal: 'Link-local (APIPA)',
    multicast: 'Multicast',
    reserved: 'Reserved',
    binary: 'Binary representation',
    note31: '/31 subnet (RFC 3021): no reserved network/broadcast addresses — both addresses are usable, typically for point-to-point links.',
    note32: '/32 subnet: identifies a single host — there are no network, broadcast or extra host addresses.',
    none: 'None (subnet has no usable hosts)'
  };

  function parseIp(str) {
    var parts = String(str || '').trim().split('.');
    if (parts.length !== 4) return null;
    var octets = [];
    for (var i = 0; i < 4; i++) {
      if (!/^\d{1,3}$/.test(parts[i])) return null;
      var n = parseInt(parts[i], 10);
      if (n < 0 || n > 255) return null;
      octets.push(n);
    }
    return octets;
  }

  function octetsToUint(octets) {
    return ((octets[0] << 24) | (octets[1] << 16) | (octets[2] << 8) | octets[3]) >>> 0;
  }

  function uintToOctets(uint) {
    return [
      (uint >>> 24) & 255,
      (uint >>> 16) & 255,
      (uint >>> 8) & 255,
      uint & 255
    ];
  }

  function octetsToStr(octets) {
    return octets.join('.');
  }

  function maskFromCidr(cidr) {
    if (cidr === 0) return 0;
    return (0xFFFFFFFF << (32 - cidr)) >>> 0;
  }

  function toBinary(uint) {
    var bin = (uint >>> 0).toString(2);
    while (bin.length < 32) bin = '0' + bin;
    return bin.match(/.{8}/g).join('.');
  }

  function classify(octets) {
    var a = octets[0];
    var b = octets[1];
    if (a === 127) return { cls: 'A', scope: 'loopback' };
    if (a === 169 && b === 254) return { cls: 'B', scope: 'linkLocal' };
    if (a >= 1 && a <= 126) return { cls: 'A', scope: privateScope(octets) };
    if (a === 128) return { cls: 'B', scope: privateScope(octets) };
    if (a >= 128 && a <= 191) return { cls: 'B', scope: privateScope(octets) };
    if (a >= 192 && a <= 223) return { cls: 'C', scope: privateScope(octets) };
    if (a >= 224 && a <= 239) return { cls: 'D', scope: 'multicast' };
    return { cls: 'E', scope: 'reserved' };
  }

  function privateScope(octets) {
    var a = octets[0], b = octets[1];
    if (a === 10) return 'private';
    if (a === 172 && b >= 16 && b <= 31) return 'private';
    if (a === 192 && b === 168) return 'private';
    return 'public';
  }

  function render() {
    var ipInput = document.getElementById('ip-address');
    var cidrSelect = document.getElementById('ip-cidr');
    var errorBox = document.getElementById('ip-error');
    var resultsBox = document.getElementById('ip-results');

    var octets = parseIp(ipInput.value);
    var cidr = parseInt(cidrSelect.value, 10);

    if (!octets) {
      errorBox.style.display = 'block';
      errorBox.innerHTML = '<p style="margin:0;">' + STR.invalidIp + '</p>';
      resultsBox.style.display = 'none';
      return;
    }
    errorBox.style.display = 'none';
    resultsBox.style.display = 'block';

    var ipUint = octetsToUint(octets);
    var maskUint = maskFromCidr(cidr);
    var wildcardUint = (~maskUint) >>> 0;
    var networkUint = (ipUint & maskUint) >>> 0;
    var broadcastUint = (networkUint | wildcardUint) >>> 0;

    var total = Math.pow(2, 32 - cidr);
    var usable, firstHost, lastHost, usableNote;

    if (cidr === 32) {
      usable = 0;
      firstHost = null;
      lastHost = null;
      usableNote = STR.note32;
    } else if (cidr === 31) {
      usable = 2;
      firstHost = networkUint;
      lastHost = broadcastUint;
      usableNote = STR.note31;
    } else {
      usable = Math.max(0, total - 2);
      firstHost = usable > 0 ? (networkUint + 1) >>> 0 : null;
      lastHost = usable > 0 ? (broadcastUint - 1) >>> 0 : null;
      usableNote = '';
    }

    var info = classify(octets);
    var scopeLabel = STR[info.scope] || info.scope;

    document.getElementById('res-network').textContent = octetsToStr(uintToOctets(networkUint)) + '/' + cidr;
    document.getElementById('res-broadcast').textContent = cidr >= 31 ? '—' : octetsToStr(uintToOctets(broadcastUint));
    document.getElementById('res-mask').textContent = octetsToStr(uintToOctets(maskUint));
    document.getElementById('res-wildcard').textContent = octetsToStr(uintToOctets(wildcardUint));
    document.getElementById('res-total').textContent = total.toLocaleString(isPT ? 'pt-BR' : 'en-US');
    document.getElementById('res-usable').textContent = usable > 0 ? usable.toLocaleString(isPT ? 'pt-BR' : 'en-US') : STR.none;
    document.getElementById('res-first').textContent = firstHost !== null ? octetsToStr(uintToOctets(firstHost)) : '—';
    document.getElementById('res-last').textContent = lastHost !== null ? octetsToStr(uintToOctets(lastHost)) : '—';
    document.getElementById('res-class').textContent = info.cls;
    document.getElementById('res-scope').textContent = scopeLabel;
    document.getElementById('res-binary').textContent = toBinary(ipUint) + ' / ' + toBinary(maskUint);

    var noteBox = document.getElementById('ip-note');
    if (usableNote) {
      noteBox.style.display = 'block';
      noteBox.textContent = usableNote;
    } else {
      noteBox.style.display = 'none';
      noteBox.textContent = '';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ip-form');
    if (!form) return;
    ['ip-address', 'ip-cidr'].forEach(function (id) {
      document.getElementById(id).addEventListener('input', render);
      document.getElementById(id).addEventListener('change', render);
    });
    render();
  });
})();
