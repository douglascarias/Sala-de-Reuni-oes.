<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/reservas.php';

$u = exigir_login();
$hoje = date('Y-m-d');
$agora = date('H:i:s');

$salas = salas_ativas();

// Situação atual + próxima reserva de cada sala
$st = db()->prepare('SELECT r.*, us.nome AS usuario_nome
                     FROM reservas r JOIN usuarios us ON us.id = r.usuario_id
                     WHERE r.status = \'ativa\' AND r.data = ?
                     ORDER BY r.hora_inicio');
$st->execute([$hoje]);
$hojeReservas = [];
foreach ($st->fetchAll() as $r) {
    $hojeReservas[(int)$r['sala_id']][] = $r;
}

$titulo = 'Salas';
include __DIR__ . '/app/layout_topo.php';
?>

<div class="pagina-topo">
  <div>
    <h1>Salas disponíveis</h1>
    <p class="sub"><?= e(ucfirst(dia_semana_pt($hoje))) ?>, <?= e(data_br($hoje)) ?></p>
  </div>
</div>

<?php if (!$salas): ?>
  <div class="vazio">
    <p>Nenhuma sala cadastrada ainda.</p>
    <?php if (eh_admin()): ?>
      <a class="btn btn-primario" href="<?= e(base_url('admin/salas.php')) ?>">Cadastrar sala</a>
    <?php endif; ?>
  </div>
<?php else: ?>
<div class="grid-salas">
  <?php foreach ($salas as $s):
      $lista = $hojeReservas[(int)$s['id']] ?? [];
      $ocupadaAgora = null;
      $proxima = null;
      foreach ($lista as $r) {
          if ($r['hora_inicio'] <= $agora && $agora < $r['hora_fim']) {
              $ocupadaAgora = $r;
          } elseif ($r['hora_inicio'] > $agora && $proxima === null) {
              $proxima = $r;
          }
      }
  ?>
  <a class="card-sala" href="<?= e(base_url('sala.php?id=' . (int)$s['id'])) ?>" style="--cor: <?= e($s['cor']) ?>">
    <div class="card-sala-topo">
      <h2><?= e($s['nome']) ?></h2>
      <span class="pill <?= $ocupadaAgora ? 'pill-ocupada' : 'pill-livre' ?>">
        <?= $ocupadaAgora ? 'Ocupada agora' : 'Livre agora' ?>
      </span>
    </div>

    <ul class="card-sala-meta">
      <?php if ($s['localizacao']): ?><li>📍 <?= e($s['localizacao']) ?></li><?php endif; ?>
      <li>👥 <?= (int)$s['capacidade'] ?> pessoas</li>
      <li>🕐 <?= e(hora_curta($s['hora_inicio'])) ?> às <?= e(hora_curta($s['hora_fim'])) ?></li>
      <?php if ($s['recursos']): ?><li>🖥️ <?= e($s['recursos']) ?></li><?php endif; ?>
    </ul>

    <div class="card-sala-agora">
      <?php if ($ocupadaAgora): ?>
        <strong><?= e(hora_curta($ocupadaAgora['hora_inicio'])) ?>–<?= e(hora_curta($ocupadaAgora['hora_fim'])) ?></strong>
        <?= e($ocupadaAgora['titulo']) ?> · <?= e($ocupadaAgora['usuario_nome']) ?>
      <?php elseif ($proxima): ?>
        Próxima: <strong><?= e(hora_curta($proxima['hora_inicio'])) ?></strong> — <?= e($proxima['usuario_nome']) ?>
      <?php else: ?>
        Sem reuniões hoje
      <?php endif; ?>
    </div>

    <span class="card-sala-cta">Ver calendário e reservar →</span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/app/layout_rodape.php'; ?>
