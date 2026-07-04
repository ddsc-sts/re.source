<?php require __DIR__ . '/../components/header.php'; ?>

<div class="container mt-4">

    <h3>Histórico de Entregas</h3>

    <?php if (empty($history)): ?>
        <div class="alert alert-info mt-3">
            Nenhuma entrega registrada.
        </div>
    <?php else: ?>

        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>ID Frete</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Concluído em</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= $h['id'] ?></td>
                        <td><?= $h['status'] ?></td>
                        <td><?= $h['created_at'] ?></td>
                        <td><?= $h['delivered_at'] ?? '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<?php require __DIR__ . '/../components/footer.php'; ?>