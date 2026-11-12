/**
 * Calculadora RAID / RAID Calculator — cálculo client-side, sem dependências.
 * Idioma detectado automaticamente via <html lang="pt-...">.
 */
(function () {
  'use strict';

  var isPT = document.documentElement.lang.indexOf('pt') === 0;

  var STR = isPT ? {
    rawCapacity: 'Capacidade bruta',
    usableCapacity: 'Capacidade útil',
    efficiency: 'Eficiência',
    faultTolerance: 'Tolerância a falhas',
    readPerf: 'Leitura (relativo)',
    writePerf: 'Escrita (relativo)',
    pros: 'Vantagens',
    cons: 'Limitações',
    ideal: 'Indicado para',
    minDisksError: function (n) { return 'Este nível RAID precisa de pelo menos ' + n + ' discos.'; },
    evenDisksError: 'Este nível RAID precisa de um número par de discos.',
    groupDivisionError: function (perGroup) { return 'O número de discos deve ser divisível pelo número de grupos (mínimo ' + perGroup + ' discos por grupo).'; },
    disksLabel: 'disco(s)',
    estimateNote: 'Valores de desempenho são estimativas indicativas, não medições reais — dependem de controlador, workload e configuração.',
    none: 'Nenhuma — a falha de qualquer disco pode causar perda de dados do array.',
    jbodNote: 'A falha de um disco afeta apenas os dados nele armazenados — não existe striping, por isso o resto do array permanece intacto.'
  } : {
    rawCapacity: 'Raw capacity',
    usableCapacity: 'Usable capacity',
    efficiency: 'Efficiency',
    faultTolerance: 'Fault tolerance',
    readPerf: 'Read (relative)',
    writePerf: 'Write (relative)',
    pros: 'Advantages',
    cons: 'Limitations',
    ideal: 'Best for',
    minDisksError: function (n) { return 'This RAID level needs at least ' + n + ' disks.'; },
    evenDisksError: 'This RAID level needs an even number of disks.',
    groupDivisionError: function (perGroup) { return 'The disk count must be divisible by the number of groups (minimum ' + perGroup + ' disks per group).'; },
    disksLabel: 'disk(s)',
    estimateNote: 'Performance values are indicative estimates, not real measurements — they depend on controller, workload and configuration.',
    none: 'None — the failure of any disk can cause data loss across the array.',
    jbodNote: 'A disk failure only affects the data stored on that disk — there is no striping, so the rest of the array stays intact.'
  };

  var LEVELS = isPT ? {
    raid0: {
      label: 'RAID 0 (Striping)', minDisks: 2, group: false, perf: { read: 5, write: 5 },
      usable: function (n, c) { return n * c; },
      fault: function () { return STR.none; },
      pros: ['Desempenho máximo de leitura e escrita', 'Usa 100% da capacidade bruta'],
      cons: ['Sem redundância — a falha de 1 disco perde todos os dados do array'],
      ideal: 'Dados temporários, cache ou cenários onde a velocidade é prioridade e já existe backup à parte.'
    },
    raid1: {
      label: 'RAID 1 (Espelhamento)', minDisks: 2, group: false, perf: { read: 4, write: 3 },
      usable: function (n, c) { return c; },
      fault: function (n) { return 'Tolera a falha de até ' + (n - 1) + ' ' + STR.disksLabel + ' (mantém-se 1 cópia íntegra).'; },
      pros: ['Alta redundância — cada disco é uma cópia completa', 'Recuperação simples após falha'],
      cons: ['Capacidade útil limitada à de um único disco', 'Overhead de armazenamento elevado'],
      ideal: 'Sistemas críticos de pequena escala onde a simplicidade e a redundância pesam mais do que a capacidade.'
    },
    raid5: {
      label: 'RAID 5 (Paridade distribuída)', minDisks: 3, group: false, perf: { read: 4, write: 3 },
      usable: function (n, c) { return (n - 1) * c; },
      fault: function () { return 'Tolera a falha de 1 disco.'; },
      pros: ['Bom equilíbrio entre capacidade, desempenho e redundância', 'Paridade distribuída por todos os discos'],
      cons: ['Reconstrução (rebuild) lenta em discos grandes, com risco acrescido nesse período', 'Escrita mais lenta que RAID 0 devido ao cálculo de paridade'],
      ideal: 'Servidores de ficheiros e cargas de trabalho gerais com boa relação custo/capacidade/redundância.'
    },
    raid6: {
      label: 'RAID 6 (Dupla paridade)', minDisks: 4, group: false, perf: { read: 4, write: 2 },
      usable: function (n, c) { return (n - 2) * c; },
      fault: function () { return 'Tolera a falha de até 2 discos simultâneos.'; },
      pros: ['Mais seguro que o RAID 5 — sobrevive a 2 falhas simultâneas', 'Boa opção para discos grandes (rebuilds mais longos)'],
      cons: ['Maior overhead de escrita, devido à dupla paridade', 'Perde 2 discos de capacidade útil'],
      ideal: 'Arrays com discos de grande capacidade, onde o tempo de rebuild é longo e o risco de uma segunda falha é relevante.'
    },
    raid10: {
      label: 'RAID 10 (1+0)', minDisks: 4, group: false, even: true, perf: { read: 5, write: 4 },
      usable: function (n, c) { return (n / 2) * c; },
      fault: function (n) { return 'Tolera, no mínimo, a falha de 1 disco por par espelhado (até ' + (n / 2) + ' no melhor caso).'; },
      pros: ['Bom desempenho de leitura e escrita', 'Boa tolerância a falhas, sem overhead de cálculo de paridade'],
      cons: ['Apenas 50% da capacidade bruta fica disponível', 'Requer um número par de discos'],
      ideal: 'Bases de dados e cargas de trabalho que exigem desempenho elevado e redundância ao mesmo tempo.'
    },
    raid01: {
      label: 'RAID 0+1', minDisks: 4, group: false, even: true, perf: { read: 5, write: 4 },
      usable: function (n, c) { return (n / 2) * c; },
      fault: function () { return 'Tolera a falha de 1 disco, mas uma segunda falha no espelho oposto pode inutilizar todo o array.'; },
      pros: ['Desempenho semelhante ao RAID 10'],
      cons: ['Tolerância a falhas inferior à do RAID 10, apesar da mesma capacidade útil', 'Geralmente preferível usar RAID 10 em vez deste'],
      ideal: 'Raramente recomendado — na maioria dos casos o RAID 10 é a escolha melhor pelo mesmo custo.'
    },
    raid50: {
      label: 'RAID 50', minDisks: 6, group: true, minPerGroup: 3, perf: { read: 4, write: 3 },
      usable: function (n, c, g) { return (n - g) * c; },
      fault: function (n, g) { return 'Tolera a falha de 1 disco por grupo (até ' + g + ' discos no total, nunca 2 no mesmo grupo).'; },
      pros: ['Melhor desempenho que um único RAID 5 grande', 'Boa relação capacidade/redundância para arrays grandes'],
      cons: ['Perde a redundância de um grupo inteiro se houver 2 falhas no mesmo grupo', 'Configuração mais complexa'],
      ideal: 'Arrays grandes (muitos discos) onde um único RAID 5 seria demasiado lento a reconstruir.'
    },
    raid60: {
      label: 'RAID 60', minDisks: 8, group: true, minPerGroup: 4, perf: { read: 4, write: 2 },
      usable: function (n, c, g) { return (n - 2 * g) * c; },
      fault: function (n, g) { return 'Tolera a falha de até 2 discos por grupo (até ' + (2 * g) + ' discos no total, no máximo 2 por grupo).'; },
      pros: ['Maior tolerância a falhas entre os níveis combinados', 'Boa opção para arrays grandes e críticos'],
      cons: ['Maior overhead de escrita e de capacidade perdida', 'Configuração mais complexa'],
      ideal: 'Arrays grandes e críticos onde o RAID 50 não oferece margem de segurança suficiente.'
    },
    jbod: {
      label: 'JBOD (Just a Bunch Of Disks)', minDisks: 1, group: false, perf: { read: 3, write: 3 },
      usable: function (n, c) { return n * c; },
      fault: function () { return STR.jbodNote; },
      pros: ['Usa 100% da capacidade bruta', 'A falha de 1 disco só afeta os dados desse disco'],
      cons: ['Sem redundância nem ganho de desempenho', 'Discos de tamanhos diferentes não são otimizados'],
      ideal: 'Armazenamento não crítico onde se quer simplesmente somar a capacidade de vários discos.'
    }
  } : {
    raid0: {
      label: 'RAID 0 (Striping)', minDisks: 2, group: false, perf: { read: 5, write: 5 },
      usable: function (n, c) { return n * c; },
      fault: function () { return STR.none; },
      pros: ['Maximum read and write performance', 'Uses 100% of raw capacity'],
      cons: ['No redundancy — a single disk failure loses all data in the array'],
      ideal: 'Temporary data, cache, or scenarios where speed is the priority and a separate backup already exists.'
    },
    raid1: {
      label: 'RAID 1 (Mirroring)', minDisks: 2, group: false, perf: { read: 4, write: 3 },
      usable: function (n, c) { return c; },
      fault: function (n) { return 'Tolerates up to ' + (n - 1) + ' ' + STR.disksLabel + ' failing (1 intact copy remains).'; },
      pros: ['High redundancy — every disk is a full copy', 'Simple recovery after a failure'],
      cons: ['Usable capacity limited to that of a single disk', 'High storage overhead'],
      ideal: 'Small, critical systems where simplicity and redundancy matter more than capacity.'
    },
    raid5: {
      label: 'RAID 5 (Distributed parity)', minDisks: 3, group: false, perf: { read: 4, write: 3 },
      usable: function (n, c) { return (n - 1) * c; },
      fault: function () { return 'Tolerates the failure of 1 disk.'; },
      pros: ['Good balance of capacity, performance and redundancy', 'Parity distributed across all disks'],
      cons: ['Slow rebuild on large disks, with added risk during that window', 'Slower writes than RAID 0 due to parity calculation'],
      ideal: 'File servers and general workloads with a good cost/capacity/redundancy balance.'
    },
    raid6: {
      label: 'RAID 6 (Double parity)', minDisks: 4, group: false, perf: { read: 4, write: 2 },
      usable: function (n, c) { return (n - 2) * c; },
      fault: function () { return 'Tolerates up to 2 simultaneous disk failures.'; },
      pros: ['Safer than RAID 5 — survives 2 simultaneous failures', 'Good choice for large disks (longer rebuilds)'],
      cons: ['Higher write overhead, due to double parity', 'Loses 2 disks worth of usable capacity'],
      ideal: 'Arrays with large-capacity disks, where rebuild time is long and a second failure is a real risk.'
    },
    raid10: {
      label: 'RAID 10 (1+0)', minDisks: 4, group: false, even: true, perf: { read: 5, write: 4 },
      usable: function (n, c) { return (n / 2) * c; },
      fault: function (n) { return 'Tolerates at least 1 disk failing per mirrored pair (up to ' + (n / 2) + ' in the best case).'; },
      pros: ['Good read and write performance', 'Good fault tolerance, without parity calculation overhead'],
      cons: ['Only 50% of raw capacity is available', 'Requires an even number of disks'],
      ideal: 'Databases and workloads that need both high performance and redundancy.'
    },
    raid01: {
      label: 'RAID 0+1', minDisks: 4, group: false, even: true, perf: { read: 5, write: 4 },
      usable: function (n, c) { return (n / 2) * c; },
      fault: function () { return 'Tolerates 1 disk failing, but a second failure on the opposite mirror can take down the whole array.'; },
      pros: ['Performance similar to RAID 10'],
      cons: ['Lower fault tolerance than RAID 10 despite the same usable capacity', 'RAID 10 is usually preferable at the same cost'],
      ideal: 'Rarely recommended — in most cases RAID 10 is the better choice for the same cost.'
    },
    raid50: {
      label: 'RAID 50', minDisks: 6, group: true, minPerGroup: 3, perf: { read: 4, write: 3 },
      usable: function (n, c, g) { return (n - g) * c; },
      fault: function (n, g) { return 'Tolerates 1 disk failing per group (up to ' + g + ' disks total, never 2 in the same group).'; },
      pros: ['Better performance than a single large RAID 5', 'Good capacity/redundancy balance for large arrays'],
      cons: ['Loses an entire group’s redundancy if 2 disks fail in the same group', 'More complex to configure'],
      ideal: 'Large arrays (many disks) where a single RAID 5 would be too slow to rebuild.'
    },
    raid60: {
      label: 'RAID 60', minDisks: 8, group: true, minPerGroup: 4, perf: { read: 4, write: 2 },
      usable: function (n, c, g) { return (n - 2 * g) * c; },
      fault: function (n, g) { return 'Tolerates up to 2 disks failing per group (up to ' + (2 * g) + ' total, max 2 per group).'; },
      pros: ['Highest fault tolerance among the combined levels', 'Good option for large, critical arrays'],
      cons: ['Higher write overhead and lost capacity', 'More complex to configure'],
      ideal: 'Large, critical arrays where RAID 50 doesn’t leave enough safety margin.'
    },
    jbod: {
      label: 'JBOD (Just a Bunch Of Disks)', minDisks: 1, group: false, perf: { read: 3, write: 3 },
      usable: function (n, c) { return n * c; },
      fault: function () { return STR.jbodNote; },
      pros: ['Uses 100% of raw capacity', 'A disk failure only affects that disk’s own data'],
      cons: ['No redundancy and no performance gain', 'Disks of different sizes aren’t used efficiently'],
      ideal: 'Non-critical storage where you simply want to add up the capacity of several disks.'
    }
  };

  function diskTypeBonus(type) {
    if (type === 'ssd') return 1;
    if (type === 'nvme') return 2;
    return 0;
  }

  function stars(value) {
    value = Math.max(1, Math.min(5, value));
    var full = '●'.repeat(value);
    var empty = '○'.repeat(5 - value);
    return full + empty;
  }

  function fmtCapacity(tb) {
    if (tb >= 1000) return (tb / 1000).toFixed(2).replace(/\.00$/, '') + ' PB';
    return tb.toFixed(tb < 10 ? 2 : 1).replace(/\.0$/, '') + ' TB';
  }

  function render() {
    var diskCount = parseInt(document.getElementById('disk-count').value, 10);
    var diskCapacity = parseFloat(document.getElementById('disk-capacity').value);
    var diskType = document.getElementById('disk-type').value;
    var raidKey = document.getElementById('raid-level').value;
    var groups = parseInt(document.getElementById('raid-groups').value, 10) || 1;

    var level = LEVELS[raidKey];
    var groupField = document.getElementById('group-field');
    groupField.style.display = level.group ? 'block' : 'none';

    var errorBox = document.getElementById('raid-error');
    var resultsBox = document.getElementById('raid-results');

    var errors = [];
    if (!diskCount || diskCount < level.minDisks) {
      errors.push(STR.minDisksError(level.minDisks));
    }
    if (level.even && diskCount % 2 !== 0) {
      errors.push(STR.evenDisksError);
    }
    if (level.group) {
      var perGroup = diskCount / groups;
      if (diskCount % groups !== 0 || perGroup < level.minPerGroup) {
        errors.push(STR.groupDivisionError(level.minPerGroup));
      }
    }

    if (errors.length) {
      errorBox.style.display = 'block';
      errorBox.innerHTML = errors.map(function (e) { return '<p style="margin:0;">' + e + '</p>'; }).join('');
      resultsBox.style.display = 'none';
      return;
    }

    errorBox.style.display = 'none';
    resultsBox.style.display = 'block';

    var raw = diskCount * diskCapacity;
    var usable = level.group ? level.usable(diskCount, diskCapacity, groups) : level.usable(diskCount, diskCapacity);
    var efficiency = raw > 0 ? Math.round((usable / raw) * 100) : 0;
    var faultText = level.group ? level.fault(diskCount, groups) : level.fault(diskCount);

    var bonus = diskTypeBonus(diskType);
    var readStars = stars(level.perf.read + bonus);
    var writeStars = stars(level.perf.write + bonus);

    document.getElementById('res-raw').textContent = fmtCapacity(raw);
    document.getElementById('res-usable').textContent = fmtCapacity(usable);
    document.getElementById('res-efficiency').textContent = efficiency + '%';
    document.getElementById('res-fault').textContent = faultText;
    document.getElementById('res-read').textContent = readStars;
    document.getElementById('res-write').textContent = writeStars;

    document.getElementById('res-label').textContent = level.label;
    document.getElementById('res-pros').innerHTML = level.pros.map(function (p) { return '<li>' + p + '</li>'; }).join('');
    document.getElementById('res-cons').innerHTML = level.cons.map(function (c) { return '<li>' + c + '</li>'; }).join('');
    document.getElementById('res-ideal').textContent = level.ideal;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('raid-form');
    if (!form) return;
    ['disk-count', 'disk-capacity', 'disk-type', 'raid-level', 'raid-groups'].forEach(function (id) {
      document.getElementById(id).addEventListener('input', render);
      document.getElementById(id).addEventListener('change', render);
    });
    render();
  });
})();
