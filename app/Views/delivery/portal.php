<?php require __DIR__ . '/../components/header.php'; ?>

<div class="container mt-4">

    <h3>Portal de Entregas</h3>

    <p class="text-muted">Fretes em andamento e validação de entrega</p>

    <?php require __DIR__ . '/../components/flash.php'; ?>

    <?php if (empty($freights)): ?>
        <div class="alert alert-info">
            Nenhum frete em andamento.
        </div>
    <?php else: ?>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Rastreamento</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($freights as $f): ?>
                    <tr>
                        <td><?= $f['id'] ?></td>
                        <td><?= $f['status'] ?></td>
                        <td><?= $f['tracking_code'] ?></td>
                        <td>

                            <!-- Gerar código -->
                            <form method="POST" action="/delivery/generate">
                                <input type="hidden" name="freight_id" value="<?= $f['id'] ?>">
                                <button class="btn btn-primary btn-sm">Gerar Código</button>
                            </form>

                            <!-- Validar entrega -->
                            <form method="POST" action="/delivery/validate" class="mt-1">
                                <input type="hidden" name="freight_id" value="<?= $f['id'] ?>">
                                <input type="text" name="code" placeholder="Código 6 dígitos" required>
                                <button class="btn btn-success btn-sm">Validar</button>
                            </form>

                            <!-- Finalizar -->
                            <form method="POST" action="/delivery/finish" class="mt-1">
                                <input type="hidden" name="freight_id" value="<?= $f['id'] ?>">
                                <button class="btn btn-dark btn-sm">Concluir</button>
                            </form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>

    <?php endif; ?>

</div>

<?php require __DIR__ . '/../components/footer.php'; ?>