<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/reservas.php';

$u    = exigir_login();
$id   = (int)($_GET['id'] ?? 0);
$sala = sala_por_id($id);

if (!$sala || (int)$sala['ativo'] !== 1) {
    flash('erro', 'Sala não encontrada.');
    redirect(base_url('index.php'));
}

// Semana exibida. Se hoje for fim de semana e o fim de semana estiver oculto,
// já abre a próxima semana (evita mostrar uma grade toda cinza).
$ref = $_GET['semana'] ?? null;
if (!$ref || !strtotime($ref)) {
    $ref = date('Y-m-d');
    if (!EXIBIR_FIM_DE_SEMANA && (int)date('N') >= 6) {
        $ref = date('Y-m-d', strtotime('next monday'));
    }
}
$segunda = inicio_semana($ref);
$dias    = dias_da_semana($segunda);
$ultimo  = end($dias);

$anterior = date('Y-m-d', strtotime('-7 day', strtotime($segunda)));
$proxima  = date('Y-m-d', strtotime('+7 day', strtotime($segunda)));

$slots = gerar_slots($sala['hora_inicio'], $sala['hora_fim']);

// Mapa [data][HH:MM] => reserva  (para pintar a grade)
$reservas = reservas_da_sala($id, $segunda, $ultimo);
$mapa = [];
foreach ($reservas as $r) {
    $ini = min_do_dia($r['hora_inicio']);
    $fim = min_do_dia($r['hora_fim']);
    $primeiro = true;
    for ($m = $ini; $m < $fim; $m += SLOT_MINUTOS) {
        $hhmm = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        $mapa[$r['data']][$hhmm] = ['r' => $r, 'inicio' => $primeiro];
        $primeiro = false;
    }
}

$hoje     = date('Y-m-d');
$agoraTs  = time();
$titulo   = $sala['nome'];
include __DIR__ . '/app/layout_topo.php';
?>

<div class="pagina-topo">
  <div>
    <a class="voltar" href="<?= e(base_url('index.php')) ?>">← Todas as salas</a>
    <h1><span class="ponto" style="background: <?= e($sala['cor']) ?>"></span><?= e($sala['nome']) ?></h1>
    <p class="sub">
      <?= $sala['localizacao'] ? e($sala['localizacao']) . ' · ' : '' ?>
      <?= (int)$sala['capacidade'] ?> pessoas ·
      expediente <?= e(hora_curta($sala['hora_inicio'])) ?>–<?= e(hora_curta($sala['hora_fim'])) ?>
      <?= $sala['recursos'] ? ' · ' . e($sala['recursos']) : '' ?>
    </p>
  </div>
</div>

<div class="semana-nav">
  <a class="btn btn-claro" href="?id=<?= $id ?>&semana=<?= e($anterior) ?>">‹ Semana anterior</a>
  <div class="semana-label">
    <?= e(data_br($segunda)) ?> — <?= e(data_br($ultimo)) ?>
    <a class="hoje-link" href="?id=<?= $id ?>">hoje</a>
  </div>
  <a class="btn btn-claro" href="?id=<?= $id ?>&semana=<?= e($proxima) ?>">Próxima semana ›</a>
</div>

<div class="legenda">
  <span><i class="qd livre"></i> Livre — clique para reservar</span>
  <span><i class="qd ocupado"></i> Reservado por outra pessoa</span>
  <span><i class="qd meu"></i> Sua reserva</span>
  <span><i class="qd passado"></i> Indisponível</span>
</div>

