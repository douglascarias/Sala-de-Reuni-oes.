<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/reservas.php';

$u = exigir_login();

// Cancelamento via formulário (sem JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf'] ?? null);
    $res = cancelar_reserva((int)($_POST['id'] ?? 0), $u['id'], eh_admin());
    flash($res['ok'] ? 'ok' : 'erro', $res['ok'] ? 'Reserva cancelada.' : $res['erro']);
    redirect(base_url('minhas-reservas.php'));
}

$filtro = $_GET['filtro'] ?? 'proximas';
$hoje   = date('Y-m-d');

$sql = 'SELECT r.*, s.nome AS sala_nome, s.cor, s.localizacao
        FROM reservas r JOIN salas s ON s.id = r.sala_id
        WHERE r.usuario_id = :uid ';

if ($filtro === 'passadas') {
    $sql .= ' AND r.status = \'ativa\' AND r.data < :hoje ORDER BY r.data DESC, r.hora_inicio DESC';
} elseif ($filtro === 'canceladas') {
    $sql .= ' AND r.status = \'cancelada\' ORDER BY r.data DESC, r.hora_inicio DESC';
} else {
    $sql .= ' AND r.status = \'ativa\' AND r.data >= :hoje ORDER BY r.data, r.hora_inicio';
}

$st = db()->prepare($sql . ' LIMIT 300');
$st->bindValue(':uid', $u['id'], PDO::PARAM_INT);
if ($filtro !== 'canceladas') { $st->bindValue(':hoje', $hoje); }
$st->execute();
$lista = $st->fetchAll();

$titulo = 'Minhas reservas';
include __DIR__ . '/app/layout_topo.php';
?>

<div class="pagina-topo">
  <div>
    <h1>Minhas reservas</h1>
    <p class="sub">Reservas feitas com a sua conta</p>
  </div>
  <a class="btn btn-primario" href="<?= e(base_url('index.php')) ?>">+ Nova reserva</a>
</div>

<div class="abas">
  <?php foreach (['proximas' => 'Próximas', 'passadas' => 'Realizadas', 'canceladas' => 'Canceladas'] as $k => $rot): ?>
    <a href="?filtro=<?= $k ?>" class="<?= $filtro === $k ? 'on' : '' ?>"><?= $rot ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$lista): ?>
  <div class="vazio"><p>Nenhuma reserva nesta lista.</p></div>
<?php else: ?>
<table class="tabela">
  <thead>
    <tr><th>Sala</th><th>Data</th><th>Horário</th><th>Assunto</th><th></th></tr>
  </thead>
  <tbody>
  <?php foreach ($lista as $r):
        $futura = strtotime($r['data'] . ' ' . $r['hora_inicio']) > time();
  ?>
    <tr class="<?= $r['status'] === 'cancelada' ? 'linha-cancelada' : '' ?>">
      <td>
        <span class="ponto" style="background: <?= e($r['cor']) ?>"></span>
        <?= e($r['sala_nome']) ?>
        <?php if ($r['localizacao']): ?><small class="mudo"><?= e($r['localizacao']) ?></small><?php endif; ?>
      </td>
      <td><?= e(dia_semana_pt($r['data'], true)) ?>, <?= e(data_br($r['data'])) ?></td>
      <td><?= e(hora_curta($r['hora_inicio'])) ?> – <?= e(hora_curta($r['hora_fim'])) ?></td>
      <td>
        <?= e($r['titulo']) ?>
        <?php if ($r['descricao']): ?><small class="mudo"><?= e($r['descricao']) ?></small><?php endif; ?>
      </td>
      <td class="td-acoes">
        <?php if ($r['status'] === 'ativa' && $futura): ?>
          <form method="post" onsubmit="return confirm('Cancelar esta reserva?')">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-perigo btn-mini" type="submit">Cancelar</button>
          </form>
        <?php elseif ($r['status'] === 'cancelada'): ?>
          <span class="pill pill-cancelada">Cancelada</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/app/layout_rodape.php'; ?>