<div class="grade-wrap">
<table class="grade">
  <thead>
    <tr>
      <th class="col-hora"></th>
      <?php foreach ($dias as $d): ?>
        <th class="<?= $d === $hoje ? 'hoje' : '' ?>">
          <span class="dia-sem"><?= e(dia_semana_pt($d, true)) ?></span>
          <span class="dia-num"><?= e(date('d/m', strtotime($d))) ?></span>
        </th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($slots as $hhmm): ?>
    <tr>
      <th class="col-hora"><?= e($hhmm) ?></th>
      <?php foreach ($dias as $d):
          $celula   = $mapa[$d][$hhmm] ?? null;
          $passado  = strtotime($d . ' ' . $hhmm) < $agoraTs;

          if ($celula) {
              $r      = $celula['r'];
              $meu    = (int)$r['usuario_id'] === $u['id'];
              $classe = $meu ? 'ocupado meu' : 'ocupado';
              if (!$celula['inicio']) {
                  echo '<td class="' . $classe . ' cont"></td>';
                  continue;
              }
              $info = sprintf('%s–%s · %s · %s',
                  hora_curta($r['hora_inicio']), hora_curta($r['hora_fim']),
                  $r['titulo'], $r['usuario_nome']);
              ?>
              <td class="<?= $classe ?>"
                  data-reserva='<?= e(json_encode([
                        'id'       => (int)$r['id'],
                        'titulo'   => $r['titulo'],
                        'descricao'=> $r['descricao'],
                        'usuario'  => $r['usuario_nome'],
                        'email'    => $r['usuario_email'],
                        'depto'    => $r['departamento'],
                        'data'     => data_br($r['data']),
                        'inicio'   => hora_curta($r['hora_inicio']),
                        'fim'      => hora_curta($r['hora_fim']),
                        'podeCancelar' => ($meu || eh_admin()) && (eh_admin() || strtotime($r['data'].' '.$r['hora_inicio']) > $agoraTs),
                  ], JSON_UNESCAPED_UNICODE)) ?>'
                  title="<?= e($info) ?>">
                <span class="ev-titulo"><?= e($r['titulo']) ?></span>
                <span class="ev-user"><?= e($r['usuario_nome']) ?></span>
                <span class="ev-hora"><?= e(hora_curta($r['hora_inicio'])) ?>–<?= e(hora_curta($r['hora_fim'])) ?></span>
              </td>
          <?php } elseif ($passado) { ?>
              <td class="passado"></td>
          <?php } else { ?>
              <td class="livre" data-data="<?= e($d) ?>" data-hora="<?= e($hhmm) ?>"
                  title="Reservar <?= e(dia_semana_pt($d, true)) ?> <?= e(date('d/m', strtotime($d))) ?> às <?= e($hhmm) ?>">
                <span class="mais">+</span>
              </td>
          <?php } ?>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- ===== Modal: nova reserva ===== -->
<div class="modal" id="modalNova" hidden>
  <div class="modal-caixa">
    <h3>Nova reserva</h3>
    <p class="modal-sub" id="novaQuando"></p>

    <form id="formNova">
      <input type="hidden" name="sala_id" value="<?= $id ?>">
      <input type="hidden" name="data" id="novaData">
      <input type="hidden" name="hora_inicio" id="novaHoraInicio">

      <label>Assunto da reunião *
        <input type="text" name="titulo" id="novaTitulo" maxlength="150" required
               placeholder="Ex: Reunião de fechamento mensal">
      </label>

      <label>Duração
        <select name="duracao" id="novaDuracao"></select>
      </label>

      <label>Observações
        <textarea name="descricao" rows="2" maxlength="500" placeholder="Opcional"></textarea>
      </label>

      <div class="modal-erro" id="novaErro" hidden></div>

      <div class="modal-acoes">
        <button type="button" class="btn btn-claro" data-fechar>Cancelar</button>
        <button type="submit" class="btn btn-primario">Confirmar reserva</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Modal: detalhes ===== -->
<div class="modal" id="modalDet" hidden>
  <div class="modal-caixa">
    <h3 id="detTitulo"></h3>
    <dl class="det-lista" id="detLista"></dl>
    <div class="modal-erro" id="detErro" hidden></div>
    <div class="modal-acoes">
      <button type="button" class="btn btn-claro" data-fechar>Fechar</button>
      <button type="button" class="btn btn-perigo" id="btnCancelar" hidden>Cancelar reserva</button>
    </div>
  </div>
</div>

<script>
  window.APP = {
    base: <?= json_encode(base_url('')) ?>,
    csrf: <?= json_encode(csrf_token()) ?>,
    slot: <?= (int)SLOT_MINUTOS ?>,
    duracaoMax: <?= (int)DURACAO_MAXIMA_MIN ?>,
    salaFim: <?= json_encode(hora_curta($sala['hora_fim'])) ?>
  };
</script>

<?php include __DIR__ . '/app/layout_rodape.php'; ?>
